/**
 * Clima Pereira Barreto — Main JavaScript
 * AJAX real-time weather bar + clock + sidebar toggle
 */

document.addEventListener('DOMContentLoaded', () => {
    initClock();
    initAjaxRefresh();
    initTooltips();
    initVoltarAoTopo();
    initAcessibilidade();
});

/**
 * Acessibilidade (barra do topo): tamanho do texto (A− / A+), alto contraste e atalhos eMAG.
 * A preferência fica salva no aparelho ("clima-a11y") e é reaplicada antes da página aparecer (script no <head>).
 * Atalhos: Alt+1 conteúdo, Alt+2 menu, Alt+3 rodapé.
 */
function initAcessibilidade() {
    const html = document.documentElement;
    const MAX_FONTE = 4; // 0 = 100% … 4 = 150%
    const ler = () => { try { return JSON.parse(localStorage.getItem('clima-a11y') || '{}'); } catch (e) { return {}; } };
    const gravar = (a) => { try { localStorage.setItem('clima-a11y', JSON.stringify(a)); } catch (e) { /* navegação privada */ } };
    const estado = Object.assign({ fonte: 0, contraste: false }, ler());
    estado.fonte = Math.min(MAX_FONTE, Math.max(0, parseInt(estado.fonte, 10) || 0));

    // região "ao vivo" pra leitores de tela anunciarem a mudança
    const aviso = document.createElement('div');
    aviso.className = 'visually-hidden';
    aviso.setAttribute('role', 'status');
    aviso.setAttribute('aria-live', 'polite');
    document.body.appendChild(aviso);
    const anunciar = (txt) => { aviso.textContent = ''; setTimeout(() => { aviso.textContent = txt; }, 50); };

    const aplicar = () => {
        for (let i = 1; i <= MAX_FONTE; i++) html.classList.toggle('a11y-fonte-' + i, estado.fonte === i);
        html.classList.toggle('a11y-contraste', !!estado.contraste);
        document.querySelectorAll('[data-a11y="contraste"]').forEach((b) => b.setAttribute('aria-pressed', estado.contraste ? 'true' : 'false'));
        document.querySelectorAll('[data-a11y="menos"]').forEach((b) => { b.disabled = estado.fonte <= 0; });
        document.querySelectorAll('[data-a11y="mais"]').forEach((b) => { b.disabled = estado.fonte >= MAX_FONTE; });
        document.querySelectorAll('[data-a11y="restaurar"]').forEach((b) => { b.disabled = estado.fonte === 0 && !estado.contraste; });
    };

    document.addEventListener('click', (e) => {
        const btn = e.target.closest && e.target.closest('[data-a11y]');
        if (!btn) return;
        const acao = btn.dataset.a11y;
        if (acao === 'mais') estado.fonte = Math.min(MAX_FONTE, estado.fonte + 1);
        if (acao === 'menos') estado.fonte = Math.max(0, estado.fonte - 1);
        if (acao === 'contraste') estado.contraste = !estado.contraste;
        if (acao === 'restaurar') { estado.fonte = 0; estado.contraste = false; }
        if (acao === 'restaurar') {
            try { localStorage.removeItem('clima-a11y'); } catch (err) { /* navegação privada */ }
        } else {
            gravar(estado);
        }
        // o botão clicado pode ficar desabilitado (restaurar, ou A−/A+ no limite): devolve o foco à barra
        const focoPerdido = document.activeElement === btn;
        aplicar();
        if (focoPerdido && btn.disabled) {
            const outro = btn.parentElement && btn.parentElement.querySelector('[data-a11y]:not(:disabled)');
            if (outro) outro.focus();
        }
        anunciar(acao === 'restaurar'
            ? 'Acessibilidade restaurada para o padrão'
            : acao === 'contraste'
                ? 'Alto contraste ' + (estado.contraste ? 'ativado' : 'desativado')
                : 'Tamanho do texto: ' + (100 + estado.fonte * 12.5) + '%');
    });

    // atalhos de teclado do eMAG
    document.addEventListener('keydown', (e) => {
        if (!e.altKey || e.ctrlKey || e.metaKey || e.shiftKey) return;
        const tecla = e.code ? e.code.replace(/^(Digit|Numpad)/, '') : e.key;
        let alvo = null;
        if (tecla === '1') alvo = document.getElementById('conteudo');
        if (tecla === '2') {
            const menuLateral = document.getElementById('menu');
            // a sidebar é position:fixed (offsetParent sempre null); getClientRects vazio = escondida (d-none)
            alvo = menuLateral && menuLateral.getClientRects().length > 0
                ? (menuLateral.querySelector('a.nav-link') || menuLateral.querySelector('a'))
                : document.querySelector('.mobile-navbar .btn-menu');
        }
        if (tecla === '3') alvo = document.getElementById('rodape');
        if (!alvo) return;
        e.preventDefault();
        alvo.focus();
        alvo.scrollIntoView({ block: 'start', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    });

    aplicar();
}

/**
 * Botão "voltar ao topo": aparece só em páginas longas, depois de rolar um pouco.
 */
function initVoltarAoTopo() {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'voltar-topo';
    btn.setAttribute('aria-label', 'Voltar ao topo da página');
    btn.title = 'Voltar ao topo';
    btn.innerHTML = '<i class="bi bi-arrow-up" aria-hidden="true"></i>';
    document.body.appendChild(btn);

    const semAnimacao = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    btn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: semAnimacao ? 'auto' : 'smooth' });
        // devolve o foco pro começo da página (quem navega pelo teclado não fica "perdido" lá embaixo)
        const alvo = document.querySelector('main, h1, h2, h3, h4') || document.body;
        if (!alvo.hasAttribute('tabindex')) alvo.setAttribute('tabindex', '-1');
        alvo.focus({ preventScroll: true });
    });

    let agendado = false;
    const atualizar = () => {
        agendado = false;
        // só em páginas com pelo menos ~2 telas de altura, e depois de rolar mais de uma tela
        const longa = document.documentElement.scrollHeight > window.innerHeight * 2;
        btn.classList.toggle('visivel', longa && window.scrollY > window.innerHeight * 0.8);
    };
    window.addEventListener('scroll', () => {
        if (!agendado) { agendado = true; requestAnimationFrame(atualizar); }
    }, { passive: true });
    window.addEventListener('resize', atualizar, { passive: true });
    atualizar();
}

