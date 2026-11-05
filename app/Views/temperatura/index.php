<?php
/** @var array $temperaturaMensal */
/** @var array $estatisticas */
/** @var string $local */
/** @var int $year */
/** @var mixed $month */

?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-6">
        <h4 class="mb-0">
            <i class="bi bi-thermometer-half text-accent-warm"></i> Monitoramento de Temperatura
            <small class="text-muted ms-2" style="font-size: 0.75rem; font-weight: 400;">(Dados atualizados a cada 1h)</small>
        </h4>
        <a href="<?= url('/inicio-temperatura/comparar') ?>?local=<?= urlencode($local) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 mt-2">
            <i class="bi bi-graph-up me-1"></i> Comparar meses e anos
        </a>
    </div>
    <div class="col-md-6">
        <form action="<?= url('/inicio-temperatura') ?>" method="GET" class="row g-2 justify-content-md-end">
            <input type="hidden" name="local" value="<?= htmlspecialchars($local) ?>">
            <div class="col-auto">
                <select name="month" class="form-select form-select-sm bg-dark text-white border-secondary">
                    <?php if (isset($meses['all'])): ?>
                        <option value="all" <?= (string)$month === 'all' ? 'selected' : '' ?>><?= $meses['all'] ?></option>
                    <?php endif; ?>
                    <?php foreach ($meses as $m => $nome): ?>
                        <?php if ($m === 'all') continue; ?>
                        <option value="<?= $m ?>" <?= (string)$m === (string)$month ? 'selected' : '' ?>><?= $nome ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="year" class="form-select form-select-sm bg-dark text-white border-secondary">
                    <?php for ($y = date('Y'); $y >= 2018; $y--): ?>
                        <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-search me-1"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="weather-card fade-in-up">
    <div class="card-header-custom">
        <i class="bi bi-calendar3"></i>
        Extremos do Mês (Min/Max)
        <span class="badge-period"><?= $meses[$month] ?? 'Período' ?> / <?= $year ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-weather mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Data</th>
                        <th class="text-center">Mínima registrada</th>
                        <th class="text-center">Máxima registrada</th>
                        <th class="text-end pe-4">Local</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($temperaturaMensal as $row): ?>
                    <tr>
                        <td class="ps-4"><?= htmlspecialchars($row['data']) ?></td>
                        <td class="text-center">
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-primary fs-5"><?= $row['temp_min'] ?>°C</span>
                                <small class="text-muted"><i class="bi bi-clock"></i> <?= $row['hora_min'] ?></small>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-accent-warm fs-5"><?= $row['temp_max'] ?>°C</span>
                                <small class="text-muted"><i class="bi bi-clock"></i> <?= $row['hora_max'] ?></small>
                            </div>
                        </td>
                        <td class="text-end pe-4">Sede</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============ EXTREMOS DO ANO CORRENTE ============ -->
<div class="row g-4 mt-2">
    <!-- Top Calor Ano Corrente -->
    <div class="col-lg-6">
        <div class="weather-card h-100 fade-in-up">
            <div class="card-header-custom" style="background: linear-gradient(135deg, #e65100 0%, #f57c00 100%);">
                <i class="bi bi-sun-fill"></i>
                Maiores Temperaturas de <?= $year ?> (Top 5)
                <span class="badge-period">Ano <?= $year ?></span>
            </div>
            <div class="card-body">
                <?php if (!empty($estatisticasAno['calor'])): ?>
                    <?php foreach ($estatisticasAno['calor'] as $i => $item): ?>
                    <div class="d-flex align-items-center justify-content-between p-3 mb-2 rounded bg-light bg-opacity-10 border-start border-4 border-warning">
                        <div>
                            <span class="fw-bold text-warning">#<?= $i+1 ?></span>
                            <span class="ms-2 text-muted small"><i class="bi bi-clock"></i> <?= $item['data'] ?></span>
                        </div>
                        <div class="fs-4 fw-bold text-warning"><?= $item['valor'] ?>°C</div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-3">Nenhum registro para <?= $year ?>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top Frio Ano Corrente -->
    <div class="col-lg-6">
        <div class="weather-card h-100 fade-in-up">
            <div class="card-header-custom" style="background: linear-gradient(135deg, #0288d1 0%, #00acc1 100%);">
                <i class="bi bi-thermometer-snow"></i>
                Menores Temperaturas de <?= $year ?> (Top 5)
                <span class="badge-period">Ano <?= $year ?></span>
            </div>
            <div class="card-body">
                <?php if (!empty($estatisticasAno['frio'])): ?>
                    <?php foreach ($estatisticasAno['frio'] as $i => $item): ?>
                    <div class="d-flex align-items-center justify-content-between p-3 mb-2 rounded bg-light bg-opacity-10 border-start border-4 border-info">
                        <div>
                            <span class="fw-bold text-info">#<?= $i+1 ?></span>
                            <span class="ms-2 text-muted small"><i class="bi bi-clock"></i> <?= $item['data'] ?></span>
                        </div>
                        <div class="fs-4 fw-bold text-info"><?= $item['valor'] ?>°C</div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-3">Nenhum registro para <?= $year ?>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============ RECORDES HISTÓRICOS GERAIS ============ -->
<div class="row g-4 mt-2">
    <!-- Top Calor (Ondas de Calor) -->
    <div class="col-lg-6">
        <div class="weather-card h-100">
            <div class="card-header-custom" style="background: linear-gradient(135deg, #d32f2f 0%, #ff5722 100%);">
                <i class="bi bi-fire"></i>
                Maiores Temperaturas (Top 5 Histórico)
                <span class="badge-period">Geral</span>
            </div>
            <div class="card-body">
                <?php foreach ($estatisticas['calor'] as $i => $item): ?>
                <div class="d-flex align-items-center justify-content-between p-3 mb-2 rounded bg-light bg-opacity-10 border-start border-4 border-danger">
                    <div>
                        <span class="fw-bold text-danger">#<?= $i+1 ?></span>
                        <span class="ms-2 text-muted small"><?= $item['data'] ?></span>
                    </div>
                    <div class="fs-4 fw-bold text-danger"><?= $item['valor'] ?>°C</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Top Frio (Ondas de Frio) -->
    <div class="col-lg-6">
        <div class="weather-card h-100">
            <div class="card-header-custom" style="background: linear-gradient(135deg, #1976d2 0%, #0d47a1 100%);">
                <i class="bi bi-snow2"></i>
                Menores Temperaturas (Top 5 Histórico)
                <span class="badge-period">Geral</span>
            </div>
            <div class="card-body">
                <?php foreach ($estatisticas['frio'] as $i => $item): ?>
                <div class="d-flex align-items-center justify-content-between p-3 mb-2 rounded bg-light bg-opacity-10 border-start border-4 border-primary">
                    <div>
                        <span class="fw-bold text-primary">#<?= $i+1 ?></span>
                        <span class="ms-2 text-muted small"><?= $item['data'] ?></span>
                    </div>
                    <div class="fs-4 fw-bold text-primary"><?= $item['valor'] ?>°C</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
