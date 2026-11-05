<?php
/**
 * Bloco de médias do período + média geral, usado nas páginas de umidade, pressão, vento e ET.
 * Espera: $medias (com 'cfg'), $year, $month, $meses, $local.
 */
$cfgM = $medias['cfg'];
$mr = $medias['resumo'] ?? null;
$mg = $medias['geral'] ?? null;
$ma = $medias['anos'] ?? null;
$fmtM = fn (float $v) => number_format($v, $cfgM['casas'], ',', '.');
$periodoM = ($month === 'all' ? 'em ' : 'em ' . mb_strtolower($meses[$month] ?? '') . ' de ') . $year;
$emAndamento = $year === (int) date('Y') && ($month === 'all' || (int) $month === (int) date('m'));
?>
<?php if ($mr): ?>
<div class="row g-3 mb-4 fade-in-up">
    <?php foreach ($cfgM['series'] as $i => $s): ?>
        <div class="col-md-6">
            <div class="weather-card h-100 p-3 mb-0">
                <div class="small text-muted fw-bold"><i class="bi <?= $s['icone'] ?> me-1"></i><?= $s['titulo'] ?> <?= htmlspecialchars($periodoM) ?></div>
                <div class="fs-3 fw-bold <?= $s['classe'] ?>"><?= $fmtM($mr['valores'][$i]) ?><?= $cfgM['unidade'] ?></div>
                <?php if ($mg): ?>
                    <?php $d = $mr['valores'][$i] - $mg[$i]; ?>
                    <div class="small text-muted">
                        Média geral: <?= $fmtM($mg[$i]) ?><?= $cfgM['unidade'] ?>
                        <span class="fw-bold <?= abs($d) < 0.05 ? 'text-muted' : ($d > 0 ? $cfgM['cor_acima'] : $cfgM['cor_abaixo']) ?>">(<?= $d > 0 ? '+' : '' ?><?= $fmtM($d) ?>)</span>
                    </div>
                <?php else: ?>
                    <div class="small text-muted">Ainda não há anos suficientes pra média geral deste período.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <small class="text-muted">
            <i class="bi bi-info-circle"></i> <?= $mr['dias'] ?> <?= $mr['dias'] === 1 ? 'dia' : 'dias' ?> com leitura<?= $emAndamento ? ' até agora (período em andamento)' : '' ?>.
            Média geral = mesmo mês em todos os anos em que ele está completo<?= $ma ? " (dados desde {$ma[0]})" : '' ?>.
        </small>
        <a href="<?= url($cfgM['rota'] . '/comparar') ?>?local=<?= urlencode($local) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
            <i class="bi bi-graph-up me-1"></i> Comparar meses e anos
        </a>
    </div>
</div>
<?php else: ?>
<div class="d-flex justify-content-end mb-3">
    <a href="<?= url($cfgM['rota'] . '/comparar') ?>?local=<?= urlencode($local) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
        <i class="bi bi-graph-up me-1"></i> Comparar meses e anos
    </a>
</div>
<?php endif; ?>
