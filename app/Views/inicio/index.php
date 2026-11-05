<?php
/** @var array $ultimaLeitura */
/** @var array $chuva7dias */
/** @var array $temperatura7dias */
/** @var array $temperatura24h */
/** @var array $umidade24h */
/** @var string $local */

$localNome = match($local) {
    '1' => 'Sede',
    '2' => 'Zona Rural',
    '3' => 'Local 3',
    '4' => 'Local 4',
    default => "Local {$local}",
};
?>

<!-- Location -->
<div class="location-header d-flex align-items-center justify-content-between">
    <div>
        <i class="bi bi-geo-alt-fill"></i> Local: <strong><?= htmlspecialchars($localNome) ?></strong>
    </div>
    <div id="mobile-data-hora" class="d-md-none small text-muted" style="font-size: 0.75rem;">
        <i class="bi bi-clock-history me-1"></i><?= $ultimaLeitura['data_hora'] ?>
    </div>
</div>

<!-- Forecast Banner -->
<div class="forecast-banner fade-in-up">
    <div class="forecast-info-main">
        <div class="forecast-title d-flex align-items-center gap-2">
            <span>Previsão hoje</span>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 fw-normal py-1 px-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                <i class="bi bi-droplet-slash me-1"></i> <?= (int) $estatisticas['current_drought'] ?> <?= (int) $estatisticas['current_drought'] === 1 ? 'dia' : 'dias' ?> sem chuva
            </span>
        </div>
        <div class="forecast-detail">
            <?php if ($previsaoHoje): ?>
                <i class="bi <?= $previsaoHoje['icon'] ?> me-1"></i>
                <?= $previsaoHoje['temp_max'] ?>° / <?= $previsaoHoje['temp_min'] ?>° · <?= $previsaoHoje['texto'] ?>
            <?php else: ?>
                <span class="text-muted small">Previsão não disponível no momento.</span>
            <?php endif; ?>
            <br><small class="text-muted">Fonte: <?= htmlspecialchars($previsaoHoje['fonte'] ?? 'CPTEC/INPE') ?></small>
        </div>
        <?php
        $solHoje = \App\Services\AstronomiaService::sol();
        $luaHoje = \App\Services\AstronomiaService::lua();
        ?>
        <div class="forecast-astro">
            <?php if ($solHoje['nascer'] && $solHoje['por']): ?>
                <span title="Nascer do sol"><i class="bi bi-sunrise" aria-hidden="true"></i> <span class="visually-hidden">Nascer do sol:</span> <?= date('H:i', $solHoje['nascer']) ?></span>
                <span title="Pôr do sol"><i class="bi bi-sunset" aria-hidden="true"></i> <span class="visually-hidden">Pôr do sol:</span> <?= date('H:i', $solHoje['por']) ?></span>
            <?php endif; ?>
            <span title="Fase da lua: <?= htmlspecialchars($luaHoje['nome']) ?>, <?= $luaHoje['iluminacao'] ?>% iluminada">
                <span class="lua-emoji<?= \App\Services\AstronomiaService::hemisferioSul() ? ' lua-sul' : '' ?>" aria-hidden="true"><?= $luaHoje['emoji'] ?></span>
                <?= htmlspecialchars($luaHoje['nome']) ?> <span class="text-muted">· <?= $luaHoje['iluminacao'] ?>%</span>
            </span>
        </div>
        <a href="/previsao-do-tempo?local=<?= htmlspecialchars($local) ?>" class="badge-link">
            Previsão 7 dias <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <div id="nowcasting-container" class="nowcasting-alert fade-in-up" style="display: <?= $nowcasting ? 'block' : 'none' ?>; background: rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); border-radius: 12px; padding: 12px 18px; margin-top: 15px;">
        <div class="d-flex align-items-center gap-3">
            <div class="nowcasting-icon" style="font-size: 1.5rem; color: #0d6efd;">
                <i class="bi bi-broadcast"></i>
            </div>
            <div class="w-100">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; font-weight: 600;">
                        Tendência do Tempo
                    </div>
                    <span id="nowcasting-time" style="font-size: 0.65rem; color: #adb5bd;">
                        <?= $nowcasting ? date('H:i', strtotime($nowcasting['created_at'])) : '' ?>
                    </span>
                </div>
                <div id="nowcasting-msg" style="color: #212529; font-size: 0.95rem; font-weight: 500;">
                    <?= $nowcasting ? htmlspecialchars($nowcasting['mensagem']) : '' ?>
                </div>
            </div>
        </div>
    </div>

    <div class="forecast-extra">
        <div class="forecast-extra-item">
            <small title="Temperatura" style="cursor:help;">TEMP.</small>
            <strong id="home-temperatura" class="text-primary"><?= number_format($ultimaLeitura['temperatura'] ?? 0, 1, ',', '.') ?>°C</strong>
        </div>
        <div class="forecast-extra-item">
            <small title="Sensação Térmica" style="cursor:help;">SENSAÇÃO</small>
            <strong id="home-sensacao" class="text-accent-warm"><?= number_format($ultimaLeitura['sensacao'] ?? 0, 1, ',', '.') ?>°C</strong>
        </div>
        <?php
            $umid = $ultimaLeitura['umidade'] ?? 0;
            $umidStatus = 'PERIGO';
            $umidColor  = 'text-danger';
            if ($umid > 60) {
                $umidStatus = 'BOA';
                $umidColor  = 'text-success';
            } elseif ($umid > 30) {
                $umidStatus = 'REGULAR';
                $umidColor  = 'text-primary';
            } elseif ($umid > 20) {
                $umidStatus = 'ATENÇÃO';
                $umidColor  = 'text-warning';
            }
        ?>
        <div class="forecast-extra-item">
            <small title="Umidade Relativa" style="cursor:help;">UMIDADE</small>
            <strong id="home-umidade-painel" class="<?= $umidColor ?>"><?= $umid ?>% <span style="font-size: 0.6rem; opacity: 0.8; font-weight: 500;" id="home-umidade-status"><?= $umidStatus ?></span></strong>
        </div>
        <div class="forecast-extra-item">
            <small title="Ponto de Orvalho" style="cursor:help;">P. ORVALHO</small>
            <strong id="home-orvalho" class="text-primary"><?= number_format($ultimaLeitura['ponto_orvalho'] ?? 0, 1, ',', '.') ?>°C</strong>
        </div>
        <div class="forecast-extra-item">
            <small title="Chuva Hoje" style="cursor:help;">CHUVA HOJE</small>
            <strong id="home-chuva-dia" class="text-info"><?= number_format($ultimaLeitura['chuva_dia'] ?? 0, 1, ',', '.') ?>mm</strong>
        </div>
        <div class="forecast-extra-item">
            <small title="Pico de Vento Hoje" style="cursor:help;">MAX VENTO</small>
            <strong id="home-vento-max" class="text-accent-warm"><?= $ultimaLeitura['vento_max_dia'] ?? 0 ?> <span style="font-size: 0.6rem;">km/h</span></strong>
        </div>
        <div class="forecast-extra-item">
            <small title="Vento Médio" style="cursor:help;">VENTO MED</small>
            <strong id="home-vento-avg" class="text-secondary"><?= $ultimaLeitura['vento_avg_dia'] ?? 0 ?> <span style="font-size: 0.6rem;">km/h</span></strong>
        </div>
        <div class="forecast-extra-item">
            <small title="Evapotranspiração" style="cursor:help;">EVAPOT.</small>
            <strong id="home-et-hoje" class="text-success"><?= number_format($ultimaLeitura['et_hoje'] ?? 0, 2, ',', '.') ?>mm</strong>
        </div>
        <div class="forecast-extra-item">
            <small title="Direção do Vento" style="cursor:help;">DIREÇÃO</small>
            <strong class="d-flex align-items-center gap-1 text-secondary">
                <span id="home-direcao-texto"><?= $ultimaLeitura['direcao'] ?? '--' ?></span>
                <i class="bi bi-arrow-up-circle-fill text-accent-warm" id="home-direcao-seta" style="display: inline-block; transform: rotate(<?= $ultimaLeitura['direcao_graus'] ?? 0 ?>deg); transition: transform 0.5s ease; font-size: 1.1rem;"></i>
            </strong>
        </div>
    </div>
