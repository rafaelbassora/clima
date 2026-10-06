<?php
/** @var array $estacao */
/** @var array $historico */
$totalMes = 0;
$mesAtual = date('Y-m');
foreach ($historico as $row) {
    if (strpos($row['data'], $mesAtual) === 0) {
        $totalMes += (float) $row['medida_mm'];
    }
}
?>

<div class="mb-4">
    <a href="<?= url('/pluviometro-cidadao') ?>" class="text-decoration-none small text-muted">
        <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
    <h2 class="h4 fw-bold mt-2">
        <i class="bi bi-geo-alt-fill text-primary"></i> Estação <?= htmlspecialchars($estacao['station_code']) ?>
    </h2>
    <p class="text-muted small mb-0">
        Bairro: <?= htmlspecialchars($estacao['bairro']) ?> — <?= htmlspecialchars($estacao['cidade']) ?>/<?= htmlspecialchars($estacao['uf']) ?>
    </p>
</div>

<div class="alert bg-primary bg-opacity-10 border border-primary border-opacity-20 d-flex align-items-center gap-3 p-4 mb-4">
    <div class="bg-primary bg-opacity-25 p-3 rounded-circle text-primary">
        <i class="bi bi-droplet-half fs-3"></i>
    </div>
    <div>
        <h5 class="mb-0 fw-bold">Acumulado do mês: <span class="text-primary"><?= number_format($totalMes, 2, ',', '.') ?> mm</span></h5>
        <p class="mb-0 text-muted small">Estação cidadã, dados informados voluntariamente.</p>
    </div>
</div>

<div class="weather-card">
    <div class="card-header-custom">
        <i class="bi bi-clock-history"></i> Histórico
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-weather">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>mm</th>
                        <th>Eventos</th>
                        <th>Observação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historico)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Nenhum lançamento registrado ainda.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($historico as $row): ?>
                        <tr>
                            <td class="fw-bold"><?= date('d/m/Y', strtotime($row['data'])) ?></td>
                            <td><?= number_format((float) $row['medida_mm'], 2, ',', '.') ?> mm</td>
                            <td>
                                <?php if (!empty($row['granizo'])): ?><span class="badge bg-info me-1">Granizo</span><?php endif; ?>
                                <?php if (!empty($row['chuva_mansa'])): ?><span class="badge bg-secondary me-1">Mansa</span><?php endif; ?>
                                <?php if (!empty($row['chuva_forte'])): ?><span class="badge bg-danger">Forte</span><?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($row['observacao'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
