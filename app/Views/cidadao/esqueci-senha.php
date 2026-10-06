<?php /** @var string|null $sucesso */ ?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="weather-card">
            <div class="card-body">
                <h4 class="fw-bold mb-3 text-center">Recuperar Senha</h4>

                <?php if (isset($sucesso)): ?>
                    <div class="alert alert-success small"><?= htmlspecialchars($sucesso) ?></div>
                <?php else: ?>
                    <p class="small text-muted text-center mb-4">Informe seu e-mail cadastrado.</p>
                    <form action="<?= url('/pluviometro-cidadao/esqueci-senha') ?>" method="POST">
                        <div class="mb-4">
                            <label class="form-label small fw-bold">E-mail</label>
                            <input type="email" name="email" class="form-control" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Enviar Link</button>
                    </form>
                <?php endif; ?>

                <div class="text-center mt-3">
                    <a href="<?= url('/pluviometro-cidadao/login') ?>" class="small text-muted text-decoration-none">Voltar ao login</a>
                </div>
            </div>
        </div>
    </div>
</div>
