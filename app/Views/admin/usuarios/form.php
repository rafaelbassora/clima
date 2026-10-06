<?php
/** @var array|null $item */
/** @var string|null $erro */
$isEdit = isset($item);
?>
<div class="mb-4">
    <a href="/admin/usuarios" class="text-decoration-none small text-muted">
        <i class="bi bi-arrow-left me-1"></i>Voltar para a lista
    </a>
    <h2 class="h4 fw-bold mt-2"><?= $isEdit ? 'Editar Usuário' : 'Novo Usuário' ?></h2>
</div>

<div class="weather-card">
    <div class="card-body">
        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger small"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form action="<?= $isEdit ? "/admin/usuario/edit?id={$item['id']}" : '/admin/usuario/add' ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Nome</label>
                    <input type="text" name="name" class="form-control"
                           value="<?= $isEdit ? htmlspecialchars($item['name']) : htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">E-mail</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= $isEdit ? htmlspecialchars($item['email']) : htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Senha <?= $isEdit ? '(deixe em branco para manter a atual)' : '' ?></label>
                    <input type="password" name="password" class="form-control" minlength="8" <?= $isEdit ? '' : 'required' ?>>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="is_admin" id="is_admin"
                               <?= ($isEdit && $item['is_admin']) ? 'checked' : '' ?>>
                        <label class="form-check-label small fw-bold" for="is_admin">Administrador</label>
                    </div>
                </div>
                <div class="col-12 mt-4 text-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">
                        <?= $isEdit ? 'Salvar Alterações' : 'Cadastrar Usuário' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
