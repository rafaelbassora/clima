<?php

namespace App\Services;

use App\Models\EstacaoConfigModel;

/**
 * Sol e Lua para o local da estação (coordenadas configuradas no admin).
 *
 * - Nascer/pôr do sol e crepúsculo civil: date_sun_info() do PHP.
 * - Fases principais da lua: algoritmo de Jean Meeus, "Astronomical Algorithms",
 *   cap. 49 (erro de poucos minutos). A fase de um dia qualquer é derivada delas.
 */
class AstronomiaService
{
    /** Prefeitura Municipal de Pereira Barreto (padrão enquanto o admin não definir). */
    public const LATITUDE_PADRAO = -20.6383;
    public const LONGITUDE_PADRAO = -51.1092;

    public const FASE_NOVA = 0;
    public const FASE_QUARTO_CRESCENTE = 1;
    public const FASE_CHEIA = 2;
    public const FASE_QUARTO_MINGUANTE = 3;

    /** Fases principais: nome e emoji (emojis no padrão do hemisfério norte; ver hemisferioSul()). */
    public const FASES_PRINCIPAIS = [
        self::FASE_NOVA              => ['Lua nova', '🌑'],
        self::FASE_QUARTO_CRESCENTE  => ['Quarto crescente', '🌓'],
        self::FASE_CHEIA             => ['Lua cheia', '🌕'],
        self::FASE_QUARTO_MINGUANTE  => ['Quarto minguante', '🌗'],
    ];

    /** Fases intermediárias: o período depois de cada fase principal. */
    private const FASES_INTERMEDIARIAS = [
        self::FASE_NOVA              => ['Lua crescente', '🌒'],
        self::FASE_QUARTO_CRESCENTE  => ['Crescente gibosa', '🌔'],
        self::FASE_CHEIA             => ['Minguante gibosa', '🌖'],
        self::FASE_QUARTO_MINGUANTE  => ['Lua minguante', '🌘'],
    ];

    private const DELTA_T = 70; // segundos (TT − UT) por volta de 2026; erro de alguns segundos é irrelevante aqui

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

    // ------------------------------------------------------------------ Sol

