<?php
/** @var array $config */
/** @var string|null $erro */
$frequencia = (int) ($config['frequencia_minutos'] ?? 5);
$chamadasPorDia = $frequencia > 0 ? (int) floor(1440 / $frequencia) : 0;
?>

<div class="mb-4">
    <h2 class="h4 fw-bold mb-1">Fonte de Dados da Estação</h2>
    <p class="text-muted small mb-0">
        O sistema suporta duas formas de captar os dados da estação meteorológica:
        o DNS spoofing atual (intercepta o upload que a estação já faz pro serviço original)
        ou a API oficial do Weather Underground. Dá pra trocar aqui a qualquer momento,
        sem mexer em código.
    </p>
</div>

<?php if (!empty($_SESSION['estacao_config_sucesso'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['estacao_config_sucesso']) ?></div>
    <?php unset($_SESSION['estacao_config_sucesso']); ?>
<?php endif; ?>

<?php if (!empty($erro)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<?php if (!empty($_SESSION['estacao_teste'])): ?>
    <?php $teste = $_SESSION['estacao_teste']; unset($_SESSION['estacao_teste']); ?>
    <?php if ($teste['sucesso']): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-1"></i> Conexão com o Wunderground OK! Dados recebidos:
            <pre class="small mb-0 mt-2"><?= htmlspecialchars(json_encode($teste['dados'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            <i class="bi bi-x-circle me-1"></i> Falha no teste: <?= htmlspecialchars($teste['erro']) ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="weather-card mb-4">
    <div class="card-body p-4">
        <form action="/admin/estacao" method="POST">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold d-block">Fonte Ativa</label>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="fonte_dados" id="fonteSpoofing" value="spoofing" <?= $config['fonte_dados'] === 'spoofing' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="fonteSpoofing">DNS Spoofing (atual)</label>
                        <input type="radio" class="btn-check" name="fonte_dados" id="fonteWunderground" value="wunderground" <?= $config['fonte_dados'] === 'wunderground' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="fonteWunderground">API Wunderground</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold">Wunderground API Key</label>
                    <input type="text" name="wunderground_api_key" class="form-control" value="<?= htmlspecialchars($config['wunderground_api_key'] ?? '') ?>" placeholder="Gerada em wunderground.com/member/api-keys">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Station ID</label>
                    <input type="text" name="wunderground_station_id" class="form-control" value="<?= htmlspecialchars($config['wunderground_station_id'] ?? '') ?>" placeholder="Ex: IPEREI31">
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-bold">Frequência de Atualização (minutos)</label>
                    <input type="number" name="frequencia_minutos" id="frequenciaInput" class="form-control" min="1" value="<?= $frequencia ?>">
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <div class="small" id="limiteInfo">
                        Isso gera <strong id="chamadasDia"><?= $chamadasPorDia ?></strong> chamadas/dia
                        (limite gratuito: <strong>1500/dia</strong> e <strong>30/min</strong>).
                    </div>
                </div>

                <div class="col-12">
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Sobre os limites da API gratuita do Wunderground:</strong>
                        1500 chamadas por dia e 30 por minuto. A chave gratuita só é emitida
                        pra quem já tem uma estação própria ativa subindo dados pra eles
                        (seu caso, estação <code><?= htmlspecialchars($config['wunderground_station_id'] ?? 'IPEREI31') ?></code>).
                        Com 1 chamada a cada <strong>1 minuto</strong> você já fica dentro do limite
                        (1440/dia), mas o ideal é <strong>2 em 2 minutos</strong> (720/dia) pra sobrar
                        margem em caso de falha/reenvio. Abaixo de 1 minuto não é recomendado.
                    </div>
                </div>

                <div class="col-12 mt-4 d-flex justify-content-between">
                    <a href="/admin/estacao/testar" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="bi bi-plug me-1"></i>Testar Conexão
                    </a>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">Salvar Configuração</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="weather-card">
    <div class="card-body p-4">
        <h3 class="h6 fw-bold mb-2">Status</h3>
        <p class="small mb-1">
            Fonte ativa agora: <strong><?= $config['fonte_dados'] === 'wunderground' ? 'API Wunderground' : 'DNS Spoofing' ?></strong>
        </p>
        <p class="small mb-1">
            Última sincronização via Wunderground:
            <strong><?= $config['ultima_sincronizacao'] ? date('d/m/Y H:i:s', strtotime($config['ultima_sincronizacao'])) : 'nunca' ?></strong>
        </p>
        <?php if (!empty($config['ultimo_erro'])): ?>
            <p class="small text-danger mb-0">Último erro: <?= htmlspecialchars($config['ultimo_erro']) ?></p>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('frequenciaInput').addEventListener('input', function () {
    const min = parseInt(this.value, 10) || 1;
    const chamadas = Math.floor(1440 / min);
    document.getElementById('chamadasDia').textContent = chamadas;
});
</script>
