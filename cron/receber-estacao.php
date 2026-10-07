<?php
/**
 * Recebe os dados da estação meteorológica via "DNS spoofing" — ou seja, o DNS
 * local da estação é configurado pra resolver o domínio de upload que
 * a própria estação já usa (ex: api.algumaapidetempoXXXXXX.com) pra este servidor, em vez
 * do serviço real. Assim a estação "pensa" que está mandando pro serviço original,
 * mas na real está mandando pro seu próprio banco de dados.
 *
 * Deploy: copie este arquivo pra uma pasta pública do servidor web que responda
 * pelo domínio spoofado (ex: C:\Apache24\htdocs\receber\index.php), e configure
 * o DNS local do seu roteador/rede pra apontar esse domínio pro IP do servidor.
 *
 * Isso é só uma alternativa (mais barata/sem depender de hardware específico)
 * à API oficial do Weather Underground — ver app/Services/WundergroundService.php
 * e a tela /admin/estacao pra trocar de fonte sem precisar mexer em código.
 *
 * ATENÇÃO: os parâmetros que chegam aqui dependem do protocolo que a SUA estação
 * usa pra subir dados (geralmente compatível com o padrão Wunderground —
 * confira o log de acesso do servidor pra ver o formato exato que a sua manda).
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/config.php';

$d = $_GET;

/**
 * Cálculo simplificado de Evapotranspiração (ET), baseado em radiação solar e
 * temperatura (FAO Penman-Monteith simplificado). Retorna o valor multiplicado
 * por 10 pra caber na coluna SMALLINT (mesma convenção usada em todo o sistema).
 */
function calcularTaxaET($temp, $solarrad, $hum, $vento, $bar)
{
    $T = $temp / 10;
    $RH = max(10, min(100, $hum));
    $u2 = max(0, ($vento / 10) / 3.6);
    $Rs = $solarrad;

    if ($Rs <= 0) {
        return 0;
    }

    $bar = max(800, min(1100, $bar));
    $P = $bar / 10;

    $gamma = 0.000665 * $P;
    $Rn = $Rs / 4000;

    $es = 0.6108 * exp((17.27 * $T) / ($T + 237.3));
    $ea = $es * ($RH / 100);
    $delta = (4098 * $es) / (($T + 237.3) ** 2);

    $ET0 = (
        (0.408 * $delta * $Rn) +
        ($gamma * (900 / ($T + 273)) * $u2 * ($es - $ea))
    ) / (
        $delta + $gamma * (1 + 0.34 * $u2)
    );

    $ET0 = $ET0 * 0.35; // Fator de calibração — ajuste conforme sua região

    if ($ET0 < 0) {
        $ET0 = 0;
    }
    if ($ET0 > 2.5) {
        $ET0 = 2.5;
    }

    return (int) round($ET0 * 10);
}

if (empty($d)) {
    echo "Aguardando sinal da estação meteorológica...";
    exit;
}

// --- Filtro de sanidade ---
// A estação manda x10, então dividimos pra validar o valor "real".
$temp_float = isset($d['temp']) ? ($d['temp'] / 10) : null;
$hum_float = isset($d['hum']) ? $d['hum'] : null;

if ($temp_float === null || $hum_float === null) {
    die('Dados insuficientes para processamento.');
}

// Ajuste essa faixa pro clima da sua região (valores em °C e %).
if ($temp_float < 5 || $temp_float > 55 || $hum_float < 1 || $hum_float > 100) {
    error_log("Leitura fora da faixa: T: {$temp_float} | H: {$hum_float}");
    http_response_code(200); // Responde 200 pra estação não ficar reenviando
    exit;
}

$db = new MysqliDb($config['db']['host'], $config['db']['username'], $config['db']['password'], $config['db']['database']);

// --- Trava anti-duplicação ---
// Se o admin trocou a fonte de dados pra "wunderground" (ver /admin/estacao),
// esse caminho do spoofing não deve gravar nada, senão duplica com o que o
// endpoint /api/sync-wunderground já está inserindo. A estação continua
// mandando pro domínio spoofado de qualquer jeito (não tem como ela saber
// da troca), então só respondemos 200 "DONE" sem tocar no banco.
$db->where('id', 1);
$fonteAtual = $db->getValue('estacao_config', 'fonte_dados');
if ($fonteAtual === 'wunderground') {
    http_response_code(200);
    echo "DONE - Ignorado (fonte ativa: Wunderground)";
    exit;
}

$temp = isset($d['temp']) ? (int) $d['temp'] : 0;
$solarrad = isset($d['solarrad']) ? (int) $d['solarrad'] : 0;
$hum = isset($d['hum']) ? (int) $d['hum'] : 50;
$vento = isset($d['wspdavg']) ? (int) $d['wspdavg'] : 0;
$bar = isset($d['bar']) && $d['bar'] > 0 ? (int) $d['bar'] : 1013;

$et = calcularTaxaET($temp, $solarrad, $hum, $vento, $bar);

$dataToInsert = [
    'local' => isset($d['local']) ? $d['local'] : 1,
    'wid' => isset($d['wid']) ? $d['wid'] : 'Local',
    'temp' => isset($d['temp']) ? $d['temp'] : null,
    'tempin' => isset($d['tempin']) ? $d['tempin'] : null,
    'dew' => isset($d['dew']) ? $d['dew'] : null,
    'dewin' => isset($d['dewin']) ? $d['dewin'] : null,
    'chill' => isset($d['chill']) ? $d['chill'] : null,
    'heat' => isset($d['heat']) ? $d['heat'] : null,
    'heatin' => isset($d['heatin']) ? $d['heatin'] : null,
    'thw' => isset($d['thw']) ? $d['thw'] : null,
    'hum' => isset($d['hum']) ? $d['hum'] : null,
    'humin' => isset($d['humin']) ? $d['humin'] : null,
    'bar' => isset($d['bar']) ? $d['bar'] : null,
    'wspd' => isset($d['wspd']) ? $d['wspd'] : null,
    'wspdhi' => isset($d['wspdhi']) ? $d['wspdhi'] : null,
    'wdir' => isset($d['wdir']) ? $d['wdir'] : null,
    'wspdavg' => isset($d['wspdavg']) ? $d['wspdavg'] : null,
    'wdiravg' => isset($d['wdiravg']) ? $d['wdiravg'] : null,
    'rain' => isset($d['rain']) ? $d['rain'] : null,
    'rainrate' => isset($d['rainrate']) ? $d['rainrate'] : null,
    'solarrad' => isset($d['solarrad']) ? $d['solarrad'] : null,
    'uvi' => isset($d['uvi']) ? $d['uvi'] : null,
    'battery' => isset($d['battery']) ? $d['battery'] : null,
    'humsoil' => isset($d['humsoil']) ? $d['humsoil'] : null,
    'et' => $et,
    'automatic' => isset($d['automatic']) ? $d['automatic'] : 1,
];

$id_nova = $db->insert('leituras_completas', $dataToInsert);

if ($id_nova) {
    http_response_code(200);
    echo "DONE";
} else {
    error_log("Erro no MySQL: " . $db->getLastError());
    http_response_code(500);
    echo "Database Error";
}
