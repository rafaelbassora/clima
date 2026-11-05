<?php
/** @var string $local */
/** @var string $modo */
/** @var int[] $anos */
/** @var array $itens */
/** @var int $anoInicial */
/** @var string $primeiroDia */
/** @var array $resultado */
/** @var array $meses */
/** @var float $limiteCalor */

$mesesCurtos = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];
$cores = ['#ff6b35', '#0d6efd', '#20c997'];
$fmt = fn (?float $v) => $v === null ? '—' : number_format($v, 1, ',', '.');
$fmtData = fn (?string $d) => $d ? date('d/m/Y', strtotime($d)) : '—';
$base = url('/inicio-temperatura/comparar') . '?local=' . urlencode($local);
$anosMedia = $resultado['anos_media'];
$textoMedia = $anosMedia ? ($anosMedia[0] === $anosMedia[1] ? (string) $anosMedia[0] : "{$anosMedia[0]}–{$anosMedia[1]}") : null;
$limiteTxt = $fmt($limiteCalor);
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-7">
        <a href="<?= url('/inicio-temperatura') ?>?local=<?= urlencode($local) ?>&year=<?= date('Y') ?>&month=<?= date('m') ?>" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left me-1"></i>Monitoramento de Temperatura
        </a>
        <h4 class="mb-0 mt-1">
            <i class="bi bi-thermometer-half text-accent-warm"></i> Comparar Temperatura
        </h4>
        <p class="text-muted small mb-0">
            Compare máximas e mínimas entre anos (mês a mês) ou entre meses (dia a dia).
            Dados da estação automática desde <?= $fmtData($primeiroDia) ?>.
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

<form action="<?= url('/inicio-temperatura/comparar') ?>" method="GET" class="weather-card p-3 mb-4">
    <input type="hidden" name="local" value="<?= htmlspecialchars($local) ?>">
    <input type="hidden" name="modo" value="<?= $modo ?>">
    <?php if ($modo === 'anos'): ?>
        <div class="row g-3 align-items-end">
            <div class="col-md-9">
                <span class="form-label small fw-bold mb-1 d-block">Anos (até 3)</span>
                <div class="d-flex flex-wrap gap-2">
                    <?php for ($y = (int) date('Y'); $y >= $anoInicial; $y--): ?>
                        <input type="checkbox" class="btn-check" name="anos[]" value="<?= $y ?>" id="ano<?= $y ?>" autocomplete="off" <?= in_array($y, $anos, true) ? 'checked' : '' ?>>
                        <label class="btn btn-sm btn-outline-primary rounded-pill px-3" for="ano<?= $y ?>"><?= $y ?></label>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="col-md-3 col-lg-2 ms-md-auto">
                <button type="submit" class="btn btn-primary px-3 w-100"><i class="bi bi-search"></i> Comparar</button>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3 align-items-end">
            <?php for ($i = 0; $i < 3; $i++): ?>
                <?php [$ia, $im] = $itens[$i] ?? [null, null]; ?>
                <div class="col-md-4 col-lg-3">
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
            <div class="col-md-12 col-lg-3">
                <button type="submit" class="btn btn-primary px-3 w-100"><i class="bi bi-search"></i> Comparar</button>
            </div>
        </div>
    <?php endif; ?>
</form>

