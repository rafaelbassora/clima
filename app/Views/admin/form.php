<?php
/** @var array|null $item */
$isEdit = isset($item);
?>
<div class="mb-4">
    <a href="/admin" class="text-decoration-none small text-muted">
        <i class="bi bi-arrow-left me-1"></i>Voltar para a lista
    </a>
    <h2 class="h4 fw-bold mt-2"><?= $isEdit ? 'Editar Registro' : 'Lançar Nova Chuva Manual' ?></h2>
</div>

<div class="weather-card">
    <div class="card-body">
        <form action="<?= $isEdit ? "/admin/edit?id={$item['id']}" : '/admin/add' ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Data do Evento</label>
                    <input type="date" name="data" class="form-control" 
                           value="<?= $isEdit ? date('Y-m-d', strtotime($item['data'])) : date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Local</label>
                    <select name="local" class="form-select" required>
                        <option value="1" <?= ($isEdit && $item['local'] == 1) ? 'selected' : '' ?>>Sede</option>
                        <option value="2" <?= ($isEdit && $item['local'] == 2) ? 'selected' : '' ?>>Zona Rural</option>
                        <option value="3" <?= ($isEdit && $item['local'] == 3) ? 'selected' : '' ?>>Local 3</option>
                        <option value="4" <?= ($isEdit && $item['local'] == 4) ? 'selected' : '' ?>>Local 4</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="choveu" id="choveu" 
                               <?= (!$isEdit || $item['choveu']) ? 'checked' : '' ?>>
                        <label class="form-check-label small fw-bold" for="choveu">Houve Chuva?</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Medida (mm)</label>
                    <input type="text" name="medida" class="form-control" placeholder="0,00"
                           value="<?= $isEdit ? number_format($item['medida'] ?? 0, 2, ',', '.') : '' ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">pH da Água</label>
                    <input type="text" name="ph" class="form-control" placeholder="7,00"
                           value="<?= $isEdit ? number_format($item['ph'] ?? 0, 2, ',', '.') : '' ?>">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Observações</label>
                    <textarea name="obs" class="form-control" rows="3"><?= $isEdit ? htmlspecialchars($item['obs']) : '' ?></textarea>
                </div>
                <div class="col-12 mt-4 text-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">
                        <?= $isEdit ? 'Salvar Alterações' : 'Cadastrar Registro' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
