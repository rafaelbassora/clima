<?php
/** @var array $resumoMensal */
/** @var array $ultimos */
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
    <div class="col-md-4 text-md-end">
        <a href="<?= url('/pluviometro-cidadao/cadastro') ?>" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-plus-circle me-1"></i> Cadastrar meu Pluviômetro
        </a>
        <a href="<?= url('/pluviometro-cidadao/login') ?>" class="btn btn-outline-secondary rounded-pill px-4 mt-2 mt-md-0">
            Já sou cadastrado
        </a>
    </div>
</div>

<div class="weather-card fade-in-up mb-4">
    <div class="card-header-custom">
        <i class="bi bi-trophy-fill text-warning"></i>
        Acumulado do Mês (<?= htmlspecialchars($mesAno) ?>)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-weather">
                <thead>
                    <tr>
                        <th>Estação</th>
                        <th>Bairro</th>
                        <th>Total (mm)</th>
                        <th>Última Leitura</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($resumoMensal)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                Nenhum dado lançado este mês ainda. Seja o primeiro a cadastrar!
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($resumoMensal as $row): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($row['station_code']) ?></td>
                            <td><?= htmlspecialchars($row['bairro']) ?></td>
                            <td><strong><?= number_format((float) $row['total_mm'], 2, ',', '.') ?> mm</strong></td>
                            <td><?= date('d/m/Y', strtotime($row['ultima_data'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="weather-card fade-in-up">
    <div class="card-header-custom">
        <i class="bi bi-clock-history"></i>
        Últimos Lançamentos
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-weather">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Estação</th>
                        <th>Bairro</th>
                        <th>mm</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ultimos)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Nenhum lançamento ainda.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($ultimos as $row): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($row['data'])) ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($row['station_code']) ?></td>
                            <td><?= htmlspecialchars($row['bairro']) ?></td>
                            <td><?= number_format((float) $row['medida_mm'], 2, ',', '.') ?> mm</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
