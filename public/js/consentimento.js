/*
 * Consentimento de cookies (LGPD).
 * - Cookies essenciais (sessão de login, segurança do formulário, esta escolha) funcionam sempre.
 * - Google Analytics só é carregado depois que a pessoa clica em "Aceitar".
 * - A escolha fica no cookie "clima_consentimento" por 1 ano; dá pra mudar pelo link
 *   "Preferências de cookies" no rodapé (qualquer elemento com data-preferencias-cookies).
 * Dispara o evento "clima:consentimento" quando há uma decisão (usado pelo aviso de instalar o app).
 */
(function () {
    'use strict';

    var NOME = 'clima_consentimento';
    var gaId = window.CLIMA_GA_ID || '';

    function lerEscolha() {
        var m = document.cookie.match(new RegExp('(?:^|; )' + NOME + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : null;
    }

    function gravarEscolha(valor) {
        var seguro = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = NOME + '=' + valor + '; Max-Age=' + (365 * 86400) + '; Path=/; SameSite=Lax' + seguro;
    }

    function carregarAnalytics() {
        if (!gaId || window.__climaGaCarregado) return;
        window.__climaGaCarregado = true;
        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { window.dataLayer.push(arguments); };
        window.gtag('js', new Date());
        window.gtag('config', gaId, { anonymize_ip: true });
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(gaId);
        document.head.appendChild(s);
    }

    function apagarCookiesAnalytics() {
        var dominios = [location.hostname, '.' + location.hostname.split('.').slice(-4).join('.'), '.' + location.hostname];
        document.cookie.split('; ').forEach(function (c) {
            var nome = c.split('=')[0];
            if (/^_ga/.test(nome) || nome === '_gid' || nome === '_gat') {
                dominios.forEach(function (d) {
                    document.cookie = nome + '=; Max-Age=0; Path=/; Domain=' + d;
                });
                document.cookie = nome + '=; Max-Age=0; Path=/';
            }
        });
    }

    function avisar(decisao) {
        try { window.dispatchEvent(new CustomEvent('clima:consentimento', { detail: decisao })); } catch (e) { /* navegador antigo */ }
    }

    var banner = null;

    function estilos() {
        if (document.getElementById('consentimento-estilo')) return;
        var s = document.createElement('style');
        s.id = 'consentimento-estilo';
        s.textContent =
            '#consentimento{position:fixed;left:16px;right:16px;bottom:16px;z-index:1090;max-width:720px;margin:0 auto;' +
            'background:#0f1923;color:#f1f3f5;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,.35);padding:16px 18px;' +
            'display:flex;flex-wrap:wrap;gap:12px 16px;align-items:center;font-size:.875rem;line-height:1.45}' +
            '#consentimento p{margin:0;flex:1 1 320px}' +
            '#consentimento a{color:#9ec5fe;text-decoration:underline}' +
            '#consentimento .acoes{display:flex;gap:8px;flex:0 0 auto;margin-left:auto}' +
            '#consentimento button{border:0;border-radius:999px;padding:8px 20px;font-weight:600;font-size:.875rem;cursor:pointer;min-width:110px}' +
            '#consentimento .recusar{background:#343a40;color:#fff}' +
            '#consentimento .aceitar{background:#0d6efd;color:#fff}' +
            '#consentimento button:focus-visible{outline:3px solid #9ec5fe;outline-offset:2px}';
        document.head.appendChild(s);
    }

    function fechar() {
        if (banner) { banner.remove(); banner = null; }
    }

    function decidir(valor) {
        var antes = lerEscolha();
        gravarEscolha(valor);
        fechar();
        if (valor === 'aceito') {
            carregarAnalytics();
        } else {
            apagarCookiesAnalytics();
            // se tinha aceitado e o Analytics já rodou nesta página, recarrega pra parar de vez
            if (antes === 'aceito' && window.__climaGaCarregado) { location.reload(); return; }
        }
        avisar(valor);
    }

    function mostrar() {
        if (banner) return;
        estilos();
        banner = document.createElement('div');
        banner.id = 'consentimento';
        banner.setAttribute('role', 'dialog');
        banner.setAttribute('aria-live', 'polite');
        banner.setAttribute('aria-label', 'Aviso de cookies');
        banner.innerHTML =
            '<p><strong>Cookies</strong> — usamos cookies essenciais para o site funcionar e, se você permitir, ' +
            'cookies de estatística para entender como o portal é usado. ' +
            '<a href="/privacidade">Política de Privacidade</a>.</p>' +
            '<div class="acoes"><button type="button" class="aceitar">Aceitar</button>' +
            '<button type="button" class="recusar">Recusar</button></div>';
        document.body.appendChild(banner);
        banner.querySelector('.aceitar').addEventListener('click', function () { decidir('aceito'); });
        banner.querySelector('.recusar').addEventListener('click', function () { decidir('recusado'); });
    }

    // expõe pra outros scripts saberem se já existe decisão
    window.climaConsentimento = { escolha: lerEscolha, abrir: mostrar };

    var escolha = lerEscolha();
    if (escolha === 'aceito') {
        carregarAnalytics();
    } else if (escolha !== 'recusado') {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', mostrar);
        } else {
            mostrar();
        }
    }

    document.addEventListener('click', function (e) {
        var alvo = e.target.closest && e.target.closest('[data-preferencias-cookies]');
        if (alvo) { e.preventDefault(); mostrar(); }
    });
})();