<?php
// Card de resumo (usado nos dois modos)
$card = function (string $titulo, ?array $r, string $cor, bool $parcial, ?array $media = null) use ($fmt, $limiteTxt) {
    ?>
    <div class="weather-card h-100 p-3" style="border-top: 4px solid <?= $cor ?>;">
        <div class="small text-muted fw-bold">
            <?= $titulo ?>
            <?php if ($parcial): ?><span class="badge bg-secondary ms-1">parcial</span><?php endif; ?>
        </div>
        <?php if (!$r): ?>
            <div class="text-muted small mt-2">Sem leituras neste período.</div>
        <?php else: ?>
            <div class="d-flex gap-3 align-items-baseline mt-1">
                <div><span class="fs-4 fw-bold text-accent-warm"><?= $fmt($r['media_max']) ?>°</span> <small class="text-muted">máx. média</small></div>
                <div><span class="fs-4 fw-bold text-primary"><?= $fmt($r['media_min']) ?>°</span> <small class="text-muted">mín. média</small></div>
            </div>
            <?php if ($media): ?>
                <?php $dif = $r['media_max'] - $media['max']; ?>
                <div class="small text-muted">
                    Máx. média histórica: <?= $fmt($media['max']) ?>°
                    <?php if (!$parcial): ?>
                        <span class="fw-bold <?= $dif >= 0 ? 'text-danger' : 'text-primary' ?>">(<?= $dif >= 0 ? '+' : '' ?><?= $fmt($dif) ?>°)</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="small text-muted">Maior: <strong><?= $fmt($r['maior']['valor']) ?>°C</strong> em <?= date('d/m', strtotime($r['maior']['dia'])) ?> às <?= $r['maior']['hora'] ?></div>
            <div class="small text-muted">Menor: <strong><?= $fmt($r['menor']['valor']) ?>°C</strong> em <?= date('d/m', strtotime($r['menor']['dia'])) ?> às <?= $r['menor']['hora'] ?></div>
            <div class="small text-muted"><?= $r['dias_calor'] ?> <?= $r['dias_calor'] === 1 ? 'dia' : 'dias' ?> com máxima ≥ <?= $limiteTxt ?>°C</div>
        <?php endif; ?>
    </div>
    <?php
};
?>

