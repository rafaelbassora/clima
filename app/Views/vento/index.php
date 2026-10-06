<?php
/** @var array $vento24h */
/** @var array $vento7dias */
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
            <i class="bi bi-wind text-primary"></i> Ventos e Velocidade
            <small class="text-muted ms-2" style="font-size: 0.75rem; font-weight: 400;">(Dados atualizados a cada 1h)</small>
        </h4>
        <small class="text-muted">Anemômetro histórico - <?= $localNome ?></small>
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
    <!-- Recordes - Velocidade Máxima -->
    <div class="col-12">
        <div class="weather-card fade-in-up border-accent-warm">
            <div class="card-header-custom bg-accent-warm text-white border-0">
                <i class="bi bi-lightning-fill"></i> Recordes de Velocidade (Rajadas Máximas)
            </div>
            <div class="card-body">
                <div class="row g-2 align-items-center">
                    <?php foreach ($estatisticas['top_max'] as $i => $row): ?>
                    <div class="col-md">
                        <div class="p-3 border rounded text-center <?= $i == 0 ? 'bg-accent-warm bg-opacity-10 border-accent-warm' : 'bg-light' ?>">
                            <div class="small fw-bold text-muted text-uppercase"><?= date('d/m/Y', strtotime($row['dia'])) ?></div>
                            <div class="display-6 fw-bold text-accent-warm my-1"><?= number_format($row['valor'], 1) ?> <small class="fs-6">km/h</small></div>
                            <div class="badge bg-dark rounded-pill"><i class="bi bi-compass me-1"></i> Dir: <?= $row['direcao_label'] ?></div>
                            <small class="d-block mt-1 text-muted">Às <?= date('H:i', strtotime($row['data_hora'])) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico/Tabela 24h -->
    <div class="col-lg-6">
        <div class="weather-card h-100 fade-in-up" style="transition-delay: 0.1s">
            <div class="card-header-custom">
                <i class="bi bi-clock-history"></i> Velocidade Hora a Hora (Últimas 24h)
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table-weather table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Velocidade</th>
                                <th>Direção</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vento24h as $row): ?>
                            <tr>
                                <td><?= $row['hora'] ?></td>
                                <td class="fw-bold"><?= $row['vento'] ?> km/h</td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= $row['direcao'] ?></span></td>
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
                <i class="bi bi-calendar3"></i> Médias Diárias de <?= date('m/Y', mktime(0,0,0,$month,1,$year)) ?>
            </div>
            <div class="card-body">
                <?php if (empty($resumoMensal)): ?>
                    <p class="text-muted text-center py-5">Nenhum dado encontrado para este período.</p>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                        <table class="table-weather table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Média</th>
                                    <th>Máxima</th>
                                    <th>Dir. Máx</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resumoMensal as $row): ?>
                                <tr class="<?= $row['max'] >= 40 ? 'bg-danger bg-opacity-10' : '' ?>">
                                    <td class="small"><?= $row['data'] ?></td>
                                    <td><?= $row['avg'] ?> <small>km/h</small></td>
                                    <td class="fw-bold text-accent-warm"><?= $row['max'] ?> <small>km/h</small></td>
                                    <td><span class="badge bg-light text-dark border"><?= $row['direcao'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
