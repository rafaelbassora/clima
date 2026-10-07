<?php
/** @var string|null $erro */
?>

<div class="mb-4">
    <a href="<?= url('/pluviometro-cidadao') ?>" class="text-decoration-none small text-muted">
        <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
    <h2 class="h4 fw-bold mt-2">Cadastrar meu Pluviômetro</h2>
    <p class="text-muted small mb-0">
        Exclusivo para moradores e empresas de <?= htmlspecialchars($config['cidade']['nome'] ?? 'Pereira Barreto') ?> - <?= htmlspecialchars($config['cidade']['uf'] ?? 'SP') ?>. Depois de enviar, seu cadastro
        passa por uma análise da prefeitura antes de ser liberado.
    </p>
</div>

<div class="weather-card">
    <div class="card-body">
        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger small"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form action="<?= url('/pluviometro-cidadao/cadastro') ?>" method="POST" id="formCadastro">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold d-block">Tipo de Cadastro</label>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="tipo" id="tipoPf" value="pf" checked>
                        <label class="btn btn-outline-primary" for="tipoPf">Pessoa Física</label>
                        <input type="radio" class="btn-check" name="tipo" id="tipoPj" value="pj">
                        <label class="btn btn-outline-primary" for="tipoPj">Empresa</label>
                    </div>
                </div>

                <div class="col-md-8">
                    <label class="form-label small fw-bold" id="labelNome">Nome Completo</label>
                    <input type="text" name="nome" class="form-control" required>
                </div>
                <div class="col-md-4 campo-pj d-none">
                    <label class="form-label small fw-bold">CNPJ</label>
                    <input type="text" name="cnpj" class="form-control" placeholder="00.000.000/0000-00">
                </div>
                <div class="col-md-4 campo-pf">
                    <label class="form-label small fw-bold">CPF</label>
                    <input type="text" name="cpf" class="form-control" placeholder="000.000.000-00">
                    <div class="form-text small">Não poderá ser alterado depois de salvo.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Celular</label>
                    <input type="tel" name="celular" class="form-control" placeholder="(18) 90000-0000" required>
                </div>

                <div class="col-12 campo-pf">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="usa_nome_social" id="usaNomeSocial">
                        <label class="form-check-label small fw-bold" for="usaNomeSocial">Uso nome social</label>
                    </div>
                </div>
                <div class="col-md-8 campo-pf campo-nome-social d-none">
                    <label class="form-label small fw-bold">Nome Social</label>
                    <input type="text" name="nome_social" class="form-control">
                </div>

                <div class="col-md-4 campo-pf">
                    <label class="form-label small fw-bold">Sexo</label>
                    <select name="sexo" class="form-select">
                        <option value="">Prefiro não informar</option>
                        <option value="feminino">Feminino</option>
                        <option value="masculino">Masculino</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
                <div class="col-md-4 campo-pf">
                    <label class="form-label small fw-bold">Data de Nascimento</label>
                    <input type="date" name="data_nascimento" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold">E-mail</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Senha</label>
                    <input type="password" name="senha" class="form-control" minlength="8" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-bold">CEP</label>
                    <input type="text" name="cep" id="cepInput" class="form-control" placeholder="00000-000" required maxlength="9">
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <div id="cepResultado" class="small text-muted"></div>
                </div>

                <div class="col-12">
                    <div class="cf-turnstile" data-sitekey="0x4AAAAAAC9uBAnb4gKBrcof" data-theme="light" data-callback="onTurnstileOk"></div>
                </div>

                <div class="col-12 mt-4 text-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" id="btnEnviar" disabled>
                        Enviar Cadastro
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<script>
function onTurnstileOk() {
    document.getElementById('btnEnviar').disabled = false;
}
</script>
<script>
document.querySelectorAll('input[name="tipo"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        const ehPj = document.getElementById('tipoPj').checked;
        document.querySelectorAll('.campo-pj').forEach(el => el.classList.toggle('d-none', !ehPj));
        document.querySelectorAll('.campo-pf').forEach(el => el.classList.toggle('d-none', ehPj));
        document.getElementById('labelNome').textContent = ehPj ? 'Razão Social' : 'Nome Completo';
    });
});

document.getElementById('usaNomeSocial').addEventListener('change', function () {
    document.querySelector('.campo-nome-social').classList.toggle('d-none', !this.checked);
});

let cepTimeout = null;
let cepValido = false;
document.getElementById('cepInput').addEventListener('input', function () {
    const cep = this.value.replace(/\D/g, '');
    const resultado = document.getElementById('cepResultado');
    cepValido = false;
    clearTimeout(cepTimeout);

    if (cep.length !== 8) {
        resultado.textContent = '';
        return;
    }

    resultado.textContent = 'Consultando CEP...';
    cepTimeout = setTimeout(function () {
        fetch('<?= url('/pluviometro-cidadao/cep-lookup') ?>?cep=' + cep)
            .then(r => r.json())
            .then(data => {
                if (data.valido) {
                    resultado.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> ' + data.bairro + ' — ' + data.cidade + '/' + data.uf + '</span>';
                    cepValido = true;
                } else {
                    resultado.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> ' + data.erro + '</span>';
                }
            })
            .catch(() => { resultado.textContent = 'Erro ao consultar CEP.'; });
    }, 500);
});

document.getElementById('formCadastro').addEventListener('submit', function (e) {
    if (!cepValido) {
        e.preventDefault();
        alert('Informe um CEP válido de <?= htmlspecialchars(addslashes($config['cidade']['nome'] ?? 'Pereira Barreto')) ?> - <?= htmlspecialchars($config['cidade']['uf'] ?? 'SP') ?> antes de enviar.');
    }
});
</script>
