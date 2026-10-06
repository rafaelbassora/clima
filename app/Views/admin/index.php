<?php
/** @var array $dados */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h4 fw-bold mb-0">Gerenciar Chuvas Manuais</h2>
    <a href="/admin/add" class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-plus-lg me-2"></i>Nova Chuva
    </a>
</div>

<div class="weather-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-weather mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Local</th>
                        <th>Choveu</th>
                        <th>Medida (mm)</th>
                        <th>pH</th>
                        <th>Obs</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dados)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Nenhum registro encontrado.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($dados as $row): ?>
                        <tr>
                            <td class="fw-bold"><?= date('d/m/Y H:i', strtotime($row['data'])) ?></td>
                            <td><?php
                                echo match((string)$row['local']) {
                                    '1' => 'Sede',
                                    '2' => 'Zona Rural',
                                    '3' => 'Local 3',
                                    '4' => 'Local 4',
                                    default => "Local {$row['local']}"
                                };
                            ?></td>
                            <td>
                                <?php if ($row['choveu']): ?>
                                    <span class="badge bg-info">Sim</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Não</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold"><?= number_format($row['medida'] ?? 0, 2, ',', '.') ?> mm</td>
                            <td><?= number_format($row['ph'] ?? 0, 2, ',', '.') ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($row['obs']) ?></td>
                            <td class="text-end">
                                <a href="/admin/edit?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary border-0" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="/admin/delete?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Excluir este registro?')" title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($pages > 1): ?>
        <div class="card-footer bg-transparent border-top py-3">
            <nav aria-label="Navegação de páginas">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-item-link page-link" href="/admin?page=<?= $page - 1 ?>">Anterior</a>
                    </li>
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="/admin?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="/admin?page=<?= $page + 1 ?>">Próxima</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<div class="mt-4 text-end">
    <a href="/logout" class="btn btn-link text-danger text-decoration-none small">
        <i class="bi bi-box-arrow-right me-1"></i>Sair do Sistema
    </a>
</div>
