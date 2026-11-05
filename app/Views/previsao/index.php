<?php
/** @var array $previsoes */
/** @var string $local */
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-6">
        <h4 class="mb-0"><i class="bi bi-umbrella-fill text-primary"></i> Previsão do Tempo</h4>
        <small class="text-muted">Dados fornecidos pelo <?= htmlspecialchars($previsoes[0]['fonte'] ?? 'CPTEC/INPE') ?></small>
    </div>
    <div class="col-md-6 text-md-end">
        <?php if (!empty($previsoes)): ?>
            <span class="badge bg-light text-dark border border-secondary border-opacity-25">
                <i class="bi bi-clock-history me-1"></i> Atualizado em: <?= $previsoes[0]['atualizado'] ?>
            </span>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($previsoes)): ?>
    <div class="weather-card text-center py-5 fade-in-up">
        <i class="bi bi-cloud-slash display-1 text-muted opacity-25"></i>
        <h5 class="mt-3 text-muted">Nenhuma previsão disponível no momento.</h5>
        <p class="small text-muted">Aguardando sincronização com a API do CPTEC/INPE ou INMET.</p>
    </div>
<?php else: ?>
    <!-- Próximos Dias -->
    <div class="row g-3">
        <?php foreach ($previsoes as $i => $p): 
            $isToday = ($i === 0);
            $cardClass = $isToday ? 'border-primary' : '';
            $bgToday = $isToday ? 'bg-primary bg-opacity-10' : '';
        ?>
        <div class="<?= $isToday ? 'col-12 col-lg-4' : 'col-6 col-lg-2' ?>">
            <div class="weather-card h-100 fade-in-up <?= $cardClass ?> <?= $bgToday ?>" style="transition-delay: <?= $i * 0.1 ?>s">
                <div class="card-body text-center d-flex flex-column h-100 justify-content-between">
                    <div>
                        <div class="text-uppercase tracking-wider fw-bold text-muted small mb-1">
                            <?= $isToday ? '<span class="badge bg-primary me-1">Hoje</span>' : '' ?>
                            <?= $p['semana'] ?>
                        </div>
                        <div class="fs-5 fw-bold text-dark mb-3"><?= $p['data_fmt'] ?></div>
                        
                        <div class="weather-icon-lg mb-3">
                            <i class="bi <?= $p['icon'] ?> text-primary display-4"></i>
                        </div>
                        
                        <div class="fw-semibold text-muted small px-2 mb-3" style="min-height: 40px; line-height: 1.2;">
                            <?= $p['tempo'] ?>
                        </div>
                    </div>

                    <div class="mt-auto">
                        <div class="d-flex justify-content-center gap-3 mb-2">
                            <div class="text-center">
                                <small class="d-block text-muted">Mín</small>
                                <span class="fw-bold text-primary"><?= $p['minima'] ?>°</span>
                            </div>
                            <div class="text-center border-start ps-3 border-secondary border-opacity-10">
                                <small class="d-block text-muted">Máx</small>
                                <span class="fw-bold text-accent-warm"><?= $p['maxima'] ?>°</span>
                            </div>
                        </div>

                        <?php if ($isToday):
                            $solHoje = \App\Services\AstronomiaService::sol();
                            $luaHoje = \App\Services\AstronomiaService::lua();
                        ?>
                        <div class="forecast-astro justify-content-center border-top border-secondary border-opacity-10 pt-2 mt-2 mb-0">
                            <?php if ($solHoje['nascer'] && $solHoje['por']): ?>
                                <span title="Nascer do sol"><i class="bi bi-sunrise" aria-hidden="true"></i> <span class="visually-hidden">Nascer do sol:</span> <?= date('H:i', $solHoje['nascer']) ?></span>
                                <span title="Pôr do sol"><i class="bi bi-sunset" aria-hidden="true"></i> <span class="visually-hidden">Pôr do sol:</span> <?= date('H:i', $solHoje['por']) ?></span>
                            <?php endif; ?>
                            <span title="Fase da lua: <?= htmlspecialchars($luaHoje['nome']) ?>, <?= $luaHoje['iluminacao'] ?>% iluminada">
                                <span class="lua-emoji<?= \App\Services\AstronomiaService::hemisferioSul() ? ' lua-sul' : '' ?>" aria-hidden="true"><?= $luaHoje['emoji'] ?></span>
                                <?= htmlspecialchars($luaHoje['nome']) ?> <span class="text-muted">· <?= $luaHoje['iluminacao'] ?>%</span>
                            </span>
                        </div>
                        <?php endif; ?>

                        <?php if ($p['iuv'] > 0): ?>
                        <div class="badge <?= $p['iuv'] > 5 ? 'bg-danger' : 'bg-success' ?> bg-opacity-10 <?= $p['iuv'] > 5 ? 'text-danger' : 'text-success' ?> w-100 py-2 mt-2">
                            Índice UV: <?= $p['iuv'] ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Previsão Estendida -->
    <?php if (!empty($extendidas)): ?>
        <div class="text-center mt-5 mb-4">
            <button class="btn btn-outline-primary px-4 py-2 rounded-pill fw-bold shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#extendedForecast" aria-expanded="false" aria-controls="extendedForecast">
                <i class="bi bi-chevron-expand me-2"></i> Ver Previsão Estendida (Próximas Semanas)
            </button>
        </div>

        <div class="collapse" id="extendedForecast">
            <div class="row g-3">
                <?php foreach ($extendidas as $i => $p): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="weather-card h-100 border-dashed">
                        <div class="card-body text-center p-3">
                            <small class="text-muted d-block fw-bold"><?= $p['semana'] ?></small>
                            <div class="fw-bold mb-2"><?= $p['data_fmt'] ?></div>
                            <i class="bi <?= $p['icon'] ?> text-secondary fs-4 d-block mb-2"></i>
                            <div class="small fw-semibold text-muted mb-2"><?= $p['tempo'] ?></div>
                            <div class="d-flex justify-content-center gap-2 small">
                                <span class="text-primary"><?= $p['minima'] ?>°</span>
                                <span class="text-accent-warm"><?= $p['maxima'] ?>°</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="alert alert-info mt-5 border-0 bg-info bg-opacity-10 d-flex gap-3 align-items-center">
        <i class="bi bi-info-circle-fill text-info fs-4"></i>
        <div class="small">
            <strong>Nota:</strong> A previsão é atualizada automaticamente de acordo com as janelas de processamento do CPTEC/INPE ou INMET para a cidade de <strong><?= $previsoes[0]['cidade'] ?></strong>.
        </div>
    </div>
<?php endif; ?>
