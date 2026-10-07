<?php

namespace App\Services;

class WundergroundService
{
    /**
     * Busca as condições atuais da PWS (Personal Weather Station) na API oficial do
     * Weather Underground e já converte pros mesmos "inteiros escalados" que o resto
     * do sistema usa internamente (ex: temp em °C * 10, vento em km/h convertido pra
     * unidade interna, etc — ver mapeamento abaixo).
     *
     * Retorna null em caso de erro (sem lançar exceção, pra não derrubar o cron).
     */
    public function buscarCondicaoAtual(string $apiKey, string $stationId): ?array
    {
        $url = "https://api.weather.com/v2/pws/observations/current"
            . "?stationId=" . urlencode($stationId)
            . "&format=json&units=m&apiKey=" . urlencode($apiKey);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erroCurl = curl_error($ch);
        curl_close($ch);

        if ($erroCurl || $httpCode !== 200 || !$response) {
            throw new \RuntimeException("Erro ao consultar API do Wunderground (HTTP {$httpCode}): {$erroCurl}");
        }

        $json = json_decode($response, true);
        $obs = $json['observations'][0] ?? null;

        if (!$obs || !isset($obs['metric'])) {
            throw new \RuntimeException("Resposta da API do Wunderground sem dados de observação.");
        }

        $m = $obs['metric'];

        // Conversões pro formato interno (mesma escala usada em leituras_completas):
        // temp/dew/chill/heat em °C * 10 | hum em % inteiro | bar em hPa * 10 |
        // vento: km/h -> unidade interna (raw = km/h * 10 / 3.6, pois o resto do
        // sistema calcula km/h = raw/10*3.6) | rain/rainrate em mm * 10 | uvi * 10
        return [
            'wid' => $stationId,
            'temp' => $this->escalar($m['temp'] ?? null, 10),
            'dew' => $this->escalar($m['dewpt'] ?? null, 10),
            'chill' => $this->escalar($m['windChill'] ?? null, 10),
            'heat' => $this->escalar($m['heatIndex'] ?? null, 10),
            'hum' => isset($obs['humidity']) ? (int) round($obs['humidity']) : null,
            'bar' => $this->escalar($m['pressure'] ?? null, 10),
            'wspd' => $this->converterVento($m['windSpeed'] ?? null),
            'wspdhi' => $this->converterVento($m['windGust'] ?? null),
            'wdir' => isset($obs['winddir']) ? (int) round($obs['winddir']) : null,
            'rain' => $this->escalar($m['precipTotal'] ?? null, 10),
            'rainrate' => $this->escalar($m['precipRate'] ?? null, 10),
            'solarrad' => isset($obs['solarRadiation']) ? (int) round($obs['solarRadiation']) : null,
            'uvi' => $this->escalar($obs['uv'] ?? null, 10),
        ];
    }

    private function escalar(?float $valor, int $fator): ?int
    {
        return $valor === null ? null : (int) round($valor * $fator);
    }

    /**
     * Converte km/h (como a API devolve) pra unidade interna, já que o resto do
     * sistema sempre faz "km/h = raw / 10 * 3.6" ao exibir (ver LeituraModel).
     */
    private function converterVento(?float $kmh): ?int
    {
        return $kmh === null ? null : (int) round($kmh * 10 / 3.6);
    }
}
