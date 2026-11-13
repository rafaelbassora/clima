<?php
/** @var array $umidade24h */
/** @var array $resumoMensal */
/** @var array $meses */
/** @var string $local */
/** @var int $year */
/** @var int $month */

$localNome = $local == '1' ? 'Sede' : 'Zona Rural';
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-6">
        <h4 class="mb-0">
            <i class="bi bi-droplet-fill" style="color: var(--primary);"></i> Umidade
            <small class="text-muted ms-2" style="font-size: 0.75rem; font-weight: 400;">(Dados atualizados a cada 1h)</small>
        </h4>
        <small class="text-muted">Monitoramento histórico - <?= $localNome ?></small>
    </div>
    <div class="col-md-6 text-md-end">
        <form method="GET" class="d-flex gap-2 justify-content-md-end align-items-center">
            <input type="hidden" name="local" value="<?= htmlspecialchars($local) ?>">
            <select name="month" class="form-select form-select-sm w-auto">
                <?php foreach($meses as $m => $nome): ?>
                    <option value="<?= $m ?>" <?= (string)$m === (string)$month ? 'selected' : '' ?>>
                        <?= $nome ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="year" class="form-select form-select-sm w-auto">
                <?php for($y=date('Y'); $y>=2018; $y--): ?>
                    <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-primary px-3">Filtrar</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../partials/medias-medida.php'; ?>

<div class="row g-3">
    <?php $tipoDia = 'umidade'; require __DIR__ . '/../partials/medida-do-dia.php'; ?>

    <!-- Card 1: Últimas 24h -->
    <div class="col-md-6">
        <div class="weather-card fade-in-up">
            <div class="card-header-custom">
                <i class="bi bi-clock-history"></i> Últimas 24 Horas
            </div>
            <div class="card-body">
                <?php if (!empty($umidade24h)): ?>
                <div class="table-responsive" style="max-height: 450px;">
                    <table class="table-weather">
                        <thead>
                            <tr><th>Hora</th><th>%</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($umidade24h as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['hora']) ?></td>
                                <td><strong><?= htmlspecialchars($row['umidade']) ?>%</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-5">Nenhum dado nas últimas 24h.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Card 2: Resumo Mensal -->
    <div class="col-md-6">
        <div class="weather-card fade-in-up">
            <div class="card-header-custom border-primary">
                <i class="bi bi-calendar-check"></i> Histórico Mensal (Extremos)
            </div>
            <div class="card-body">
                <?php if (!empty($resumoMensal)): ?>
                <div class="table-responsive" style="max-height: 450px;">
                    <table class="table-weather">
                        <thead>
                            <tr>
                                <th>Dia</th>
                                <th class="text-primary">Mín</th>
                                <th class="text-accent-warm">Máx</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resumoMensal as $row): ?>
                            <tr>
                                <td><?php $iso = \DateTime::createFromFormat('!d/m/Y', $row['data'])->format('Y-m-d'); ?><a href="?<?= htmlspecialchars(http_build_query(['local' => $local, 'month' => $month, 'year' => $year, 'dia' => $iso])) ?>#medida-dia" title="Ver hora a hora deste dia"><?= htmlspecialchars($row['data']) ?></a></td>
                                <td class="text-primary"><?= htmlspecialchars($row['min']) ?>%</td>
                                <td class="text-accent-warm fw-bold"><?= htmlspecialchars($row['max']) ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-5">Nenhum registro para este período.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
