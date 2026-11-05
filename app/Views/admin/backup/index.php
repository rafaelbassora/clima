<?php
/** @var bool $semPermissao */
$mb = fn ($b) => number_format($b / 1048576, 1, ',', '.') . ' MB';
?>

<div class="mb-4">
    <h2 class="h4 fw-bold mb-1">Backup do Banco</h2>
    <p class="text-muted small mb-0">
        Gera uma cópia do banco de dados inteiro (estrutura e dados) em <strong>SQL compactado</strong>.
        O arquivo fica guardado <strong>fora do site</strong>, numa pasta do servidor que nenhum endereço da internet alcança,
        e só pode ser baixado por aqui. Sempre fica só o backup mais recente: ao gerar um novo, o anterior é apagado.
    </p>
</div>

<?php if ($semPermissao): ?>
    <div class="alert alert-warning">
        <i class="bi bi-shield-lock me-1"></i> O backup do banco é restrito a usuários <strong>administradores</strong>.
    </div>
<?php else: ?>

<?php if (!empty($msg)): ?>
    <div class="alert <?= $msg[0] === 'ok' ? 'alert-success' : 'alert-danger' ?>"><?= htmlspecialchars($msg[1]) ?></div>
<?php endif; ?>
<?php if (!empty($erroPasta)): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-octagon me-1"></i><?= htmlspecialchars($erroPasta) ?></div>
<?php endif; ?>

<div class="alert alert-info small d-flex gap-2">
    <i class="bi bi-shield-check fs-5"></i>
    <div>
        <strong>O que nunca vai no backup:</strong> senhas (de administradores e de cidadãos), segredos do 2FA, tokens de
        redefinição de senha e a chave da API do Wunderground — essas colunas saem vazias. Depois de restaurar um backup,
        é preciso redefinir as senhas, ativar o 2FA de novo e recadastrar a chave da estação.
    </div>
</div>

<?php if (!$confirmado): ?>
    <!-- 1. Confirmar a senha -->
    <form action="/admin/backup/confirmar" method="POST" class="weather-card mb-4" autocomplete="off">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="card-body">
            <h3 class="h6 fw-bold mb-1"><i class="bi bi-lock me-1"></i>Confirme sua identidade</h3>
            <p class="small text-muted">Por segurança, gerar, baixar ou apagar um backup exige a sua senha<?= $exige2fa ? ' e o código do 2FA' : '' ?>. A confirmação vale por 15 minutos.</p>
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-bold" for="senhaBackup">Sua senha</label>
                    <input type="password" name="senha" id="senhaBackup" class="form-control" required autocomplete="current-password">
                </div>
                <?php if ($exige2fa): ?>
                <div class="col-md-3">
                    <label class="form-label small fw-bold" for="codigoBackup">Código 2FA</label>
                    <input type="text" name="codigo" id="codigoBackup" class="form-control" inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" required autocomplete="one-time-code">
                </div>
                <?php endif; ?>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold"><i class="bi bi-unlock me-1"></i>Confirmar</button>
                </div>
            </div>
        </div>
    </form>
<?php else: ?>
    <p class="small text-success mb-3"><i class="bi bi-unlock-fill me-1"></i>Senha confirmada — liberado até <?= date('H:i', $confirmadoAte) ?>.</p>
<?php endif; ?>

<!-- Andamento -->
<div class="weather-card mb-4" id="cardAndamento" style="<?= ($emAndamento || in_array($status['estado'] ?? '', ['iniciando', 'rodando'], true)) ? '' : 'display:none' ?>">
    <div class="card-body">
        <h3 class="h6 fw-bold mb-2"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Gerando backup…</h3>
        <div class="progress mb-2" style="height: 10px;" role="progressbar" aria-label="Andamento do backup">
            <div class="progress-bar progress-bar-striped progress-bar-animated" id="barraBackup" style="width: 0%"></div>
        </div>
        <div class="small text-muted" id="textoBackup" aria-live="polite">Preparando…</div>
    </div>
</div>

<!-- Backup atual -->
<div class="weather-card mb-4">
    <div class="card-body">
        <h3 class="h6 fw-bold mb-3"><i class="bi bi-archive me-1"></i>Backup guardado no servidor</h3>
        <?php if (empty($arquivos)): ?>
            <p class="small text-muted mb-0">Nenhum backup guardado.</p>
        <?php else: ?>
            <?php foreach ($arquivos as $a): ?>
            <div class="d-flex flex-wrap align-items-center gap-3 py-2 border-bottom">
                <div class="me-auto">
                    <div class="fw-semibold small text-break"><?= htmlspecialchars($a['nome']) ?></div>
                    <div class="small text-muted">Gerado em <?= date('d/m/Y H:i', $a['data']) ?> · <?= $mb($a['bytes']) ?></div>
                </div>
                <?php if ($confirmado): ?>
                    <form action="/admin/backup/baixar" method="POST" class="d-inline">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <input type="hidden" name="arquivo" value="<?= htmlspecialchars($a['nome']) ?>">
                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3"><i class="bi bi-download me-1"></i>Baixar</button>
                    </form>
                    <form action="/admin/backup/apagar" method="POST" class="d-inline" onsubmit="return confirm('Apagar este backup do servidor?');">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <input type="hidden" name="arquivo" value="<?= htmlspecialchars($a['nome']) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3"><i class="bi bi-trash me-1"></i>Apagar</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if (($status['estado'] ?? '') === 'erro'): ?>
            <div class="alert alert-danger small mt-3 mb-0">O último backup falhou: <?= htmlspecialchars($status['erro'] ?? 'erro desconhecido') ?></div>
        <?php endif; ?>
    </div>
</div>

