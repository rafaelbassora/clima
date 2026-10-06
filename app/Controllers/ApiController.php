<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\LeituraModel;

class ApiController extends Controller
{
    private function verifyCronToken(): void
    {
        $token = $this->getParam('token');
        $expected = $this->config['cron_token'] ?? null;

        if (!$expected || $token !== $expected) {
            $this->json([
                'success' => false,
                'message' => 'Acesso negado: Token de segurança inválido.',
            ], 403);
            exit;
        }
    }

    /**
     * GET /api/clima-atual?local=1
     * Retorna JSON com todos os dados da barra fixa.
     */
    public function climaAtual(): void
    {
        $local = $this->getParam('local', '1');
        $model = new LeituraModel();
        $leitura = $model->getUltimaLeitura($local);

        if ($leitura) {
            $this->json([
                'success'     => true,
                'temperatura' => $leitura['temperatura'],
                'umidade'     => $leitura['umidade'],
                'barometro'   => $leitura['barometro'],
                'vento'       => $leitura['vento'],
                'rajada'      => $leitura['rajada'],
                'direcao'     => $leitura['direcao'],
                'direcao_graus' => $leitura['direcao_graus'] ?? 0,
                'solar'       => $leitura['solar'],
                'uv'          => $leitura['uv'],
                'sensacao'    => $leitura['sensacao'] ?? 0,
                'orvalho'     => $leitura['ponto_orvalho'] ?? 0,
                'chuva_dia'   => $leitura['chuva_dia'] ?? 0,
                'vento_max_dia' => $leitura['vento_max_dia'] ?? 0,
                'vento_avg_dia' => $leitura['vento_avg_dia'] ?? 0,
                'et_hoje'     => $leitura['et_hoje'] ?? 0,
                'nowcasting'  => $model->getLatestNowcasting($local),
                'data_hora'   => $leitura['data_hora'] ?? '',
                'atualizado'  => $leitura['atualizado'] ?? '',
                'timestamp'   => date('Y-m-d H:i:s'),
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => 'Nenhuma leitura encontrada.',
            ], 404);
        }
    }

    /**
     * POST /api/nowcasting
     * Recebe um novo nowcasting (previsão de curto prazo).
     */
    public function postNowcasting(): void
    {
        // Pega os dados do POST (JSON ou Form)
        $local    = $this->getParam('local');
        $mensagem = $this->getParam('mensagem');
        $minutos  = (int)$this->getParam('minutos', 180); // Default 3h

        if (!$local || !$mensagem) {
            $this->json([
                'success' => false,
                'message' => 'Parâmetros "local" e "mensagem" são obrigatórios.',
            ], 400);
            return;
        }

        $model = new LeituraModel();
        $success = $model->saveNowcasting($local, $mensagem, $minutos);

        if ($success) {
            $this->json([
                'success' => true,
                'message' => 'Nowcasting salvo com sucesso.',
                'valido_ate' => date('d/m/Y H:i', strtotime("+{$minutos} minutes"))
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => 'Erro ao salvar no banco de dados.',
            ], 500);
        }
    }

    /**
     * GET /api/nowcasting-test?local=1&mensagem=Texto&minutos=60
     * Método facilitador para testar via navegador.
     */
    public function testNowcasting(): void
    {
        $local    = $this->getParam('local');
        $mensagem = $this->getParam('mensagem');
        $minutos  = (int)$this->getParam('minutos', 180);

        if (!$local || !$mensagem) {
            echo "Erro: Forneça 'local' e 'mensagem' na URL. <br>Exemplo: ?local=1&mensagem=Teste&minutos=60";
            return;
        }

        $model = new LeituraModel();
        $model->saveNowcasting($local, $mensagem, $minutos);

        echo "Sucesso! Nowcasting gravado. <a href='/inicio'>Voltar para Home</a>";
    }

