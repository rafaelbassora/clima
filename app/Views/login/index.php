<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Login - Clima PB' ?></title>
    <link rel="shortcut icon" href="/img/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
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
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo i {
            font-size: 3rem;
            color: var(--accent);
        }
        .btn-login {
            background: var(--gradient-cool);
            color: #fff;
            border: none;
            padding: 12px;
            font-weight: 600;
        }
    </style>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>

<div class="login-card fade-in-up">
    <div class="login-logo">
        <i class="bi bi-cloud-sun-fill"></i>
        <h4 class="mt-2 fw-bold">Clima Admin</h4>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger p-2 small"><?= $error ?></div>
    <?php endif; ?>

    <form action="/login" method="POST" id="loginForm">
        <div class="mb-3">
            <label class="form-label small fw-bold">E-mail</label>
            <input type="email" name="usuario" class="form-control" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label small fw-bold">Senha</label>
            <input type="password" name="senha" class="form-control" required>
        </div>

        <div class="cf-turnstile mb-4" data-sitekey="0x4AAAAAAC9uBAnb4gKBrcof" data-theme="light"></div>

        <button type="submit" class="btn btn-login w-100 rounded-pill">Entrar</button>
    </form>
    
    <div class="text-center mt-4">
        <a href="/" class="text-muted small text-decoration-none">Voltar ao site</a>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</body>
</html>
