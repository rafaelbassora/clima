<?php
/** @var array $item */
/** @var string|null $erro */
/** @var string|null $sucesso */
?>

<div class="mb-4">
    <a href="<?= url('/pluviometro-cidadao/painel') ?>" class="text-decoration-none small text-muted">
        <i class="bi bi-arrow-left me-1"></i>Voltar ao painel
    </a>
    <h2 class="h4 fw-bold mt-2">Meus Dados</h2>
</div>

<div class="weather-card">
    <div class="card-body">
        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger small"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <?php if (!empty($sucesso)): ?>
            <div class="alert alert-success small"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form action="<?= url('/pluviometro-cidadao/painel/perfil') ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label small fw-bold"><?= $item['tipo'] === 'pj' ? 'Razão Social' : 'Nome Completo' ?></label>
                    <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($item['nome']) ?>" required>
                </div>

                <?php if ($item['tipo'] === 'pj'): ?>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">CNPJ</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($item['cnpj'] ?? '') ?>" disabled>
                        <div class="form-text small">Não pode ser alterado.</div>
                    </div>
                <?php else: ?>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">CPF</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($item['cpf'] ?? '') ?>" disabled>
                        <div class="form-text small">Não pode ser alterado.</div>
                    </div>
                <?php endif; ?>

                <?php if ($item['tipo'] === 'pf'): ?>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="usa_nome_social" id="usaNomeSocial" <?= $item['usa_nome_social'] ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-bold" for="usaNomeSocial">Uso nome social</label>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">Nome Social</label>
                        <input type="text" name="nome_social" class="form-control" value="<?= htmlspecialchars($item['nome_social'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Sexo</label>
                        <select name="sexo" class="form-select">
                            <option value="" <?= empty($item['sexo']) ? 'selected' : '' ?>>Prefiro não informar</option>
                            <option value="feminino" <?= $item['sexo'] === 'feminino' ? 'selected' : '' ?>>Feminino</option>
                            <option value="masculino" <?= $item['sexo'] === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                            <option value="outro" <?= $item['sexo'] === 'outro' ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Data de Nascimento</label>
                        <input type="date" name="data_nascimento" class="form-control" value="<?= htmlspecialchars($item['data_nascimento'] ?? '') ?>">
                    </div>
                <?php endif; ?>

                <div class="col-md-6">
                    <label class="form-label small fw-bold">E-mail</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($item['email']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Celular</label>
                    <input type="tel" name="celular" class="form-control" value="<?= htmlspecialchars($item['celular'] ?? '') ?>" required>
                </div>

                <div class="col-12 mt-4 text-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">Salvar Alterações</button>
                </div>
            </div>
        </form>
    </div>
</div>
