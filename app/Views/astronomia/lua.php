<?php
use App\Controllers\AstronomiaController as AC;

/** @var array $hoje @var array $proximas @var array $dias @var array $fasesMes @var array $fasesAno */
$sul = !empty($hemisferioSul) ? ' lua-sul' : '';
$emoji = fn (string $e, string $extra = '') => '<span class="lua-emoji' . $sul . $extra . '" aria-hidden="true">' . $e . '</span>';
$semana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
$semanaLonga = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
$pesca = fn (string $nome) => AC::PESCA_POR_FASE[$nome] ?? [2, 'Regular'];
$hojeData = date('Y-m-d');
?>

<nav aria-label="Você está em" class="mb-3">
    <ol class="breadcrumb breadcrumb-astro mb-0">
        <li class="breadcrumb-item"><a href="<?= url('/inicio') ?>"><i class="bi bi-house-door" aria-hidden="true"></i> Início</a></li>
        <li class="breadcrumb-item"><a href="<?= url('/previsao-do-tempo') ?>?local=1">Previsão do Tempo</a></li>
        <li class="breadcrumb-item active" aria-current="page">Fases da Lua</li>
    </ol>
</nav>

<header class="mb-4">
    <h1 class="h3 fw-bold mb-1"><i class="bi bi-moon-stars-fill text-primary" aria-hidden="true"></i>
        <?= $mesEspecifico ? 'Fases da Lua em ' . htmlspecialchars($nomeMes) . ' de ' . $ano : 'Fase da Lua hoje em ' . htmlspecialchars($cidade) ?>
    </h1>
    <p class="text-muted small mb-0">Calendário lunar calculado para <?= htmlspecialchars($cidade) ?>. Horários de Brasília.</p>
</header>

<div class="row g-3 mb-4">
    <!-- Lua de hoje -->
    <div class="col-lg-5">
        <section class="weather-card h-100 fade-in-up" aria-labelledby="lua-hoje-titulo">
            <div class="card-body p-4 text-center">
                <h2 id="lua-hoje-titulo" class="h6 text-uppercase text-muted fw-bold mb-3">
                    Hoje, <?= date('d/m/Y') ?> (<?= $semanaLonga[(int) date('w')] ?>)
                </h2>
                <div class="astro-lua-grande"><?= $emoji($hoje['emoji']) ?></div>
                <div class="fs-3 fw-bold mt-2"><?= htmlspecialchars($hoje['nome']) ?></div>
                <div class="text-muted">
                    <?= $hoje['iluminacao'] ?>% iluminada · <?= number_format($hoje['idade'], 1, ',', '') ?> dias desde a lua nova
                    <?php if ($hoje['principal'] && $hoje['instante']): ?>
                        <br><?= htmlspecialchars($hoje['nome']) ?> exata às <strong><?= date('H:i', $hoje['instante']) ?></strong>
                    <?php endif; ?>
                </div>
                <?php [$nivel, $rotulo] = $pesca($hoje['nome']); ?>
                <div class="astro-pesca astro-pesca-<?= $nivel ?> mt-3">
                    <i class="bi bi-water" aria-hidden="true"></i> Pesca (tradição popular): <strong><?= $rotulo ?></strong>
                </div>
            </div>
        </section>
    </div>

    <!-- Próximas fases -->
    <div class="col-lg-7">
        <section class="weather-card h-100 fade-in-up" aria-labelledby="proximas-titulo">
            <div class="card-body p-4">
                <h2 id="proximas-titulo" class="h6 text-uppercase text-muted fw-bold mb-3">Próximas fases</h2>
                <ul class="list-unstyled astro-proximas mb-0">
                    <?php foreach ($proximas as $f): ?>
                    <li>
                        <?= $emoji($f['emoji']) ?>
                        <div>
                            <strong><?= htmlspecialchars($f['nome']) ?></strong>
                            <span class="d-block small text-muted">
                                <?= $semanaLonga[(int) date('w', $f['ts'])] ?>, <?= date('d/m/Y', $f['ts']) ?> às <?= date('H:i', $f['ts']) ?>
                            </span>
                        </div>
                        <span class="badge rounded-pill bg-light text-dark border ms-auto">
                            <?php $dd = (int) floor((strtotime(date('Y-m-d', $f['ts'])) - strtotime($hojeData)) / 86400); ?>
                            <?= $dd === 0 ? 'hoje' : ($dd === 1 ? 'amanhã' : "em {$dd} dias") ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    </div>