    /**
     * GET /api/calculate-nowcast?local=1
     * Calcula automaticamente a tendência e salva no banco.
     */
    public function calculateNowcast(): void
    {
        $this->verifyCronToken();
        $local = $this->getParam('local', '1');
        $model = new LeituraModel();
        
        $atual    = $model->getUltimaLeitura($local);
        $anterior = $model->getLeituraAnterior($local, 60);

        if (!$atual || !$anterior) {
            $this->json([
                'success' => false,
                'message' => 'Dados insuficientes para calcular tendência.',
            ], 400);
            return;
        }

        $nowcastConfig = (new \App\Models\NowcastConfigModel())->getConfig();
        $service = new \App\Services\NowcastService($nowcastConfig);
        $chuvaRecente = $model->wasRainingRecently($local, 10);
        $resTendencia = $service->calcularTendencia($atual, $anterior, 60, $chuvaRecente);
        $tendencia = $resTendencia['mensagem'];
        $tipo      = $resTendencia['tipo'];
        
        $minutos = (int)$this->getParam('minutos', 180);

        // Só expressa em horas quando dá uma hora "redonda" (sem decimais tipo "1.5 horas"
        // ou mistura errada de gênero tipo "as próximas 90 minutos"); caso contrário usa minutos.
        if ($minutos >= 60 && $minutos % 60 === 0) {
            $horas = (int) ($minutos / 60);
            $textoTempo = $horas === 1 ? "1 hora" : "{$horas} horas";
            $preposicao = $horas === 1 ? 'a próxima' : 'as próximas';
        } else {
            $textoTempo = $minutos === 1 ? "1 minuto" : "{$minutos} minutos";
            $preposicao = $minutos === 1 ? 'o próximo' : 'os próximos';
        }

        $mensagemCompleta = "{$tendencia} para {$preposicao} {$textoTempo}";
        
        $success = $model->saveNowcasting($local, $mensagemCompleta, $minutos, $tipo, $resTendencia);

        if ($success) {
            $this->json([
                'success'   => true,
                'tendencia' => $tendencia,
                'minutos'   => $minutos,
                'mensagem'  => $mensagemCompleta
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => 'Erro ao salvar tendência.',
            ], 500);
        }
    }

    /**
     * GET /api/validate-nowcasting
     * Motor que valida se as previsões passadas se confirmaram.
     */
    public function validateNowcastings(): void
    {
        $this->verifyCronToken();
        $model = new LeituraModel();
        $pendentes = $model->getNowcastingsPendentes(20);
        $validados = 0;

        foreach ($pendentes as $p) {
            $maxRate = $model->getMaxRainRateNoPeriodo($p['local'], $p['created_at'], $p['valido_ate']);
            $acertou = 0;

            if ($p['tipo_previsao'] === 'firme') {
                $acertou = ($maxRate == 0) ? 1 : 0;
            } elseif ($p['tipo_previsao'] === 'chuva') {
                $acertou = ($maxRate > 0) ? 1 : 0;
            } elseif ($p['tipo_previsao'] === 'melhoria') {
                // Para melhoria, consideramos acerto se a chuva for leve ou inexistente
                $acertou = ($maxRate < 1.0) ? 1 : 0;
            }

            $model->updateNowcastingResultado($p['id'], $acertou, $maxRate);
            $validados++;
        }

        // Recalibra os limiares automaticamente, no máximo 1x por semana
        $configModel = new \App\Models\NowcastConfigModel();
        $configAtual = $configModel->getConfig();
        $ultimaAtualizacao = $configAtual['atualizado_em'] ?? null;
        $recalibrado = null;
        if (!$ultimaAtualizacao || strtotime($ultimaAtualizacao) <= strtotime('-7 days')) {
            $recalibrado = (new \App\Services\NowcastCalibrationService())->recalibrar($model, $configModel);
        }

        $this->json([
            'success'   => true,
            'validados' => $validados,
            'stats'     => $model->getNowcastingStats(),
            'recalibrado' => $recalibrado,
        ]);
    }

    /**
     * GET /api/recalibrar-nowcasting
     * Recalibra os limiares de decisão (alta/moderada/baixa) com base na precisão
     * real recente. Também roda sozinho (1x por semana) dentro do validateNowcastings.
     */
    public function recalibrarNowcasting(): void
    {
        $this->verifyCronToken();
        $model = new LeituraModel();
        $configModel = new \App\Models\NowcastConfigModel();
        $resultado = (new \App\Services\NowcastCalibrationService())->recalibrar($model, $configModel, true);

        $this->json([
            'success' => true,
            'resultado' => $resultado,
        ]);
    }

    /**
     * GET /api/circuit-breaker
     * Roda a cada minuto para corrigir a previsão imediatamente caso comece a chover
     * e o nowcasting atual não for de chuva.
     */
    public function circuitBreaker(): void
    {
        $this->verifyCronToken();
        $local = $this->getParam('local', '1');
        $model = new LeituraModel();
        
        $atual = $model->getUltimaLeitura($local);
        
        if (!$atual) {
            $this->json(['success' => false, 'message' => 'Nenhuma leitura encontrada.'], 404);
            return;
        }

        // Se estiver chovendo no momento
        if ($atual['rainrate'] > 0) {
            $latest = $model->getLatestNowcasting($local);
            
            // Se não tiver previsão ou a previsão não for chuva (ex: firme, melhoria)
            if (!$latest || $latest['tipo_previsao'] !== 'chuva') {
                $minutos = 60; // Duração do alerta do circuit breaker
                $mensagem = "Chovendo no momento";
                
                $success = $model->saveNowcasting($local, $mensagem, $minutos, 'chuva');
                
                if ($success) {
                    $this->json([
                        'success' => true,
                        'message' => 'Circuit breaker ativado: Chuva repentina detectada.',
                        'tendencia' => $mensagem,
                        'tipo' => 'chuva'
                    ]);
                    return;
                } else {
                    $this->json(['success' => false, 'message' => 'Erro ao ativar circuit breaker.'], 500);
                    return;
                }
            }
        }
        
        $this->json([
            'success' => true, 
            'message' => 'Nenhuma ação necessária. Status mantido.'
        ]);
    }

