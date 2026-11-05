<?php
/** @var array $cfg */
/** @var string $local */
/** @var string $modo */
/** @var int[] $anos */
/** @var array $itens */
/** @var int $anoInicial */
/** @var string $primeiroDia */
/** @var array $resultado */
/** @var array $meses */

$mesesCurtos = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];
$cores = ['#ff6b35', '#0d6efd', '#20c997'];
$un = $cfg['unidade'];
$casas = $cfg['casas'];
$fmt = fn (?float $v, ?int $c = null) => $v === null ? '—' : number_format($v, $c ?? $casas, ',', '.');
$fmtData = fn (?string $d) => $d ? date('d/m/Y', strtotime($d)) : '—';
$base = url($cfg['rota'] . '/comparar') . '?local=' . urlencode($local);
$series = $cfg['series'];
// séries distintas pra gráfico/tabela (na evapotranspiração as duas séries vêm do mesmo campo)
$serieTabela = $series[0]['campo'] === ($series[1]['campo'] ?? null) ? [0] : array_keys($series);
$ehSoma = $series[0]['agg'] === 'soma';

$dif = function (float $v, float $ref) use ($fmt, $cfg) {
    $d = $v - $ref;
    $classe = abs($d) < 0.05 ? 'text-muted' : ($d > 0 ? $cfg['cor_acima'] : $cfg['cor_abaixo']);
    return '<span class="fw-bold ' . $classe . '">(' . ($d > 0 ? '+' : '') . $fmt($d) . ')</span>';
};

$card = function (string $titulo, ?array $r, string $cor, bool $parcial, ?array $geral) use ($fmt, $un, $series, $cfg, $dif) {
    ?>
    <div class="weather-card h-100 p-3" style="border-top: 4px solid <?= $cor ?>;">
        <div class="small text-muted fw-bold">
            <?= $titulo ?>
            <?php if ($parcial): ?><span class="badge bg-secondary ms-1">parcial</span><?php endif; ?>
        </div>
        <?php if (!$r): ?>
            <div class="text-muted small mt-2">Sem leituras neste período.</div>
        <?php else: ?>
            <div class="d-flex flex-wrap gap-3 align-items-baseline mt-1">
                <?php foreach ($series as $i => $s): ?>
                    <div><span class="fs-4 fw-bold <?= $s['classe'] ?>"><?= $fmt($r['valores'][$i]) ?><?= $un ?></span> <small class="text-muted"><?= $s['nome'] ?></small></div>
                <?php endforeach; ?>
            </div>
            <?php if ($geral): ?>
                <?php foreach ($series as $i => $s): ?>
                    <div class="small text-muted">Média geral (<?= $s['nome'] ?>): <?= $fmt($geral[$i]) ?><?= $un ?> <?= $dif($r['valores'][$i], $geral[$i]) ?></div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="small text-muted">Média geral: ainda sem anos suficientes.</div>
            <?php endif; ?>
            <?php if ($r['maior']): ?>
                <div class="small text-muted">Maior: <strong><?= $fmt($r['maior']['valor']) ?><?= $un ?></strong> em <?= date('d/m', strtotime($r['maior']['dia'])) ?><?= $r['maior']['hora'] ? ' às ' . $r['maior']['hora'] : '' ?></div>
            <?php endif; ?>
            <?php if ($r['menor']): ?>
                <div class="small text-muted">Menor: <strong><?= $fmt($r['menor']['valor']) ?><?= $un ?></strong> em <?= date('d/m', strtotime($r['menor']['dia'])) ?><?= $r['menor']['hora'] ? ' às ' . $r['menor']['hora'] : '' ?></div>
            <?php endif; ?>
            <?php if ($cfg['contagem']): ?>
                <div class="small text-muted"><?= $r['contagem'] ?> <?= $r['contagem'] === 1 ? 'dia' : 'dias' ?> <?= $cfg['contagem']['texto'] ?></div>
            <?php endif; ?>
            <div class="small text-muted"><?= $r['dias'] ?> <?= $r['dias'] === 1 ? 'dia' : 'dias' ?> com leitura</div>
        <?php endif; ?>
    </div>
    <?php
};
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-7">
        <a href="<?= url($cfg['rota']) ?>?local=<?= urlencode($local) ?>" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left me-1"></i><?= $cfg['titulo'] ?>
        </a>
        <h4 class="mb-0 mt-1">
            <i class="bi <?= $cfg['icone'] ?> text-primary"></i> Comparar <?= $cfg['titulo'] ?>
        </h4>
        <p class="text-muted small mb-0">
            Compare entre anos (mês a mês) ou entre meses (dia a dia). Dados da estação automática desde <?= $fmtData($primeiroDia) ?>.
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