<?php if ($modo === 'anos'): ?>
    <?php $series = $resultado['series']; $media = $resultado['media_mensal']; ?>

    <div class="row g-3 mb-4">
        <?php $i = 0; foreach ($series as $ano => $s): ?>
            <div class="col-md-4"><?php $card((string) $ano, $s['ano'], $cores[$i++ % count($cores)], $s['parcial']); ?></div>
        <?php endforeach; ?>
    </div>

    <div class="weather-card mb-4">
        <div class="card-header-custom">
            <i class="bi bi-graph-up"></i> Máximas e mínimas médias por mês
        </div>
        <div class="card-body">
            <div style="position: relative; height: 360px;">
                <canvas id="graficoComparacao" aria-label="Gráfico das temperaturas máximas e mínimas médias por mês para cada ano" role="img"></canvas>
            </div>
            <p class="small text-muted mb-0 mt-2">Linha cheia: média das máximas do mês. Linha tracejada: média das mínimas.</p>
        </div>
    </div>

    <div class="weather-card">
        <div class="card-header-custom">
            <i class="bi bi-table"></i> Tabela mês a mês — máx. / mín. médias (°C)
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
                                    <?php $r = $s['mensal'][$m]; ?>
                                    <td class="text-end">
                                        <?php if ($r): ?>
                                            <span class="text-accent-warm fw-bold"><?= $fmt($r['media_max']) ?></span> / <span class="text-primary fw-bold"><?= $fmt($r['media_min']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php if ($textoMedia): ?>
                                    <td class="text-end text-muted"><?= $media[$m] ? $fmt($media[$m]['max']) . ' / ' . $fmt($media[$m]['min']) : '—' ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php
    $grafico = ['labels' => array_values($mesesCurtos), 'xTitulo' => '', 'datasets' => []];
    $i = 0;
    foreach ($series as $ano => $s) {
        $cor = $cores[$i++ % count($cores)];
        $grafico['datasets'][] = ['label' => "{$ano} máx.", 'borderColor' => $cor, 'backgroundColor' => $cor, 'borderWidth' => 2.5, 'pointRadius' => 3, 'tension' => 0.3, 'spanGaps' => false,
            'data' => array_map(fn ($r) => $r ? round($r['media_max'], 1) : null, array_values($s['mensal']))];
        $grafico['datasets'][] = ['label' => "{$ano} mín.", 'borderColor' => $cor, 'backgroundColor' => $cor, 'borderWidth' => 2, 'borderDash' => [6, 4], 'pointRadius' => 2, 'tension' => 0.3, 'spanGaps' => false,
            'data' => array_map(fn ($r) => $r ? round($r['media_min'], 1) : null, array_values($s['mensal']))];
    }
    ?>

<?php else: ?>
    <?php $series = $resultado['series']; $maxDias = $series ? max(array_map(fn ($s) => count($s['diario']), $series)) : 0; ?>

    <div class="row g-3 mb-4">
        <?php $i = 0; foreach ($series as $s): ?>
            <div class="col-md-4"><?php $card($meses[$s['mes']] . ' de ' . $s['ano'], $s['resumo'], $cores[$i++ % count($cores)], $s['parcial'], $s['media_historica']); ?></div>
        <?php endforeach; ?>
    </div>

    <div class="weather-card mb-4">
        <div class="card-header-custom">
            <i class="bi bi-graph-up"></i> Máxima e mínima de cada dia
        </div>
        <div class="card-body">
            <div style="position: relative; height: 360px;">
                <canvas id="graficoComparacao" aria-label="Gráfico da temperatura máxima e mínima de cada dia para cada mês" role="img"></canvas>
            </div>
            <p class="small text-muted mb-0 mt-2">
                Linha cheia: máxima do dia. Linha tracejada: mínima do dia.
                <?php if ($textoMedia): ?>Médias históricas calculadas com os meses completos de <?= $textoMedia ?>.<?php endif; ?>
            </p>
        </div>
    </div>

    <div class="weather-card">
        <div class="card-header-custom">
            <i class="bi bi-table"></i> Máx. / mín. por dia (°C)
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
                                    <?php $v = $s['diario'][$d] ?? null; ?>
                                    <td class="text-end">
                                        <?php if ($v): ?>
                                            <span class="text-accent-warm fw-bold"><?= $fmt($v['tmax']) ?></span> / <span class="text-primary fw-bold"><?= $fmt($v['tmin']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php
    $grafico = ['labels' => range(1, max(1, $maxDias)), 'xTitulo' => 'Dia do mês', 'datasets' => []];
    $i = 0;
    foreach ($series as $s) {
        $cor = $cores[$i++ % count($cores)];
        $nome = $mesesCurtos[$s['mes']] . '/' . $s['ano'];
        $grafico['datasets'][] = ['label' => "{$nome} máx.", 'borderColor' => $cor, 'backgroundColor' => $cor, 'borderWidth' => 2.5, 'pointRadius' => 0, 'pointHoverRadius' => 4, 'tension' => 0.2,
            'data' => array_map(fn ($v) => $v ? $v['tmax'] : null, array_values($s['diario']))];
        $grafico['datasets'][] = ['label' => "{$nome} mín.", 'borderColor' => $cor, 'backgroundColor' => $cor, 'borderWidth' => 2, 'borderDash' => [6, 4], 'pointRadius' => 0, 'pointHoverRadius' => 4, 'tension' => 0.2,
            'data' => array_map(fn ($v) => $v ? $v['tmin'] : null, array_values($s['diario']))];
    }
    ?>
<?php endif; ?>

<div class="text-end mt-3 mb-4">
    <small class="text-muted"><i class="bi bi-info-circle"></i> Dados informativos da estação automática, sujeitos a falhas do equipamento. Períodos em andamento aparecem como parciais.</small>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cfg = <?= json_encode($grafico, JSON_UNESCAPED_UNICODE) ?>;
    const el = document.getElementById('graficoComparacao');
    if (!el || typeof Chart === 'undefined') return;

    const fmt = v => v === null || v === undefined ? '—' : v.toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' °C';

    new Chart(el, {
        type: 'line',
        data: { labels: cfg.labels, datasets: cfg.datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                tooltip: {
                    callbacks: {
                        title: items => cfg.xTitulo ? 'Dia ' + items[0].label : items[0].label,
                        label: ctx => ' ' + ctx.dataset.label + ': ' + fmt(ctx.parsed.y)
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, title: { display: !!cfg.xTitulo, text: cfg.xTitulo } },
                y: { title: { display: true, text: '°C' }, ticks: { callback: v => v.toLocaleString('pt-BR') } }
            }
        }
    });
});
</script>
