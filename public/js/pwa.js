/*
 * PWA: registra o service worker e oferece a instalação do app.
 * - Android/Chrome/Edge: usa o evento beforeinstallprompt e mostra o botão "Instalar".
 * - iPhone/iPad (Safari): mostra como adicionar à Tela de Início (o Safari não tem botão automático).
 * - Não aparece se o app já está instalado, nem por 14 dias depois de "Agora não".
 */
(function () {
    'use strict';

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            // endereço com versão (ex.: /sw.js?v=...), pra Cloudflare nunca entregar um service worker antigo
            navigator.serviceWorker.register(window.CLIMA_SW_URL || '/sw.js', { scope: '/' }).catch(function () { /* sem PWA, o site segue normal */ });
        });
    }

    var CHAVE = 'clima-pwa-dispensado';
    var DIAS_SILENCIO = 14;

    function ler() { try { return localStorage.getItem(CHAVE); } catch (e) { return null; } }
    function gravar(v) { try { localStorage.setItem(CHAVE, v); } catch (e) { /* navegação privada */ } }

    var jaInstalado = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    var dispensadoEm = parseInt(ler() || '0', 10);
    var silenciado = dispensadoEm && (Date.now() - dispensadoEm) < DIAS_SILENCIO * 86400000;
    if (jaInstalado || silenciado || /^\/(admin|login)/.test(location.pathname)) return;

    var ua = navigator.userAgent || '';
    var ehIOS = /iphone|ipad|ipod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    var ehSafari = ehIOS && /safari/i.test(ua) && !/crios|fxios|edgios|opios/i.test(ua);
    var promptGuardado = null;
    var banner = null;

    function estilos() {
        if (document.getElementById('pwa-estilo')) return;
        var css = '' +
            '#pwa-banner{position:fixed;left:16px;right:16px;bottom:16px;z-index:1080;max-width:460px;margin:0 auto;' +
            'background:#fff;color:#1a1a2e;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,.25);padding:14px 16px;' +
            'display:flex;gap:12px;align-items:center;transform:translateY(150%);transition:transform .35s cubic-bezier(.4,0,.2,1)}' +
            '#pwa-banner.visivel{transform:translateY(0)}' +
            '#pwa-banner img{width:48px;height:48px;border-radius:12px;flex:0 0 auto}' +
            '#pwa-banner .pwa-texto{flex:1 1 auto;min-width:0;font-size:.875rem;line-height:1.35}' +
            '#pwa-banner .pwa-texto strong{display:block;font-size:.95rem}' +
            '#pwa-banner .pwa-acoes{display:flex;gap:6px;flex:0 0 auto;align-items:center}' +
            '#pwa-banner .pwa-fechar{background:none;border:0;color:#6c757d;font-size:1.4rem;line-height:1;padding:4px 6px;cursor:pointer}' +
            '@media (prefers-reduced-motion:reduce){#pwa-banner{transition:none}}';
        var s = document.createElement('style');
        s.id = 'pwa-estilo';
        s.textContent = css;
        document.head.appendChild(s);
    }

    function fechar(lembrar) {
        if (!banner) return;
        banner.classList.remove('visivel');
        if (lembrar) gravar(String(Date.now()));
        setTimeout(function () { if (banner) { banner.remove(); banner = null; } }, 400);
    }

    function mostrar(modo) {
        if (banner) return;
        estilos();
        banner = document.createElement('div');
        banner.id = 'pwa-banner';
        banner.setAttribute('role', 'dialog');
        banner.setAttribute('aria-label', 'Instalar o aplicativo Clima PBTO');

        var texto = modo === 'ios'
            ? '<strong>Instale o Clima PBTO</strong>Toque em <i class="bi bi-box-arrow-up" aria-hidden="true"></i> <b>Compartilhar</b> e depois em <b>Adicionar à Tela de Início</b>.'
            : '<strong>Instale o Clima PBTO</strong>Acesso rápido pela tela inicial, como um aplicativo.';
        var acao = modo === 'ios'
            ? ''
            : '<button type="button" class="btn btn-primary btn-sm rounded-pill px-3 pwa-instalar">Instalar</button>';

        banner.innerHTML =
            '<img src="/img/icons/icon-192.png" alt="">' +
            '<div class="pwa-texto">' + texto + '</div>' +
            '<div class="pwa-acoes">' + acao +
            '<button type="button" class="pwa-fechar" aria-label="Agora não">&times;</button></div>';
        document.body.appendChild(banner);

        banner.querySelector('.pwa-fechar').addEventListener('click', function () { fechar(true); });
        var btn = banner.querySelector('.pwa-instalar');
        if (btn) {
            btn.addEventListener('click', function () {
                if (!promptGuardado) return fechar(false);
                promptGuardado.prompt();
                promptGuardado.userChoice.then(function (escolha) {
                    if (escolha && escolha.outcome !== 'accepted') gravar(String(Date.now()));
                    promptGuardado = null;
                    fechar(false);
                });
            });
        }
        requestAnimationFrame(function () { requestAnimationFrame(function () { banner && banner.classList.add('visivel'); }); });
    }

    // só oferece a instalação depois que a pessoa respondeu o aviso de cookies (pra não empilhar dois avisos)
    function depoisDoConsentimento(fn, espera) {
        var c = window.climaConsentimento;
        if (!c || c.escolha() !== null) {
            setTimeout(fn, espera);
            return;
        }
        window.addEventListener('clima:consentimento', function () { setTimeout(fn, 1500); }, { once: true });
    }

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        promptGuardado = e;
        depoisDoConsentimento(function () { mostrar('padrao'); }, 3000);
    });

    window.addEventListener('appinstalled', function () {
        gravar(String(Date.now()));
        fechar(false);
    });

    if (ehSafari) {
        depoisDoConsentimento(function () { mostrar('ios'); }, 3000);
    }
})();
