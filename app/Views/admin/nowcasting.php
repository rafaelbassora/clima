<?php if (!empty($_SESSION['nowcast_recalibrado'])): ?>
    <?php $r = $_SESSION['nowcast_recalibrado']; unset($_SESSION['nowcast_recalibrado']); ?>
    <?php if ($r['aplicado']): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-1"></i>
            Recalibrado com <?= $r['amostras'] ?> amostras (precisão geral: <?= $r['precisao_geral'] ?>%).
            Novos limiares — Alta: <?= $r['limiar_alta'] ?>, Moderada: <?= $r['limiar_moderada'] ?>, Baixa: <?= $r['limiar_baixa'] ?>.
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Não recalibrado: <?= htmlspecialchars($r['motivo']) ?> (amostras disponíveis: <?= $r['amostras'] ?>).
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h2 class="h5 fw-bold mb-2">
                    <i class="bi bi-sliders me-2"></i>Calibração Automática
                </h2>
                <p class="small text-muted mb-1">
                    Os limiares de decisão (score mínimo pra cada categoria) se ajustam sozinhos
                    toda semana, com base na precisão real das últimas previsões.
                </p>
                <p class="small mb-0">
                    <strong>Alta:</strong> score ≥ <?= $nowcastConfig['limiar_alta'] ?>
                    &nbsp;·&nbsp; <strong>Moderada:</strong> score ≥ <?= $nowcastConfig['limiar_moderada'] ?>
                    &nbsp;·&nbsp; <strong>Baixa:</strong> score ≥ <?= $nowcastConfig['limiar_baixa'] ?>
                    <br>
                    <span class="text-muted">
                        Última calibração: <?= $nowcastConfig['atualizado_em'] ? date('d/m/Y H:i', strtotime($nowcastConfig['atualizado_em'])) : 'nunca' ?>
                        (<?= (int) ($nowcastConfig['amostras_usadas'] ?? 0) ?> amostras)
                    </span>
                </p>
            </div>
            <a href="/admin/nowcasting/recalibrar" class="btn btn-primary rounded-pill px-4" onclick="return confirm('Recalibrar agora com os dados mais recentes?')">
                <i class="bi bi-arrow-repeat me-1"></i>Recalibrar Agora
            </a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <h2 class="h4 fw-bold mb-4 text-primary">
            <i class="bi bi-graph-up-arrow me-2"></i>Rating de Acerto do Nowcasting
        </h2>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-3 text-center border">
                    <div class="text-muted small text-uppercase mb-1 fw-semibold">Total de Previsões</div>
                    <div class="h2 fw-bold mb-0 text-dark"><?= $stats['geral']['total'] ?? 0 ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-success bg-opacity-10 rounded-3 text-center border border-success border-opacity-25">
                    <div class="text-success small text-uppercase mb-1 fw-semibold">Acertos</div>
                    <div class="h2 fw-bold mb-0 text-success"><?= $stats['geral']['acertos'] ?? 0 ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-danger bg-opacity-10 rounded-3 text-center border border-danger border-opacity-25">
                    <div class="text-danger small text-uppercase mb-1 fw-semibold">Erros / Inconsistentes</div>
                    <div class="h2 fw-bold mb-0 text-danger"><?= $stats['geral']['erros'] ?? 0 ?></div>
                </div>
            </div>
        </div>

        <?php 
        $total = $stats['geral']['total'] ?? 0;
        $acertos = $stats['geral']['acertos'] ?? 0;
        $rating = $total > 0 ? round(($acertos / $total) * 100, 1) : 0;
        ?>

        <div class="mb-5">
            <div class="d-flex justify-content-between mb-2">
                <span class="fw-bold">Taxa de Assertividade Geral</span>
                <span class="fw-bold text-primary"><?= $rating ?>%</span>
            </div>
            <div class="progress" style="height: 12px; border-radius: 6px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                     style="width: <?= $rating ?>%" aria-valuenow="<?= $rating ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>

        <?php
        $f = $filtros ?? ['inicio' => '', 'fim' => '', 'tipo' => '', 'status' => '', 'busca' => '', 'chuva' => '', 'ordem' => 'desc', 'por' => 50];
        $rf = $resumoFiltro ?? ['total' => 0, 'acertos' => 0, 'erros' => 0, 'pendentes' => 0];
        $pagina = $pagina ?? 1;
        $paginas = $paginas ?? 1;
        $totalFiltro = (int) ($totalFiltro ?? 0);
        $validadosFiltro = (int) $rf['acertos'] + (int) $rf['erros'];
        $ratingFiltro = $validadosFiltro > 0 ? round((int) $rf['acertos'] / $validadosFiltro * 100, 1) : null;
        // monta a URL mantendo os filtros atuais (só os preenchidos)
        $urlPagina = function (int $p) use ($f): string {
            $q = array_filter(array_merge($f, ['pagina' => $p]), fn ($v) => $v !== '' && $v !== null);
            if (($q['ordem'] ?? '') === 'desc') unset($q['ordem']);
            if ((int) ($q['por'] ?? 50) === 50) unset($q['por']);
            if ((int) ($q['pagina'] ?? 1) === 1) unset($q['pagina']);
            return '/admin/nowcasting' . ($q ? '?' . http_build_query($q) : '') . '#historico';
        };
        $deAte = $totalFiltro ? (($pagina - 1) * $f['por'] + 1) . '–' . min($totalFiltro, $pagina * $f['por']) : '0';
        ?>

        <h3 class="h5 fw-bold mb-3" id="historico">Histórico de Validações</h3>

        <form method="GET" action="/admin/nowcasting#historico" class="border rounded-3 p-3 mb-3 bg-light bg-opacity-50">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold mb-1" for="fInicio">De</label>
                    <input type="date" class="form-control form-control-sm" id="fInicio" name="inicio" value="<?= htmlspecialchars($f['inicio']) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold mb-1" for="fFim">Até</label>
                    <input type="date" class="form-control form-control-sm" id="fFim" name="fim" value="<?= htmlspecialchars($f['fim']) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold mb-1" for="fTipo">Tipo</label>
                    <select class="form-select form-select-sm" id="fTipo" name="tipo">
                        <option value="">Todos</option>
                        <?php foreach (['firme' => 'Firme', 'chuva' => 'Chuva', 'melhoria' => 'Melhoria'] as $v => $r): ?>
                            <option value="<?= $v ?>" <?= $f['tipo'] === $v ? 'selected' : '' ?>><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold mb-1" for="fStatus">Status</label>
                    <select class="form-select form-select-sm" id="fStatus" name="status">
                        <option value="">Todos</option>
                        <?php foreach (['acertou' => 'Acertou', 'errou' => 'Errou', 'pendente' => 'Pendente'] as $v => $r): ?>
                            <option value="<?= $v ?>" <?= $f['status'] === $v ? 'selected' : '' ?>><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-bold mb-1" for="fBusca">Buscar na mensagem</label>
                    <input type="search" class="form-control form-control-sm" id="fBusca" name="busca" maxlength="100" placeholder="ex.: baixa chance, alta probabilidade, chovendo…" value="<?= htmlspecialchars($f['busca']) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold mb-1" for="fOrdem">Ordem</label>
                    <select class="form-select form-select-sm" id="fOrdem" name="ordem">
                        <option value="desc" <?= $f['ordem'] === 'desc' ? 'selected' : '' ?>>Mais recentes</option>
                        <option value="asc" <?= $f['ordem'] === 'asc' ? 'selected' : '' ?>>Mais antigos</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold mb-1" for="fPor">Por página</label>
                    <select class="form-select form-select-sm" id="fPor" name="por">
                        <?php foreach ([25, 50, 100] as $n): ?>
                            <option value="<?= $n ?>" <?= (int) $f['por'] === $n ? 'selected' : '' ?>><?= $n ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" id="fChuva" name="chuva" value="1" <?= $f['chuva'] ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="fChuva">Só com chuva registrada</label>
                    </div>
                </div>
                <div class="col-12 col-md-5 d-flex gap-2 justify-content-md-end">
                    <?php if (!empty($filtrado) || $f['ordem'] !== 'desc' || (int) $f['por'] !== 50): ?>
                        <a href="/admin/nowcasting#historico" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg me-1"></i> Limpar</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-sm btn-primary px-3"><i class="bi bi-funnel me-1"></i> Filtrar</button>
                </div>
            </div>
        </form>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 small">
            <div class="text-muted">
                <strong class="text-body"><?= number_format($totalFiltro, 0, ',', '.') ?></strong> registro(s)<?= !empty($filtrado) ? ' com esses filtros' : '' ?>
                · mostrando <?= $deAte ?>
                <?php if ((int) $rf['pendentes'] > 0): ?> · <?= number_format((int) $rf['pendentes'], 0, ',', '.') ?> pendente(s)<?php endif; ?>
            </div>
            <?php if (!empty($filtrado) && $ratingFiltro !== null): ?>
                <div>
                    Rating deste filtro:
                    <strong class="<?= $ratingFiltro >= 70 ? 'text-success' : ($ratingFiltro >= 50 ? 'text-ambar' : 'text-danger') ?>"><?= number_format($ratingFiltro, 1, ',', '.') ?>%</strong>
                    <span class="text-muted">(<?= number_format((int) $rf['acertos'], 0, ',', '.') ?> acertos de <?= number_format($validadosFiltro, 0, ',', '.') ?>)</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle border-top">
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Mensagem</th>
                        <th>Chuva Máx.</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historico)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted"><?= !empty($filtrado) ? 'Nenhum registro com esses filtros.' : 'Aguardando validações de cron...' ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($historico as $h): ?>
                        <tr>
                            <td class="small text-nowrap"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></td>
                            <td>
                                <span class="badge rounded-pill text-uppercase bg-secondary bg-opacity-10 text-secondary border">
                                    <?= $h['tipo_previsao'] ?>
                                </span>
                            </td>
                            <td class="small"><?= htmlspecialchars($h['mensagem']) ?></td>
                            <td><?= number_format($h['chuva_registrada'], 1) ?> mm/h</td>
                            <td>
                                <?php if ($h['acertou'] === null): ?>
                                    <span class="badge bg-warning text-dark">Pendente</span>
                                <?php elseif ($h['acertou'] == 1): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Acertou</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Errou</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($paginas > 1): ?>
            <?php
            // janela de páginas ao redor da atual: 1 … 4 5 [6] 7 8 … 20
            $janela = array_unique(array_filter(
                [1, $paginas, $pagina - 2, $pagina - 1, $pagina, $pagina + 1, $pagina + 2],
                fn ($p) => $p >= 1 && $p <= $paginas
            ));
            sort($janela);
            ?>
            <nav aria-label="Paginação do histórico" class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                <span class="small text-muted">Página <?= $pagina ?> de <?= number_format($paginas, 0, ',', '.') ?></span>
                <ul class="pagination pagination-sm mb-0 flex-wrap">
                    <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars($urlPagina(max(1, $pagina - 1))) ?>" aria-label="Página anterior">&laquo;</a>
                    </li>
                    <?php $anterior = 0; foreach ($janela as $p): ?>
                        <?php if ($p - $anterior > 1): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                        <li class="page-item <?= $p === $pagina ? 'active' : '' ?>" <?= $p === $pagina ? 'aria-current="page"' : '' ?>>
                            <a class="page-link" href="<?= htmlspecialchars($urlPagina($p)) ?>"><?= number_format($p, 0, ',', '.') ?></a>
                        </li>
                        <?php $anterior = $p; ?>
                    <?php endforeach; ?>
                    <li class="page-item <?= $pagina >= $paginas ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars($urlPagina(min($paginas, $pagina + 1))) ?>" aria-label="Próxima página">&raquo;</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>
