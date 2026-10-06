<?php
/** @var array $chuvaAuto */
/** @var array $chuvaManual */
/** @var string $local */
/** @var int $year */
/** @var int $month */

?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-6">
        <h4 class="mb-0">
            <i class="bi bi-cloud-rain-heavy-fill text-primary"></i> Índice Pluviométrico
            <small class="text-muted ms-2" style="font-size: 0.75rem; font-weight: 400;">(Dados atualizados a cada 1h)</small>
        </h4>
    </div>
    <div class="col-md-6">
        <form action="<?= url('/inicio-chuva') ?>" method="GET" class="row g-2 justify-content-md-end">
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

<div class="weather-card fade-in-up">
    <div class="card-body p-0">
        <!-- Tabs Header -->
        <ul class="nav nav-tabs nav-justified border-0" id="rainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active py-3 border-0 rounded-0" id="auto-tab" data-bs-toggle="tab" data-bs-target="#auto-content" type="button" role="tab">
                    <i class="bi bi-robot me-2"></i>Coleta Automatizada
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3 border-0 rounded-0" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual-content" type="button" role="tab">
                    <i class="bi bi-hand-index-thumb me-2"></i>Pluviômetro Manual
                </button>
            </li>
        </ul>

        <!-- Tabs Content -->
        <div class="tab-content border-top border-secondary border-opacity-25" id="rainTabsContent">
            
            <!-- Tab Automática (Primeira) -->
            <div class="tab-pane fade show active p-4" id="auto-content" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-0 text-white-50"><?= $month === 'all' ? 'Resumo Anual' : 'Resumo Mensal' ?> (Automático)</h5>
                        <small class="text-muted"><?= $meses[$month] ?> / <?= $year ?></small>
                    </div>
                    <div class="text-end">
                        <span class="fs-2 fw-bold text-primary"><?= $chuvaAuto['total'] ?></span>
                        <span class="text-muted ms-1">mm total</span>
                    </div>
                </div>

                <?php if (!empty($chuvaAuto['detalhe'])): ?>
                <div class="table-responsive">
                    <table class="table-weather">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Total Acumulado (Dia)</th>
                                <th>Local</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($chuvaAuto['detalhe'] as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['dia']) ?></td>
                                <td><strong><?= htmlspecialchars($row['chuva']) ?> mm</strong></td>
                                <td>Sede</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-cloud-slash fs-1 text-muted opacity-25"></i>
                        <p class="mt-3 text-muted">Nenhum dado automatizado encontrado para este período.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab Manual (Segunda) -->
            <div class="tab-pane fade p-4" id="manual-content" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-0 text-white-50"><?= $month === 'all' ? 'Resumo Anual' : 'Resumo Mensal' ?> (Manual)</h5>
                        <small class="text-muted"><?= $meses[$month] ?> / <?= $year ?></small>
                    </div>
                    <div class="text-end">
                        <span class="fs-2 fw-bold text-accent"><?= $chuvaManual['total'] ?></span>
                        <span class="text-muted ms-1">mm total</span>
                    </div>
                </div>

                <div class="alert alert-info small py-2 mb-4">
                    <i class="bi bi-info-circle me-1"></i>
                    Os valores do pluviômetro manual informados referem-se à chuva acumulada em 24 horas,
                    aferida entre às 7 e às 9h da manhã do dia seguinte.
                </div>

                <?php if (!empty($chuvaManual['detalhe'])): ?>
                <div class="table-responsive">
                    <table class="table-weather">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Leitura (mm)</th>
                                <th>pH</th>
                                <th>Observações</th>
                                <th>Local</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($chuvaManual['detalhe'] as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['dia']) ?></td>
                                <td><strong><?= htmlspecialchars($row['chuva']) ?> mm</strong></td>
                                <td><span class="badge bg-secondary opacity-75"><?= htmlspecialchars($row['ph']) ?></span></td>
                                <td class="small text-muted"><?= htmlspecialchars($row['obs']) ?></td>
                                <td>Sede</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-cloud-slash fs-1 text-muted opacity-25"></i>
                        <p class="mt-3 text-muted">Nenhum dado manual encontrado para este período.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- ============ SEÇÃO DE ESTATÍSTICAS E RECORDES ============ -->
