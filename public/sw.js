/*
 * Service worker do Clima Pereira Barreto (PWA).
 * - Páginas: rede primeiro (dados sempre atualizados); sem conexão, mostra a última versão salva ou a página offline.
 * - CSS/JS/imagens do site: rede primeiro (sempre a versão nova); a cópia guardada só é usada sem internet.
 * - Bibliotecas de CDN (Bootstrap, ícones, Chart.js): cache primeiro (versões fixas), só para pedidos com crossorigin.
 * - Área administrativa, login, painel do cidadão e API: nunca passam pelo cache.
 */
const VERSAO = 'clima-v4'; // ao mudar, o activate apaga todos os caches das versões anteriores
const CACHE_ESTATICO = `${VERSAO}-estatico`;
const CACHE_PAGINAS = `${VERSAO}-paginas`;
const CACHE_CDN = `${VERSAO}-cdn`;
const MAX_PAGINAS = 30;

const PRE_CACHE = [
    '/offline.html',
    '/css/app.css',
    '/js/app.js',
    '/js/pwa.js',
    '/js/consentimento.js',
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

async function estaticoRedePrimeiro(request) {
    // rede primeiro: sempre a versão publicada mais recente (o ?v= muda a cada atualização do arquivo,
    // e o cache do navegador cuida da velocidade); a cópia guardada só é usada sem internet.
    // Guarda uma cópia por arquivo (sem o ?v=), pra não acumular versões antigas.
    const url = new URL(request.url);
    const chave = url.origin + url.pathname;
    const cache = await caches.open(CACHE_ESTATICO);
    try {
        const resposta = await fetch(request);
        if (resposta.ok) cache.put(chave, resposta.clone());
        return resposta;
    } catch (e) {
        const salvo = await cache.match(chave);
        if (salvo) return salvo;
        throw e;
    }
}

async function cdnCachePrimeiro(request) {
    // Só guarda respostas "cors" (pedidas com crossorigin). Uma resposta opaca (pedido sem crossorigin)
    // NÃO pode ser reaproveitada por uma página que pede o mesmo arquivo com crossorigin — o navegador
    // recusa ("an opaque response was used for a request whose type is not no-cors") e o Bootstrap não carrega.
    if (request.mode !== 'cors') return fetch(request);
    const cache = await caches.open(CACHE_CDN);
    const salvo = await cache.match(request);
    if (salvo && salvo.type === 'cors') return salvo;
    const resposta = await fetch(request);
    if (resposta.ok && resposta.type === 'cors') cache.put(request, resposta.clone());
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
            event.respondWith(estaticoRedePrimeiro(request));
        }
        return;
    }

    if (url.hostname === 'cdn.jsdelivr.net' || url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') {
        event.respondWith(cdnCachePrimeiro(request));
    }
});
