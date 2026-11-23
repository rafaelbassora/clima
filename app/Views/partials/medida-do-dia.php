<?php
/**
 * Pesquisa "hora a hora em um dia" de temperatura, umidade, pressão ou evapotranspiração.
 * Espera: $tipoDia ('temperatura' | 'umidade' | 'pressao' | 'et'), $dia, $primeiroDia, $medidasDia (LeituraModel::getMedidasDoDia),
 *         $local, $month, $year.
 */
$cfgDia = [
    'temperatura' => ['titulo' => 'Temperatura', 'icone' => 'bi-thermometer-half', 'unidade' => '°C', 'campo' => 'tmin', 'casas' => 1],
    'umidade' => ['titulo' => 'Umidade', 'icone' => 'bi-droplet-fill', 'unidade' => '%', 'campo' => 'hmin', 'casas' => 0],
    'pressao' => ['titulo' => 'Pressão', 'icone' => 'bi-speedometer2', 'unidade' => 'hPa', 'campo' => 'bmin', 'casas' => 1],
    'et'      => ['titulo' => 'Evapotranspiração', 'icone' => 'bi-moisture', 'unidade' => 'mm', 'campo' => 'et', 'casas' => 2],
][$tipoDia];
$fmtDia = fn ($v, $casas = null) => $v === null ? '—' : number_format($v, $casas ?? $cfgDia['casas'], ',', '.');
$horasDia = array_filter($medidasDia, fn ($h) => $h[$cfgDia['campo']] !== null);
$hojeDia = date('Y-m-d');

// Dia incompleto: horas (de um dia já encerrado) em que a estação não mandou nenhuma leitura.
// Faixas contínuas, ex.: [[8, 23]] = das 08:00 às 23:59.
$faixasSemDados = [];
if ($dia < $hojeDia && $medidasDia) {
    $inicioFaixa = null;
    for ($hh = 0; $hh <= 24; $hh++) {
        $falta = $hh < 24 && !isset($medidasDia[$hh]);
        if ($falta && $inicioFaixa === null) { $inicioFaixa = $hh; }
        if (!$falta && $inicioFaixa !== null) { $faixasSemDados[] = [$inicioFaixa, $hh - 1]; $inicioFaixa = null; }
    }
}
$horasSemDados = array_sum(array_map(fn ($f) => $f[1] - $f[0] + 1, $faixasSemDados));
$previsaoDia = $previsaoDia ?? null; // só a temperatura recebe (previsão do INMET)
$linkMedidaDia = fn ($d) => '?' . http_build_query(['local' => $local, 'month' => $month, 'year' => $year, 'dia' => $d]) . '#medida-dia';

