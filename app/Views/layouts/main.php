<?php
// Detecta base path para assets
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$assetsBase = rtrim(dirname($scriptName), '/\\');

$appTitulo = $config['app']['titulo'] ?? $config['app_title'] ?? 'Clima';
$appNomeCurto = $config['app']['nome_curto'] ?? 'Clima';
$appDominio = $config['app']['dominio'] ?? ($_SERVER['HTTP_HOST'] ?? '');
$metaDescription = $config['seo']['meta_description'] ?? '';
$logoPrefeitura = $config['branding']['logo_prefeitura'] ?? '/img/logo.png';
$footerCreditos = $config['branding']['footer_creditos'] ?? '';
$footerAviso = $config['branding']['footer_aviso'] ?? '';
$parceiros = $config['parceiros'] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($config['ga_id']) ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= htmlspecialchars($config['ga_id']) ?>');
    </script>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= htmlspecialchars($appTitulo) ?> - <?= htmlspecialchars($pageTitle ?? '') ?></title>

    <!-- SEO & Branding -->
    <link rel="shortcut icon" href="<?= htmlspecialchars($logoPrefeitura) ?>" type="image/png">
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($appTitulo) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:image" content="https://<?= htmlspecialchars($appDominio) ?><?= htmlspecialchars($logoPrefeitura) ?>">
    <meta property="og:url" content="https://<?= htmlspecialchars($appDominio) ?>/">
    <meta property="og:type" content="website">

    <meta name="theme-color" content="#0f1923">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($appTitulo) ?>">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <title><?= htmlspecialchars($pageTitle ?? 'Início') ?> — <?= htmlspecialchars($appTitulo) ?></title>

    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- App CSS -->
    <link href="<?= $assetsBase ?>/css/app.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>

    <!-- ============ TOPBAR (Barra Fixa Tempo Real) ============ -->
    <div class="topbar" id="topbar">
        <div class="topbar-data">
            <div class="topbar-metric" title="Temperatura">
                <i class="bi bi-thermometer-half temp-icon"></i>
                <span id="topbar-temp"><?php
                    $ul = $ultimaLeitura ?? null;
                    echo $ul ? htmlspecialchars($ul['temperatura']) . '°C' : '--°C';
                ?></span>
            </div>
            <div class="topbar-metric" title="Umidade">
                <i class="bi bi-droplet-fill hum-icon"></i>
                <span id="topbar-hum"><?php
                    echo $ul ? htmlspecialchars($ul['umidade']) . '%' : '--%';
                ?></span>
            </div>
            <!-- Novos Sensores -->
            <div class="topbar-metric" title="Barômetro">
                <i class="bi bi-speedometer2 bar-icon"></i>
                <span id="topbar-bar"><?= $ul ? htmlspecialchars($ul['barometro']) : '--' ?> hPa</span>
            </div>
            <div class="topbar-metric" title="Vento (Velocidade e Rajada)">
                <i class="bi bi-wind wind-icon"></i>
                <span id="topbar-wind"><?= $ul ? htmlspecialchars($ul['vento']) . ' (' . htmlspecialchars($ul['rajada']) . ') km/h ' . htmlspecialchars($ul['direcao']) : '--' ?></span>
            </div>
            <div class="topbar-metric" title="Índice UV">
                <i class="bi bi-sun-fill uv-icon"></i>
                <span id="topbar-uv">UV: <?= $ul ? htmlspecialchars($ul['uv']) : '--' ?></span>
            </div>
        </div>
        <div class="topbar-info">
            <span class="topbar-clock" id="topbar-clock">00:00<span class="clock-seconds">:00</span></span>
            <span class="d-none d-sm-inline">
                <span class="live-dot"></span>
                <i class="bi bi-stopwatch"></i>
                <span id="topbar-updated"><?php
                    echo $ul ? 'atualizado: ' . htmlspecialchars($ul['atualizado']) : 'aguardando...';
                ?></span>
            </span>
        </div>
    </div>

    <?php if (!empty($config['ambiente_testes']) && $config['ambiente_testes'] == 1): ?>
    <div class="dev-bar">⚠️ SISTEMA EM DESENVOLVIMENTO — NÃO LIBERADO PARA USO REAL</div>
    <?php endif; ?>

    <aside class="sidebar d-none d-lg-block">
        <a href="<?= url('/inicio') ?>?local=1" class="sidebar-brand">
            <i class="bi bi-cloud-sun-fill"></i>
            <?= htmlspecialchars($appNomeCurto) ?>
        </a>
        <ul class="sidebar-nav">
            <?php
            $menuItems = [
                ['url' => url('/inicio'),                                      'icon' => 'bi-house-fill',           'label' => 'Início',               'id' => 'inicio'],
                ['url' => url('/sobre-nos'),                                   'icon' => 'bi-people-fill',          'label' => 'Sobre Nós',            'id' => 'sobre'],
                ['url' => url('/inicio-chuva') . '?local=1&year=' . date('Y') . '&month=' . date('m'), 'icon' => 'bi-cloud-rain-heavy-fill', 'label' => 'Índice Pluviométrico', 'id' => 'chuva'],
                ['url' => url('/pluviometro-cidadao'),                         'icon' => 'bi-people-fill',          'label' => 'Pluviômetro Cidadão', 'id' => 'cidadao'],
                ['url' => url('/inicio-et') . '?local=1&year=' . date('Y') . '&month=' . date('m'), 'icon' => 'bi-droplet',                 'label' => 'Evapotranspiração',    'id' => 'et'],
                ['url' => url('/inicio-umidade') . '?local=1&year=' . date('Y') . '&month=' . date('m'), 'icon' => 'bi-droplet-fill',          'label' => 'Umidade',              'id' => 'umidade'],
                ['url' => url('/inicio-pressao') . '?local=1&year=' . date('Y') . '&month=' . date('m'), 'icon' => 'bi-speedometer2',        'label' => 'Pressão Atmosférica',  'id' => 'pressao'],
                ['url' => url('/inicio-vento') . '?local=1&year=' . date('Y') . '&month=' . date('m'),   'icon' => 'bi-wind',                'label' => 'Ventos',               'id' => 'vento'],
                ['url' => url('/inicio-temperatura') . '?local=1&year=' . date('Y') . '&month=' . date('m'), 'icon' => 'bi-thermometer-half',      'label' => 'Temperatura',          'id' => 'temperatura'],
                ['url' => url('/previsao-do-tempo') . '?local=1',                   'icon' => 'bi-umbrella-fill',        'label' => 'Previsão do Tempo',    'id' => 'previsao'],
                ['url' => url('/noticias'),                                      'icon' => 'bi-newspaper',            'label' => 'Notícias',             'id' => 'noticias'],
                ['url' => url('/termos'),                                        'icon' => 'bi-file-earmark-text-fill', 'label' => 'Termos de Uso',    'id' => 'termos'],
            ];
            $activeMenu = $activeMenu ?? '';
            foreach ($menuItems as $item):
            ?>
            <li class="nav-item">
                <a href="<?= $item['url'] ?>" class="nav-link <?= $activeMenu === $item['id'] ? 'active' : '' ?>">
                    <i class="bi <?= $item['icon'] ?>"></i>
                    <span><?= $item['label'] ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="sidebar-footer">
            <a href="<?= url('/login') ?>" class="nav-link <?= $activeMenu === 'login' ? 'active' : '' ?>">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Administração</span>
            </a>
        </div>
    </aside>

    <!-- ============ MOBILE NAVBAR ============ -->
    <nav class="mobile-navbar d-lg-none">
        <a href="<?= url('/inicio') ?>?local=1" class="navbar-brand">
            <i class="bi bi-cloud-sun-fill"></i>
            <?= htmlspecialchars($appNomeCurto) ?>
        </a>
        <button class="btn-menu" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-label="Menu">
            <i class="bi bi-list"></i>
        </button>
    </nav>

    <!-- ============ OFFCANVAS (Mobile Menu) ============ -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
        <div class="offcanvas-header border-bottom border-secondary">
            <h5 class="offcanvas-title text-white" id="mobileMenuLabel">
                <i class="bi bi-cloud-sun-fill" style="color: var(--accent);"></i> Menu
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
        </div>
        <div class="offcanvas-body p-0">
            <ul class="sidebar-nav pt-2">
                <?php foreach ($menuItems as $item): ?>
                <li class="nav-item">
                    <a href="<?= $item['url'] ?>" class="nav-link <?= $activeMenu === $item['id'] ? 'active' : '' ?>">
                        <i class="bi <?= $item['icon'] ?>"></i>
                        <span><?= $item['label'] ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
                <li class="nav-item mt-3 pt-3" style="border-top: 1px solid rgba(255,255,255,0.1)">
                    <a href="<?= url('/login') ?>" class="nav-link <?= $activeMenu === 'login' ? 'active' : '' ?>">
                        <i class="bi bi-shield-lock-fill"></i>
                        <span>Administração</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- ============ MAIN CONTENT ============ -->
    <main class="main-content">
        <?= $content ?>
    </main>

    <!-- ============ FOOTER ============ -->
    <footer class="footer">
        <div class="container">
            <?php if (!empty($parceiros)): ?>
            <div class="footer-logos">
                <?php foreach ($parceiros as $parceiro): ?>
                    <img src="<?= htmlspecialchars($parceiro['logo']) ?>"
                         alt="Logo <?= htmlspecialchars($parceiro['nome']) ?>" loading="lazy">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($footerCreditos)): ?>
            <p class="footer-text mb-2">
                <?= htmlspecialchars($footerCreditos) ?>
            </p>
            <?php endif; ?>
            <?php if (!empty($footerAviso)): ?>
            <div class="footer-disclaimer mx-auto" style="max-width: 900px; font-size: 0.75rem; color: rgba(255,255,255,0.4); line-height: 1.5;">
                <?= htmlspecialchars($footerAviso) ?> Ao utilizar este sistema, você concorda com os nossos <a href="<?= url('/termos') ?>" class="text-white text-decoration-underline opacity-75">Termos de Uso</a>. Os dados meteorológicos possuem caráter informativo e estão sujeitos a variações técnicas. Consulte sempre os canais oficiais da Defesa Civil.
            </div>
            <?php endif; ?>
        </div>
    </footer>

    <!-- Bootstrap 5.3.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <!-- App Config & JS -->
    <script>
        window.CLIMA_CONFIG = {
            refreshMs: <?= (int)($config['ajax_refresh_ms'] ?? 60000) ?>,
            basePath: '<?= $assetsBase ?>'
        };
    </script>
    <script src="<?= $assetsBase ?>/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
