<?php /** @var bool $sucesso */ ?>
<div class="weather-card text-center py-5">
    <div class="card-body">
        <?php if ($sucesso): ?>
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
            <h3 class="h4 fw-bold mt-3">E-mail Confirmado!</h3>
            <p class="text-muted">Seu novo e-mail já está valendo. A partir de agora, use ele pra entrar no painel.</p>
        <?php else: ?>
            <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
            <h3 class="h4 fw-bold mt-3">Link Inválido</h3>
            <p class="text-muted">Este link de confirmação é inválido, já expirou ou o e-mail já estava em uso por outro cadastro.</p>
        <?php endif; ?>
        <a href="<?= url('/pluviometro-cidadao/login') ?>" class="btn btn-primary rounded-pill px-4 mt-2">
            Ir para o login
        </a>
    </div>
</div>
