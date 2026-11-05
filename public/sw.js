/*
 * Service worker do Clima Pereira Barreto (PWA).
 * - Páginas: rede primeiro (dados sempre atualizados); sem conexão, mostra a última versão salva ou a página offline.
 * - CSS/JS/imagens do site: responde do cache e atualiza em segundo plano.
 * - Bibliotecas de CDN (Bootstrap, ícones, Chart.js): cache primeiro (versões fixas).
 * - Área administrativa, login, painel do cidadão e API: nunca passam pelo cache.
 */
const VERSAO = 'clima-v1';
const CACHE_ESTATICO = `${VERSAO}-estatico`;
const CACHE_PAGINAS = `${VERSAO}-paginas`;
const CACHE_CDN = `${VERSAO}-cdn`;
const MAX_PAGINAS = 30;

const PRE_CACHE = [
    '/offline.html',
    '/css/app.css',
    '/js/app.js',
    '/js/pwa.js',
    '/img/icons/icon-192.png',
    '/img/icons/icon-512.png',
    '/manifest.webmanifest',
];

// caminhos que nunca devem ser guardados (dados pessoais, sessão ou dados em tempo real)
const SEM_CACHE = [/^\/admin/, /^\/login/, /^\/api\//, /^\/phpmyadmin/, /^\/pluviometro-cidadao\/(painel|login|logout|cadastro|perfil|esqueci|redefinir|confirmar)/];

self.addEventListener('install', (event) => {
    // grava um por um (cache.addAll cancela tudo se um único item der problema);
    // só a página offline é obrigatória pra instalação
    event.waitUntil((async () => {
        const cache = await caches.open(CACHE_ESTATICO);
        for (const url of PRE_CACHE) {
            try {
                const resposta = await fetch(url, { cache: 'reload' });
                if (!resposta.ok) throw new Error(`${url}: HTTP ${resposta.status}`);
                await cache.delete(url);
                await cache.put(url, resposta);
            } catch (e) {
                if (url === '/offline.html') throw e;
            }
        }
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((nomes) => Promise.all(nomes.filter((n) => !n.startsWith(VERSAO)).map((n) => caches.delete(n))))
            .then(() => self.clients.claim())
    );
});

async function limitarCache(nome, maximo) {
    const cache = await caches.open(nome);
    const chaves = await cache.keys();
    for (let i = 0; i < chaves.length - maximo; i++) {
        await cache.delete(chaves[i]);
    }
}

async function paginaRedePrimeiro(request) {
    try {
        const resposta = await fetch(request);
        if (resposta.ok && resposta.type === 'basic') {
            const cache = await caches.open(CACHE_PAGINAS);
            cache.put(request, resposta.clone()).then(() => limitarCache(CACHE_PAGINAS, MAX_PAGINAS));
        }
        return resposta;
    } catch (e) {
        const salva = await caches.match(request, { ignoreVary: true });
        return salva || caches.match('/offline.html');
    }
}

async function estaticoCacheEAtualiza(request) {
    // app.js e app.css usam ?v=... só pra furar cache do navegador; guarda sem a query
    const url = new URL(request.url);
    const chave = url.origin + url.pathname;
    const cache = await caches.open(CACHE_ESTATICO);
    const salvo = await cache.match(chave);
    const atualiza = fetch(request)
        .then((resposta) => {
            if (resposta.ok) cache.put(chave, resposta.clone());
            return resposta;
        })
        .catch(() => salvo);
    return salvo || atualiza;
}

async function cdnCachePrimeiro(request) {
    const cache = await caches.open(CACHE_CDN);
    const salvo = await cache.match(request);
    if (salvo) return salvo;
    const resposta = await fetch(request);
    if (resposta.ok || resposta.type === 'opaque') cache.put(request, resposta.clone());
    return resposta;
}

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    if (url.origin === self.location.origin) {
        if (SEM_CACHE.some((r) => r.test(url.pathname))) return;

        if (request.mode === 'navigate') {
            event.respondWith(paginaRedePrimeiro(request));
            return;
        }
        if (/^\/(css|js|img)\//.test(url.pathname) || url.pathname === '/manifest.webmanifest') {
            event.respondWith(estaticoCacheEAtualiza(request));
        }
        return;
    }

    if (url.hostname === 'cdn.jsdelivr.net' || url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') {
        event.respondWith(cdnCachePrimeiro(request));
    }
});
