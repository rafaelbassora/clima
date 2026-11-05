<?php $config404 = require __DIR__ . '/../../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página não encontrada — <?= htmlspecialchars($config404['app']['nome_sistema'] ?? 'Clima') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= asset('/css/app.css') ?>" rel="stylesheet">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--gradient-sky);">
    <div class="text-center">
        <i class="bi bi-cloud-slash" style="font-size: 5rem; color: var(--text-muted); opacity: 0.4;"></i>
        <h1 style="font-size: 4rem; font-weight: 800; color: var(--text-main);">404</h1>
        <p class="text-muted mb-4">Página não encontrada.</p>
        <a href="/inicio" class="btn-action" style="display: inline-flex; width: auto; padding: 10px 28px;">
            <i class="bi bi-house-fill"></i> Voltar ao Início
        </a>
    </div>
</body>
</html>
