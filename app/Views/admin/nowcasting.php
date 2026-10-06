<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <h2 class="h4 fw-bold mb-4 text-primary">
            <i class="bi bi-graph-up-arrow me-2"></i>Rating de Acerto do Nowcasting
        </h2>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-3 text-center border">
                    <div class="text-muted small text-uppercase mb-1 fw-semibold">Total de Previsões</div>
                    <div class="h2 fw-bold mb-0 text-dark"><?= $stats['geral']['total'] ?? 0 ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-success bg-opacity-10 rounded-3 text-center border border-success border-opacity-25">
                    <div class="text-success small text-uppercase mb-1 fw-semibold">Acertos</div>
                    <div class="h2 fw-bold mb-0 text-success"><?= $stats['geral']['acertos'] ?? 0 ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-danger bg-opacity-10 rounded-3 text-center border border-danger border-opacity-25">
                    <div class="text-danger small text-uppercase mb-1 fw-semibold">Erros / Inconsistentes</div>
                    <div class="h2 fw-bold mb-0 text-danger"><?= $stats['geral']['erros'] ?? 0 ?></div>
                </div>
            </div>
        </div>

        <?php 
        $total = $stats['geral']['total'] ?? 0;
        $acertos = $stats['geral']['acertos'] ?? 0;
        $rating = $total > 0 ? round(($acertos / $total) * 100, 1) : 0;
        ?>

        <div class="mb-5">
            <div class="d-flex justify-content-between mb-2">
                <span class="fw-bold">Taxa de Assertividade Geral</span>
                <span class="fw-bold text-primary"><?= $rating ?>%</span>
            </div>
            <div class="progress" style="height: 12px; border-radius: 6px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                     style="width: <?= $rating ?>%" aria-valuenow="<?= $rating ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>

        <h3 class="h5 fw-bold mb-3">Histórico de Validações (Últimas 50)</h3>
        <div class="table-responsive">
            <table class="table table-hover align-middle border-top">
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Mensagem</th>
                        <th>Chuva Máx.</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historico)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Aguardando validações de cron...</td></tr>
                    <?php else: ?>
                        <?php foreach ($historico as $h): ?>
                        <tr>
                            <td class="small"><?= date('d/m H:i', strtotime($h['created_at'])) ?></td>
                            <td>
                                <span class="badge rounded-pill text-uppercase bg-secondary bg-opacity-10 text-secondary border">
                                    <?= $h['tipo_previsao'] ?>
                                </span>
                            </td>
                            <td class="small"><?= htmlspecialchars($h['mensagem']) ?></td>
                            <td><?= number_format($h['chuva_registrada'], 1) ?> mm/h</td>
                            <td>
                                <?php if ($h['acertou'] === null): ?>
                                    <span class="badge bg-warning text-dark">Pendente</span>
                                <?php elseif ($h['acertou'] == 1): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Acertou</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Errou</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