/**
 * Inicializa os tooltips do Bootstrap
 */
function initTooltips() {
    // se o Bootstrap não carregou (sem internet, CDN fora), segue sem tooltips em vez de quebrar a página
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover focus' // evita tooltip travado no touch do mobile
        });
    });
}

/**
 * Relógio em tempo real
 */
function initClock() {
    const clockEl = document.getElementById('topbar-clock');
    if (!clockEl) return;

    function updateClock() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        
        // Atualiza mantendo a estrutura para o CSS esconder os segundos no mobile
        clockEl.innerHTML = `${h}:${m}<span class="clock-seconds">:${s}</span>`;
    }

    updateClock();
    setInterval(updateClock, 1000);
}

/**
 * AJAX auto-refresh da barra fixa (temperatura + umidade)
 */
function initAjaxRefresh() {
    const refreshMs = window.CLIMA_CONFIG?.refreshMs || 30000; // Reduzido para 30s para melhor resposta
    const local = new URLSearchParams(window.location.search).get('local') || '1';

    // Primeiro refresh imediato
    fetchClimaAtual(local);

    // Auto-refresh periódico
    setInterval(() => fetchClimaAtual(local), refreshMs);
}

async function fetchClimaAtual(local) {
    const basePath = (window.CLIMA_CONFIG?.basePath || '').replace(/\/+$/, '');
    const apiPath = `/api/clima-atual?local=${encodeURIComponent(local)}&_t=${Date.now()}`;
    
    let url = `${basePath}${apiPath}`;
    console.debug('Tentando carregar clima de:', url);
    
    try {
        let response = await fetch(url, {
            headers: { 'Accept': 'application/json' },
            cache: 'no-cache'
        });

        if (!response.ok) {
            console.warn('[Clima] Erro na API:', response.status);
            return;
        }

        const data = await response.json();
        if (!data.success) return;

        // Atualiza elementos do topbar
        const tempEl = document.getElementById('topbar-temp');
        const humEl = document.getElementById('topbar-hum');
        const barEl = document.getElementById('topbar-bar');
        const windEl = document.getElementById('topbar-wind');
        const uvEl = document.getElementById('topbar-uv');
        const updatedEl = document.getElementById('topbar-updated');

        const flash = (el) => {
            el.closest('.topbar-metric')?.classList.add('topbar-flash');
            setTimeout(() => el.closest('.topbar-metric')?.classList.remove('topbar-flash'), 600);
        };

        if (tempEl && data.temperatura) {
            tempEl.textContent = `${data.temperatura}°C`;
            flash(tempEl);
        }

        if (humEl && data.umidade) {
            humEl.textContent = `${data.umidade}%`;
            flash(humEl);
        }

        if (barEl && data.barometro) {
            barEl.textContent = `${data.barometro} hPa`;
            flash(barEl);
        }

        if (windEl && data.vento) {
            windEl.textContent = `${data.vento} (${data.rajada || data.vento}) km/h ${data.direcao}`;
            flash(windEl);
        }

        if (uvEl && data.uv !== undefined) {
            uvEl.textContent = `UV: ${data.uv}`;
            flash(uvEl);
        }

        // Home View specific metrics
        const homeTemperatura = document.getElementById('home-temperatura');
        const homeSensacao = document.getElementById('home-sensacao');
        const homeOrvalho = document.getElementById('home-orvalho');
        const homeChuvaDia = document.getElementById('home-chuva-dia');
        const nowcastContainer = document.getElementById('nowcasting-container');
        const nowcastMsg = document.getElementById('nowcasting-msg');
        const nowcastTime = document.getElementById('nowcasting-time');

        // Nowcasting Sync (Priorizado)
        if (nowcastContainer) {
            if (data.nowcasting && data.nowcasting.mensagem) {
                if (nowcastMsg) nowcastMsg.textContent = data.nowcasting.mensagem;
                if (nowcastTime && data.nowcasting.created_at) {
                    const parts = data.nowcasting.created_at.split(' ');
                    if (parts[1]) {
                        const timeParts = parts[1].split(':');
                        nowcastTime.textContent = `${timeParts[0]}:${timeParts[1]}`;
                    }
                }
                nowcastContainer.style.display = 'block';
            } else {
                nowcastContainer.style.display = 'none';
            }
        }

        if (homeTemperatura && data.temperatura !== undefined) {
            homeTemperatura.textContent = `${formatNumber(data.temperatura)}°C`;
        }

        if (homeSensacao && data.sensacao !== undefined) {
            homeSensacao.textContent = `${formatNumber(data.sensacao)}°C`;
        }

        const homeUmidadePainel = document.getElementById('home-umidade-painel');
        if (homeUmidadePainel && data.umidade !== undefined) {
            let umid = data.umidade;
            let status = 'PERIGO';
            let colorClass = 'text-danger';
            if (umid > 60) {
                status = 'BOA';
                colorClass = 'text-success';
            } else if (umid > 30) {
                status = 'REGULAR';
                colorClass = 'text-primary';
            } else if (umid > 20) {
                status = 'ATENÇÃO';
                colorClass = 'text-warning';
            }
            
            // Remover classes de cor antigas e adicionar a nova
            homeUmidadePainel.classList.remove('text-success', 'text-primary', 'text-warning', 'text-danger');
            homeUmidadePainel.classList.add(colorClass);
            
            homeUmidadePainel.innerHTML = `${umid}% <span style="font-size: 0.6rem; opacity: 0.8; font-weight: 500;" id="home-umidade-status">${status}</span>`;
        }

        if (homeOrvalho && data.orvalho !== undefined) {
            homeOrvalho.textContent = `${formatNumber(data.orvalho)}°C`;
        }

        if (homeChuvaDia && data.chuva_dia !== undefined) {
            homeChuvaDia.textContent = `${formatNumber(data.chuva_dia)}mm`;
        }
        
        const homeVentoMax = document.getElementById('home-vento-max');
        if (homeVentoMax && data.vento_max_dia !== undefined) {
            homeVentoMax.innerHTML = `${data.vento_max_dia} <span style="font-size: 0.6rem;">km/h</span>`;
        }

        const homeVentoAvg = document.getElementById('home-vento-avg');
        if (homeVentoAvg && data.vento_avg_dia !== undefined) {
            homeVentoAvg.innerHTML = `${data.vento_avg_dia} <span style="font-size: 0.6rem;">km/h</span>`;
        }
        
        const homeEtHoje = document.getElementById('home-et-hoje');
        if (homeEtHoje && data.et_hoje !== undefined) {
            homeEtHoje.textContent = `${Number(data.et_hoje).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}mm`;
        }

        const homeDirecaoTexto = document.getElementById('home-direcao-texto');
        if (homeDirecaoTexto && data.direcao) {
            homeDirecaoTexto.textContent = data.direcao;
        }

        const homeDirecaoSeta = document.getElementById('home-direcao-seta');
        if (homeDirecaoSeta && data.direcao_graus !== undefined) {
            homeDirecaoSeta.style.transform = `rotate(${data.direcao_graus}deg)`;
        }

        const mobileDataHora = document.getElementById('mobile-data-hora');
        if (mobileDataHora && data.data_hora) {
            mobileDataHora.innerHTML = `<i class="bi bi-clock-history me-1"></i>${data.data_hora}`;
        }

        if (updatedEl && data.atualizado) {
            updatedEl.textContent = `atualizado: ${data.atualizado}`;
        }

    } catch (err) {
        console.warn('[Clima] Erro ao atualizar dados:', err.message);
    }
}

/**
 * Formata número para padrão brasileiro (1.2 -> 1,2)
 */
function formatNumber(num) {
    return Number(num).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
}
