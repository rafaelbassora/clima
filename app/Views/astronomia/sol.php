<?php
use App\Services\AstronomiaService as Astro;

/** @var array $hoje @var array $dias @var ?array $maisLongo @var ?array $maisCurto @var ?int $diferenca */
$hm = fn ($ts) => $ts ? date('H:i', $ts) : '--';
$semanaLonga = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
$semana = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
$hojeData = date('Y-m-d');
?>

<nav aria-label="Você está em" class="mb-3">
    <ol class="breadcrumb breadcrumb-astro mb-0">
        <li class="breadcrumb-item"><a href="<?= url('/inicio') ?>"><i class="bi bi-house-door" aria-hidden="true"></i> Início</a></li>
        <li class="breadcrumb-item"><a href="<?= url('/previsao-do-tempo') ?>?local=1">Previsão do Tempo</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nascer e Pôr do Sol</li>
    </ol>
</nav>

<header class="mb-4">
    <h1 class="h3 fw-bold mb-1"><i class="bi bi-sunrise-fill text-warning" aria-hidden="true"></i>
        <?= $mesEspecifico ? 'Nascer e pôr do sol em ' . htmlspecialchars($nomeMes) . ' de ' . $ano : 'Nascer e pôr do sol hoje em ' . htmlspecialchars($cidade) ?>
    </h1>
    <p class="text-muted small mb-0">Horários calculados para <?= htmlspecialchars($cidade) ?>, no horário de Brasília.</p>
</header>

<section class="weather-card mb-4 fade-in-up" aria-labelledby="sol-hoje-titulo">
    <div class="card-body p-4">
        <h2 id="sol-hoje-titulo" class="h6 text-uppercase text-muted fw-bold mb-3">
            Hoje, <?= date('d/m/Y') ?> (<?= $semanaLonga[(int) date('w')] ?>)
        </h2>
        <div class="row g-3 text-center astro-sol-hoje">
            <div class="col-6 col-md-3">
                <i class="bi bi-sunrise text-warning" aria-hidden="true"></i>
                <div class="small text-muted">Nascer do sol</div>
                <div class="astro-sol-hora"><?= $hm($hoje['nascer']) ?></div>
                <div class="small text-muted">clareia às <?= $hm($hoje['claro_inicio']) ?></div>
            </div>
            <div class="col-6 col-md-3">
                <i class="bi bi-sunset" style="color:#e8590c" aria-hidden="true"></i>
                <div class="small text-muted">Pôr do sol</div>
                <div class="astro-sol-hora"><?= $hm($hoje['por']) ?></div>
                <div class="small text-muted">escurece às <?= $hm($hoje['claro_fim']) ?></div>
            </div>
            <div class="col-6 col-md-3">
                <i class="bi bi-hourglass-split text-primary" aria-hidden="true"></i>
                <div class="small text-muted">Duração do dia</div>
                <div class="astro-sol-hora"><?= Astro::formatarDuracao($hoje['duracao']) ?></div>
                <div class="small text-muted">
                    <?php if ($diferenca !== null): ?>
                        <?php $m = (int) round(abs($diferenca) / 60); $s = abs($diferenca) % 60; ?>
                        <?= $diferenca >= 0 ? '+' : '−' ?><?= $m >= 1 ? $m . ' min' : $s . ' s' ?> em relação a ontem
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <i class="bi bi-brightness-high-fill text-warning" aria-hidden="true"></i>
                <div class="small text-muted">Sol a pino</div>
                <div class="astro-sol-hora"><?= $hm($hoje['meio_dia']) ?></div>
                <div class="small text-muted">meio-dia solar</div>
            </div>
        </div>
    </div>
</section>

<?php if ($maisLongo && $maisCurto): ?>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="weather-card h-100 fade-in-up"><div class="card-body p-3 d-flex gap-3 align-items-center">
            <i class="bi bi-sun-fill fs-2 text-warning" aria-hidden="true"></i>
            <div>
                <div class="small text-muted">Dia mais longo de <?= $ano ?></div>
                <strong><?= date('d/m', $maisLongo['dia']) ?></strong> — <?= Astro::formatarDuracao($maisLongo['duracao']) ?> de sol
                <span class="small text-muted">(<?= $hm($maisLongo['nascer']) ?> às <?= $hm($maisLongo['por']) ?>)</span>
            </div>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="weather-card h-100 fade-in-up"><div class="card-body p-3 d-flex gap-3 align-items-center">
            <i class="bi bi-cloud-sun fs-2 text-secondary" aria-hidden="true"></i>
            <div>
                <div class="small text-muted">Dia mais curto de <?= $ano ?></div>
                <strong><?= date('d/m', $maisCurto['dia']) ?></strong> — <?= Astro::formatarDuracao($maisCurto['duracao']) ?> de sol
                <span class="small text-muted">(<?= $hm($maisCurto['nascer']) ?> às <?= $hm($maisCurto['por']) ?>)</span>
            </div>
        </div></div>
    </div>
