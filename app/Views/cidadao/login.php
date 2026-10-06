<?php /** @var string|null $erro */ ?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="weather-card">
            <div class="card-body">
                <h4 class="fw-bold mb-3 text-center">
                    <i class="bi bi-people-fill text-primary"></i> Pluviômetro Cidadão
                </h4>

                <?php if (!empty($erro)): ?>
                    <div class="alert alert-danger small"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['redefinida'])): ?>
                    <div class="alert alert-success small">Senha redefinida com sucesso. Faça login.</div>
                <?php endif; ?>

                <form action="<?= url('/pluviometro-cidadao/login') ?>" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">E-mail</label>
                        <input type="email" name="email" class="form-control" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Senha</label>
                        <input type="password" name="senha" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <div class="cf-turnstile" data-sitekey="0x4AAAAAAC9uBAnb4gKBrcof" data-theme="light" data-callback="onTurnstileOk"></div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold" id="btnEntrar" disabled>Entrar</button>
                </form>

                <div class="text-center mt-3">
                    <a href="<?= url('/pluviometro-cidadao/esqueci-senha') ?>" class="small text-muted text-decoration-none">Esqueci minha senha</a>
                </div>
                <div class="text-center mt-2">
                    <a href="<?= url('/pluviometro-cidadao/cadastro') ?>" class="small text-muted text-decoration-none">Ainda não tenho cadastro</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<script>
function onTurnstileOk() {
    document.getElementById('btnEntrar').disabled = false;
}
</script>
