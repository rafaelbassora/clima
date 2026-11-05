<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Verificação - Clima PB' ?></title>
    <link rel="shortcut icon" href="/img/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= asset('/css/app.css') ?>">
    <style>
        body {
            background: var(--gradient-topbar);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .login-logo { text-align: center; margin-bottom: 30px; }
        .login-logo i { font-size: 3rem; color: var(--accent); }
        .btn-login { background: var(--gradient-cool); color: #fff; border: none; padding: 12px; font-weight: 600; }
        .codigo-input { letter-spacing: 0.5em; font-size: 1.5rem; text-align: center; }
    </style>
</head>
<body>

<div class="login-card fade-in-up">
    <div class="login-logo">
        <i class="bi bi-shield-lock-fill"></i>
        <h4 class="mt-2 fw-bold">Verificação em Duas Etapas</h4>
        <p class="small text-muted mb-0">Digite o código do seu app autenticador</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger p-2 small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="/login/2fa" method="POST">
        <div class="mb-4">
            <input type="text" name="codigo" class="form-control codigo-input" maxlength="6" pattern="[0-9]{6}"
                   inputmode="numeric" autocomplete="one-time-code" required autofocus>
        </div>
        <button type="submit" class="btn btn-login w-100 rounded-pill">Verificar</button>
    </form>

    <div class="text-center mt-4">
        <a href="/login" class="text-muted small text-decoration-none">Voltar</a>
    </div>
</div>

</body>
</html>
