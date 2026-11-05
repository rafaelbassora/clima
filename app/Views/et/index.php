<?php
/** @var array $etResumo */
/** @var string $local */
/** @var int $year */
/** @var int $month */
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-6">
        <h4 class="mb-0">
            <i class="bi bi-droplet text-success"></i> Evapotranspiração (ET)
            <small class="text-muted ms-2" style="font-size: 0.75rem; font-weight: 400;">(Dados atualizados a cada 1h)</small>
        </h4>
    </div>
    <div class="col-md-6">
        <form action="<?= url('/inicio-et') ?>" method="GET" class="row g-2 justify-content-md-end">
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
                <button type="submit" class="btn btn-sm btn-primary px-3">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../partials/medias-medida.php'; ?>

<div class="weather-card fade-in-up">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-white-50"><?= $month === 'all' ? 'Resumo Anual' : 'Resumo Mensal' ?></h5>
                <small class="text-muted"><?= $month === 'all' ? $year : $meses[$month] . ' / ' . $year ?></small>
            </div>
            <div class="text-end">
                <?php 
                    $totalMes = 0;
                    if (!empty($etResumo)) {
                        foreach ($etResumo as $row) {
                            $totalMes += (float)str_replace(',', '.', $row['total']);
                        }
                    }
                ?>
                <span class="fs-2 fw-bold text-success"><?= number_format($totalMes, 2, ',', '.') ?></span>
                <span class="text-muted ms-1">mm total</span>
            </div>
        </div>

        <?php if (!empty($etResumo)): ?>
        <div class="table-responsive">
            <table class="table-weather">
                <thead>
                    <tr>
                        <th><?= $month === 'all' ? 'Mês' : 'Data' ?></th>
                        <th>Total Acumulado</th>
                        <th>Local</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($etResumo as $row): ?>
                    <tr>
                        <td>
                            <?php if ($month === 'all'): ?>
                                <strong><?= htmlspecialchars($row['mes_nome']) ?></strong>
                            <?php else: ?>
                                <?= htmlspecialchars($row['data']) ?>
                            <?php endif; ?>
                        </td>
                        <td><strong class="text-success"><?= htmlspecialchars($row['total']) ?> mm</strong></td>
                        <td>Sede</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-droplet-half fs-1 text-muted opacity-25"></i>
                <p class="mt-3 text-muted">Nenhum dado de evapotranspiração encontrado para este período.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
