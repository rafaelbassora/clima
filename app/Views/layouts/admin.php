<?php
// Layout administrativo simplificado
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$assetsBase = rtrim(dirname($scriptName), '/\\');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Admin') ?> — Clima PB</title>
    <link rel="shortcut icon" href="/img/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $assetsBase ?>/css/app.css" rel="stylesheet">
    <style>
        body { background: var(--gradient-sky); min-height: 100vh; padding-top: 20px; }
        .admin-container { max-width: 1000px; margin: 0 auto; padding: 0 15px; }
    </style>
</head>
<body>

    <div class="admin-container">
        <header class="d-flex justify-content-between align-items-center mb-4 py-3 border-bottom">
            <h1 class="h3 fw-bold mb-0 text-primary">
                <i class="bi bi-shield-lock-fill me-2"></i>Clima Admin
            </h1>
            <div class="text-end">
                <span class="small text-muted me-3">Logado como: <strong><?= $_SESSION['usuario'] ?></strong></span>
                <a href="/admin" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-2">Chuvas</a>
                <a href="/admin/chuva-automatica" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-2">Corrigir Automática</a>
                <a href="/admin/alertas" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-2">Alertas</a>
                <a href="/admin/noticias" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-2">Notícias</a>
                <a href="/admin/usuarios" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-2">Usuários</a>
                <a href="/admin/2fa" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-2">2FA</a>
                <a href="/admin/cidadaos" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-2">Cidadãos</a>
                <a href="/admin/nowcasting" class="btn btn-sm btn-primary rounded-pill px-3 me-2">Rating</a>
                <a href="/" class="btn btn-sm btn-outline-secondary rounded-pill px-3 me-2">Ver Site</a>
                <a href="/logout" class="btn btn-sm btn-danger rounded-pill px-3">Sair</a>
            </div>
        </header>

        <main>
            <?= $content ?>
        </main>

        <footer class="mt-5 py-4 border-top text-center text-muted small">
            &copy; <?= date('Y') ?> Clima Pereira Barreto — Painel Administrativo
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
