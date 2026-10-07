<?php
/**
 * Busca a leitura de um termômetro/higrômetro WiFi local (ex: um ESP8266/ESP32
 * caseiro na rede da prefeitura que expõe os dados via GET) e grava nas tabelas
 * legadas 'temperatura'/'umidade'.
 *
 * Deploy: agende pra rodar a cada 1 minuto (ou no intervalo que preferir).
 * Ajuste SENSOR_URL abaixo pro endereço do seu sensor na rede local.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/config.php';

// URL do sensor WiFi local que retorna os dados (formato querystring: t=temp&h=umid)
const SENSOR_URL = 'http://192.168.12.100/dados';

$ch = curl_init(SENSOR_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);

if ($response === false) {
    die('Erro ao acessar a URL: ' . curl_error($ch));
}
curl_close($ch);

parse_str($response, $data);

$temperature = $data['t'] ?? null;
$humidity = $data['h'] ?? null;

if ($temperature === null || $humidity === null) {
    die('Erro ao extrair os dados.');
}

echo "Temperatura: {$temperature}°C | Umidade: {$humidity}%\n";

// Filtro de sanidade — ajuste os limites pro clima da sua região.
if ($temperature < 12 || $temperature > 40 || $humidity < 20 || $humidity > 100) {
    echo "Leitura descartada por anomalia do hardware.\n";
    exit;
}

$db = new MysqliDb($config['db']['host'], $config['db']['username'], $config['db']['password'], $config['db']['database']);

$idTemp = $db->insert('temperatura', [
    'data' => $db->now(),
    'local' => 1,
    'medida' => $temperature,
    'obs' => 'Informação automatizada',
]);
echo $idTemp ? "Temperatura inserida (ID {$idTemp}).\n" : 'Erro ao inserir temperatura: ' . $db->getLastError() . "\n";

$idHum = $db->insert('umidade', [
    'data' => $db->now(),
    'local' => 1,
    'medida' => $humidity,
    'obs' => 'Informação automatizada',
]);
echo $idHum ? "Umidade inserida (ID {$idHum}).\n" : 'Erro ao inserir umidade: ' . $db->getLastError() . "\n";