<?php if ($confirmado): ?>
<!-- Gerar novo -->
<form action="/admin/backup/gerar" method="POST" class="weather-card mb-4" id="formGerar">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <div class="card-body">
        <h3 class="h6 fw-bold mb-3"><i class="bi bi-database-add me-1"></i>Gerar novo backup</h3>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="modo" id="modoCompleto" value="completo" checked>
                    <label class="form-check-label" for="modoCompleto"><strong>Completo</strong> — todas as tabelas, com todo o histórico de leituras (leva cerca de 1 minuto)</label>
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="radio" name="modo" id="modoLeve" value="leve">
                    <label class="form-check-label" for="modoLeve"><strong>Sem o histórico</strong> — cadastros, notícias e configurações; as tabelas grandes de leituras e previsões vão só com a estrutura (segundos)</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="anonimizar" value="1" id="anonimizar">
                    <label class="form-check-label" for="anonimizar"><strong>Anonimizar os cidadãos</strong> — tira nome, CPF/CNPJ, celular, e-mail e data de nascimento do Pluviômetro Cidadão</label>
                </div>
                <div class="small text-muted mt-2">
                    <i class="bi bi-info-circle me-1"></i>Sem anonimizar, o arquivo contém dados pessoais (LGPD): guarde-o em local seguro e não compartilhe.
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" id="btnGerar" <?= $emAndamento ? 'disabled' : '' ?>>
                    <i class="bi bi-play-fill me-1"></i>Gerar backup
                </button>
                <?php if (!empty($arquivos)): ?>
                    <span class="small text-muted ms-2">O backup guardado agora será substituído quando o novo ficar pronto.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>
<?php endif; ?>

<!-- Tabelas -->
<?php if (!empty($tabelas)): ?>
<details class="weather-card mb-4">
    <summary class="card-body py-3 fw-bold small" style="cursor:pointer">Tabelas do banco (<?= count($tabelas) ?>)</summary>
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table table-sm small align-middle mb-0">
                <thead><tr><th>Tabela</th><th class="text-end">Linhas (aprox.)</th><th class="text-end">Tamanho</th><th>Observação</th></tr></thead>
                <tbody>
                <?php foreach ($tabelas as $nome => $t): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($nome) ?></code></td>
                        <td class="text-end"><?= number_format($t['linhas'], 0, ',', '.') ?></td>
                        <td class="text-end"><?= $mb($t['bytes']) ?></td>
                        <td>
                            <?php if ($t['protegida']): ?><span class="badge bg-success bg-opacity-10 text-success">senhas/segredos removidos</span><?php endif; ?>
                            <?php if ($t['historica']): ?><span class="badge bg-secondary bg-opacity-10 text-secondary">histórico</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</details>
<?php endif; ?>

<!-- Como restaurar + registro -->
<div class="row g-3">
    <div class="col-lg-6">
        <div class="weather-card h-100"><div class="card-body small">
            <h3 class="h6 fw-bold mb-2"><i class="bi bi-arrow-counterclockwise me-1"></i>Como restaurar</h3>
            <ol class="mb-0 ps-3">
                <li>Descompacte o arquivo <code>.sql.gz</code> (7-Zip ou WinRAR).</li>
                <li>Importe no MySQL: <code>mysql -u USUARIO -p NOME_DO_BANCO &lt; arquivo.sql</code></li>
                <li>Redefina as senhas dos administradores, ative o 2FA e recadastre a chave da estação em <em>Fonte da Estação</em>.</li>
            </ol>
            <p class="text-muted mt-2 mb-0">Atenção: a importação <strong>substitui</strong> as tabelas que já existirem no banco de destino.</p>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="weather-card h-100"><div class="card-body small">
            <h3 class="h6 fw-bold mb-2"><i class="bi bi-journal-text me-1"></i>Registro (quem gerou, baixou ou apagou)</h3>
            <?php if (empty($logs)): ?>
                <p class="text-muted mb-0">Nenhum registro ainda.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-0" style="max-height: 220px; overflow:auto;">
                    <?php foreach ($logs as $l): ?><li class="border-bottom py-1 text-break"><?= htmlspecialchars($l) ?></li><?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div></div>
    </div>
</div>

<script>
(function () {
    const card = document.getElementById('cardAndamento');
    const barra = document.getElementById('barraBackup');
    const texto = document.getElementById('textoBackup');
    const btn = document.getElementById('btnGerar');
    const fmt = (n) => Number(n || 0).toLocaleString('pt-BR');
    let estavaRodando = card.style.display !== 'none';

    async function atualizar() {
        try {
            const r = await fetch('/admin/backup/status', { cache: 'no-store', credentials: 'same-origin' });
            const d = await r.json();
            const s = d.status || {};
            const rodando = d.emAndamento || s.estado === 'iniciando' || s.estado === 'rodando';
            if (rodando) {
                card.style.display = '';
                if (btn) btn.disabled = true;
                const pct = s.total ? Math.round((s.indice - 1) / s.total * 100) : 2;
                barra.style.width = Math.max(2, pct) + '%';
                texto.textContent = s.tabela
                    ? `Tabela ${s.indice} de ${s.total}: ${s.tabela} — ${fmt(s.linhas)} linhas copiadas até agora`
                    : 'Preparando…';
                estavaRodando = true;
                setTimeout(atualizar, 2000);
            } else if (estavaRodando) {
                location.reload(); // terminou: mostra o arquivo novo (ou o erro)
            }
        } catch (e) {
            setTimeout(atualizar, 5000);
        }
    }
    if (estavaRodando) atualizar();
    const form = document.getElementById('formGerar');
    if (form) form.addEventListener('submit', () => { if (btn) btn.disabled = true; });
})();
</script>

<?php endif; ?>
