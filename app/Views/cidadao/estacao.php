<?php
/** @var array $estacao */
/** @var array $historico */
/** @var float $totalMes */
/** @var array $totaisMensais */
/** @var string $mesAno */
/** @var int $year */
/** @var int $month */
/** @var int $anoInicial */
/** @var array $meses */
$urlEstacao = url('/pluviometro-cidadao/estacao') . '?codigo=' . urlencode($estacao['station_code']);
?>

<div class="row align-items-end mb-4 g-3">
    <div class="col-md-6">
        <a href="<?= url('/pluviometro-cidadao') ?>?mes=<?= $month ?>&ano=<?= $year ?>" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
        <h2 class="h4 fw-bold mt-2">
            <i class="bi bi-geo-alt-fill text-primary"></i> Estação <?= htmlspecialchars($estacao['station_code']) ?>
        </h2>
        <p class="text-muted small mb-0">
            Bairro: <?= htmlspecialchars($estacao['bairro']) ?> — <?= htmlspecialchars($estacao['cidade']) ?>/<?= htmlspecialchars($estacao['uf']) ?>
        </p>
    </div>
    <div class="col-md-6">
        <form action="<?= url('/pluviometro-cidadao/estacao') ?>" method="GET" class="row g-2 justify-content-md-end">
            <input type="hidden" name="codigo" value="<?= htmlspecialchars($estacao['station_code']) ?>">
            <div class="col-auto">
                <select name="mes" class="form-select form-select-sm bg-dark text-white border-secondary" aria-label="Mês">
                    <?php foreach ($meses as $m => $nome): ?>
                        <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= $nome ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="ano" class="form-select form-select-sm bg-dark text-white border-secondary" aria-label="Ano">
                    <?php for ($y = (int) date('Y'); $y >= $anoInicial; $y--): ?>
                        <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
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

<div class="alert bg-primary bg-opacity-10 border border-primary border-opacity-20 d-flex align-items-center gap-3 p-4 mb-4">
    <div class="bg-primary bg-opacity-25 p-3 rounded-circle text-primary">
        <i class="bi bi-droplet-half fs-3"></i>
    </div>
    <div>
        <h5 class="mb-0 fw-bold">Acumulado em <?= htmlspecialchars($mesAno) ?>: <span class="text-primary"><?= number_format($totalMes, 2, ',', '.') ?> mm</span></h5>
        <p class="mb-0 text-muted small">Estação cidadã, dados informados voluntariamente.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="weather-card h-100">
            <div class="card-header-custom">
                <i class="bi bi-clock-history"></i> Lançamentos — <?= htmlspecialchars($mesAno) ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table-weather">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>mm</th>
                                <th>Eventos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historico)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">Nenhum lançamento em <?= htmlspecialchars($mesAno) ?>.</td>
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
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="weather-card h-100">
            <div class="card-header-custom">
                <i class="bi bi-calendar3"></i> Resumo por mês
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table-weather">
                        <thead>
                            <tr>
                                <th>Mês</th>
                                <th>Acumulado</th>
                                <th>Dias com chuva</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($totaisMensais)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">Sem lançamentos ainda.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($totaisMensais as $row): ?>
                                <?php $selecionado = (int) $row['ano'] === $year && (int) $row['mes'] === $month; ?>
                                <tr<?= $selecionado ? ' class="fw-bold"' : '' ?>>
                                    <td>
                                        <a href="<?= $urlEstacao ?>&mes=<?= (int) $row['mes'] ?>&ano=<?= (int) $row['ano'] ?>" class="text-decoration-none">
                                            <?= $meses[(int) $row['mes']] ?>/<?= (int) $row['ano'] ?>
                                        </a>
                                    </td>
                                    <td><?= number_format((float) $row['total_mm'], 2, ',', '.') ?> mm</td>
                                    <td><?= (int) $row['dias_chuva'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
