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
     * Lança RuntimeException em caso de erro (capturado pelo chamador).
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

        $tempRaw = $this->escalar($m['temp'] ?? null, 10);
        $barRaw = $this->escalar($m['pressure'] ?? null, 10);
        $humRaw = isset($obs['humidity']) ? (int) round($obs['humidity']) : null;
        $solarradRaw = isset($obs['solarRadiation']) ? (int) round($obs['solarRadiation']) : null;
        $windSpeedKmh = isset($m['windSpeed']) ? (float) $m['windSpeed'] : null;

        // Conversões pro formato interno (mesma escala usada em leituras_completas):
        // temp/dew/chill/heat em °C * 10 | hum em % inteiro | bar em hPa * 10 |
        // vento: km/h -> unidade interna (raw = km/h * 10 / 3.6, pois o resto do
        // sistema calcula km/h = raw/10*3.6) | rain/rainrate em mm * 10 | uvi * 10
        return [
            // Mesma convenção que o receptor do spoofing usa quando a estação não
            // manda um ID próprio: identifica a origem do dado direto na coluna 'wid'.
            'wid' => 'Wunderground',
            'temp' => $tempRaw,
            'dew' => $this->escalar($m['dewpt'] ?? null, 10),
            'chill' => $this->escalar($m['windChill'] ?? null, 10),
            'heat' => $this->escalar($m['heatIndex'] ?? null, 10),
            'hum' => $humRaw,
            'bar' => $barRaw,
            'wspd' => $this->converterVento($windSpeedKmh),
            'wspdhi' => $this->converterVento($m['windGust'] ?? null),
            'wdir' => isset($obs['winddir']) ? (int) round($obs['winddir']) : null,
            'rain' => $this->escalar($m['precipTotal'] ?? null, 10),
            'rainrate' => $this->escalar($m['precipRate'] ?? null, 10),
            'solarrad' => $solarradRaw,
            'uvi' => $this->escalar($obs['uv'] ?? null, 10),
            'et' => $this->calcularTaxaET($tempRaw ?? 0, $solarradRaw ?? 0, $humRaw ?? 50, $windSpeedKmh ?? 0, $barRaw ?? 1013),
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

    /**
     * Mesma fórmula de ET (FAO Penman-Monteith simplificada) usada pelo receptor do
     * DNS spoofing (C:\Apache24\htdocs\receber\index.php), copiada aqui pra manter
     * os dois caminhos equivalentes — senão a Evapotranspiração ficaria faltando
     * quando a fonte fosse trocada pra Wunderground.
     *
     * Nota: aqui $windKmh já é km/h de verdade (vindo direto da API), por isso a
     * conta de conversão pra m/s é só /3.6 (sem a casa decimal *10 que o
     * receptor do spoofing precisa desfazer do dado bruto da estação).
     */
    private function calcularTaxaET(int $tempRaw, int $solarrad, int $hum, float $windKmh, int $barRaw): int
    {
        $T = $tempRaw / 10;
        $RH = max(10, min(100, $hum));
        $u2 = max(0, $windKmh / 3.6);
        $Rs = $solarrad;

        if ($Rs <= 0) {
            return 0;
        }

        $bar = max(800, min(1100, $barRaw));
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

        $ET0 = $ET0 * 0.35; // Mesma calibração do receptor original

        if ($ET0 < 0) {
            $ET0 = 0;
        }
        if ($ET0 > 2.5) {
            $ET0 = 2.5;
        }

        return (int) round($ET0 * 10);
    }
}