</div>

<!-- Active Alerts -->
<?php if (!empty($alertasAtivos)): ?>
<div class="alerts-container mt-4 mb-2">
    <?php foreach ($alertasAtivos as $alerta): ?>
        <div class="alert alert-<?= htmlspecialchars($alerta['tipo']) ?> alert-dismissible fade show border-0 shadow-sm rounded-4 py-3" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                <div class="fw-bold"><?= nl2br(htmlspecialchars($alerta['mensagem'])) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Cards Grid -->
<div class="row g-3">

    <!-- Card 1: Pluviômetro 7 dias -->
    <div class="col-lg-6">
        <div class="weather-card fade-in-up">
            <div class="card-header-custom">
                <i class="bi bi-cloud-lightning-rain-fill"></i>
                Pluviômetro
                <span class="badge-period">7 dias*</span>
            </div>
            <div class="card-body">
                <?php if (!empty($chuva7dias)): ?>
                <div class="table-responsive">
                    <table class="table-weather">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>MM</th>
                                <th>Última</th>
                                <th>Local</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($chuva7dias as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['data']) ?></td>
                                <td><strong><?= htmlspecialchars($row['mm']) ?></strong></td>
                                <td><i class="bi bi-clock"></i> <?= htmlspecialchars($row['ultima_hora']) ?></td>
                                <td><?= htmlspecialchars($row['local']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-3">Sem dados de chuva disponíveis.</p>
                <?php endif; ?>
                <a href="/inicio-chuva?local=<?= htmlspecialchars($local) ?>" class="btn-action">
                    <i class="bi bi-search"></i> Pesquisar por Período
                </a>
            </div>
        </div>
    </div>

    <!-- Card 2: Temperatura 7 dias -->
    <div class="col-lg-6">
        <div class="weather-card fade-in-up">
            <div class="card-header-custom">
                <i class="bi bi-thermometer-sun"></i>
                Temperatura C°
                <span class="badge-period">7 dias*</span>
            </div>
            <div class="card-body">
                <?php if (!empty($temperatura7dias)): ?>
                <div class="table-responsive">
                    <table class="table-weather">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Mín</th>
                                <th>Máx</th>
                                <th>Local</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($temperatura7dias as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['data']) ?></td>
                                <td style="color: #2196f3;"><strong><?= htmlspecialchars($row['temp_min']) ?></strong></td>
                                <td style="color: #ff5722;"><strong><?= htmlspecialchars($row['temp_max']) ?></strong></td>
                                <td><?= htmlspecialchars($row['local']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-3">Sem dados de temperatura disponíveis.</p>
                <?php endif; ?>
                <a href="/inicio-temperatura?local=<?= htmlspecialchars($local) ?>" class="btn-action">
                    <i class="bi bi-search"></i> Pesquisar por Período
                </a>
            </div>
        </div>
    </div>

    <!-- Card 3: Temperatura 24h -->
    <div class="col-lg-6">
        <div class="weather-card fade-in-up">
            <div class="card-header-custom">
                <i class="bi bi-thermometer-half"></i>
                Temperatura C°
                <span class="badge-period">últimas 24h*</span>
            </div>
            <div class="card-body">
                <?php if (!empty($temperatura24h)): ?>
                <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                    <table class="table-weather">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Temp</th>
                                <th>Local</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($temperatura24h as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['hora']) ?></td>
                                <td><strong><?= htmlspecialchars($row['temp']) ?></strong></td>
                                <td><?= htmlspecialchars($row['local']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-3">Sem dados de temperatura disponíveis.</p>
                <?php endif; ?>
                <a href="/inicio-temperatura?local=<?= htmlspecialchars($local) ?>" class="btn-action">
                    <i class="bi bi-search"></i> Pesquisar por Período
                </a>
            </div>
        </div>
    </div>

    <!-- Card 4: Umidade 24h -->
    <div class="col-lg-6">
        <div class="weather-card fade-in-up">
            <div class="card-header-custom">
                <i class="bi bi-droplet-fill"></i>
                Umidade
                <span class="badge-period">últimas 24h*</span>
            </div>
            <div class="card-body">
                <?php if (!empty($umidade24h)): ?>
                <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                    <table class="table-weather">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>%</th>
                                <th>Local</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($umidade24h as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['hora']) ?></td>
                                <td><strong><?= htmlspecialchars($row['umidade']) ?>%</strong></td>
                                <td><?= htmlspecialchars($row['local']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-3">Sem dados de umidade disponíveis.</p>
                <?php endif; ?>
                <a href="/inicio-umidade?local=<?= htmlspecialchars($local) ?>" class="btn-action">
                    <i class="bi bi-search"></i> Pesquisar por Período
                </a>
            </div>
        </div>
    </div>

    <!-- Card 5: Vento 7 dias -->
    <div class="col-lg-6">
        <div class="weather-card fade-in-up">
            <div class="card-header-custom">
                <i class="bi bi-wind"></i>
                Vento (Velocidade)
                <span class="badge-period">7 dias*</span>
            </div>
            <div class="card-body">
                <?php if (!empty($vento7dias)): ?>
                <div class="table-responsive">
                    <table class="table-weather">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Média</th>
                                <th>Máxima</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vento7dias as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['data']) ?></td>
                                <td><?= htmlspecialchars($row['vento_avg']) ?> <small>km/h</small></td>
                                <td class="fw-bold text-accent-warm"><?= htmlspecialchars($row['vento_max']) ?> <small>km/h</small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-3">Sem dados de vento disponíveis.</p>
                <?php endif; ?>
                <a href="/inicio-vento?local=<?= htmlspecialchars($local) ?>" class="btn-action">
                    <i class="bi bi-search"></i> Pesquisar por Período
                </a>
            </div>
        </div>
    </div>

    <!-- Card 6: Pressão 7 dias -->
    <div class="col-lg-6">
        <div class="weather-card fade-in-up">
            <div class="card-header-custom">
                <i class="bi bi-speedometer2"></i>
                Pressão Atmosférica
                <span class="badge-period">7 dias*</span>
            </div>
            <div class="card-body">
                <?php if (!empty($pressao7dias)): ?>
                <div class="table-responsive">
                    <table class="table-weather">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Mín</th>
                                <th>Máx</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pressao7dias as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['data']) ?></td>
                                <td class="text-primary"><?= htmlspecialchars($row['pressao_min']) ?></td>
                                <td class="text-accent-warm fw-bold"><?= htmlspecialchars($row['pressao_max']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-3">Sem dados de pressão disponíveis.</p>
                <?php endif; ?>
                <a href="/inicio-pressao?local=<?= htmlspecialchars($local) ?>" class="btn-action">
                    <i class="bi bi-search"></i> Pesquisar por Período
                </a>
            </div>
        </div>
    </div>

    <!-- Card 7: Gráfico Pressão x Umidade -->
    <div class="col-lg-6">
        <div class="weather-card fade-in-up">
            <div class="card-header-custom">
                <i class="bi bi-graph-up"></i>
                Pressão x Umidade
                <span class="badge-period">últimas 24h*</span>
            </div>
            <div class="card-body">
                <canvas id="chartPressaoUmidade" height="220"></canvas>
            </div>
        </div>
    </div>

    <!-- Card 8: Gráfico Temperatura x Sensação -->
    <div class="col-lg-6">
        <div class="weather-card fade-in-up">
            <div class="card-header-custom">
                <i class="bi bi-thermometer-sun"></i>
                Temperatura x Sensação
                <span class="badge-period">últimas 24h*</span>
            </div>
            <div class="card-body">
                <canvas id="chartTempSensacao" height="220"></canvas>
            </div>
        </div>
    </div>

    <!-- Card 9: Evapotranspiração 7 dias -->
    <div class="col-lg-6">
        <div class="weather-card fade-in-up">
            <div class="card-header-custom">
                <i class="bi bi-droplet"></i>
                Evapotranspiração (ET)
                <span class="badge-period">7 dias*</span>
            </div>
            <div class="card-body">
                <?php if (!empty($et7dias)): ?>
                <div class="table-responsive">
                    <table class="table-weather">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Total (mm)</th>
                                <th>Local</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($et7dias as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['data']) ?></td>
                                <td class="text-success fw-bold"><?= htmlspecialchars($row['mm']) ?></td>
                                <td><?= htmlspecialchars($row['local']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-3">Sem dados de ET disponíveis.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Card 10: Gráfico Chuva x ET 7 dias -->
    <div class="col-lg-6">
        <div class="weather-card fade-in-up">
            <div class="card-header-custom">
                <i class="bi bi-bar-chart-line"></i>
                Chuva x Evapotranspiração
                <span class="badge-period">7 dias*</span>
            </div>
            <div class="card-body">
                <canvas id="chartChuvaEt" height="220"></canvas>
            </div>
        </div>
    </div>

</div>

<div class="text-end mb-4 px-3">
    <small class="text-muted"><i class="bi bi-info-circle"></i> * Dados de tendência e históricos atualizados a cada 1 hora para otimização do sistema.</small>
</div>

<!-- Scripts para Gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dados = <?= json_encode($dadosGraficos) ?>;
    const labels = dados.map(d => d.hora);
    
    // Gráfico 1: Pressão x Umidade
    new Chart(document.getElementById('chartPressaoUmidade'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Pressão (hPa)',
                    data: dados.map(d => d.pressao),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    yAxisID: 'yPressao',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Umidade (%)',
                    data: dados.map(d => d.hum),
                    borderColor: '#20c997',
                    backgroundColor: 'transparent',
                    yAxisID: 'yUmidade',
                    tension: 0.4,
                    borderDash: [5, 5]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { 
                legend: { 
                    position: 'top', 
                    labels: { boxWidth: 10, font: { size: 10 }, padding: 15 } 
                } 
            },
            scales: {
                yPressao: { 
                    type: 'linear', 
                    position: 'left', 
                    title: { display: true, text: 'hPa', font: { size: 10 } },
                    grid: { color: 'rgba(0,0,0,0.03)' },
                    ticks: { font: { size: 10 } }
                },
                yUmidade: { 
                    type: 'linear', 
                    position: 'right', 
                    title: { display: true, text: '%', font: { size: 10 } }, 
                    min: 0, 
                    max: 100,
                    grid: { drawOnChartArea: false },
                    ticks: { font: { size: 10 } }
                },
                x: {
                    ticks: { font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }
                }
            }
        }
    });

    // Gráfico 2: Temperatura x Sensação
    new Chart(document.getElementById('chartTempSensacao'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Temp (°C)',
                    data: dados.map(d => d.temp),
                    borderColor: '#ff5733',
                    backgroundColor: 'transparent',
                    tension: 0.4,
                    pointRadius: 2
                },
                {
                    label: 'Sensação (°C)',
                    data: dados.map(d => d.sensacao),
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { 
                legend: { 
                    position: 'top', 
                    labels: { boxWidth: 10, font: { size: 10 }, padding: 15 } 
                } 
            },
            scales: {
                y: { 
                    title: { display: true, text: '°C', font: { size: 10 } },
                    grid: { color: 'rgba(0,0,0,0.03)' },
                    ticks: { font: { size: 10 } }
                },
                x: {
                    ticks: { font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }
                }
            }
        }
    });

    // Dados 7 dias para Gráfico Chuva x ET (precisam estar em ordem cronológica)
    const chuvaData = <?= json_encode(array_reverse($chuva7dias ?? [])) ?>;
    const etData = <?= json_encode(array_reverse($et7dias ?? [])) ?>;
    
    // Garante que ambos têm os mesmos rótulos (datas)
    const labels7dias = etData.map(d => d.data);
    
    // Gráfico 3: Chuva x ET 7 Dias
    new Chart(document.getElementById('chartChuvaEt'), {
        type: 'bar',
        data: {
            labels: labels7dias,
            datasets: [
                {
                    type: 'bar',
                    label: 'Chuva (mm)',
                    data: chuvaData.map(d => parseFloat(d.mm.replace(',', '.')) || 0),
                    backgroundColor: 'rgba(13, 110, 253, 0.6)',
                    borderColor: '#0d6efd',
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    type: 'line',
                    label: 'ET (mm)',
                    data: etData.map(d => parseFloat(d.mm) || 0),
                    borderColor: '#28a745',
                    backgroundColor: '#28a745',
                    borderWidth: 2,
                    tension: 0.3,
                    yAxisID: 'y',
                    pointRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { 
                legend: { 
                    position: 'top', 
                    labels: { boxWidth: 10, font: { size: 10 }, padding: 15 } 
                } 
            },
            scales: {
                y: { 
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: 'mm', font: { size: 10 } },
                    grid: { color: 'rgba(0,0,0,0.03)' },
                    ticks: { font: { size: 10 } },
                    min: 0
                },
                x: {
                    ticks: { font: { size: 10 }, maxRotation: 45, minRotation: 45 }
                }
            }
        }
    });
});
</script>
