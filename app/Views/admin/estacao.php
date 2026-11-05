<?php
/** @var array $estacaoConfig */
/** @var string|null $erro */
$frequencia = (int) ($estacaoConfig['frequencia_minutos'] ?? 5);
$chamadasPorDia = $frequencia > 0 ? (int) floor(1440 / $frequencia) : 0;
?>

<div class="mb-4">
    <h2 class="h4 fw-bold mb-1">Fonte de Dados da Estação</h2>
    <p class="text-muted small mb-0">
        O sistema suporta duas formas de captar os dados da estação meteorológica:
        o Local (DNS spoofing — intercepta o upload que a estação já faz pro serviço original)
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
                        <input type="radio" class="btn-check" name="fonte_dados" id="fonteSpoofing" value="spoofing" <?= $estacaoConfig['fonte_dados'] === 'spoofing' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="fonteSpoofing">Local</label>
                        <input type="radio" class="btn-check" name="fonte_dados" id="fonteWunderground" value="wunderground" <?= $estacaoConfig['fonte_dados'] === 'wunderground' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="fonteWunderground">API Wunderground</label>
                    </div>
                </div>

                <?php
                // a chave nunca é enviada pro navegador: só os 4 últimos caracteres, pra identificar qual está salva
                $chaveSalva = (string) ($estacaoConfig['wunderground_api_key'] ?? '');
                $temChave = $chaveSalva !== '';
                $finalChave = $temChave ? mb_substr($chaveSalva, -4) : '';
                ?>
                <div class="col-md-6">
                    <label class="form-label small fw-bold" for="wuApiKey">Wunderground API Key</label>
                    <input type="password" id="wuApiKey" name="wunderground_api_key" class="form-control" value=""
                           autocomplete="new-password" spellcheck="false"
                           placeholder="<?= $temChave ? '•••••••••••• (deixe em branco para manter a atual)' : 'Gerada em wunderground.com/member/api-keys' ?>">
                    <?php if ($temChave): ?>
                        <div class="form-text small">
                            <i class="bi bi-shield-lock me-1"></i>Chave salva, termina em <code>…<?= htmlspecialchars($finalChave) ?></code>.
                            Para trocar, digite a nova; em branco, a atual é mantida.
                        </div>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="remover_api_key" value="1" id="removerApiKey">
                            <label class="form-check-label small" for="removerApiKey">Remover chave salva</label>
                        </div>
                    <?php else: ?>
                        <div class="form-text small">Nenhuma chave salva.</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Station ID</label>
                    <input type="text" name="wunderground_station_id" class="form-control" value="<?= htmlspecialchars($estacaoConfig['wunderground_station_id'] ?? '') ?>" placeholder="Ex: IPEREI31">
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

                <?php
                $latSalva = $estacaoConfig['latitude'] ?? null;
                $lonSalva = $estacaoConfig['longitude'] ?? null;
                $fmtCoord = fn ($v) => ($v === null || $v === '') ? '' : rtrim(rtrim(number_format((float) $v, 6, '.', ''), '0'), '.');
                $solHoje = \App\Services\AstronomiaService::sol();
                ?>
                <div class="col-12 mt-2">
                    <label class="form-label small fw-bold d-block mb-1"><i class="bi bi-geo-alt me-1"></i>Localização da estação</label>
                    <div class="form-text small mt-0 mb-2">
                        Usada para calcular o nascer e o pôr do sol e para mostrar o índice UV só durante o dia.
                        No Google Maps, clique com o botão direito no local da estação e copie os números
                        (dá para colar os dois juntos no campo da latitude). Em branco, usa a Prefeitura Municipal de Pereira Barreto.
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold" for="latitudeInput">Latitude</label>
                    <input type="text" inputmode="decimal" name="latitude" id="latitudeInput" class="form-control" value="<?= htmlspecialchars($fmtCoord($latSalva)) ?>" placeholder="<?= \App\Services\AstronomiaService::LATITUDE_PADRAO ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold" for="longitudeInput">Longitude</label>
                    <input type="text" inputmode="decimal" name="longitude" id="longitudeInput" class="form-control" value="<?= htmlspecialchars($fmtCoord($lonSalva)) ?>" placeholder="<?= \App\Services\AstronomiaService::LONGITUDE_PADRAO ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="small text-muted">
                        Hoje: sol nasce às <strong><?= $solHoje['nascer'] ? date('H:i', $solHoje['nascer']) : '--' ?></strong>
                        e se põe às <strong><?= $solHoje['por'] ? date('H:i', $solHoje['por']) : '--' ?></strong>.
                        <?php if ($latSalva !== null && $latSalva !== '' && $lonSalva !== null && $lonSalva !== ''): ?>
                            <a href="https://www.google.com/maps?q=<?= rawurlencode($fmtCoord($latSalva) . ',' . $fmtCoord($lonSalva)) ?>" target="_blank" rel="noopener">Ver no mapa</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12">
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Sobre os limites da API gratuita do Wunderground:</strong>
                        1500 chamadas por dia e 30 por minuto. A chave gratuita só é emitida
                        pra quem já tem uma estação própria ativa subindo dados pra eles
                        (seu caso, estação <code><?= htmlspecialchars($estacaoConfig['wunderground_station_id'] ?? 'SUA_ESTACAO') ?></code>).
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
            Fonte ativa agora: <strong><?= $estacaoConfig['fonte_dados'] === 'wunderground' ? 'API Wunderground' : 'Local' ?></strong>
        </p>
        <p class="small mb-1">
            Última sincronização via Wunderground:
            <strong><?= $estacaoConfig['ultima_sincronizacao'] ? date('d/m/Y H:i:s', strtotime($estacaoConfig['ultima_sincronizacao'])) : 'nunca' ?></strong>
        </p>
        <?php if (!empty($estacaoConfig['ultimo_erro'])): ?>
            <p class="small text-danger mb-0">Último erro: <?= htmlspecialchars($estacaoConfig['ultimo_erro']) ?></p>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('frequenciaInput').addEventListener('input', function () {
    const min = parseInt(this.value, 10) || 1;
    const chamadas = Math.floor(1440 / min);
    document.getElementById('chamadasDia').textContent = chamadas;
});
// colar "-20.6383, -51.1092" (como o Google Maps copia) no campo da latitude preenche os dois
document.getElementById('latitudeInput').addEventListener('paste', function (e) {
    const texto = (e.clipboardData || window.clipboardData).getData('text');
    const partes = texto.match(/(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)/);
    if (!partes) return;
    e.preventDefault();
    this.value = partes[1];
    document.getElementById('longitudeInput').value = partes[2];
});
</script>
