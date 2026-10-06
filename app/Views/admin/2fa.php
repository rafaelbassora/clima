<?php
/** @var bool $ativado */
/** @var string|null $secret */
/** @var string|null $otpUri */
/** @var string|null $erro */
?>
<div class="mb-4">
    <h2 class="h4 fw-bold mb-0">Autenticação em Duas Etapas (2FA)</h2>
    <p class="small text-muted mt-1 mb-0">
        Protege sua conta exigindo também um código do Google Authenticator (ou app compatível) no login,
        além da sua senha. É opcional — só passa a valer depois que você mesmo configurar e confirmar abaixo.
    </p>
</div>

<?php if ($ativado): ?>
    <div class="weather-card">
        <div class="card-body">
            <div class="alert alert-success d-flex align-items-center mb-4">
                <i class="bi bi-shield-check fs-4 me-2"></i>
                <div>2FA está <strong>ativado</strong> na sua conta.</div>
            </div>
            <form action="/admin/2fa/disable" method="POST" onsubmit="return confirm('Desativar a verificação em duas etapas da sua conta?')">
                <button type="submit" class="btn btn-outline-danger rounded-pill px-4">Desativar 2FA</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="weather-card">
        <div class="card-body">
            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger small"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-md-5 text-center">
                    <p class="small fw-bold mb-2">1. Escaneie com o app autenticador</p>
                    <div id="qrcode" class="d-inline-block p-2 bg-white rounded"></div>
                    <p class="small text-muted mt-2 mb-0">Ou digite manualmente:</p>
                    <code class="small"><?= htmlspecialchars($secret) ?></code>
                </div>
                <div class="col-md-7">
                    <p class="small fw-bold mb-2">2. Digite o código gerado pelo app pra confirmar</p>
                    <form action="/admin/2fa/enable" method="POST">
                        <div class="mb-3">
                            <input type="text" name="codigo" class="form-control" maxlength="6" pattern="[0-9]{6}"
                                   inputmode="numeric" placeholder="000000" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Ativar 2FA</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: <?= json_encode($otpUri) ?>,
            width: 180,
            height: 180,
        });
    </script>
<?php endif; ?>
