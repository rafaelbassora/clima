<?php
/** @var string $local */
/** @var string $modo */
/** @var string $fonte */
/** @var int[] $anos */
/** @var array $itens */
/** @var int $anoInicial */
/** @var string $primeiroDia */
/** @var array $resultado */
/** @var array $meses */

$mesesCurtos = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];
$cores = ['#0d6efd', '#ff6b35', '#20c997', '#6f42c1', '#d63384', '#6c757d'];
$fmt = fn (?float $v, int $casas = 1) => $v === null ? '—' : number_format($v, $casas, ',', '.');
$fmtData = fn (?string $d) => $d ? date('d/m/Y', strtotime($d)) : '—';
$base = url('/inicio-chuva/comparar') . '?local=' . urlencode($local) . '&fonte=' . $fonte;
$nomeFonte = $fonte === 'auto' ? 'Coleta Automatizada' : 'Pluviômetro Manual';
$hoje = $resultado['hoje'];
$anosMedia = $resultado['anos_media'];
$textoMedia = $anosMedia ? ($anosMedia[0] === $anosMedia[1] ? (string) $anosMedia[0] : "{$anosMedia[0]}–{$anosMedia[1]}") : null;
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-7">
        <a href="<?= url('/inicio-chuva') ?>?local=<?= urlencode($local) ?>&year=<?= date('Y') ?>&month=<?= date('m') ?>" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left me-1"></i>Índice Pluviométrico
        </a>
        <h4 class="mb-0 mt-1">
            <i class="bi bi-bar-chart-line-fill text-primary"></i> Comparar Chuva
        </h4>
        <p class="text-muted small mb-0">
            Compare o volume de chuva entre anos (mês a mês) ou entre meses (dia a dia).
            Dados <?= $fonte === 'auto' ? 'da' : 'do' ?> <?= $nomeFonte ?> desde <?= $fmtData($primeiroDia) ?>.
        </p>
    </div>
    <div class="col-md-5 text-md-end">
        <div class="btn-group" role="group" aria-label="Tipo de comparação">
            <a href="<?= $base ?>&modo=anos" class="btn btn-sm <?= $modo === 'anos' ? 'btn-primary' : 'btn-outline-primary' ?> px-3">
                <i class="bi bi-calendar-range me-1"></i> Anos
            </a>
            <a href="<?= $base ?>&modo=meses" class="btn btn-sm <?= $modo === 'meses' ? 'btn-primary' : 'btn-outline-primary' ?> px-3">
                <i class="bi bi-calendar-month me-1"></i> Meses
            </a>
        </div>
    </div>
</div>

<form action="<?= url('/inicio-chuva/comparar') ?>" method="GET" class="weather-card p-3 mb-4">
    <input type="hidden" name="local" value="<?= htmlspecialchars($local) ?>">
    <input type="hidden" name="modo" value="<?= $modo ?>">
    <div class="row g-3 align-items-end">
        <div class="col-md-5 col-lg-4">
            <label class="form-label small fw-bold mb-1" for="fonte">Fonte dos dados</label>
            <select name="fonte" id="fonte" class="form-select">
                <option value="manual" <?= $fonte === 'manual' ? 'selected' : '' ?>>Pluviômetro Manual (histórico maior)</option>
                <option value="auto" <?= $fonte === 'auto' ? 'selected' : '' ?>>Coleta Automatizada</option>
            </select>
        </div>

        <?php if ($modo === 'anos'): ?>
            <div class="col-md-7 col-lg-6">
                <span class="form-label small fw-bold mb-1 d-block">Anos (até 6)</span>
                <div class="d-flex flex-wrap gap-2">
                    <?php for ($y = (int) date('Y'); $y >= $anoInicial; $y--): ?>
                        <input type="checkbox" class="btn-check" name="anos[]" value="<?= $y ?>" id="ano<?= $y ?>" autocomplete="off" <?= in_array($y, $anos, true) ? 'checked' : '' ?>>
                        <label class="btn btn-sm btn-outline-primary rounded-pill px-3" for="ano<?= $y ?>"><?= $y ?></label>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="col-lg-2">
                <button type="submit" class="btn btn-primary px-3 w-100">
                    <i class="bi bi-search"></i> Comparar
                </button>
            </div>
        <?php else: ?>
            <div class="col-md-3 col-lg-2 ms-md-auto">
                <button type="submit" class="btn btn-primary px-3 w-100">
                    <i class="bi bi-search"></i> Comparar
                </button>
            </div>
        </div>

        <div class="row g-3 mt-0">
            <?php for ($i = 0; $i < 3; $i++): ?>
                <?php [$ia, $im] = $itens[$i] ?? [null, null]; ?>
                <div class="col-md-4">
                    <label class="form-label small fw-bold mb-1" for="mes<?= $i ?>">
                        <span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background: <?= $cores[$i] ?>;"></span>
                        <?= $i + 1 ?>º mês<?= $i === 2 ? ' <span class="text-muted fw-normal">(opcional)</span>' : '' ?>
                    </label>
                    <div class="input-group">
                        <select name="m[]" id="mes<?= $i ?>" class="form-select" aria-label="Mês do <?= $i + 1 ?>º período">
                            <option value="">— nenhum —</option>
                            <?php foreach ($meses as $m => $nome): ?>
                                <option value="<?= $m ?>" <?= $im === $m ? 'selected' : '' ?>><?= $nome ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="a[]" class="form-select" style="max-width: 6.5rem;" aria-label="Ano do <?= $i + 1 ?>º período">
                            <?php for ($y = (int) date('Y'); $y >= $anoInicial; $y--): ?>
                                <option value="<?= $y ?>" <?= ($ia ?? (int) date('Y')) === $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            <?php endfor; ?>
        <?php endif; ?>
    </div>
