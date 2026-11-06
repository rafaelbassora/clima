<?php
/** @var array $vento24h */
/** @var array $vento7dias */
/** @var array $resumoMensal */
/** @var array $estatisticas */
/** @var string $local */
/** @var int $year */
/** @var int $month */

$localNome = $local == '1' ? 'Sede' : 'Zona Rural';
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-6">
        <h4 class="mb-0">
            <i class="bi bi-wind text-primary"></i> Ventos e Velocidade
            <small class="text-muted ms-2" style="font-size: 0.75rem; font-weight: 400;">(Dados atualizados a cada 1h)</small>
        </h4>
        <small class="text-muted">Anemômetro histórico - <?= $localNome ?></small>
    </div>
    <div class="col-md-6 text-md-end">
        <form method="GET" class="d-flex gap-2 justify-content-md-end align-items-center">
            <input type="hidden" name="local" value="<?= htmlspecialchars($local) ?>">
            <select name="month" class="form-select form-select-sm w-auto">
                <?php foreach($meses as $m => $nome): ?>
                    <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>>
                        <?= $nome ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="year" class="form-select form-select-sm w-auto">
                <?php for($y=date('Y'); $y>=2018; $y--): ?>
                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-primary px-3">Filtrar</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../partials/medias-medida.php'; ?>

