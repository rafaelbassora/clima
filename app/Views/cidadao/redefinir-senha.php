<?php
/** @var bool $tokenInvalido */
/** @var string|null $token */
/** @var string|null $erro */
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="weather-card">
            <div class="card-body">
                <h4 class="fw-bold mb-3 text-center">Redefinir Senha</h4>

                <?php if (!empty($tokenInvalido)): ?>
                    <div class="alert alert-danger small">Este link é inválido ou já expirou.</div>
                    <div class="text-center">
                        <a href="<?= url('/pluviometro-cidadao/esqueci-senha') ?>" class="btn btn-primary rounded-pill px-4">Solicitar novo link</a>
                    </div>
                <?php else: ?>
                    <?php if (!empty($erro)): ?>
                        <div class="alert alert-danger small"><?= htmlspecialchars($erro) ?></div>
                    <?php endif; ?>
                    <form action="<?= url('/pluviometro-cidadao/redefinir-senha') ?>" method="POST">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nova Senha</label>
                            <input type="password" name="senha" class="form-control" minlength="8" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Confirmar Nova Senha</label>
                            <input type="password" name="senha_confirmacao" class="form-control" minlength="8" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Redefinir Senha</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
