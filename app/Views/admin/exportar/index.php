<?php
/** @var bool $semPermissao */
/** @var array $tabelas */
/** @var array $periodos */
/** @var string $token */
/** @var string|null $erro */
$fmtData = fn (?string $d) => $d ? date('d/m/Y', strtotime($d)) : '—';
?>

<div class="mb-4">
    <h2 class="h4 fw-bold mb-1">Exportar Dados</h2>
    <p class="text-muted small mb-0">
        Baixe as tabelas de leitura em <strong>CSV</strong> (abre no Excel/LibreOffice) ou <strong>SQL</strong>
        (pra importar em outro banco MySQL). Tabelas de usuários e de cidadãos não podem ser exportadas por aqui.
    </p>
</div>

<?php if ($semPermissao): ?>
    <div class="alert alert-warning">
        <i class="bi bi-shield-lock me-1"></i> A exportação de dados é restrita a usuários <strong>administradores</strong>.
    </div>
<?php else: ?>

<?php if (!empty($erro)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<form action="/admin/exportar/baixar" method="POST" id="formExportar" class="weather-card mb-4">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold" for="tabela">Tabela</label>
                <select name="tabela" id="tabela" class="form-select" required>
                    <?php foreach ($tabelas as $t => $cfg): ?>
                        <?php $per = $periodos[$t] ?? []; ?>
                        <option value="<?= $t ?>"
                                data-local="<?= $cfg['local'] ? '1' : '0' ?>"
                                data-inicio="<?= htmlspecialchars($per['inicio'] ?? '') ?>"
                                data-fim="<?= htmlspecialchars($per['fim'] ?? '') ?>">
                            <?= htmlspecialchars($cfg['rotulo']) ?> (<?= $t ?>) — ~<?= number_format($per['linhas_aprox'] ?? 0, 0, ',', '.') ?> linhas
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text small" id="tabelaInfo"></div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold" for="inicio">Data inicial</label>
                <input type="date" name="inicio" id="inicio" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold" for="fim">Data final</label>
                <input type="date" name="fim" id="fim" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>

            <div class="col-md-3" id="grupoLocal">
                <label class="form-label small fw-bold" for="local">Local</label>
                <select name="local" id="local" class="form-select">
                    <option value="">Todos</option>
                    <option value="1">1 — Sede</option>
                    <option value="2">2 — Zona Rural</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                </select>
            </div>
            <div class="col-md-3">
                <span class="form-label small fw-bold d-block">Formato</span>
                <div class="btn-group w-100" role="group" aria-label="Formato">
                    <input type="radio" class="btn-check" name="formato" id="fmtCsv" value="csv" checked>
                    <label class="btn btn-outline-primary" for="fmtCsv"><i class="bi bi-filetype-csv me-1"></i> CSV</label>
                    <input type="radio" class="btn-check" name="formato" id="fmtSql" value="sql">
                    <label class="btn btn-outline-primary" for="fmtSql"><i class="bi bi-filetype-sql me-1"></i> SQL</label>
                </div>
            </div>
            <div class="col-md-3" id="grupoSeparador">
                <label class="form-label small fw-bold" for="separador">Separador do CSV</label>
                <select name="separador" id="separador" class="form-select">
                    <option value=";">; (Excel em português)</option>
                    <option value=",">, (padrão internacional)</option>
                </select>
            </div>
            <div class="col-md-3 d-flex flex-column justify-content-end gap-1">
                <div class="form-check" id="grupoEstrutura">
                    <input class="form-check-input" type="checkbox" name="estrutura" id="estrutura" value="1">
                    <label class="form-check-label small" for="estrutura">Incluir estrutura (CREATE TABLE)</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="gzip" id="gzip" value="1">
                    <label class="form-check-label small" for="gzip">Compactar (.gz)</label>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <button type="button" class="btn btn-outline-secondary" id="btnContar">
                <i class="bi bi-calculator me-1"></i> Contar linhas
            </button>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-download me-1"></i> Baixar arquivo
            </button>
            <span class="small text-muted" id="resultadoContagem" aria-live="polite"></span>
        </div>
        <p class="small text-muted mt-3 mb-0">
            <i class="bi bi-info-circle"></i> O arquivo é gerado na hora, direto do banco. Exportações grandes
            (ex.: a tabela inteira de leituras, ~1,5 milhão de linhas) podem levar alguns minutos — a dica é marcar
            <strong>Compactar (.gz)</strong>, que reduz o arquivo em ~90%.
        </p>
    </div>
</form>

<div class="weather-card">
    <div class="card-body">
        <h3 class="h6 fw-bold mb-3">Período disponível em cada tabela</h3>
        <div class="table-responsive">
            <table class="table table-weather mb-0">
                <thead>
                    <tr><th>Tabela</th><th>Desde</th><th>Até</th><th class="text-end">Linhas (aprox.)</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($tabelas as $t => $cfg): ?>
                        <?php $per = $periodos[$t] ?? []; ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cfg['rotulo']) ?></strong> <span class="text-muted small">(<?= $t ?>)</span></td>
                            <td><?= $fmtData($per['inicio'] ?? null) ?></td>
                            <td><?= $fmtData($per['fim'] ?? null) ?></td>
                            <td class="text-end"><?= number_format($per['linhas_aprox'] ?? 0, 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('formExportar');
    const tabela = document.getElementById('tabela');
    const info = document.getElementById('tabelaInfo');
    const grupoLocal = document.getElementById('grupoLocal');
    const grupoSep = document.getElementById('grupoSeparador');
    const grupoEstr = document.getElementById('grupoEstrutura');
    const resultado = document.getElementById('resultadoContagem');
    const fmtBR = (d) => d ? d.split('-').reverse().join('/') : '—';

    function atualizar() {
        const op = tabela.selectedOptions[0];
        const temLocal = op.dataset.local === '1';
        grupoLocal.style.display = temLocal ? '' : 'none';
        if (!temLocal) document.getElementById('local').value = '';
        info.textContent = 'Dados de ' + fmtBR(op.dataset.inicio) + ' até ' + fmtBR(op.dataset.fim) + '.';
        const sql = document.getElementById('fmtSql').checked;
        grupoSep.style.display = sql ? 'none' : '';
        grupoEstr.style.display = sql ? '' : 'none';
        resultado.textContent = '';
    }

    tabela.addEventListener('change', atualizar);
    form.querySelectorAll('input[name="formato"]').forEach((r) => r.addEventListener('change', atualizar));
    ['inicio', 'fim', 'local'].forEach((id) => document.getElementById(id).addEventListener('change', () => { resultado.textContent = ''; }));
    atualizar();

    document.getElementById('btnContar').addEventListener('click', async function () {
        const dados = new URLSearchParams(new FormData(form));
        dados.delete('token');
        resultado.textContent = 'Contando…';
        this.disabled = true;
        try {
            const r = await fetch('/admin/exportar/contar?' + dados.toString(), { headers: { 'Accept': 'application/json' } });
            const j = await r.json();
            resultado.textContent = j.erro
                ? j.erro
                : j.linhas.toLocaleString('pt-BR') + ' linha(s) — ' + j.filtros + ' — arquivo: ' + j.arquivo;
        } catch (e) {
            resultado.textContent = 'Não foi possível contar agora.';
        } finally {
            this.disabled = false;
        }
    });
})();
</script>

<?php endif; ?>