    /**
     * Horários do sol no dia de $ts (timestamps; null se não houver no dia).
     * @return array{nascer: ?int, por: ?int, meio_dia: ?int, claro_inicio: ?int, claro_fim: ?int, duracao: ?int}
     */
    public static function sol(?int $ts = null): array
    {
        $ts ??= time();
        [$lat, $lon] = self::coordenadas();
        $meioDia = strtotime(date('Y-m-d 12:00:00', $ts));
        $s = date_sun_info($meioDia, $lat, $lon);
        $v = fn ($k) => (isset($s[$k]) && is_int($s[$k])) ? $s[$k] : null;
        $nascer = $v('sunrise');
        $por = $v('sunset');
        return [
            'nascer'       => $nascer,
            'por'          => $por,
            'meio_dia'     => $v('transit'),
            'claro_inicio' => $v('civil_twilight_begin'),
            'claro_fim'    => $v('civil_twilight_end'),
            'duracao'      => ($nascer && $por) ? $por - $nascer : null, // segundos de sol
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

    /** "13h05" a partir de segundos. */
    public static function formatarDuracao(?int $segundos): string
    {
        if ($segundos === null) {
            return '--';
        }
        $min = (int) round($segundos / 60);
        return intdiv($min, 60) . 'h' . str_pad((string) ($min % 60), 2, '0', STR_PAD_LEFT);
    }

    // ------------------------------------------------------------------ Lua

    /**
     * Fases principais (nova, quartos, cheia) entre $inicio e $fim, em ordem.
     * @return list<array{tipo: int, ts: int, nome: string, emoji: string}>
     */
    public static function fasesPrincipais(int $inicio, int $fim): array
    {
        $anos = ($inicio - 946728000) / (365.2425 * 86400);   // anos desde 2000-01-01
        $k = floor($anos * 12.3685) - 1;
        $fases = [];
        for ($guarda = 0; $guarda < 2000; $guarda++, $k++) {
            for ($q = 0; $q < 4; $q++) {
                $ts = self::instanteFase($k + $q / 4, $q);
                if ($ts > $fim) {
                    return $fases;
                }
                if ($ts >= $inicio) {
                    $fases[] = [
                        'tipo'  => $q,
                        'ts'    => $ts,
                        'nome'  => self::FASES_PRINCIPAIS[$q][0],
                        'emoji' => self::FASES_PRINCIPAIS[$q][1],
                    ];
                }
            }
        }
        return $fases;
    }

    /**
     * Fase da lua no dia de $ts. No dia em que acontece uma fase principal, o dia leva o nome dela
     * (como nos calendários); nos outros, o nome do período (ex.: entre a cheia e o quarto minguante
     * é "Minguante gibosa").
     * @return array{nome: string, emoji: string, iluminacao: int, idade: float, principal: bool, instante: ?int}
     */
    public static function lua(?int $ts = null): array
    {
        $ts ??= time();
        $inicioDia = strtotime(date('Y-m-d 00:00:00', $ts));
        $fimDia = $inicioDia + 86399;
        $eventos = self::fasesPrincipais($ts - 32 * 86400, $ts + 32 * 86400);

        $anterior = null;
        $novaAnterior = null;
        $novaSeguinte = null;
        $doDia = null;
        foreach ($eventos as $e) {
            if ($e['ts'] >= $inicioDia && $e['ts'] <= $fimDia) {
                $doDia = $e;
            }
            if ($e['ts'] <= $ts) {
                $anterior = $e;
                if ($e['tipo'] === self::FASE_NOVA) {
                    $novaAnterior = $e['ts'];
                }
            } elseif ($e['tipo'] === self::FASE_NOVA && $novaSeguinte === null) {
                $novaSeguinte = $e['ts'];
            }
        }

        // iluminação e idade a partir das luas novas reais em volta de $ts
        $ciclo = ($novaAnterior && $novaSeguinte) ? ($novaSeguinte - $novaAnterior) : 29.530588853 * 86400;
        $idadeSeg = $novaAnterior ? ($ts - $novaAnterior) : 0;
        $fracao = $ciclo > 0 ? $idadeSeg / $ciclo : 0;
        $iluminacao = (int) round((1 - cos(2 * M_PI * $fracao)) / 2 * 100);

        if ($doDia) {
            [$nome, $emoji] = self::FASES_PRINCIPAIS[$doDia['tipo']];
        } else {
            [$nome, $emoji] = self::FASES_INTERMEDIARIAS[$anterior['tipo'] ?? self::FASE_NOVA];
        }

        return [
            'nome'       => $nome,
            'emoji'      => $emoji,
            'iluminacao' => $iluminacao,
            'idade'      => round($idadeSeg / 86400, 1),
            'principal'  => $doDia !== null,
            'instante'   => $doDia['ts'] ?? null,
        ];
    }

    /** Os emojis da lua seguem o hemisfério norte: no sul o lado iluminado é o oposto. */
    public static function hemisferioSul(): bool
    {
        return self::coordenadas()[0] < 0;
    }

    /**
     * Meeus, cap. 49: instante (timestamp UTC) da fase $q (0 nova, 1 quarto crescente,
     * 2 cheia, 3 quarto minguante) para o lunação $k (com a fração .25/.5/.75 já somada).
     */
    private static function instanteFase(float $k, int $q): int
    {
        $T = $k / 1236.85;
        $T2 = $T * $T;
        $T3 = $T2 * $T;
        $T4 = $T3 * $T;
        $jde = 2451550.09766 + 29.530588861 * $k + 0.00015437 * $T2 - 0.000000150 * $T3 + 0.00000000073 * $T4;

        $E = 1 - 0.002516 * $T - 0.0000074 * $T2;
        $r = fn ($g) => deg2rad(fmod($g, 360));
        $M = $r(2.5534 + 29.10535670 * $k - 0.0000014 * $T2 - 0.00000011 * $T3);
        $Mp = $r(201.5643 + 385.81693528 * $k + 0.0107582 * $T2 + 0.00001238 * $T3 - 0.000000058 * $T4);
        $F = $r(160.7108 + 390.67050284 * $k - 0.0016118 * $T2 - 0.00000227 * $T3 + 0.000000011 * $T4);
        $Om = $r(124.7746 - 1.56375588 * $k + 0.0020672 * $T2 + 0.00000215 * $T3);

        if ($q === self::FASE_NOVA || $q === self::FASE_CHEIA) {
            $c = $q === self::FASE_NOVA
                ? [-0.40720, 0.17241, 0.01608, 0.01039, 0.00739, -0.00514, 0.00208]
                : [-0.40614, 0.17302, 0.01614, 0.01043, 0.00734, -0.00515, 0.00209];
            $corr = $c[0] * sin($Mp)
                + $c[1] * $E * sin($M)
                + $c[2] * sin(2 * $Mp)
                + $c[3] * sin(2 * $F)
                + $c[4] * $E * sin($Mp - $M)
                + $c[5] * $E * sin($Mp + $M)
                + $c[6] * $E * $E * sin(2 * $M)
                - 0.00111 * sin($Mp - 2 * $F)
                - 0.00057 * sin($Mp + 2 * $F)
                + 0.00056 * $E * sin(2 * $Mp + $M)
                - 0.00042 * sin(3 * $Mp)
                + 0.00042 * $E * sin($M + 2 * $F)
                + 0.00038 * $E * sin($M - 2 * $F)
                - 0.00024 * $E * sin(2 * $Mp - $M)
                - 0.00017 * sin($Om)
                - 0.00007 * sin($Mp + 2 * $M)
                + 0.00004 * sin(2 * $Mp - 2 * $F)
                + 0.00004 * sin(3 * $M)
                + 0.00003 * sin($Mp + $M - 2 * $F)
                + 0.00003 * sin(2 * $Mp + 2 * $F)
                - 0.00003 * sin($Mp + $M + 2 * $F)
                + 0.00003 * sin($Mp - $M + 2 * $F)
                - 0.00002 * sin($Mp - $M - 2 * $F)
                - 0.00002 * sin(3 * $Mp + $M)
                + 0.00002 * sin(4 * $Mp);
        } else {
            $corr = -0.62801 * sin($Mp)
                + 0.17172 * $E * sin($M)
                - 0.01183 * $E * sin($Mp + $M)
                + 0.00862 * sin(2 * $Mp)
                + 0.00804 * sin(2 * $F)
                + 0.00454 * $E * sin($Mp - $M)
                + 0.00204 * $E * $E * sin(2 * $M)
                - 0.00180 * sin($Mp - 2 * $F)
                - 0.00070 * sin($Mp + 2 * $F)
                - 0.00040 * sin(3 * $Mp)
                - 0.00034 * $E * sin(2 * $Mp - $M)
                + 0.00032 * $E * sin($M + 2 * $F)
                + 0.00032 * $E * sin($M - 2 * $F)
                - 0.00028 * $E * $E * sin($Mp + 2 * $M)
                + 0.00027 * $E * sin(2 * $Mp + $M)
                - 0.00017 * sin($Om)
                - 0.00005 * sin($Mp - $M - 2 * $F)
                + 0.00004 * sin(2 * $Mp + 2 * $F)
                - 0.00004 * sin($Mp + $M + 2 * $F)
                + 0.00004 * sin($Mp - 2 * $M)
                + 0.00003 * sin($Mp + $M - 2 * $F)
                + 0.00003 * sin(3 * $M)
                + 0.00002 * sin(2 * $Mp - 2 * $F)
                + 0.00002 * sin($Mp - $M + 2 * $F)
                - 0.00002 * sin(3 * $Mp + $M);
            $W = 0.00306 - 0.00038 * $E * cos($M) + 0.00026 * cos($Mp)
                - 0.00002 * cos($Mp - $M) + 0.00002 * cos($Mp + $M) + 0.00002 * cos(2 * $F);
            $corr += $q === self::FASE_QUARTO_CRESCENTE ? $W : -$W;
        }

        $jde += $corr;
        return (int) round(($jde - 2440587.5) * 86400) - self::DELTA_T;
    }
}
