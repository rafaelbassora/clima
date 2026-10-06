<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Redefinir Senha - Clima PB' ?></title>
    <link rel="shortcut icon" href="/img/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
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
        <i class="bi bi-key-fill"></i>
        <h4 class="mt-2 fw-bold">Redefinir Senha</h4>
    </div>

    <?php if (!empty($tokenInvalido)): ?>
        <div class="alert alert-danger p-2 small">Este link é inválido ou já expirou. Solicite um novo.</div>
        <div class="text-center">
            <a href="/login/esqueci-senha" class="btn btn-login rounded-pill px-4">Solicitar novo link</a>
        </div>
    <?php else: ?>
        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger p-2 small"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <form action="/login/redefinir-senha" method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="mb-3">
                <label class="form-label small fw-bold">Nova Senha</label>
                <input type="password" name="senha" class="form-control" minlength="8" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Confirmar Nova Senha</label>
                <input type="password" name="senha_confirmacao" class="form-control" minlength="8" required>
            </div>
            <button type="submit" class="btn btn-login w-100 rounded-pill">Redefinir Senha</button>
        </form>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="/login" class="text-muted small text-decoration-none">Voltar ao login</a>
    </div>
</div>

</body>
</html>