</form>

<?php if ($modo === 'anos'): ?>
    <?php $series = $resultado['series']; $media = $resultado['media_mensal']; ?>

    <div class="row g-3 mb-4">
        <?php $i = 0; foreach ($series as $ano => $s): ?>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="weather-card h-100 p-3" style="border-top: 4px solid <?= $cores[$i++ % count($cores)] ?>;">
                    <div class="small text-muted fw-bold">
                        <?= $ano ?>
                        <?php if ($s['parcial']): ?><span class="badge bg-secondary ms-1">parcial</span><?php endif; ?>
                    </div>
                    <div class="fs-4 fw-bold"><?= $fmt($s['total']) ?> <small class="fs-6 text-muted">mm</small></div>
                    <div class="small text-muted">Até <?= date('d/m', strtotime($hoje)) ?>: <strong><?= $fmt($s['ate_hoje']) ?> mm</strong></div>
                    <div class="small text-muted"><?= $s['dias_chuva'] ?> <?= $s['dias_chuva'] === 1 ? 'dia' : 'dias' ?> com chuva</div>
                    <?php if ($s['maior']['dia']): ?>
                        <div class="small text-muted">Maior: <?= $fmt($s['maior']['mm']) ?> mm (<?= date('d/m', strtotime($s['maior']['dia'])) ?>)</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="weather-card mb-4">
        <div class="card-header-custom">
            <i class="bi bi-bar-chart-fill"></i> Chuva por mês — <?= $nomeFonte ?>
        </div>
        <div class="card-body">
            <div style="position: relative; height: 340px;">
                <canvas id="graficoComparacao" aria-label="Gráfico de chuva por mês para cada ano" role="img"></canvas>
            </div>
            <?php if ($textoMedia): ?>
                <p class="small text-muted mb-0 mt-2">Linha tracejada: média de cada mês em <?= $textoMedia ?> (só meses completos).</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="weather-card">
        <div class="card-header-custom">
            <i class="bi bi-table"></i> Tabela mês a mês (mm)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table-weather">
                    <thead>
                        <tr>
                            <th>Mês</th>
                            <?php foreach ($series as $ano => $s): ?><th class="text-end"><?= $ano ?></th><?php endforeach; ?>
                            <?php if ($textoMedia): ?><th class="text-end">Média <?= $textoMedia ?></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <tr>
                                <td class="fw-bold"><?= $meses[$m] ?></td>
                                <?php foreach ($series as $s): ?>
                                    <td class="text-end"><?= $fmt($s['mensal'][$m]) ?></td>
                                <?php endforeach; ?>
                                <?php if ($textoMedia): ?><td class="text-end text-muted"><?= $fmt($media[$m]) ?></td><?php endif; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Total</td>
                            <?php foreach ($series as $s): ?><td class="text-end"><?= $fmt($s['total']) ?></td><?php endforeach; ?>
                            <?php if ($textoMedia): ?><td class="text-end text-muted"><?= $fmt($resultado['media_total']) ?></td><?php endif; ?>
                        </tr>
                        <tr>
                            <td>Até <?= date('d/m', strtotime($hoje)) ?></td>
                            <?php foreach ($series as $s): ?><td class="text-end"><?= $fmt($s['ate_hoje']) ?></td><?php endforeach; ?>
                            <?php if ($textoMedia): ?><td></td><?php endif; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <?php
    $grafico = [
        'tipo' => 'bar',
        'labels' => array_values($mesesCurtos),
        'datasets' => [],
    ];
    $i = 0;
    foreach ($series as $ano => $s) {
        $cor = $cores[$i++ % count($cores)];
        $grafico['datasets'][] = [
            'type' => 'bar', 'label' => (string) $ano, 'backgroundColor' => $cor, 'borderRadius' => 4,
            'data' => array_map(fn ($v) => $v === null ? null : round($v, 1), array_values($s['mensal'])),
        ];
    }
    if ($textoMedia) {
        $grafico['datasets'][] = [
            'type' => 'line', 'label' => "Média {$textoMedia}", 'borderColor' => '#1a1a2e', 'backgroundColor' => '#1a1a2e',
            'borderDash' => [6, 4], 'borderWidth' => 2, 'pointRadius' => 3, 'tension' => 0.3,
            'data' => array_map(fn ($v) => $v === null ? null : round($v, 1), array_values($media)),
        ];
    }
    ?>

