<?php /** @var array $historico */ ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0">Meu Painel</h4>
        <p class="text-muted small mb-0">
            Estação: <strong><?= htmlspecialchars($_SESSION['cidadao_station'] ?? '--') ?></strong>
            &middot; Olá, <?= htmlspecialchars($_SESSION['cidadao_nome'] ?? '') ?>
        </p>
    </div>
    <a href="<?= url('/pluviometro-cidadao/logout') ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3">Sair</a>
</div>

<div class="weather-card mb-4">
    <div class="card-header-custom">
        <i class="bi bi-cloud-rain-fill"></i> Lançar Chuva
    </div>
    <div class="card-body">
        <form action="<?= url('/pluviometro-cidadao/painel/lancar') ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Data</label>
                    <input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                    <div class="form-text small">Pode lançar dados de dias anteriores também.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Medida (mm)</label>
                    <input type="text" name="medida_mm" class="form-control" placeholder="0,00" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold d-block">Evento</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="granizo" id="granizo">
                        <label class="form-check-label small" for="granizo">Granizo</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="chuva_mansa" id="chuvaMansa">
                        <label class="form-check-label small" for="chuvaMansa">Chuva mansa</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="chuva_forte" id="chuvaForte">
                        <label class="form-check-label small" for="chuvaForte">Chuva forte</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Observação (opcional)</label>
                    <input type="text" name="observacao" class="form-control" placeholder="Ex: ventania forte, queda de galhos, etc.">
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">Salvar Lançamento</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="weather-card">
    <div class="card-header-custom">
        <i class="bi bi-clock-history"></i> Meu Histórico
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-weather">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>mm</th>
                        <th>Eventos</th>
                        <th>Observação</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historico)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Nenhum lançamento ainda.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($historico as $row): ?>
                        <tr>
                            <td class="fw-bold"><?= date('d/m/Y', strtotime($row['data'])) ?></td>
                            <td><?= number_format((float) $row['medida_mm'], 2, ',', '.') ?> mm</td>
                            <td>
                                <?php if (!empty($row['granizo'])): ?><span class="badge bg-info me-1">Granizo</span><?php endif; ?>
                                <?php if (!empty($row['chuva_mansa'])): ?><span class="badge bg-secondary me-1">Chuva Mansa</span><?php endif; ?>
                                <?php if (!empty($row['chuva_forte'])): ?><span class="badge bg-danger">Chuva Forte</span><?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($row['observacao'] ?? '') ?></td>
                            <td class="text-end">
                                <a href="<?= url('/pluviometro-cidadao/painel/editar') ?>?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary border-0" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= url('/pluviometro-cidadao/painel/excluir') ?>?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Excluir este lançamento?')" title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
