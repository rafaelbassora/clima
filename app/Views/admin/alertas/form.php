<?php
/** @var array|null $item */
$isEdit = isset($item);
?>
<div class="mb-4">
    <a href="/admin/alertas" class="text-decoration-none small text-muted">
        <i class="bi bi-arrow-left me-1"></i>Voltar para a lista
    </a>
    <h2 class="h4 fw-bold mt-2"><?= $isEdit ? 'Editar Alerta' : 'Novo Alerta de Sistema' ?></h2>
</div>

<div class="weather-card">
    <div class="card-body">
        <form action="<?= $isEdit ? "/admin/alerta/edit?id={$item['id']}" : '/admin/alerta/add' ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Tipo de Alerta</label>
                    <select name="tipo" class="form-select" required>
                        <option value="info" <?= ($isEdit && $item['tipo'] == 'info') ? 'selected' : '' ?>>Informativo (Azul)</option>
                        <option value="success" <?= ($isEdit && $item['tipo'] == 'success') ? 'selected' : '' ?>>Sucesso (Verde)</option>
                        <option value="warning" <?= ($isEdit && $item['tipo'] == 'warning') ? 'selected' : '' ?>>Aviso (Amarelo)</option>
                        <option value="danger" <?= ($isEdit && $item['tipo'] == 'danger') ? 'selected' : '' ?>>Perigo/Urgente (Vermelho)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="ativo" id="ativo" 
                               <?= (!$isEdit || $item['ativo']) ? 'checked' : '' ?>>
                        <label class="form-check-label small fw-bold" for="ativo">Alerta Ativo?</label>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Início da Exibição</label>
                    <input type="datetime-local" name="data_inicio" class="form-control" 
                           value="<?= $isEdit ? date('Y-m-d\TH:i', strtotime($item['data_inicio'])) : date('Y-m-d\TH:i') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Fim da Exibição</label>
                    <input type="datetime-local" name="data_fim" class="form-control" 
                           value="<?= $isEdit ? date('Y-m-d\TH:i', strtotime($item['data_fim'])) : date('Y-m-d\T23:59', strtotime('+1 day')) ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-bold">Mensagem do Alerta</label>
                    <textarea name="mensagem" class="form-control" rows="4" required placeholder="Digite aqui o aviso que aparecerá no site..."><?= $isEdit ? htmlspecialchars($item['mensagem']) : '' ?></textarea>
                    <div class="form-text small">Use mensagens curtas e diretas para melhor leitura no mobile.</div>
                </div>

                <div class="col-12 mt-4 text-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">
                        <?= $isEdit ? 'Atualizar Alerta' : 'Publicar Alerta' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