</div>

<!-- Aviso: legislação e piracema -->
<div class="alert <?= $piracemaAgora ? 'alert-warning' : 'alert-info' ?> d-flex gap-3 align-items-start" role="note">
    <i class="bi bi-exclamation-triangle-fill fs-4" aria-hidden="true"></i>
    <div class="small">
        <?php if ($piracemaAgora): ?>
            <strong>Atenção: período de piracema.</strong>
        <?php else: ?>
            <strong>Antes de pescar:</strong>
        <?php endif; ?>
        Observe sempre a legislação de pesca e os períodos de defeso. Na bacia do Rio Paraná, onde fica <?= htmlspecialchars($cidade) ?>,
        a piracema (época de reprodução dos peixes) costuma ir de <strong>1º de novembro a 28 de fevereiro</strong>,
        com restrições e proibições de pesca. As regras podem mudar: confira as normas vigentes no
        <a href="https://www.gov.br/ibama" target="_blank" rel="noopener">IBAMA</a> e com a Polícia Militar Ambiental antes de sair para pescar.
        A relação entre lua e pesca é tradição popular, sem comprovação científica consistente.
    </div>
</div>

<!-- Calendário do mês -->
<section class="weather-card mb-4 fade-in-up" aria-labelledby="calendario-titulo">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 id="calendario-titulo" class="h5 fw-bold mb-0">Calendário lunar de <?= htmlspecialchars($nomeMes) ?> de <?= $ano ?></h2>
            <div class="d-flex gap-2">
                <?php if ($navegacao['anterior']): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($navegacao['anterior']['url']) ?>" rel="prev"><i class="bi bi-chevron-left" aria-hidden="true"></i> <?= htmlspecialchars($navegacao['anterior']['rotulo']) ?></a>
                <?php endif; ?>
                <?php if ($navegacao['proximo']): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($navegacao['proximo']['url']) ?>" rel="next"><?= htmlspecialchars($navegacao['proximo']['rotulo']) ?> <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($fasesMes): ?>
        <p class="small text-muted mb-3">
            <?php foreach ($fasesMes as $i => $f): ?><?= $i ? ' · ' : '' ?><?= $emoji($f['emoji']) ?> <?= htmlspecialchars($f['nome']) ?>: <strong><?= date('d/m', $f['ts']) ?> às <?= date('H:i', $f['ts']) ?></strong><?php endforeach; ?>
        </p>
        <?php endif; ?>

        <div class="astro-calendario" role="table" aria-label="Fase da lua em cada dia de <?= htmlspecialchars($nomeMes) ?> de <?= $ano ?>">
            <div class="astro-cal-linha" role="row">
                <?php foreach ($semana as $s): ?><div class="astro-cal-semana" role="columnheader"><?= $s ?></div><?php endforeach; ?>
            </div>
            <?php
            $primeiroDiaSemana = (int) date('w', mktime(0, 0, 0, $mes, 1, $ano));
            $celulas = array_merge(array_fill(0, $primeiroDiaSemana, null), array_keys($dias));
            while (count($celulas) % 7) { $celulas[] = null; }
            foreach (array_chunk($celulas, 7) as $linha): ?>
            <div class="astro-cal-linha" role="row">
                <?php foreach ($linha as $d):
                    if ($d === null): ?><div class="astro-cal-dia vazio" role="cell"></div><?php continue; endif;
                    $l = $dias[$d];
                    $ehHoje = sprintf('%04d-%02d-%02d', $ano, $mes, $d) === $hojeData;
                    [$nivel, $rotulo] = $pesca($l['nome']);
                ?>
                <div class="astro-cal-dia<?= $l['principal'] ? ' principal' : '' ?><?= $ehHoje ? ' hoje' : '' ?>" role="cell"
                     title="<?= $d ?>/<?= $mes ?>: <?= htmlspecialchars($l['nome']) ?>, <?= $l['iluminacao'] ?>% iluminada — pesca (tradição): <?= $rotulo ?>">
                    <span class="astro-cal-num"><?= $d ?></span>
                    <?= $emoji($l['emoji']) ?>
                    <span class="astro-cal-nome"><?= htmlspecialchars($l['nome']) ?></span>
                    <span class="astro-cal-info">
                        <?= $l['principal'] && $l['instante'] ? date('H:i', $l['instante']) : $l['iluminacao'] . '%' ?>
                    </span>
                    <span class="astro-pesca-ponto astro-pesca-<?= $nivel ?>" aria-hidden="true"></span>
                    <span class="visually-hidden"><?= htmlspecialchars($l['nome']) ?>, <?= $l['iluminacao'] ?>% iluminada, pesca <?= $rotulo ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="small text-muted mt-3 mb-0 d-flex flex-wrap gap-3 align-items-center">
            <span>Pesca pela tradição popular:</span>
            <span><span class="astro-pesca-ponto astro-pesca-4"></span> Ótima</span>
            <span><span class="astro-pesca-ponto astro-pesca-3"></span> Boa</span>
            <span><span class="astro-pesca-ponto astro-pesca-2"></span> Regular</span>
            <span><span class="astro-pesca-ponto astro-pesca-1"></span> Fraca</span>
        </p>
    </div>
