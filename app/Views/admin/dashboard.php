<?php
/** @var int $totalUsuarios */
/** @var int $totalCidadaosPendentes */
/** @var int $totalEstacoesAprovadas */
/** @var int $totalChuvasManuais */
/** @var int $totalAlertasAtivos */
/** @var int $totalNoticias */
/** @var float|null $acuraciaNowcasting */
/** @var array $cidadaosPendentesRecentes */
/** @var array $correcoesRecentes */
?>

<div class="mb-4">
    <h2 class="h4 fw-bold mb-1">Visão Geral</h2>
    <p class="text-muted small mb-0">Resumo rápido de tudo que está rolando no sistema agora.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(79,141,253,0.15); color: #4f8dfd;">
                <i class="bi bi-person-badge-fill"></i>
            </div>
            <div>
                <div class="stat-value"><?= $totalUsuarios ?></div>
                <div class="stat-label">Usuários do Sistema</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
                <div class="stat-value"><?= $totalCidadaosPendentes ?></div>
                <div class="stat-label">Cidadãos Pendentes</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(34,195,166,0.15); color: #22c3a6;">
                <i class="bi bi-geo-alt-fill"></i>
            </div>
            <div>
                <div class="stat-value"><?= $totalEstacoesAprovadas ?></div>
                <div class="stat-label">Estações Cidadãs Aprovadas</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(79,141,253,0.15); color: #4f8dfd;">
                <i class="bi bi-cloud-rain-heavy-fill"></i>
            </div>
            <div>
                <div class="stat-value"><?= $totalChuvasManuais ?></div>
                <div class="stat-label">Chuvas Manuais Registradas</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <div class="stat-value"><?= $totalAlertasAtivos ?></div>
                <div class="stat-label">Alertas Ativos</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(168,85,247,0.15); color: #a855f7;">
                <i class="bi bi-newspaper"></i>
            </div>
            <div>
                <div class="stat-value"><?= $totalNoticias ?></div>
                <div class="stat-label">Notícias Publicadas</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(34,195,166,0.15); color: #22c3a6;">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div>
                <div class="stat-value"><?= $acuraciaNowcasting !== null ? number_format($acuraciaNowcasting, 1, ',', '.') . '%' : '--' ?></div>
                <div class="stat-label">Acurácia Nowcasting</div>
            </div>
        </div>
    </div>
</div>

<div class="mb-4">
    <h3 class="h6 fw-bold text-muted text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">Ações Rápidas</h3>
    <div class="d-flex flex-wrap gap-2">
        <a href="/admin/add" class="btn btn-sm btn-primary rounded-pill px-3"><i class="bi bi-plus-lg me-1"></i>Nova Chuva Manual</a>
        <a href="/admin/alerta/add" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-plus-lg me-1"></i>Novo Alerta</a>
        <a href="/admin/noticia/add" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-plus-lg me-1"></i>Nova Notícia</a>
        <a href="/admin/chuva-automatica" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-tools me-1"></i>Corrigir Chuva Automática</a>
        <a href="/admin/cidadaos" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-people me-1"></i>Moderar Cidadãos</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="weather-card h-100 mb-0">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <span><i class="bi bi-hourglass-split"></i> Cidadãos Aguardando Aprovação</span>
                <a href="/admin/cidadaos" class="small text-decoration-none">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table-weather mb-0">
                        <thead>
                            <tr><th>Nome</th><th>Bairro</th><th>Cadastrado em</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cidadaosPendentesRecentes)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">Nenhum cadastro pendente.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($cidadaosPendentesRecentes as $row): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($row['nome']) ?></td>
                                    <td><?= htmlspecialchars($row['bairro']) ?></td>
                                    <td class="small text-muted"><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="weather-card h-100 mb-0">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <span><i class="bi bi-tools"></i> Últimas Correções de Chuva</span>
                <a href="/admin/chuva-automatica" class="small text-decoration-none">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table-weather mb-0">
                        <thead>
                            <tr><th>Data</th><th>Local</th><th>Valor</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($correcoesRecentes)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">Nenhuma correção lançada.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($correcoesRecentes as $row): ?>
                                <tr>
                                    <td class="fw-bold"><?= date('d/m/Y', strtotime($row['data'])) ?></td>
                                    <td><?= htmlspecialchars($row['local']) ?></td>
                                    <td><?= $row['choveu'] ? '+' : '-' ?><?= number_format((float) $row['valor_mm'], 2, ',', '.') ?> mm</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
