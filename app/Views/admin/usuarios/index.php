<?php
/** @var array $dados */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h4 fw-bold mb-0">Gerenciar Usuários</h2>
    <a href="/admin/usuario/add" class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-plus-lg me-2"></i>Novo Usuário
    </a>
</div>

<div class="weather-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-weather mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Admin</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dados)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Nenhum usuário cadastrado.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($dados as $row): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td>
                                <?php if ($row['is_admin']): ?>
                                    <span class="badge bg-primary">Sim</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Não</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="/admin/usuario/edit?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary border-0" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ((int)$row['id'] !== (int)($_SESSION['usuario_id'] ?? 0)): ?>
                                    <a href="/admin/usuario/delete?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Excluir este usuário?')" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
