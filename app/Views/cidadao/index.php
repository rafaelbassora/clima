<?php
/** @var float $mediaCidade */
/** @var int $totalEstacoes */
/** @var array $amostra */
/** @var string $mesAno */
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-8">
        <h4 class="mb-0">
            <i class="bi bi-people-fill text-primary"></i> Pluviômetro Cidadão
        </h4>
        <p class="text-muted small mb-0">
            Moradores e empresas de Pereira Barreto cadastram seu próprio pluviômetro e ajudam a
            mapear a chuva em todos os bairros da cidade. O endereço nunca é divulgado — cada
            estação aparece apenas com um código e o bairro.
        </p>
    </div>
    <div class="col-md-4 text-md-end d-flex flex-column flex-md-row gap-2 justify-content-md-end">
        <a href="<?= url('/pluviometro-cidadao/cadastro') ?>" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-plus-circle me-1"></i> Cadastrar meu Pluviômetro
        </a>
        <a href="<?= url('/pluviometro-cidadao/login') ?>" class="btn btn-outline-secondary rounded-pill px-4">
            Já sou cadastrado
        </a>
    </div>
</div>

<div class="alert bg-primary bg-opacity-10 border border-primary border-opacity-20 d-flex align-items-center gap-3 p-4 mb-4 fade-in-up">
    <div class="bg-primary bg-opacity-25 p-3 rounded-circle text-primary">
        <i class="bi bi-droplet-half fs-3"></i>
    </div>
    <div>
        <h5 class="mb-0 fw-bold">
            Média da cidade: <span class="text-primary"><?= number_format($mediaCidade, 2, ',', '.') ?> mm</span>
        </h5>
        <p class="mb-0 text-muted small">
            Média do acumulado de <?= $totalEstacoes ?> estação(ões) cidadã(s) em <?= htmlspecialchars($mesAno) ?>.
        </p>
    </div>
</div>

<div class="weather-card fade-in-up">
    <div class="card-header-custom">
        <i class="bi bi-geo-alt-fill"></i>
        Situação de Hoje (amostra de estações)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-weather">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Bairro</th>
                        <th>Hoje</th>
                        <th>Eventos</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($amostra)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Nenhuma estação cadastrada ainda. Seja o primeiro!
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($amostra as $row): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($row['station_code']) ?></td>
                            <td><?= htmlspecialchars($row['bairro']) ?></td>
                            <td>
                                <?php if ($row['medida_mm'] !== null): ?>
                                    <strong><?= number_format((float) $row['medida_mm'], 2, ',', '.') ?> mm</strong>
                                <?php else: ?>
                                    <span class="text-muted small">Sem registro ainda</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['granizo'])): ?><span class="badge bg-info me-1">Granizo</span><?php endif; ?>
                                <?php if (!empty($row['chuva_mansa'])): ?><span class="badge bg-secondary me-1">Mansa</span><?php endif; ?>
                                <?php if (!empty($row['chuva_forte'])): ?><span class="badge bg-danger">Forte</span><?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('/pluviometro-cidadao/estacao') ?>?codigo=<?= urlencode($row['station_code']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    Ver estação <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
