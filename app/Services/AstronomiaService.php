<?php

namespace App\Services;

use App\Models\EstacaoConfigModel;

/**
 * Sol e Lua para o local da estação (coordenadas configuradas no admin).
 *
 * - Nascer/pôr do sol e crepúsculo civil: date_sun_info() do PHP.
 * - Fase da lua: idade da lua no ciclo sinódico (29,53 dias) a partir de uma
 *   lua nova de referência — precisão de algumas horas, suficiente pra exibição.
 */
class AstronomiaService
{
    /** Prefeitura Municipal de Pereira Barreto (padrão enquanto o admin não definir). */
    public const LATITUDE_PADRAO = -20.6383;
    public const LONGITUDE_PADRAO = -51.1092;

    private const MES_SINODICO = 29.530588853;          // dias
    private const LUA_NOVA_REFERENCIA = 947182440;      // 2000-01-06 18:14 UTC

    private static ?array $coordenadas = null;

    /** @return array{0: float, 1: float} [latitude, longitude] */
    public static function coordenadas(): array
    {
        if (self::$coordenadas === null) {
            $lat = self::LATITUDE_PADRAO;
            $lon = self::LONGITUDE_PADRAO;
            try {
                $cfg = (new EstacaoConfigModel())->getConfig();
                if (isset($cfg['latitude'], $cfg['longitude']) && $cfg['latitude'] !== '' && $cfg['longitude'] !== '') {
                    $lat = (float) $cfg['latitude'];
                    $lon = (float) $cfg['longitude'];
                }
            } catch (\Throwable $e) {
                // sem banco ou sem as colunas: segue com o padrão
            }
            self::$coordenadas = [$lat, $lon];
        }
        return self::$coordenadas;
    }

    /**
     * Horários do sol no dia de $ts (timestamps; null se não houver no dia).
     * @return array{nascer: ?int, por: ?int, claro_inicio: ?int, claro_fim: ?int}
     */
    public static function sol(?int $ts = null): array
    {
        $ts ??= time();
        [$lat, $lon] = self::coordenadas();
        $meioDia = strtotime(date('Y-m-d 12:00:00', $ts));
        $s = date_sun_info($meioDia, $lat, $lon);
        $v = fn ($k) => (isset($s[$k]) && is_int($s[$k])) ? $s[$k] : null;
        return [
            'nascer'       => $v('sunrise'),
            'por'          => $v('sunset'),
            'claro_inicio' => $v('civil_twilight_begin'),
            'claro_fim'    => $v('civil_twilight_end'),
        ];
    }

    /** Está claro (entre o início do amanhecer e o fim do anoitecer)? Usado pra mostrar o UV só de dia. */
    public static function ehDia(?int $ts = null): bool
    {
        $ts ??= time();
        $s = self::sol($ts);
        if ($s['claro_inicio'] === null || $s['claro_fim'] === null) {
            return true; // não acontece nesta latitude; na dúvida, mostra
        }
        return $ts >= $s['claro_inicio'] && $ts <= $s['claro_fim'];
    }

    /**
     * Fase da lua em $ts.
     * @return array{nome: string, emoji: string, iluminacao: int, idade: float}
     */
    public static function lua(?int $ts = null): array
    {
        $ts ??= time();
        $idade = fmod(($ts - self::LUA_NOVA_REFERENCIA) / 86400, self::MES_SINODICO);
        if ($idade < 0) {
            $idade += self::MES_SINODICO;
        }
        $fracao = $idade / self::MES_SINODICO;                          // 0 = nova, 0,5 = cheia
        $iluminacao = (int) round((1 - cos(2 * M_PI * $fracao)) / 2 * 100);

        // 8 fases; as principais (nova, quartos, cheia) ocupam ~1 dia em torno do instante exato
        $fases = [
            ['Lua nova', '🌑'], ['Lua crescente', '🌒'], ['Quarto crescente', '🌓'], ['Crescente gibosa', '🌔'],
            ['Lua cheia', '🌕'], ['Minguante gibosa', '🌖'], ['Quarto minguante', '🌗'], ['Lua minguante', '🌘'],
        ];
        $marcos = [[0.0, 0], [0.25, 2], [0.5, 4], [0.75, 6], [1.0, 0]]; // [fração do ciclo, fase]
        $indice = null;
        foreach ($marcos as [$marco, $i]) {
            if (abs($fracao - $marco) * self::MES_SINODICO <= 1.0) {
                $indice = $i;
                break;
            }
        }
        if ($indice === null) {
            $indice = $fracao < 0.25 ? 1 : ($fracao < 0.5 ? 3 : ($fracao < 0.75 ? 5 : 7));
        }

        return [
            'nome'       => $fases[$indice][0],
            'emoji'      => $fases[$indice][1],
            'iluminacao' => $iluminacao,
            'idade'      => round($idade, 1),
        ];
    }

    /** Os emojis da lua seguem o hemisfério norte: no sul o lado iluminado é o oposto. */
    public static function hemisferioSul(): bool
    {
        return self::coordenadas()[0] < 0;
    }
}
