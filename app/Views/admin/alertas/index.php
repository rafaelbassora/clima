<?php
/** @var array $dados */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="/admin" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left me-1"></i>Voltar para Chuvas
        </a>
        <h2 class="h4 fw-bold mb-0 mt-2">Gerenciar Alertas do Site</h2>
    </div>
    <a href="/admin/alerta/add" class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-plus-lg me-2"></i>Novo Alerta
    </a>
</div>

<div class="weather-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-weather mb-0">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Tipo</th>
                        <th>Mensagem</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dados)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Nenhum alerta cadastrado.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($dados as $row): ?>
                        <tr>
                            <td>
                                <?php 
                                $now = date('Y-m-d H:i:s');
                                $isVigente = ($row['ativo'] && $row['data_inicio'] <= $now && $row['data_fim'] >= $now);
                                if ($isVigente): ?>
                                    <span class="badge bg-success">Vigente</span>
                                <?php elseif (!$row['ativo']): ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php elseif ($row['data_inicio'] > $now): ?>
                                    <span class="badge bg-warning text-dark">Agendado</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Expirado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $row['tipo'] ?>"><?= strtoupper($row['tipo']) ?></span>
                            </td>
                            <td class="small w-50"><?= nl2br(htmlspecialchars($row['mensagem'])) ?></td>
                            <td class="small"><?= date('d/m/Y H:i', strtotime($row['data_inicio'])) ?></td>
                            <td class="small"><?= date('d/m/Y H:i', strtotime($row['data_fim'])) ?></td>
                            <td class="text-end">
                                <a href="/admin/alerta/edit?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary border-0">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="/admin/alerta/delete?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Excluir este alerta?')">
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
