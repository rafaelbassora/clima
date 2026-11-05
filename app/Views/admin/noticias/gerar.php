<?php
/** @var string $tipo */
/** @var array $p */
/** @var array|null $noticia */
/** @var string|null $erro */
/** @var array $eventos */
/** @var string $de */
/** @var string $ate */
/** @var string $token */
$meses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
    7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
$anoAtual = (int) date('Y');
$erro = $erro ?? (isset($_GET['erro']) ? (string) $_GET['erro'] : null);
?>
<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
    <h3 class="mb-0"><i class="bi bi-magic me-2"></i><?= $title ?></h3>
    <a href="<?= url('/admin/noticias') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>
<p class="text-muted small mb-4">
    O texto é montado a partir dos dados do sistema (chuva do pluviômetro da Sede, temperatura e umidade da estação),
    com os números exatos do banco. A notícia é salva como <strong>rascunho desativado</strong> — revise, ajuste e publique.
</p>

<?php if (!empty($erro)): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <form method="GET" action="/admin/noticias/gerar" class="card bg-dark text-white border-secondary shadow h-100">
            <div class="card-body">
                <h5 class="fw-bold"><i class="bi bi-calendar-month me-1"></i> Resumo do mês</h5>
                <p class="small text-white-50">Chuva do mês vs. média e série histórica, distribuição das chuvas, temperatura e umidade.</p>
                <input type="hidden" name="tipo" value="mes">
                <div class="row g-2">
                    <div class="col-7">
                        <select name="mes" class="form-select bg-dark text-white border-secondary" aria-label="Mês">
                            <?php foreach ($meses as $n => $nome): ?>
                                <option value="<?= $n ?>" <?= (int) $p['mes'] === $n ? 'selected' : '' ?>><?= $nome ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-5">
                        <select name="ano" class="form-select bg-dark text-white border-secondary" aria-label="Ano">
                            <?php for ($a = $anoAtual; $a >= 2018; $a--): ?>
                                <option value="<?= $a ?>" <?= (int) $p['ano'] === $a ? 'selected' : '' ?>><?= $a ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary w-100 mt-3"><i class="bi bi-eye me-1"></i> Pré-visualizar</button>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        <form method="GET" action="/admin/noticias/gerar" class="card bg-dark text-white border-secondary shadow h-100">
            <div class="card-body">
                <h5 class="fw-bold"><i class="bi bi-calendar-range me-1"></i> Resumo do ano</h5>
                <p class="small text-white-50">Total do ano vs. série histórica, mês mais chuvoso e mais seco, maior chuva, estiagem, temperatura e umidade.</p>
                <input type="hidden" name="tipo" value="ano">
                <select name="ano" class="form-select bg-dark text-white border-secondary" aria-label="Ano">
                    <?php for ($a = $anoAtual; $a >= 2018; $a--): ?>
                        <option value="<?= $a ?>" <?= (int) $p['ano'] === $a ? 'selected' : '' ?>><?= $a ?><?= $a === $anoAtual ? ' (parcial)' : '' ?></option>
                    <?php endfor; ?>
                </select>
                <button class="btn btn-primary w-100 mt-3"><i class="bi bi-eye me-1"></i> Pré-visualizar</button>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        <form method="GET" action="/admin/noticias/gerar#eventos" class="card bg-dark text-white border-secondary shadow h-100">
            <div class="card-body">
                <h5 class="fw-bold"><i class="bi bi-lightning-charge me-1"></i> Eventos extremos</h5>
                <p class="small text-white-50">
                    Chuva ≥ <?= (int) \App\Services\NoticiaAutomaticaService::CHUVA_FORTE_MM ?> mm, calor ≥ <?= (int) \App\Services\NoticiaAutomaticaService::CALOR_C ?> °C,
                    frio ≤ <?= (int) \App\Services\NoticiaAutomaticaService::FRIO_C ?> °C, umidade ≤ <?= \App\Services\NoticiaAutomaticaService::UMIDADE_ALERTA ?>%,
                    recordes e <?= \App\Services\NoticiaAutomaticaService::ESTIAGEM_DIAS ?>+ dias sem chuva.
                </p>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small mb-1" for="de">De</label>
                        <input type="date" id="de" name="de" value="<?= htmlspecialchars($de) ?>" class="form-control bg-dark text-white border-secondary">
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1" for="ate">Até</label>
                        <input type="date" id="ate" name="ate" value="<?= htmlspecialchars($ate) ?>" class="form-control bg-dark text-white border-secondary">
                    </div>
                </div>
                <button class="btn btn-outline-light w-100 mt-3"><i class="bi bi-search me-1"></i> Procurar eventos</button>
            </div>
        </form>
    </div>
</div>

<?php if ($noticia): ?>
    <div class="card bg-dark text-white border-primary shadow mb-4" id="previa">
        <div class="card-header border-primary d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="fw-bold"><i class="bi bi-eye me-1"></i> Pré-visualização</span>
            <form method="POST" action="/admin/noticias/gerar/salvar" class="m-0">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
                <input type="hidden" name="ano" value="<?= (int) $p['ano'] ?>">
                <input type="hidden" name="mes" value="<?= (int) $p['mes'] ?>">
                <input type="hidden" name="chave" value="<?= htmlspecialchars($p['chave']) ?>">
                <button class="btn btn-success"><i class="bi bi-save me-1"></i> Salvar como rascunho e editar</button>
            </form>
        </div>
        <div class="card-body">
            <h4 class="fw-bold mb-2"><?= htmlspecialchars($noticia['titulo']) ?></h4>
            <p class="text-white-50 fst-italic"><?= htmlspecialchars($noticia['resumo']) ?></p>
            <hr class="border-secondary">
            <div style="line-height: 1.7;"><?= nl2br(htmlspecialchars($noticia['conteudo'])) ?></div>
        </div>
    </div>
<?php endif; ?>

<div class="card bg-dark text-white border-secondary shadow" id="eventos">
    <div class="card-body">
        <h5 class="fw-bold mb-1"><i class="bi bi-lightning-charge me-1"></i> Eventos encontrados</h5>
        <p class="small text-white-50 mb-3">
            De <?= date('d/m/Y', strtotime($de)) ?> a <?= date('d/m/Y', strtotime($ate)) ?> ·
            dias seguidos do mesmo tipo aparecem juntos, pelo dia de pico.
        </p>
        <?php if (empty($eventos)): ?>
            <p class="text-white-50 mb-0">Nenhum evento extremo nesse período.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <tbody>
                        <?php foreach ($eventos as $e): ?>
                            <?php $atual = $tipo === 'evento' && $p['chave'] === $e['chave']; ?>
                            <tr class="<?= $atual ? 'table-active' : '' ?>">
                                <td style="width: 2.5rem;"><i class="bi <?= $e['icone'] ?> fs-5"></i></td>
                                <td class="fw-bold text-nowrap"><?= htmlspecialchars($e['tipo']) ?></td>
                                <td class="small"><?= htmlspecialchars($e['descricao']) ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm <?= $atual ? 'btn-primary' : 'btn-outline-light' ?>"
                                       href="/admin/noticias/gerar?<?= htmlspecialchars(http_build_query(['tipo' => 'evento', 'chave' => $e['chave'], 'de' => $de, 'ate' => $ate])) ?>#previa">
                                        <i class="bi bi-eye"></i> Pré-visualizar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
