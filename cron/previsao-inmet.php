<?php
/**
 * Busca a previsão do tempo (5 dias) na API do INMET e grava nas tabelas
 * 'previsoes' e 'previsoesextendidas'.
 *
 * Deploy: copie este arquivo pra FORA da pasta public/ do servidor web
 * (ex: direto no DocumentRoot do Apache/IIS), e agende uma tarefa pra
 * rodar `php previsao-inmet.php` periodicamente (ex: a cada minuto — o
 * próprio script decide se é hora de buscar de verdade, controlado por
 * $horarios_permitidos abaixo, pra não martelar a API do INMET).
 *
 * Ajuste $horarios_permitidos conforme a frequência de atualização que
 * sua prefeitura quiser (o INMET costuma atualizar a previsão poucas
 * vezes ao dia, então não adianta buscar com mais frequência que isso).
 */

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/config.php';

// Só busca de verdade nesses horários, pra não martelar a API do INMET.
$hora_atual = date('H:i');
$horarios_permitidos = ['00:00', '07:05', '09:05', '16:05', '21:05'];
if (!in_array($hora_atual, $horarios_permitidos)) {
    echo "Ainda não é hora de executar a tarefa.\n";
    exit;
}

$db = new MysqliDb($config['db']['host'], $config['db']['username'], $config['db']['password'], $config['db']['database']);

// Código IBGE da cidade (config['cidade']['ibge']) — necessário pra API do INMET.
$codigoIbge = $config['cidade']['ibge'] ?? null;
if (!$codigoIbge) {
    die("Configure config['cidade']['ibge'] com o código IBGE da sua cidade antes de usar este script.\n");
}

$url = "https://apiprevmet3.inmet.gov.br/previsao/{$codigoIbge}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$erroCurl = curl_error($ch);
curl_close($ch);

if ($erroCurl || $httpCode !== 200 || !$response) {
    die("Falha ao consultar a API do INMET (HTTP {$httpCode}): {$erroCurl}\n");
}

$json = json_decode($response, true);
$dadosCidade = $json[$codigoIbge] ?? null;

if (!$dadosCidade) {
    die("Resposta da API do INMET sem dados para o código IBGE {$codigoIbge}.\n");
}

$cidade = $config['cidade']['nome'] ?? '';
$uf = $config['cidade']['uf'] ?? '';
$hoje = date('Y-m-d');

$previsoesPorDia = [];

foreach ($dadosCidade as $dataBr => $periodos) {
    $data = \DateTime::createFromFormat('d/m/Y', $dataBr);
    if (!$data) {
        continue;
    }
    $dia = $data->format('Y-m-d');

    $temperaturasMax = [];
    $temperaturasMin = [];
    $resumo = '';

    // Os 2 primeiros dias vêm com períodos (manha/tarde/noite); os dias seguintes
    // vêm "achatados", com temp_max/temp_min/resumo direto na raiz do dia.
    $temPeriodos = isset($periodos['manha']) || isset($periodos['tarde']) || isset($periodos['noite']);
    $blocos = $temPeriodos
        ? array_filter([$periodos['tarde'] ?? null, $periodos['manha'] ?? null, $periodos['noite'] ?? null])
        : [$periodos];

    foreach ($blocos as $info) {
        if (isset($info['temp_max']) && $info['temp_max'] !== null && $info['temp_max'] !== '') {
            $temperaturasMax[] = (int) $info['temp_max'];
        }
        if (isset($info['temp_min']) && $info['temp_min'] !== null && $info['temp_min'] !== '') {
            $temperaturasMin[] = (int) $info['temp_min'];
        }
        if ($resumo === '' && !empty($info['resumo'])) {
            $resumo = trim((string) $info['resumo']);
        }
    }

    if (empty($temperaturasMax) && empty($temperaturasMin)) {
        continue;
    }

    $previsoesPorDia[] = [
        'dia' => $dia,
        'tempo' => $resumo,
        'maxima' => !empty($temperaturasMax) ? max($temperaturasMax) : null,
        'minima' => !empty($temperaturasMin) ? min($temperaturasMin) : null,
    ];
}

if (empty($previsoesPorDia)) {
    die("Nenhuma previsão válida recebida do INMET.\n");
}

// Remove os registros de hoje antes de inserir, pra não acumular duplicatas
// a cada execução (o script pode rodar várias vezes no mesmo dia)
$db->where('atualizacao', $hoje);
$db->where('local', 1);
$db->delete('previsoes');

// === Tabela 'previsoes' (usada pela home e pelo card "hoje") ===
foreach ($previsoesPorDia as $p) {
    $db->insert('previsoes', [
        'cidade' => $cidade,
        'uf' => $uf,
        'atualizacao' => $hoje,
        'dia' => $p['dia'],
        'tempo' => $p['tempo'],
        'maxima' => $p['maxima'],
        'minima' => $p['minima'],
        'iuv' => null,
        'local' => 1,
        'staPrevisao' => 'S',
        'fonte' => 'INMET',
        'data' => $db->now(),
    ]);
}
echo count($previsoesPorDia) . " dias inseridos em 'previsoes'.\n";

// === Tabela 'previsoesextendidas' (usada na tela de Previsão do Tempo) ===
$db->where('local', 1);
if ($db->update('previsoesextendidas', ['atual' => 0])) {
    echo $db->count . " registros antigos invalidados em 'previsoesextendidas'.\n";
} else {
    echo 'Falha ao invalidar registros antigos: ' . $db->getLastError() . "\n";
}

foreach ($previsoesPorDia as $p) {
    $db->insert('previsoesextendidas', [
        'cidade' => $cidade,
        'uf' => $uf,
        'atualizacao' => $hoje,
        'dia' => $p['dia'],
        'tempo' => $p['tempo'],
        'maxima' => $p['maxima'],
        'minima' => $p['minima'],
        'iuv' => null,
        'local' => 1,
        'atual' => 1,
        'staPrevisao' => 'S',
        'fonte' => 'INMET',
        'data' => $db->now(),
    ]);
}
echo count($previsoesPorDia) . " dias inseridos em 'previsoesextendidas'.\n";