<?php else: ?>
    <?php $series = $resultado['series']; $maxDias = $series ? max(array_map(fn ($s) => count($s['diario']), $series)) : 0; ?>

    <div class="row g-3 mb-4">
        <?php $i = 0; foreach ($series as $s): ?>
            <?php
            $diff = ($s['media_historica'] ?? 0) > 0 && !$s['parcial']
                ? ($s['total'] - $s['media_historica']) / $s['media_historica'] * 100
                : null;
            ?>
            <div class="col-md-4">
                <div class="weather-card h-100 p-3" style="border-top: 4px solid <?= $cores[$i++ % count($cores)] ?>;">
                    <div class="small text-muted fw-bold">
                        <?= $meses[$s['mes']] ?> de <?= $s['ano'] ?>
                        <?php if ($s['parcial']): ?><span class="badge bg-secondary ms-1">parcial</span><?php endif; ?>
                    </div>
                    <div class="fs-4 fw-bold"><?= $fmt($s['total']) ?> <small class="fs-6 text-muted">mm</small></div>
                    <?php if ($s['media_historica'] !== null): ?>
                        <div class="small text-muted">
                            Média do mês: <?= $fmt($s['media_historica']) ?> mm
                            <?php if ($diff !== null): ?>
                                <span class="fw-bold <?= $diff >= 0 ? 'text-primary' : 'text-danger' ?>">(<?= $diff >= 0 ? '+' : '' ?><?= $fmt($diff, 0) ?>%)</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="small text-muted"><?= $s['dias_chuva'] ?> <?= $s['dias_chuva'] === 1 ? 'dia' : 'dias' ?> com chuva</div>
                    <?php if ($s['maior']['dia']): ?>
                        <div class="small text-muted">Maior: <?= $fmt($s['maior']['mm']) ?> mm (dia <?= (int) substr($s['maior']['dia'], 8, 2) ?>)</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="weather-card mb-4">
        <div class="card-header-custom">
            <i class="bi bi-graph-up"></i> Chuva acumulada no mês, dia a dia — <?= $nomeFonte ?>
        </div>
        <div class="card-body">
            <div style="position: relative; height: 340px;">
                <canvas id="graficoComparacao" aria-label="Gráfico de chuva acumulada por dia para cada mês" role="img"></canvas>
            </div>
            <?php if ($textoMedia): ?>
                <p class="small text-muted mb-0 mt-2">Média do mês calculada com os meses completos de <?= $textoMedia ?>.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="weather-card">
        <div class="card-header-custom">
            <i class="bi bi-table"></i> Chuva por dia (mm)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                <table class="table-weather">
                    <thead>
                        <tr>
                            <th>Dia</th>
                            <?php foreach ($series as $s): ?><th class="text-end"><?= $mesesCurtos[$s['mes']] ?>/<?= $s['ano'] ?></th><?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($d = 1; $d <= $maxDias; $d++): ?>
                            <tr>
                                <td class="fw-bold"><?= $d ?></td>
                                <?php foreach ($series as $s): ?>
                                    <?php $v = array_key_exists($d, $s['diario']) ? $s['diario'][$d] : null; ?>
                                    <td class="text-end <?= ($v ?? 0) > 0 ? 'fw-bold' : 'text-muted' ?>"><?= $fmt($v) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Total</td>
                            <?php foreach ($series as $s): ?><td class="text-end"><?= $fmt($s['total']) ?></td><?php endforeach; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <?php
    $grafico = [
        'tipo' => 'line',
        'labels' => range(1, max(1, $maxDias)),
        'datasets' => [],
    ];
    $i = 0;
    foreach ($series as $s) {
        $cor = $cores[$i++ % count($cores)];
        $grafico['datasets'][] = [
            'label' => $mesesCurtos[$s['mes']] . '/' . $s['ano'], 'borderColor' => $cor, 'backgroundColor' => $cor,
            'borderWidth' => 2.5, 'pointRadius' => 0, 'pointHoverRadius' => 4, 'tension' => 0.15, 'stepped' => false,
            'data' => array_values($s['acumulado']),
        ];
    }
    ?>
<?php endif; ?>

<div class="text-end mt-3 mb-4">
    <small class="text-muted"><i class="bi bi-info-circle"></i> Dados informativos, sujeitos a falhas dos equipamentos. Meses e anos em andamento aparecem como parciais.</small>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cfg = <?= json_encode($grafico, JSON_UNESCAPED_UNICODE) ?>;
    const el = document.getElementById('graficoComparacao');
    if (!el || typeof Chart === 'undefined') return;

    const fmt = v => v === null || v === undefined ? '—' : v.toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' mm';

    new Chart(el, {
        type: cfg.tipo,
        data: { labels: cfg.labels, datasets: cfg.datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                tooltip: {
                    callbacks: {
                        title: items => cfg.tipo === 'line' ? 'Dia ' + items[0].label : items[0].label,
                        label: ctx => ' ' + ctx.dataset.label + ': ' + fmt(ctx.parsed.y)
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, title: { display: cfg.tipo === 'line', text: 'Dia do mês' } },
                y: { beginAtZero: true, title: { display: true, text: 'mm' }, ticks: { callback: v => v.toLocaleString('pt-BR') } }
            }
        }
    });
});
</script>