    /**
     * GET /api/preload-cache
     * Preaquece o cache dos métodos mais pesados para evitar lentidão nos usuários.
     */
    public function preloadCache(): void
    {
        // Evita timeout do PHP já que o processamento do cache inteiro pode ser demorado na primeira vez
        set_time_limit(0);

        $this->verifyCronToken();
        $local = $this->getParam('local', '1');
        $model = new LeituraModel();
        
        $year = (int)date('Y');
        $month = (int)date('n');

        // Chama silenciosamente os métodos para forçar a criação/atualização do cache
        $model->getUltimaLeitura($local);
        
        // 24 horas e 7 dias
        $model->getChuva7Dias($local);
        $model->getEt7Dias($local);
        $model->getTemperatura7Dias($local);
        $model->getTemperatura24h($local);
        $model->getUmidade24h($local);
        $model->getPressao24h($local);
        $model->getPressao7Dias($local);
        $model->getVento24h($local);
        $model->getVento7Dias($local);
        
        // Estatísticas
        $model->getEstatisticasPressao($local);
        $model->getEstatisticasVento($local);
        $model->getEstatisticasTemperatura($local);

        // Resumos Mensais/Anuais do mês em curso e do ano
        $model->getChuvaResumo($local, $year, $month);
        $model->getChuvaResumo($local, $year, 'all');
        
        $model->getPressaoResumo($local, $year, $month);
        $model->getUmidadeResumo($local, $year, $month);
        $model->getVentoResumo($local, $year, $month);
        
        $model->getEtResumo($local, $year, $month);
        $model->getEtResumo($local, $year, 'all');

        $this->json([
            'success' => true,
            'message' => 'Cache preloaded successfully for local ' . $local
        ]);
    }

    /**
     * POST /api/umidade-solo
     * Recebe leitura de umidade do solo via POST.
     *
     * Payload esperado (JSON ou form-data):
     *   { "hora": "20:15", "data": "13/05/2026", "porcentagem": 67, "key": "..." }
     */
    public function postUmidadeSolo(): void
    {
        // Lê dados do POST (suporta JSON e form-data)
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $input = $_POST;
        }

        $key         = trim($input['key'] ?? '');
        $hora        = trim($input['hora'] ?? '');
        $data        = trim($input['data'] ?? '');
        $porcentagem = $input['porcentagem'] ?? null;
        $local       = (int) ($input['local'] ?? 1);
        $obs         = trim($input['obs'] ?? '');

        // Valida chave de autenticação
        $expected = $this->config['CHAVE_UMIDADE'] ?? null;
        if (!$expected || $key !== $expected) {
            $this->json([
                'success' => false,
                'message' => 'Acesso negado: chave inválida.',
            ], 403);
            return;
        }

        // Valida campos obrigatórios
        if ($hora === '' || $data === '' || $porcentagem === null) {
            $this->json([
                'success' => false,
                'message' => 'Campos "hora", "data" e "porcentagem" são obrigatórios.',
            ], 400);
            return;
        }

        // Monta datetime a partir de "dd/mm/yyyy" + "HH:ii"
        $dt = \DateTime::createFromFormat('d/m/Y H:i', "{$data} {$hora}");
        if (!$dt) {
            $this->json([
                'success' => false,
                'message' => 'Formato de data/hora inválido. Use data=dd/mm/aaaa e hora=HH:mm.',
            ], 400);
            return;
        }

        $model = new \App\Models\UmidadeSoloModel();
        $success = $model->create([
            'data'   => $dt->format('Y-m-d H:i:s'),
            'local'  => $local,
            'medida' => round((float) $porcentagem, 2),
            'obs'    => $obs ?: null,
        ]);

        if ($success) {
            $this->json([
                'success' => true,
                'message' => 'Leitura de umidade do solo salva com sucesso.',
                'data'    => $dt->format('d/m/Y H:i'),
                'medida'  => round((float) $porcentagem, 2),
                'local'   => $local,
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => 'Erro ao salvar no banco de dados.',
            ], 500);
        }
    }
}
