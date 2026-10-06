<?php
/** @var array $dados */
?>
<div class="mb-4">
    <h2 class="h4 fw-bold mb-0">Corrigir Chuva Automática</h2>
    <p class="small text-muted mt-1 mb-0">
        Use isto quando o sensor da estação não captar toda a chuva de um dia (ex: funil entupido).
        Informando a <strong>hora</strong>, se a estação voltar a captar chuva de verdade naquela mesma
        hora, essa correção é automaticamente sobreposta (não soma em dobro). Deixando a hora em branco,
        a correção vale pro dia inteiro e sempre se soma. Pra corrigir um dia que já passou da meia-noite,
        é só escolher a data daquele dia. Dá pra lançar quantas correções quiser.
    </p>
</div>

<div class="weather-card mb-4">
    <div class="card-body">
        <h3 class="h6 fw-bold mb-3">Nova Correção</h3>
        <form action="/admin/chuva-automatica/salvar" method="POST">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Local</label>
                    <select name="local" class="form-select" required>
                        <option value="1">Sede</option>
                        <option value="2">Zona Rural</option>
                        <option value="3">Local 3</option>
                        <option value="4">Local 4</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Data</label>
                    <input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Hora (opcional)</label>
                    <input type="time" name="hora" class="form-control" value="<?= date('H:i') ?>">
                    <div class="form-text small">Em branco = correção vale pro dia inteiro.</div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="choveu" id="choveu" checked>
                        <label class="form-check-label small fw-bold" for="choveu">Choveu nesta hora?</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Valor (mm)</label>
                    <input type="text" name="valor_mm" class="form-control" placeholder="0,00" required>
                    <div class="form-text small">
                        Se "Choveu" estiver <strong>marcado</strong>: mm a somar.
                        Se <strong>desmarcado</strong>: mm a subtrair (corrige falso positivo do sensor).
                    </div>
                </div>
                <div class="col-md-8">
                    <label class="form-label small fw-bold">Observação (interna)</label>
                    <input type="text" name="obs" class="form-control" placeholder="Ex: funil entupido">
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">Salvar Correção</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="weather-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-weather mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Hora</th>
                        <th>Local</th>
                        <th>Choveu?</th>
                        <th>Valor (mm)</th>
                        <th>Observação</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dados)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Nenhuma correção cadastrada.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($dados as $row): ?>
                        <tr>
                            <td class="fw-bold"><?= date('d/m/Y', strtotime($row['data'])) ?></td>
                            <td><?= $row['hora'] ? substr($row['hora'], 0, 5) : '<span class="text-muted">Dia todo</span>' ?></td>
                            <td><?php
                                echo match((string)$row['local']) {
                                    '1' => 'Sede',
                                    '2' => 'Zona Rural',
                                    '3' => 'Local 3',
                                    '4' => 'Local 4',
                                    default => "Local {$row['local']}"
                                };
                            ?></td>
                            <td>
                                <?php if ($row['choveu']): ?>
                                    <span class="badge bg-info">Sim</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Não</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold"><?= $row['choveu'] ? '+' : '-' ?><?= number_format($row['valor_mm'], 2, ',', '.') ?> mm</td>
                            <td class="small text-muted"><?= htmlspecialchars($row['obs'] ?? '') ?></td>
                            <td class="text-end">
                                <a href="/admin/chuva-automatica/delete?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Remover esta correção? O valor original do sensor volta a valer.')" title="Remover">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
