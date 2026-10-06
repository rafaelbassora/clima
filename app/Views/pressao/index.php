<?php
/** @var array $pressao24h */
/** @var array $pressao7dias */
/** @var array $resumoMensal */
/** @var array $estatisticas */
/** @var string $local */
/** @var int $year */
/** @var int $month */

$localNome = $local == '1' ? 'Sede' : 'Zona Rural';
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-6">
        <h4 class="mb-0">
            <i class="bi bi-speedometer2 text-primary"></i> Pressão Atmosférica
            <small class="text-muted ms-2" style="font-size: 0.75rem; font-weight: 400;">(Dados atualizados a cada 1h)</small>
        </h4>
        <small class="text-muted">Monitoramento histórico - <?= $localNome ?></small>
    </div>
    <div class="col-md-6 text-md-end">
        <form method="GET" class="d-flex gap-2 justify-content-md-end align-items-center">
            <input type="hidden" name="local" value="<?= htmlspecialchars($local) ?>">
            <select name="month" class="form-select form-select-sm w-auto">
                <?php foreach($meses as $m => $nome): ?>
                    <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>>
                        <?= $nome ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="year" class="form-select form-select-sm w-auto">
                <?php for($y=date('Y'); $y>=2018; $y--): ?>
                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-primary px-3">Filtrar</button>
        </form>
    </div>
</div>

<div class="row g-3">
    <!-- Recordes -->
    <div class="col-12">
        <div class="weather-card fade-in-up">
            <div class="card-header-custom">
                <i class="bi bi-trophy-fill text-warning"></i> Recordes Históricos
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="text-accent-warm fw-bold mb-3"><i class="bi bi-arrow-up-circle-fill"></i> Top 5 - Maiores Pressões</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-weather mb-0">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Pressão</th>
                                        <th>Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estatisticas['top_max'] as $row): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($row['dia'])) ?></td>
                                        <td class="fw-bold text-accent-warm"><?= number_format($row['valor'], 1) ?> hPa</td>
                                        <td class="small text-muted"><?= date('H:i', strtotime($row['data_hora'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6 border-start-md">
                        <h6 class="text-primary fw-bold mb-3"><i class="bi bi-arrow-down-circle-fill"></i> Top 5 - Menores Pressões</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-weather mb-0">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Pressão</th>
                                        <th>Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estatisticas['top_min'] as $row): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($row['dia'])) ?></td>
                                        <td class="fw-bold text-primary"><?= number_format($row['valor'], 1) ?> hPa</td>
                                        <td class="small text-muted"><?= date('H:i', strtotime($row['data_hora'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tendência 24h -->
    <div class="col-lg-6">
        <div class="weather-card h-100 fade-in-up" style="transition-delay: 0.1s">
            <div class="card-header-custom">
                <i class="bi bi-graph-up"></i> Tendência Últimas 24h
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table-weather table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Pressão (hPa)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pressao24h as $row): ?>
                            <tr>
                                <td><?= $row['hora'] ?></td>
                                <td class="fw-bold text-dark"><?= $row['pressao'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Histórico Mensal -->
    <div class="col-lg-6">
        <div class="weather-card h-100 fade-in-up" style="transition-delay: 0.2s">
            <div class="card-header-custom">
                <i class="bi bi-calendar3"></i> Médias de <?= date('F/Y', mktime(0,0,0,$month,1,$year)) ?>
            </div>
            <div class="card-body">
                <?php if (empty($resumoMensal)): ?>
                    <p class="text-muted text-center py-5">Nenhum dado encontrado para este período.</p>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table-weather table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Mín</th>
                                    <th>Máx</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resumoMensal as $row): ?>
                                <tr class="<?= $row['max'] >= 1020 ? 'bg-danger bg-opacity-10' : ($row['min'] <= 1005 ? 'bg-primary bg-opacity-10' : '') ?>">
                                    <td class="small"><?= $row['data'] ?></td>
                                    <td class="text-primary fw-bold"><?= $row['min'] ?></td>
                                    <td class="text-accent-warm fw-bold"><?= $row['max'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 7 Dias Summary -->
    <div class="col-12">
        <div class="weather-card fade-in-up" style="transition-delay: 0.3s">
            <div class="card-header-custom">
                <i class="bi bi-display"></i> Monitoramento 7 Dias (Local: <?= $localNome ?>)
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <?php foreach ($pressao7dias as $row): ?>
                    <div class="col">
                        <div class="p-3 border rounded text-center bg-light">
                            <small class="text-muted d-block mb-1"><?= $row['data'] ?></small>
                            <div class="fs-6 fw-bold text-dark"><?= $row['pressao_max'] ?></div>
                            <small class="text-secondary"><?= $row['pressao_min'] ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
