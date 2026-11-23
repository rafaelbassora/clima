<?php
/**
 * Data da tabela mensal + olho que abre o "hora a hora" daquele dia em nova aba.
 * Marca "incompleto" no dia (já encerrado) em que a estação ficou sem dados em alguma hora.
 * Espera: $olhoData ('d/m/Y'), $olhoMedida (ex.: 'a temperatura'), $olhoAncora ('medida-dia' | 'vento-dia'),
 *         $local, $month, $year.
 */
$olhoIso = \DateTime::createFromFormat('!d/m/Y', (string) $olhoData);
$GLOBALS['olhoModel'] = $GLOBALS['olhoModel'] ?? new \App\Models\LeituraModel();
$olhoIncompleto = $olhoIso && $GLOBALS['olhoModel']->diaIncompleto((string) $local, $olhoIso->format('Y-m-d'));
?>
<?= htmlspecialchars((string) $olhoData) ?>
<?php if ($olhoIso): ?>
    <a href="?<?= htmlspecialchars(http_build_query(['local' => $local, 'month' => $month, 'year' => $year, 'dia' => $olhoIso->format('Y-m-d')])) ?>#<?= htmlspecialchars($olhoAncora) ?>"
       target="_blank" rel="noopener" class="ms-1 text-primary"
       title="Ver <?= htmlspecialchars($olhoMedida) ?> hora a hora de <?= htmlspecialchars((string) $olhoData) ?> (abre em nova aba)">
        <i class="bi bi-eye" aria-hidden="true"></i><span class="visually-hidden">Ver hora a hora de <?= htmlspecialchars((string) $olhoData) ?> em nova aba</span>
    </a>
<?php endif; ?>
<?php if ($olhoIncompleto): ?>
    <span class="badge bg-secondary fw-normal" title="A estação ficou sem dados em parte deste dia: os valores são só do período medido">incompleto</span>
<?php endif; ?>