// resumo do dia
$resumoDia = [];
if ($horasDia) {
    if ($tipoDia === 'et') {
        $total = array_sum(array_column($horasDia, 'et'));
        $picoHora = null;
        foreach ($horasDia as $hora => $h) {
            if ($picoHora === null || $h['et'] > $horasDia[$picoHora]['et']) { $picoHora = $hora; }
        }
        $resumoDia = [
            ['Total do dia', $fmtDia($total) . ' <small class="fs-6 fw-normal">mm</small>', null, 'text-success'],
            ['Hora de maior evapotranspiração', sprintf('%02d:00', $picoHora), $fmtDia($horasDia[$picoHora]['et']) . ' mm nessa hora', ''],
            ['Horas com registro', count($horasDia) . ' <small class="fs-6 fw-normal">de 24</small>', null, ''],
        ];
    } else {
        $p = ['temperatura' => 't', 'umidade' => 'h'][$tipoDia] ?? 'b';
        $min = null; $max = null;
        foreach ($horasDia as $h) {
            if ($min === null || $h[$p . 'min'] < $min[$p . 'min']) { $min = $h; }
            if ($max === null || $h[$p . 'max'] > $max[$p . 'max']) { $max = $h; }
        }
        $medias = array_column($horasDia, $p . 'avg');
        $u = ' <small class="fs-6 fw-normal">' . $cfgDia['unidade'] . '</small>';
        $resumoDia = [
            ['Mínima', $fmtDia($min[$p . 'min']) . $u, 'às ' . htmlspecialchars($min['h' . $p . 'min'] ?? '--:--'), 'text-primary'],
            ['Máxima', $fmtDia($max[$p . 'max']) . $u, 'às ' . htmlspecialchars($max['h' . $p . 'max'] ?? '--:--'), 'text-accent-warm'],
            $tipoDia === 'umidade'
                ? ['Média do dia', $fmtDia(array_sum($medias) / count($medias)) . $u, null, '']
                : ['Variação no dia', $fmtDia($max[$p . 'max'] - $min[$p . 'min']) . $u, 'diferença entre máxima e mínima', ''],
        ];
    }
}
?>
<div class="col-12" id="medida-dia">
    <div class="weather-card fade-in-up">
        <div class="card-header-custom">
            <i class="bi bi-search"></i> <?= $cfgDia['titulo'] ?> Hora a Hora em um Dia
        </div>
        <div class="card-body">
            <form method="GET" action="#medida-dia" class="d-flex flex-wrap gap-2 align-items-end mb-3">
                <input type="hidden" name="local" value="<?= htmlspecialchars($local) ?>">
                <input type="hidden" name="month" value="<?= htmlspecialchars((string) $month) ?>">
                <input type="hidden" name="year" value="<?= (int) $year ?>">
                <div>
                    <label for="medida-dia-data" class="form-label small text-muted mb-1">Escolha o dia</label>
                    <input type="date" id="medida-dia-data" name="dia" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($dia) ?>" min="<?= htmlspecialchars($primeiroDia) ?>" max="<?= $hojeDia ?>" required>
                </div>
                <button type="submit" class="btn btn-sm btn-primary px-3"><i class="bi bi-search me-1"></i>Pesquisar</button>
                <div class="btn-group btn-group-sm ms-sm-auto" role="group" aria-label="Navegar entre os dias">
                    <?php if ($dia > $primeiroDia): ?>
                        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($linkMedidaDia(date('Y-m-d', strtotime($dia . ' -1 day')))) ?>"><i class="bi bi-chevron-left"></i> Dia anterior</a>
                    <?php endif; ?>
                    <?php if ($dia < $hojeDia): ?>
                        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($linkMedidaDia(date('Y-m-d', strtotime($dia . ' +1 day')))) ?>">Próximo dia <i class="bi bi-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            </form>

            <h5 class="h6 fw-bold mb-3"><?= date('d/m/Y', strtotime($dia)) ?><?= $dia === $hojeDia ? ' <span class="badge bg-primary bg-opacity-10 text-primary">hoje, até agora</span>' : '' ?><?= $faixasSemDados ? ' <span class="badge bg-secondary fw-normal">dia incompleto</span>' : '' ?></h5>

            <?php if ($faixasSemDados): ?>
                <div class="alert alert-secondary small py-2 mb-3" role="note">
                    <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>
                    <strong>Dia incompleto:</strong> a estação ficou sem dados
                    <?= implode(', ', array_map(fn ($f) => sprintf('das %02d:00 às %02d:59', $f[0], $f[1]), $faixasSemDados)) ?>
                    (<?= $horasSemDados ?> de 24 horas). Os valores abaixo são só do período medido.
                    <?php if ($previsaoDia): ?>
                        <br>Para referência, a <strong>previsão</strong> do INMET para este dia era
                        máxima <?= (int) $previsaoDia['max'] ?> °C e mínima <?= (int) $previsaoDia['min'] ?> °C (previsão, não medição).
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!$horasDia): ?>
                <p class="text-muted text-center py-4 mb-0">Não há registros de <?= mb_strtolower($cfgDia['titulo']) ?> neste dia.</p>
            <?php else: ?>
                <div class="row g-2 mb-3">
                    <?php foreach ($resumoDia as $i => [$rotulo, $valor, $detalhe, $cor]): ?>
                    <div class="<?= $i < 2 ? 'col-6' : 'col-12' ?> col-md-4">
                        <div class="p-2 border rounded text-center bg-light h-100">
                            <div class="small text-muted"><?= $rotulo ?></div>
                            <div class="fw-bold fs-5 <?= $cor ?>"><?= $valor ?></div>
                            <?php if ($detalhe): ?><div class="small text-muted"><?= $detalhe ?></div><?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="table-responsive">
                    <table class="table-weather table-sm table-hover medida-dia-tabela">
                        <caption class="visually-hidden"><?= $cfgDia['titulo'] ?> hora a hora em <?= date('d/m/Y', strtotime($dia)) ?></caption>
                        <thead>
                            <tr>
                                <th scope="col">Hora</th>
                                <?php if ($tipoDia === 'et'): ?>
                                    <th scope="col">Na hora</th>
                                    <th scope="col">Acumulado no dia</th>
                                <?php else: ?>
                                    <th scope="col">Média</th>
                                    <th scope="col" class="text-primary">Mínima</th>
                                    <th scope="col" class="text-accent-warm">Máxima</th>
                                    <?php if ($tipoDia === 'pressao'): ?><th scope="col">Variação</th><?php endif; ?>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $acumulado = 0; $mediaAnterior = null; ?>
                            <?php foreach ($horasDia as $hora => $h): ?>
                            <tr>
                                <th scope="row" class="fw-normal"><?= sprintf('%02d:00', $hora) ?></th>
                                <?php if ($tipoDia === 'et'): $acumulado += $h['et']; ?>
                                    <td class="fw-bold text-success"><?= $fmtDia($h['et']) ?> <small>mm</small></td>
                                    <td><?= $fmtDia($acumulado) ?> <small>mm</small></td>
                                <?php else: $p = ['temperatura' => 't', 'umidade' => 'h'][$tipoDia] ?? 'b'; ?>
                                    <td><?= $fmtDia($h[$p . 'avg']) ?> <small><?= $cfgDia['unidade'] ?></small></td>
                                    <td class="text-primary"><?= $fmtDia($h[$p . 'min']) ?> <small><?= $cfgDia['unidade'] ?></small></td>
                                    <td class="text-accent-warm fw-bold"><?= $fmtDia($h[$p . 'max']) ?> <small><?= $cfgDia['unidade'] ?></small></td>
                                    <?php if ($tipoDia === 'pressao'): ?>
                                        <td class="small">
                                            <?php if ($mediaAnterior === null): ?>
                                                <span class="text-muted">—</span>
                                            <?php else: $dif = round($h['bavg'] - $mediaAnterior, 1); ?>
                                                <?php if ($dif > 0): ?>
                                                    <span class="text-accent-warm"><i class="bi bi-arrow-up-short" aria-hidden="true"></i>+<?= $fmtDia($dif) ?></span><span class="visually-hidden"> subiu</span>
                                                <?php elseif ($dif < 0): ?>
                                                    <span class="text-primary"><i class="bi bi-arrow-down-short" aria-hidden="true"></i><?= $fmtDia($dif) ?></span><span class="visually-hidden"> caiu</span>
                                                <?php else: ?>
                                                    <span class="text-muted">estável</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <?php $mediaAnterior = $h['bavg']; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mb-0 mt-2">
                    <?php if ($tipoDia === 'et'): ?>
                        Evapotranspiração: quantidade de água (em mm) que o solo e as plantas perderam para o ar em cada hora.
                    <?php elseif ($tipoDia === 'temperatura'): ?>
                        Temperatura do ar medida pela estação, agrupada por hora (média, mínima e máxima de cada hora).
                    <?php elseif ($tipoDia === 'pressao'): ?>
                        Variação: diferença entre a média da hora e a da hora anterior. Queda contínua costuma indicar chegada de frente ou chuva.
                    <?php else: ?>
                        Umidade relativa do ar. Abaixo de 30% a Defesa Civil recomenda cuidados com a saúde.
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>
