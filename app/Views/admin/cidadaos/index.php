<?php
/** @var array $pendentes */
/** @var array $aprovados */
?>
<div class="mb-4">
    <h2 class="h4 fw-bold mb-0">Pluviômetro Cidadão — Moderação</h2>
</div>

<div class="weather-card mb-4">
    <div class="card-header-custom">
        <i class="bi bi-hourglass-split"></i> Pendentes de Aprovação (<?= count($pendentes) ?>)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-weather mb-0">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Bairro</th>
                        <th>Cadastrado em</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pendentes)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Nenhum cadastro pendente.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($pendentes as $row): ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?= $row['tipo'] === 'pj' ? 'Empresa' : 'Pessoa Física' ?></span></td>
                            <td class="fw-bold"><?= htmlspecialchars($row['nome']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['bairro']) ?></td>
                            <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                            <td class="text-end">
                                <a href="/admin/cidadao/aprovar?id=<?= $row['id'] ?>" class="btn btn-sm btn-success rounded-pill px-3" onclick="return confirm('Aprovar este cadastro? Um e-mail será enviado ao cidadão.')">
                                    <i class="bi bi-check-lg"></i> Aprovar
                                </a>
                                <a href="/admin/cidadao/rejeitar?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Rejeitar este cadastro?')">
                                    <i class="bi bi-x-lg"></i> Rejeitar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="weather-card">
    <div class="card-header-custom">
        <i class="bi bi-check-circle"></i> Estações Aprovadas (<?= count($aprovados) ?>)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-weather mb-0">
                <thead>
                    <tr>
                        <th>Código da Estação</th>
                        <th>Bairro</th>
                        <th>Nome (interno)</th>
                        <th>E-mail</th>
                        <th>Aprovado em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($aprovados)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Nenhuma estação aprovada ainda.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($aprovados as $row): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($row['station_code']) ?></td>
                            <td><?= htmlspecialchars($row['bairro']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($row['nome']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($row['email']) ?></td>
                            <td class="small text-muted"><?= $row['approved_at'] ? date('d/m/Y', strtotime($row['approved_at'])) : '--' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