<div class="row g-3">
    <!-- Recordes - Velocidade Máxima -->
    <div class="col-12">
        <div class="weather-card fade-in-up border-accent-warm">
            <div class="card-header-custom bg-accent-warm text-white border-0">
                <i class="bi bi-lightning-fill"></i> Recordes de Velocidade (Rajadas Máximas)
            </div>
            <div class="card-body">
                <div class="row g-2 align-items-center">
                    <?php foreach ($estatisticas['top_max'] as $i => $row): ?>
                    <div class="col-md">
                        <div class="p-3 border rounded text-center <?= $i == 0 ? 'bg-accent-warm bg-opacity-10 border-accent-warm' : 'bg-light' ?>">
                            <div class="small fw-bold text-muted text-uppercase"><?= date('d/m/Y', strtotime($row['dia'])) ?></div>
                            <div class="display-6 fw-bold text-accent-warm my-1"><?= number_format($row['valor'], 1) ?> <small class="fs-6">km/h</small></div>
                            <div class="badge bg-dark rounded-pill"><i class="bi bi-compass me-1"></i> Dir: <?= $row['direcao_label'] ?></div>
                            <small class="d-block mt-1 text-muted">Às <?= date('H:i', strtotime($row['data_hora'])) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Vento hora a hora de um dia escolhido -->
    <?php
    $fmtVento = fn ($v) => $v === null ? '—' : number_format($v, 1, ',', '.');
    $diaAnterior = date('Y-m-d', strtotime($dia . ' -1 day'));
    $diaSeguinte = date('Y-m-d', strtotime($dia . ' +1 day'));
    $linkDia = fn ($d) => '?' . http_build_query(['local' => $local, 'month' => $month, 'year' => $year, 'dia' => $d]) . '#vento-dia';
    $rajadaDia = null;
    foreach ($ventoDia as $h) {
        if ($h['rajada'] !== null && ($rajadaDia === null || $h['rajada'] > $rajadaDia['rajada'])) {
            $rajadaDia = $h;
        }
    }
    $mediasDia = array_filter(array_column($ventoDia, 'media'), fn ($v) => $v !== null);
    ?>
    <div class="col-12" id="vento-dia">
        <div class="weather-card fade-in-up">
            <div class="card-header-custom">
                <i class="bi bi-search"></i> Vento Hora a Hora em um Dia
            </div>
            <div class="card-body">
                <form method="GET" action="#vento-dia" class="d-flex flex-wrap gap-2 align-items-end mb-3">
                    <input type="hidden" name="local" value="<?= htmlspecialchars($local) ?>">
                    <input type="hidden" name="month" value="<?= (int) $month ?>">
                    <input type="hidden" name="year" value="<?= (int) $year ?>">
                    <div>
                        <label for="vento-dia-data" class="form-label small text-muted mb-1">Escolha o dia</label>
                        <input type="date" id="vento-dia-data" name="dia" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($dia) ?>" min="<?= htmlspecialchars($primeiroDia) ?>" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary px-3"><i class="bi bi-search me-1"></i>Pesquisar</button>
                    <div class="btn-group btn-group-sm ms-sm-auto" role="group" aria-label="Navegar entre os dias">
                        <?php if ($dia > $primeiroDia): ?>
                            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($linkDia($diaAnterior)) ?>"><i class="bi bi-chevron-left"></i> Dia anterior</a>
                        <?php endif; ?>
                        <?php if ($dia < date('Y-m-d')): ?>
                            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($linkDia($diaSeguinte)) ?>">Próximo dia <i class="bi bi-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                </form>

                <h5 class="h6 fw-bold mb-3"><?= date('d/m/Y', strtotime($dia)) ?><?= $dia === date('Y-m-d') ? ' <span class="badge bg-primary bg-opacity-10 text-primary">hoje, até agora</span>' : '' ?></h5>

                <?php if (empty($ventoDia)): ?>
                    <p class="text-muted text-center py-4 mb-0">Não há registros de vento neste dia.</p>
                <?php else: ?>
                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-4">
                            <div class="p-2 border rounded text-center bg-light h-100">
                                <div class="small text-muted">Média do dia</div>
                                <div class="fw-bold fs-5"><?= $fmtVento($mediasDia ? array_sum($mediasDia) / count($mediasDia) : null) ?> <small class="fs-6 fw-normal">km/h</small></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="p-2 border rounded text-center bg-light h-100">
                                <div class="small text-muted">Maior rajada</div>
                                <div class="fw-bold fs-5 text-accent-warm"><?= $fmtVento($rajadaDia['rajada'] ?? null) ?> <small class="fs-6 fw-normal">km/h</small></div>
                                <?php if ($rajadaDia): ?><div class="small text-muted">às <?= htmlspecialchars($rajadaDia['hora_rajada'] ?? '--:--') ?><?= $rajadaDia['dir_rajada'] ? ', vindo de ' . htmlspecialchars($rajadaDia['dir_rajada']) : '' ?></div><?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="p-2 border rounded text-center bg-light h-100">
                                <div class="small text-muted">Horas com registro</div>
                                <div class="fw-bold fs-5"><?= count($ventoDia) ?> <small class="fs-6 fw-normal">de 24</small></div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table-weather table-sm table-hover vento-dia-tabela">
                            <caption class="visually-hidden">Vento hora a hora em <?= date('d/m/Y', strtotime($dia)) ?></caption>
                            <thead>
                                <tr>
                                    <th scope="col">Hora</th>
                                    <th scope="col">Média</th>
                                    <th scope="col">Rajada máx.</th>
                                    <th scope="col">Horário da rajada</th>
                                    <th scope="col">Direção da rajada</th>
                                    <th scope="col">Direção predominante</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ventoDia as $h): ?>
                                <tr class="<?= ($h['rajada'] ?? 0) >= 40 ? 'bg-danger bg-opacity-10' : '' ?>">
                                    <th scope="row" class="fw-normal"><?= $h['hora'] ?></th>
                                    <td><?= $fmtVento($h['media']) ?> <small>km/h</small></td>
                                    <td class="fw-bold text-accent-warm"><?= $fmtVento($h['rajada']) ?> <small>km/h</small></td>
                                    <td class="small"><?= htmlspecialchars($h['hora_rajada'] ?? '—') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($h['dir_rajada'] ?? '—') ?></span></td>
                                    <td>
                                        <?php if ($h['graus_predominante'] !== null): ?>
                                            <i class="bi bi-arrow-up vento-seta" style="transform: rotate(<?= ($h['graus_predominante'] + 180) % 360 ?>deg)" aria-hidden="true"></i>
                                        <?php endif; ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary"><?= htmlspecialchars($h['dir_predominante']) ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mb-0 mt-2">A direção indica de onde o vento vem (a seta aponta para onde ele sopra). Linhas em vermelho: rajadas a partir de 40 km/h.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Gráfico/Tabela 24h -->
    <div class="col-lg-6">
        <div class="weather-card h-100 fade-in-up" style="transition-delay: 0.1s">
            <div class="card-header-custom">
                <i class="bi bi-clock-history"></i> Velocidade Hora a Hora (Últimas 24h)
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table-weather table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Velocidade</th>
                                <th>Direção</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vento24h as $row): ?>
                            <tr>
                                <td><?= $row['hora'] ?></td>
                                <td class="fw-bold"><?= $row['vento'] ?> km/h</td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= $row['direcao'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Histórico Mensal -->
    <div class="col-lg-6">
        <div class="weather-card h-100 fade-in-up" style="transition-delay: 0.2s">
            <div class="card-header-custom">
                <i class="bi bi-calendar3"></i> Médias Diárias de <?= date('m/Y', mktime(0,0,0,$month,1,$year)) ?>
            </div>
            <div class="card-body">
                <?php if (empty($resumoMensal)): ?>
                    <p class="text-muted text-center py-5">Nenhum dado encontrado para este período.</p>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                        <table class="table-weather table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Média</th>
                                    <th>Máxima</th>
                                    <th>Dir. Máx</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resumoMensal as $row): ?>
                                <tr class="<?= ($row['max_num'] ?? 0) >= 40 ? 'bg-danger bg-opacity-10' : '' ?>">
                                    <td class="small"><?php $iso = \DateTime::createFromFormat('!d/m/Y', $row['data'])->format('Y-m-d'); ?><a href="<?= htmlspecialchars($linkDia($iso)) ?>" title="Ver o vento hora a hora deste dia"><?= $row['data'] ?></a></td>
                                    <td><?= $row['avg'] ?> <small>km/h</small></td>
                                    <td class="fw-bold text-accent-warm"><?= $row['max'] ?> <small>km/h</small></td>
                                    <td><span class="badge bg-light text-dark border"><?= $row['direcao'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
