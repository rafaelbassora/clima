<?php /** @var array $item */ ?>

<div class="mb-4">
    <a href="<?= url('/pluviometro-cidadao/painel') ?>" class="text-decoration-none small text-muted">
        <i class="bi bi-arrow-left me-1"></i>Voltar ao painel
    </a>
    <h2 class="h4 fw-bold mt-2">Editar Lançamento</h2>
</div>

<div class="weather-card">
    <div class="card-body">
        <form action="<?= url('/pluviometro-cidadao/painel/editar') ?>?id=<?= $item['id'] ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Data</label>
                    <input type="date" name="data" class="form-control" value="<?= date('Y-m-d', strtotime($item['data'])) ?>" max="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Medida (mm)</label>
                    <input type="text" name="medida_mm" class="form-control" value="<?= number_format((float) $item['medida_mm'], 2, ',', '.') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold d-block">Evento</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="granizo" id="granizo" <?= $item['granizo'] ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="granizo">Granizo</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="chuva_mansa" id="chuvaMansa" <?= $item['chuva_mansa'] ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="chuvaMansa">Chuva mansa</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="chuva_forte" id="chuvaForte" <?= $item['chuva_forte'] ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="chuvaForte">Chuva forte</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Observação (opcional)</label>
                    <input type="text" name="observacao" class="form-control" value="<?= htmlspecialchars($item['observacao'] ?? '') ?>">
                </div>
                <div class="col-12 mt-4 text-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">Salvar Alterações</button>
                </div>
            </div>
        </form>
    </div>
</div>
