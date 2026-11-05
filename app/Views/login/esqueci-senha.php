<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Recuperar Senha - Clima PB' ?></title>
    <link rel="shortcut icon" href="/img/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('/css/app.css') ?>">
    <style>
        body { background: var(--gradient-topbar); height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .login-card { background: var(--bg-card); border-radius: var(--radius); box-shadow: var(--shadow-lg); padding: 40px; width: 100%; max-width: 400px; }
        .login-logo { text-align: center; margin-bottom: 30px; }
        .login-logo i { font-size: 3rem; color: var(--accent); }
        .btn-login { background: var(--gradient-cool); color: #fff; border: none; padding: 12px; font-weight: 600; }
    </style>
</head>
<body>

<div class="login-card fade-in-up">
    <div class="login-logo">
        <i class="bi bi-envelope-paper-fill"></i>
        <h4 class="mt-2 fw-bold">Recuperar Senha</h4>
    </div>

    <?php if (isset($sucesso)): ?>
        <div class="alert alert-success p-2 small"><?= htmlspecialchars($sucesso) ?></div>
    <?php else: ?>
        <p class="small text-muted text-center mb-4">Informe seu e-mail cadastrado. Enviaremos um link pra você criar uma nova senha.</p>
        <form action="/login/esqueci-senha" method="POST">
            <div class="mb-4">
                <label class="form-label small fw-bold">E-mail</label>
                <input type="email" name="email" class="form-control" required autofocus>
            </div>
            <button type="submit" class="btn btn-login w-100 rounded-pill">Enviar Link</button>
        </form>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="/login" class="text-muted small text-decoration-none">Voltar ao login</a>
    </div>
</div>

</body>
</html>