<div class="row g-4 mt-2">
    <!-- Status Atual e Nota Agrícola -->
    <div class="col-12">
        <div class="alert bg-primary bg-opacity-10 border border-primary border-opacity-20 d-flex align-items-center justify-content-between p-4 fade-in-up">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-25 p-3 rounded-circle text-primary">
                    <i class="bi bi-calendar-check fs-3"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold">Estamos há <span class="text-primary"><?= $estatisticas['current_drought'] ?> dias</span> sem chuva acumulada.</h5>
                    <p class="mb-0 text-muted small">Última leitura significativa detectada pelos sensores automáticos.</p>
                </div>
            </div>
            <div class="d-none d-md-block text-end opacity-75" style="max-width: 300px;">
                <i class="bi bi-info-circle me-1"></i>
                <small>Para a agricultura, chuvas acima de <strong>10mm</strong> são essenciais para infiltração profunda no solo.</small>
            </div>
        </div>
    </div>

    <!-- Card de Estiagem -->
    <div class="col-lg-5">
        <div class="weather-card h-100 mb-0">
            <div class="card-header-custom" style="background: var(--gradient-warm);">
                <i class="bi bi-sun-fill"></i>
                Recorde de Estiagem Agrícola
                <span class="badge-period">Histórico</span>
            </div>
            <div class="card-body text-center py-4">
                <div class="display-1 fw-bold text-accent mb-0"><?= $estatisticas['max_estiagem']['dias'] ?></div>
                <div class="text-uppercase tracking-wider fw-bold text-muted mb-1" style="font-size: 0.8rem;">Dias sem Chuva > 10mm</div>
                <div class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 mb-3 px-3">Critério Agrícola</div>
                
                <div class="d-flex justify-content-center gap-4 border-top border-secondary border-opacity-10 pt-3">
                    <div class="text-start">
                        <small class="d-block text-muted">Início</small>
                        <span class="fw-semibold text-dark"><?= $estatisticas['max_estiagem']['inicio'] ?></span>
                    </div>
                    <div class="text-start">
                        <small class="d-block text-muted">Fim</small>
                        <span class="fw-semibold text-dark"><?= $estatisticas['max_estiagem']['fim'] ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card de Recordes de Chuva -->
    <div class="col-lg-7">
        <div class="weather-card h-100 mb-0">
            <div class="card-header-custom">
                <i class="bi bi-trophy-fill text-warning"></i>
                Maiores Chuvas em 24h
                <span class="badge-period">Geral</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-borderless align-middle mb-0">
                        <thead>
                            <tr class="text-muted small text-uppercase">
                                <th>Posição</th>
                                <th class="text-center">Data</th>
                                <th class="text-end">Volume / Impacto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $pos = 1;
                            foreach ($estatisticas['top_chuva'] as $top): 
                                $medal = '';
                                if ($pos == 1) $medal = 'text-warning';
                                else if ($pos == 2) $medal = 'text-secondary';
                                else if ($pos == 3) $medal = 'text-danger opacity-75';
                            ?>
                            <tr class="border-bottom border-light border-opacity-10">
                                <td class="py-2">
                                    <span class="fw-bold <?= $medal ?>">#<?= $pos++ ?></span>
                                </td>
                                <td class="text-center"><?= date('d/m/Y', strtotime($top['data'])) ?></td>
                                <td class="text-end">
                                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2">
                                        <?= number_format($top['total'], 2) ?> mm
                                    </span>
                                    <?php if ($top['total'] >= 10): ?>
                                        <i class="bi bi-patch-check-fill text-success ms-1" title="Chuva Boa (Agrícola)"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.weather-card .display-1 { font-size: 5rem; line-height: 1; }
.tracking-wider { letter-spacing: 2px; }
</style>

<style>
#rainTabs .nav-link {
    color: var(--text-muted);
    background: #f1f3f5;
    transition: all 0.2s ease;
    border-bottom: 2px solid #dee2e6 !important;
    border-right: 1px solid #dee2e6;
    font-weight: 500;
}
#rainTabs .nav-link:hover {
    color: var(--text-main);
    background: #e9ecef;
}
#rainTabs .nav-link.active {
    color: var(--primary) !important;
    background: #fff !important;
    border-bottom: 2px solid var(--primary) !important;
    font-weight: 700;
}
#manual-tab.active {
    color: var(--accent) !important;
    background: #fff !important;
    border-bottom: 2px solid var(--accent) !important;
}
</style>