<form action="<?= url($cfg['rota'] . '/comparar') ?>" method="GET" class="weather-card p-3 mb-4">
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
                <?php if ($anoInicial === (int) date('Y')): ?>
                    <small class="text-muted d-block mt-2"><i class="bi bi-info-circle"></i> Por enquanto só há dados de <?= $anoInicial ?>. A comparação entre meses já funciona.</small>
                <?php endif; ?>
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

<?php if ($modo === 'anos'): ?>
    <?php $lista = $resultado['series']; $media = $resultado['media_mensal']; $temMedia = (bool) array_filter($media); ?>

    <div class="row g-3 mb-4">
        <?php $i = 0; foreach ($lista as $ano => $s): ?>
            <div class="col-md-4"><?php $card((string) $ano, $s['resumo'], $cores[$i++ % count($cores)], $s['parcial'], $s['geral']); ?></div>
        <?php endforeach; ?>
    </div>

    <div class="weather-card mb-4">
        <div class="card-header-custom">
            <i class="bi bi-graph-up"></i> <?= $cfg['titulo'] ?> por mês
        </div>
        <div class="card-body">
            <div style="position: relative; height: 360px;">
                <canvas id="graficoComparacao" aria-label="Gráfico de <?= $cfg['titulo'] ?> por mês para cada ano" role="img"></canvas>
            </div>
            <?php if (count($serieTabela) > 1): ?>
                <p class="small text-muted mb-0 mt-2">Linha cheia: <?= $series[0]['titulo'] ?>. Linha tracejada: <?= $series[1]['titulo'] ?>.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="weather-card">
        <div class="card-header-custom">
            <i class="bi bi-table"></i> Tabela mês a mês — <?= implode(' / ', array_map(fn ($i) => $series[$i]['nome'], $serieTabela)) ?> (<?= trim($un) ?>)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table-weather">
                    <thead>
                        <tr>
                            <th>Mês</th>
                            <?php foreach ($lista as $ano => $s): ?><th class="text-end"><?= $ano ?></th><?php endforeach; ?>
                            <?php if ($temMedia): ?><th class="text-end">Média geral</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <tr>
                                <td class="fw-bold"><?= $meses[$m] ?></td>
                                <?php foreach ($lista as $s): ?>
                                    <?php $r = $s['mensal'][$m]; ?>
                                    <td class="text-end">
                                        <?php if ($r): ?>
                                            <?= implode(' / ', array_map(fn ($i) => '<span class="fw-bold ' . $series[$i]['classe'] . '">' . $fmt($r['valores'][$i]) . '</span>', $serieTabela)) ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php if ($temMedia): ?>
                                    <td class="text-end text-muted"><?= $media[$m] ? implode(' / ', array_map(fn ($i) => $fmt($media[$m][$i]), $serieTabela)) : '—' ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php
    $grafico = ['tipo' => $cfg['grafico'], 'labels' => array_values($mesesCurtos), 'xTitulo' => '', 'datasets' => []];
    $i = 0;
    foreach ($lista as $ano => $s) {
        $cor = $cores[$i++ % count($cores)];
        foreach ($serieTabela as $k => $si) {
            $grafico['datasets'][] = [
                'label' => "{$ano} {$series[$si]['nome']}", 'borderColor' => $cor, 'backgroundColor' => $cor,
                'borderWidth' => $k === 0 ? 2.5 : 2, 'borderDash' => $k === 0 ? [] : [6, 4], 'pointRadius' => 3, 'tension' => 0.3, 'borderRadius' => 4,
                'data' => array_map(fn ($r) => $r ? round($r['valores'][$si], 2) : null, array_values($s['mensal'])),
            ];
        }
    }
    if ($ehSoma && $temMedia) {
        $grafico['datasets'][] = ['type' => 'line', 'label' => 'Média geral', 'borderColor' => '#1a1a2e', 'backgroundColor' => '#1a1a2e',
            'borderDash' => [6, 4], 'borderWidth' => 2, 'pointRadius' => 2, 'tension' => 0.3,
            'data' => array_map(fn ($v) => $v ? round($v[0], 2) : null, array_values($media))];
    }
    ?>

