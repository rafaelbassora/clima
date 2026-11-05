<?php
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$assetsBase = rtrim(dirname($scriptName), '/\\');
$activeMenu = $activeMenu ?? '';

$menuItems = [
    ['url' => '/admin/dashboard',       'icon' => 'bi-speedometer2',          'label' => 'Dashboard',  'id' => 'dashboard'],
    ['url' => '/admin',                 'icon' => 'bi-cloud-rain-heavy-fill', 'label' => 'Chuvas Manuais', 'id' => 'chuvas'],
    ['url' => '/admin/chuva-automatica','icon' => 'bi-tools',                 'label' => 'Corrigir Automática', 'id' => 'chuva-automatica'],
    ['url' => '/admin/estacao',         'icon' => 'bi-broadcast',             'label' => 'Fonte da Estação', 'id' => 'estacao'],
    ['url' => '/admin/cidadaos',        'icon' => 'bi-people-fill',           'label' => 'Pluviômetro Cidadão', 'id' => 'cidadaos'],
    ['url' => '/admin/alertas',         'icon' => 'bi-exclamation-triangle-fill', 'label' => 'Alertas', 'id' => 'alertas'],
    ['url' => '/admin/noticias',        'icon' => 'bi-newspaper',             'label' => 'Notícias',   'id' => 'noticias'],
    ['url' => '/admin/nowcasting',      'icon' => 'bi-graph-up-arrow',        'label' => 'Rating Nowcasting', 'id' => 'nowcasting'],
    ['url' => '/admin/usuarios',        'icon' => 'bi-person-badge-fill',     'label' => 'Usuários',   'id' => 'usuarios'],
    ['url' => '/admin/2fa',             'icon' => 'bi-shield-lock-fill',      'label' => '2FA',        'id' => '2fa'],
];
if (!empty($_SESSION['is_admin'])) {
    array_splice($menuItems, count($menuItems) - 2, 0, [
        ['url' => '/admin/exportar',    'icon' => 'bi-download',              'label' => 'Exportar Dados', 'id' => 'exportar'],
    ]);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Admin') ?> — <?= htmlspecialchars($config['app']['nome_curto'] ?? 'Clima') ?></title>
    <link rel="icon" href="<?= htmlspecialchars($config['branding']['favicon'] ?? '/img/logo.png') ?>" type="image/png">
    <link rel="shortcut icon" href="<?= htmlspecialchars($config['branding']['favicon'] ?? '/img/logo.png') ?>" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= asset('/css/app.css') ?>" rel="stylesheet">
    <style>
        :root {
            --adm-bg: #0f1623;
            --adm-panel: #161e2e;
            --adm-panel-2: #1b2435;
            --adm-border: rgba(255,255,255,0.08);
            --adm-text: #e7ebf3;
            --adm-muted: #8995ab;
            --adm-accent: #4f8dfd;
            --adm-accent-2: #22c3a6;
            --adm-sidebar-w: 250px;
        }
        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        body {
            background: var(--adm-bg);
            color: var(--adm-text);
            min-height: 100vh;
        }
        .adm-sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: var(--adm-sidebar-w);
            background: linear-gradient(180deg, #121a29 0%, #0e1522 100%);
            border-right: 1px solid var(--adm-border);
            padding: 24px 16px;
            overflow-y: auto;
            z-index: 100;
        }
        .adm-brand {
            display: flex; align-items: center; gap: 10px;
            padding: 0 8px 20px 8px;
            margin-bottom: 16px;
            border-bottom: 1px solid var(--adm-border);
            color: #fff; text-decoration: none;
        }
        .adm-brand i { font-size: 1.6rem; color: var(--adm-accent); }
        .adm-brand span { font-weight: 800; font-size: 1.05rem; }
        .adm-nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 14px; margin-bottom: 4px;
            border-radius: 10px;
            color: var(--adm-muted);
            text-decoration: none;
            font-size: 0.88rem; font-weight: 500;
            transition: all .15s ease;
        }
        .adm-nav-link i { font-size: 1.05rem; width: 20px; text-align: center; }
        .adm-nav-link:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .adm-nav-link.active {
            background: linear-gradient(90deg, rgba(79,141,253,0.18), rgba(79,141,253,0.03));
            color: #fff;
            border-left: 3px solid var(--adm-accent);
            padding-left: 11px;
        }
        .adm-sidebar-footer {
            margin-top: 24px; padding-top: 16px;
            border-top: 1px solid var(--adm-border);
        }
        .adm-main {
            margin-left: var(--adm-sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .adm-topbar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 32px;
            border-bottom: 1px solid var(--adm-border);
            background: rgba(255,255,255,0.015);
        }
        .adm-user-chip {
            display: flex; align-items: center; gap: 10px;
            font-size: 0.85rem; color: var(--adm-muted);
        }
        .adm-user-chip .avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--adm-accent), var(--adm-accent-2));
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; color: #fff; font-size: 0.85rem;
        }
        .adm-content { padding: 32px; flex: 1; }
        .adm-footer {
            padding: 16px 32px; border-top: 1px solid var(--adm-border);
            text-align: center; color: var(--adm-muted); font-size: 0.78rem;
        }

        /* Cards/tabelas dentro do admin, no tema escuro */
        .adm-content .weather-card, .adm-content .card {
            background: var(--adm-panel);
            border: 1px solid var(--adm-border);
            border-radius: 14px;
            color: var(--adm-text);
        }
        .adm-content .card-header-custom {
            background: var(--adm-panel-2);
            border-bottom: 1px solid var(--adm-border);
            padding: 14px 20px; font-weight: 600; border-radius: 14px 14px 0 0;
            color: var(--adm-text);
        }
        .adm-content h2, .adm-content h3, .adm-content h4, .adm-content h5 { color: #fff; }
        .adm-content .table-weather, .adm-content .table { color: var(--adm-text); }
        .adm-content .table-weather thead th, .adm-content .table thead th {
            color: var(--adm-muted); font-size: 0.75rem; text-transform: uppercase;
            letter-spacing: 0.4px; border-bottom: 1px solid var(--adm-border);
            background: transparent;
        }
        .adm-content .table-weather td, .adm-content .table td {
            border-color: var(--adm-border); vertical-align: middle;
        }
        .adm-content .text-muted { color: var(--adm-muted) !important; }
        .adm-content .form-control, .adm-content .form-select {
            background: var(--adm-panel-2); border-color: var(--adm-border); color: var(--adm-text);
        }
        .adm-content .form-control:focus, .adm-content .form-select:focus {
            background: var(--adm-panel-2); color: var(--adm-text); border-color: var(--adm-accent);
            box-shadow: 0 0 0 3px rgba(79,141,253,0.15);
        }
        .adm-content .form-label { color: var(--adm-muted); }

        .stat-card {
            background: var(--adm-panel);
            border: 1px solid var(--adm-border);
            border-radius: 16px;
            padding: 20px;
            display: flex; align-items: center; gap: 16px;
            transition: transform .15s ease, border-color .15s ease;
        }
        .stat-card:hover { transform: translateY(-2px); border-color: rgba(79,141,253,0.4); }
        .stat-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; flex-shrink: 0;
        }
        .stat-value { font-size: 1.6rem; font-weight: 800; color: #fff; line-height: 1.1; }
        .stat-label { font-size: 0.78rem; color: var(--adm-muted); margin-top: 2px; }

        @media (max-width: 991px) {
            .adm-sidebar { transform: translateX(-100%); transition: transform .2s ease; }
            .adm-sidebar.open { transform: translateX(0); }
            .adm-main { margin-left: 0; }
            .adm-content { padding: 18px; }
            .adm-topbar { padding: 14px 18px; }
        }
    </style>
</head>
<body>

    <aside class="adm-sidebar" id="admSidebar">
        <a href="/admin/dashboard" class="adm-brand">
            <i class="bi bi-cloud-sun-fill"></i>
            <span>Clima Admin</span>
        </a>
        <nav>
            <?php foreach ($menuItems as $item): ?>
                <a href="<?= $item['url'] ?>" class="adm-nav-link <?= $activeMenu === $item['id'] ? 'active' : '' ?>">
                    <i class="bi <?= $item['icon'] ?>"></i>
                    <span><?= $item['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="adm-sidebar-footer">
            <a href="/" class="adm-nav-link"><i class="bi bi-box-arrow-up-right"></i><span>Ver Site</span></a>
            <a href="/logout" class="adm-nav-link text-danger"><i class="bi bi-box-arrow-right"></i><span>Sair</span></a>
        </div>
    </aside>

    <div class="adm-main">
        <header class="adm-topbar">
            <button class="btn btn-sm btn-outline-light d-lg-none" type="button" onclick="document.getElementById('admSidebar').classList.toggle('open')">
                <i class="bi bi-list"></i>
            </button>
            <h1 class="h5 fw-bold mb-0 d-none d-lg-block"><?= htmlspecialchars($title ?? 'Admin') ?></h1>
            <div class="adm-user-chip">
                <div class="avatar"><?= strtoupper(substr($_SESSION['usuario'] ?? '?', 0, 1)) ?></div>
                <span><?= htmlspecialchars($_SESSION['usuario'] ?? '') ?></span>
            </div>
        </header>

        <main class="adm-content">
            <?= $content ?>
        </main>

        <footer class="adm-footer">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($config['app']['nome_sistema'] ?? 'Clima') ?> — Painel Administrativo
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