</div>
<?php endif; ?>

<section class="weather-card mb-4 fade-in-up" aria-labelledby="tabela-titulo">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 id="tabela-titulo" class="h5 fw-bold mb-0">Nascer e pôr do sol em <?= htmlspecialchars($nomeMes) ?> de <?= $ano ?></h2>
            <div class="d-flex gap-2">
                <?php if ($navegacao['anterior']): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($navegacao['anterior']['url']) ?>" rel="prev"><i class="bi bi-chevron-left" aria-hidden="true"></i> <?= htmlspecialchars($navegacao['anterior']['rotulo']) ?></a>
                <?php endif; ?>
                <?php if ($navegacao['proximo']): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($navegacao['proximo']['url']) ?>" rel="next"><?= htmlspecialchars($navegacao['proximo']['rotulo']) ?> <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                <?php endif; ?>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle astro-tabela mb-0">
                <thead>
                    <tr>
                        <th scope="col">Dia</th>
                        <th scope="col" class="d-none d-md-table-cell">Clareia</th>
                        <th scope="col"><i class="bi bi-sunrise" aria-hidden="true"></i> Nascer</th>
                        <th scope="col"><i class="bi bi-sunset" aria-hidden="true"></i> Pôr</th>
                        <th scope="col" class="d-none d-md-table-cell">Escurece</th>
                        <th scope="col">Duração</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dias as $d => $s):
                        $ts = mktime(12, 0, 0, $mes, $d, $ano);
                        $ehHoje = date('Y-m-d', $ts) === $hojeData;
                    ?>
                    <tr<?= $ehHoje ? ' class="table-primary"' : '' ?><?= $ehHoje ? ' aria-current="date"' : '' ?>>
                        <th scope="row"><?= str_pad((string) $d, 2, '0', STR_PAD_LEFT) ?> <span class="text-muted small fw-normal"><?= $semana[(int) date('w', $ts)] ?></span></th>
                        <td class="d-none d-md-table-cell text-muted"><?= $hm($s['claro_inicio']) ?></td>
                        <td class="fw-semibold"><?= $hm($s['nascer']) ?></td>
                        <td class="fw-semibold"><?= $hm($s['por']) ?></td>
                        <td class="d-none d-md-table-cell text-muted"><?= $hm($s['claro_fim']) ?></td>
                        <td><?= Astro::formatarDuracao($s['duracao']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="weather-card mb-4 fade-in-up astro-texto" aria-labelledby="sobre-sol-titulo">
    <div class="card-body p-4">
        <h2 id="sobre-sol-titulo" class="h5 fw-bold mb-3">Como ler estes horários</h2>
        <p><strong>Nascer</strong> e <strong>pôr do sol</strong> são os momentos em que a borda superior do sol cruza o horizonte.
            <strong>Clareia</strong> e <strong>escurece</strong> marcam o crepúsculo civil, quando o sol está até 6° abaixo do horizonte e
            ainda há luz natural suficiente para atividades ao ar livre. O <strong>sol a pino</strong> é o meio-dia solar, quando o sol
            atinge o ponto mais alto do céu.</p>
        <p>Em <?= htmlspecialchars($cidade) ?>, perto do Trópico de Capricórnio, a duração do dia varia cerca de duas horas e meia ao longo
            do ano: os dias mais longos ficam perto do solstício de verão, em dezembro, e os mais curtos perto do solstício de inverno, em junho.</p>
        <p class="mb-0">Os horários são calculados para as coordenadas da estação meteorológica e podem variar alguns minutos com o relevo
            e a altitude. Veja também as <a href="<?= url('/fases-da-lua') ?>">fases da lua</a> e a
            <a href="<?= url('/previsao-do-tempo') ?>?local=1">previsão do tempo</a> para <?= htmlspecialchars($cidade) ?>.</p>
    </div>
</section>
