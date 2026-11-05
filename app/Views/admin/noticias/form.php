<div class="d-flex justify-content-between align-items-center mb-4">
    <h3><?= $title ?></h3>
    <a href="<?= url('/admin/noticias') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<?php if (!empty($_SESSION['noticia_gerada'])): ?>
    <div class="alert alert-info"><i class="bi bi-magic me-1"></i><?= htmlspecialchars($_SESSION['noticia_gerada']) ?></div>
    <?php unset($_SESSION['noticia_gerada']); ?>
<?php endif; ?>

<div class="card bg-dark text-white border-secondary shadow">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-7 mb-3">
                    <label class="form-label">Título</label>
                    <input type="text" name="titulo" class="form-control bg-dark text-white border-secondary" 
                           value="<?= isset($item) ? htmlspecialchars($item['titulo']) : '' ?>" required>
                </div>
                <div class="col-md-1 mb-3">
                    <label class="form-label">Status</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="ativo" id="statusSwitch" 
                               <?= (!isset($item) || $item['ativo']) ? 'checked' : '' ?>>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Data da Notícia</label>
                    <input type="datetime-local" name="data_noticia" class="form-control bg-dark text-white border-secondary" 
                           value="<?= isset($item) ? date('Y-m-d\TH:i', strtotime($item['data_noticia'])) : date('Y-m-d\TH:i') ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Agendamento</label>
                    <input type="datetime-local" name="data_agendamento" class="form-control bg-dark text-white border-secondary" 
                           value="<?= isset($item) ? date('Y-m-d\TH:i', strtotime($item['data_agendamento'])) : date('Y-m-d\TH:i') ?>">
                    <small class="text-muted" style="font-size: 0.7rem;">Quando ficará visível</small>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Resumo (Breve descrição para a listagem)</label>
                <textarea name="resumo" class="form-control bg-dark text-white border-secondary" rows="3" required><?= isset($item) ? htmlspecialchars($item['resumo']) : '' ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Conteúdo Completo</label>
                <textarea name="conteudo" class="form-control bg-dark text-white border-secondary" rows="10" required><?= isset($item) ? htmlspecialchars($item['conteudo']) : '' ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Imagem de Destaque</label>
                <?php if (isset($item) && $item['imagem']): ?>
                    <div class="mb-2">
                        <img src="<?= url('/' . $item['imagem']) ?>" style="max-height: 150px; border-radius: 8px;" class="border border-secondary">
                        <p class="small text-muted mt-1">Imagem atual. Se desejar trocar, selecione uma nova abaixo.</p>
                    </div>
                <?php endif; ?>
                <input type="file" name="imagem" class="form-control bg-dark text-white border-secondary" accept="image/*">
                <small class="text-muted">Formatos permitidos: JPG, PNG, WEBP. Tamanho ideal: 800x600 ou superior.</small>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary px-5 py-2">
                    <i class="bi bi-save"></i> Salvar Notícia
                </button>
            </div>
        </form>
    </div>
</div>