<?php else: ?>
    <?php $lista = $resultado['series']; $maxDias = $lista ? max(array_map(fn ($s) => count($s['diario']), $lista)) : 0; ?>

    <div class="row g-3 mb-4">
        <?php $i = 0; foreach ($lista as $s): ?>
            <div class="col-md-4"><?php $card($meses[$s['mes']] . ' de ' . $s['ano'], $s['resumo'], $cores[$i++ % count($cores)], $s['parcial'], $s['geral']); ?></div>
        <?php endforeach; ?>
    </div>

    <div class="weather-card mb-4">
        <div class="card-header-custom">
            <i class="bi bi-graph-up"></i> <?= $cfg['titulo'] ?> de cada dia
        </div>
        <div class="card-body">
            <div style="position: relative; height: 360px;">
                <canvas id="graficoComparacao" aria-label="Gráfico de <?= $cfg['titulo'] ?> por dia para cada mês" role="img"></canvas>
            </div>
            <?php if (count($serieTabela) > 1): ?>
                <p class="small text-muted mb-0 mt-2">Linha cheia: <?= $series[0]['nome'] ?> do dia. Linha tracejada: <?= $series[1]['nome'] ?> do dia.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="weather-card">
        <div class="card-header-custom">
            <i class="bi bi-table"></i> Por dia — <?= implode(' / ', array_map(fn ($i) => $series[$i]['nome'], $serieTabela)) ?> (<?= trim($un) ?>)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                <table class="table-weather">
                    <thead>
                        <tr>
                            <th>Dia</th>
                            <?php foreach ($lista as $s): ?><th class="text-end"><?= $mesesCurtos[$s['mes']] ?>/<?= $s['ano'] ?></th><?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($d = 1; $d <= $maxDias; $d++): ?>
                            <tr>
                                <td class="fw-bold"><?= $d ?></td>
                                <?php foreach ($lista as $s): ?>
                                    <?php $v = $s['diario'][$d] ?? null; ?>
                                    <td class="text-end">
                                        <?php if ($v): ?>
                                            <?= implode(' / ', array_map(fn ($i) => '<span class="fw-bold ' . $series[$i]['classe'] . '">' . $fmt($v[$series[$i]['campo']]) . '</span>', $serieTabela)) ?>
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
    $grafico = ['tipo' => $cfg['grafico'], 'labels' => range(1, max(1, $maxDias)), 'xTitulo' => 'Dia do mês', 'datasets' => []];
    $i = 0;
    foreach ($lista as $s) {
        $cor = $cores[$i++ % count($cores)];
        $nome = $mesesCurtos[$s['mes']] . '/' . $s['ano'];
        foreach ($serieTabela as $k => $si) {
            $campo = $series[$si]['campo'];
            $grafico['datasets'][] = [
                'label' => "{$nome} {$series[$si]['nome']}", 'borderColor' => $cor, 'backgroundColor' => $cor,
                'borderWidth' => $k === 0 ? 2.5 : 2, 'borderDash' => $k === 0 ? [] : [6, 4], 'pointRadius' => 0, 'pointHoverRadius' => 4, 'tension' => 0.2, 'borderRadius' => 3,
                'data' => array_map(fn ($v) => $v ? round((float) $v[$campo], 2) : null, array_values($s['diario'])),
            ];
        }
    }
    ?>
<?php endif; ?>

<div class="text-end mt-3 mb-4">
    <small class="text-muted"><i class="bi bi-info-circle"></i> Dados informativos da estação automática, sujeitos a falhas do equipamento. Períodos em andamento aparecem como parciais. A média geral usa os meses completos de pelo menos 2 anos.</small>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cfg = <?= json_encode($grafico, JSON_UNESCAPED_UNICODE) ?>;
    const unidade = <?= json_encode($un, JSON_UNESCAPED_UNICODE) ?>;
    const casas = <?= (int) $casas ?>;
    const el = document.getElementById('graficoComparacao');
    if (!el || typeof Chart === 'undefined') return;

    const fmt = v => v === null || v === undefined ? '—' : v.toLocaleString('pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas }) + unidade;

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
                        title: items => cfg.xTitulo ? 'Dia ' + items[0].label : items[0].label,
                        label: ctx => ' ' + ctx.dataset.label + ': ' + fmt(ctx.parsed.y)
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, title: { display: !!cfg.xTitulo, text: cfg.xTitulo } },
                y: { beginAtZero: cfg.tipo === 'bar', title: { display: true, text: unidade.trim() }, ticks: { callback: v => v.toLocaleString('pt-BR') } }
            }
        }
    });
});
</script>