</section>

<!-- Tabela do ano -->
<section class="weather-card mb-4 fade-in-up" aria-labelledby="ano-titulo">
    <div class="card-body p-3 p-md-4">
        <h2 id="ano-titulo" class="h5 fw-bold mb-3">Fases da lua em <?= $ano ?></h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle astro-tabela mb-0">
                <thead>
                    <tr>
                        <th scope="col">Mês</th>
                        <?php foreach (\App\Services\AstronomiaService::FASES_PRINCIPAIS as [$nome, $e]): ?>
                            <th scope="col"><?= $emoji($e) ?> <?= htmlspecialchars($nome) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (AC::MESES as $m => $nm):
                        $porTipo = [];
                        foreach ($fasesAno[$m] ?? [] as $f) { $porTipo[$f['tipo']][] = $f; }
                    ?>
                    <tr<?= $m === $mes ? ' class="table-active"' : '' ?>>
                        <th scope="row"><a href="/fases-da-lua?ano=<?= $ano ?>&amp;mes=<?= $m ?>"><?= ucfirst($nm) ?></a></th>
                        <?php for ($t = 0; $t < 4; $t++): ?>
                            <td>
                                <?php foreach ($porTipo[$t] ?? [] as $i => $f): ?>
                                    <?= $i ? '<br>' : '' ?><?= date('d/m', $f['ts']) ?> <span class="text-muted small"><?= date('H:i', $f['ts']) ?></span>
                                <?php endforeach; ?>
                                <?= empty($porTipo[$t]) ? '<span class="text-muted">—</span>' : '' ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Texto explicativo -->
<section class="weather-card mb-4 fade-in-up astro-texto" aria-labelledby="sobre-titulo">
    <div class="card-body p-4">
        <h2 id="sobre-titulo" class="h5 fw-bold mb-3">Sobre as fases da lua</h2>
        <p>A lua leva cerca de <strong>29,5 dias</strong> para completar um ciclo de fases (a lunação). As quatro fases principais são a
            <strong>lua nova</strong> (o lado iluminado fica de costas para a Terra), o <strong>quarto crescente</strong>, a
            <strong>lua cheia</strong> e o <strong>quarto minguante</strong>, separadas por cerca de 7 dias e 9 horas. Entre elas, a lua
            passa pelas fases intermediárias: crescente, crescente gibosa, minguante gibosa e minguante.</p>
        <p>No hemisfério sul, como em <?= htmlspecialchars($cidade) ?>, a lua crescente aparece iluminada do lado esquerdo e a minguante
            do lado direito — o contrário do que se vê no hemisfério norte. Por isso os desenhos desta página aparecem espelhados.</p>
        <h3 class="h6 fw-bold mt-4">Lua e pesca</h3>
        <p>Pela tradição popular dos pescadores, a lua cheia e as fases crescentes são consideradas as mais favoráveis à pesca, e a
            minguante a menos favorável. É um conhecimento popular, sem comprovação científica consistente: na prática, chuva,
            temperatura da água, vento e pressão atmosférica costumam influenciar mais. Acompanhe esses dados em tempo real no
            <a href="<?= url('/inicio') ?>">painel da estação</a>.</p>
        <p class="mb-0">Os horários são calculados pelo algoritmo de Jean Meeus (<em>Astronomical Algorithms</em>), com precisão de poucos
            minutos, e estão no horário de Brasília. Veja também o <a href="<?= url('/nascer-e-por-do-sol') ?>">nascer e o pôr do sol</a>
            em <?= htmlspecialchars($cidade) ?>.</p>
    </div>
</section>
