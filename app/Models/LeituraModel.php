<?php

namespace App\Models;

use Core\Cache;

class LeituraModel
{
    /** Início das leituras válidas do barômetro (antes disso a coluna bar não é confiável). */
    public const ATIVACAO_BAROMETRO = '2026-04-13 15:00:00';

    private \MysqliDb $db;
    private Cache $cache;
    private int $ttlRapido;
    private int $ttlHist;
    private ChuvaCorrecaoModel $chuvaCorrecao;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
        $this->cache = $GLOBALS['cache'];
        $config = $GLOBALS['config'];
        $this->ttlRapido = $config['cache']['ttl_rapido'] ?? 60;
        $this->ttlHist = $config['cache']['ttl_hist'] ?? 3600;
        $this->chuvaCorrecao = new ChuvaCorrecaoModel();
    }

    /**
     * Calcula dinamicamente o TTL para expirar na próxima hora cheia, limitado a no máximo 3 minutos (180s).
     */
    private function getTtlHistorico(): int
    {
        $now = time();
        $proximaHora = ceil($now / 3600) * 3600;
        $ttl = $proximaHora - $now;

        // Limita o cache a no máximo 180s (3 min) para atualizar frequentemente os cards e gráficos da home
        return ($ttl > 0 && $ttl < 180) ? $ttl : 180;
    }

    /**
     * Última leitura de um local.
     * Atualizado para incluir Barômetro, Vento, Direção, Solar e UV.
     */
    public function getUltimaLeitura(string $local = '1'): ?array
    {
        return $this->cache->remember("ultima_leitura_v4_{$local}", $this->ttlRapido, function () use ($local) {
            $result = $this->db->rawQueryOne(
                "SELECT l.temp, l.hum, l.bar, l.wspd, l.wspdhi, l.wdir, l.solarrad, l.uvi, l.dew, l.heat, l.rainrate, l.created_at, l.local,
                        (SELECT MAX(rain) FROM leituras_completas WHERE local = l.local AND DATE(created_at) = DATE(l.created_at)) as rain_today,
                        (SELECT MAX(COALESCE(wspdhi, wspd, 0)) FROM leituras_completas WHERE local = l.local AND DATE(created_at) = DATE(l.created_at)) as vento_max_today,
                        (SELECT AVG(COALESCE(wspd, 0)) FROM leituras_completas WHERE local = l.local AND DATE(created_at) = DATE(l.created_at)) as vento_avg_today
                 FROM leituras_completas l
                 WHERE l.local = ?
                 ORDER BY l.created_at DESC
                 LIMIT 1",
                [$local]
            );

            if (!$result) {
                return null;
            }

            $hoje = date('Y-m-d', strtotime($result['created_at'] ?? 'now'));
            $correcaoHoje = $this->chuvaCorrecao->getValor($local, $hoje) ?? 0;
            $chuvaDia = round((float) (($result['rain_today'] ?? 0) / 10) + $correcaoHoje, 2);

            return [
                'temperatura' => round(($result['temp'] ?? 0) / 10, 1),
                'umidade' => (int) ($result['hum'] ?? 0),
                'barometro' => number_format((float) (($result['bar'] ?? 0) / 10), 1, ',', '.'),
                'vento' => round((float) (($result['wspd'] ?? 0) / 10 * 3.6), 1),
                'rajada' => round((float) (($result['wspdhi'] ?? 0) / 10 * 3.6), 1),
                'vento_max_dia' => round((float) (($result['vento_max_today'] ?? 0) / 10 * 3.6), 1),
                'vento_avg_dia' => round((float) (($result['vento_avg_today'] ?? 0) / 10 * 3.6), 1),
                'direcao' => $this->getDirecaoVento((int) ($result['wdir'] ?? 0)),
                'direcao_graus' => (int) ($result['wdir'] ?? 0),
                'solar' => (int) ($result['solarrad'] ?? 0),
                'uv' => round(($result['uvi'] ?? 0) / 10, 1),
                'sensacao' => round((float) (($result['heat'] ?? $result['temp'] ?? 0) / 10), 1),
                'ponto_orvalho' => round((float) (($result['dew'] ?? 0) / 10), 1),
                'chuva_dia' => $chuvaDia,
                'et_hoje' => $this->getEvapotranspiracaoHoje($local),
                'rainrate' => round((float) (($result['rainrate'] ?? 0) / 10), 1),
                'atualizado' => date('H:i', strtotime($result['created_at'] ?? 'now')),
                'data_hora' => date('d/m/Y H:i', strtotime($result['created_at'] ?? 'now')),
                'local' => $result['local'] ?? $local,
            ];
        });
    }

    /**
     * Chuva nos últimos 7 dias.
     */
    public function getChuva7Dias(string $local = '1'): array
    {
        return $this->cache->remember("chuva_7dias_v3_{$local}", $this->ttlRapido, function () use ($local) {
            $results = $this->db->rawQuery(
                "SELECT data_leitura AS data,
                        MAX(rain) / 10 AS mm,
                        DATE_FORMAT(MAX(CASE WHEN rainrate > 0 THEN created_at END), '%H:%i') as ultima_hora
                 FROM leituras_completas
                 WHERE local = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                 GROUP BY data_leitura
                 ORDER BY data DESC",
                [$local]
            );

            // Garante 7 dias consecutivos
            $dias = [];
            for ($i = 0; $i < 7; $i++) {
                $d = date('Y-m-d', strtotime("-{$i} days"));
                $dias[$d] = [
                    'data' => date('d/m/Y', strtotime($d)),
                    'mm_raw' => 0.0,
                    'ultima_hora' => '-',
                    'local' => $this->getLocalNome($local),
                ];
            }

            foreach ($results as $row) {
                if (!$row['data'])
                    continue;
                $d = $row['data'];
                if (isset($dias[$d])) {
                    $dias[$d]['mm_raw'] = (float) $row['mm'];
                    $dias[$d]['ultima_hora'] = $row['ultima_hora'] ?: '-';
                }
            }

            // Soma correções manuais (ex: funil entupido) ao valor captado pelo sensor
            $inicio = date('Y-m-d', strtotime('-6 days'));
            $fim = date('Y-m-d');
            $correcoes = $this->chuvaCorrecao->getMapaPeriodo($local, $inicio, $fim);
            foreach ($correcoes as $d => $valorMm) {
                if (isset($dias[$d])) {
                    $dias[$d]['mm_raw'] += $valorMm;
                }
            }

            foreach ($dias as $d => $info) {
                $dias[$d]['mm'] = number_format($info['mm_raw'], 2, ',', '.');
                unset($dias[$d]['mm_raw']);
            }

            return array_values($dias);
        });
    }

    /**
     * Evapotranspiração nos últimos 7 dias.
     */
    public function getEt7Dias(string $local = '1'): array
    {
        return $this->cache->remember("et_7dias_v2_{$local}", $this->getTtlHistorico(), function () use ($local) {
            $results = $this->db->rawQuery(
                "SELECT DATE(created_at) AS data,
                        SUM(et * diff_seg / 36000) AS total_mm
                 FROM (
                     SELECT created_at, et,
                            TIMESTAMPDIFF(
                                SECOND,
                                created_at,
                                LEAD(created_at) OVER (ORDER BY created_at)
                            ) AS diff_seg
                     FROM leituras_completas
                     WHERE local = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                 ) t
                 WHERE diff_seg IS NOT NULL
                 GROUP BY DATE(created_at)
                 ORDER BY data DESC",
                [$local]
            );

            // Garante 7 dias consecutivos
            $dias = [];
            for ($i = 0; $i < 7; $i++) {
                $d = date('Y-m-d', strtotime("-{$i} days"));
                $dias[$d] = [
                    'data' => date('d/m/Y', strtotime($d)),
                    'mm' => '0.00',
                    'local' => $this->getLocalNome($local)
                ];
            }

            foreach ($results as $row) {
                $d = $row['data'];
                if (isset($dias[$d])) {
                    $dias[$d]['mm'] = number_format((float) $row['total_mm'], 2, '.', '');
                }
            }

            return array_values($dias);
        });
    }

    /**
     * Temperatura min/max nos últimos 7 dias.
     */
    public function getTemperatura7Dias(string $local = '1'): array
    {
        return $this->cache->remember("temp_7dias_v2_{$local}", $this->getTtlHistorico(), function () use ($local) {
            $results = $this->db->rawQuery(
                "SELECT data_leitura AS data,
                        MIN(temp) / 10 AS temp_min,
                        MAX(temp) / 10 AS temp_max
                 FROM leituras_completas
                 WHERE local = ?
                 GROUP BY data_leitura
                 ORDER BY data DESC
                 LIMIT 7",
                [$local]
            );

            return array_map(function ($row) use ($local) {
                $data = $row['data'] ?? date('Y-m-d');
                return [
                    'data' => date('d/m/Y', strtotime($data)),
                    'temp_min' => number_format((float) ($row['temp_min'] ?? 0), 1),
                    'temp_max' => number_format((float) ($row['temp_max'] ?? 0), 1),
                    'local' => $this->getLocalNome($local),
                ];
            }, $results ?: []);
        });
    }

    /**
     * Temperatura hora a hora nas últimas 24h.
     */
    public function getTemperatura24h(string $local = '1'): array
    {
        return $this->cache->remember("temp_24h_{$local}", $this->getTtlHistorico(), function () use ($local) {
            $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
            $results = $this->db->rawQuery(
                "SELECT DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') AS data_hora,
                        ROUND(AVG(temp) / 10, 1) AS temp
                 FROM leituras_completas
                 WHERE local = ? AND created_at >= ?
                 GROUP BY data_hora
                 ORDER BY data_hora DESC
                 LIMIT 24",
                [$local, $since]
            );

            return array_map(function ($row) use ($local) {
                $dataHora = $row['data_hora'] ?? date('Y-m-d H:i:s');
                return [
                    'hora' => date('H:i', strtotime($dataHora)),
                    'temp' => number_format((float) ($row['temp'] ?? 0), 1),
                    'local' => $this->getLocalNome($local),
                ];
            }, $results ?: []);
        });
    }

    /**
     * Umidade hora a hora nas últimas 24h.
     */
    public function getUmidade24h(string $local = '1'): array
    {
        return $this->cache->remember("umidade_24h_{$local}", $this->getTtlHistorico(), function () use ($local) {
            $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
            $results = $this->db->rawQuery(
                "SELECT DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') AS data_hora,
                        ROUND(AVG(hum)) AS umidade
                 FROM leituras_completas
                 WHERE local = ? AND created_at >= ?
                 GROUP BY data_hora
                 ORDER BY data_hora DESC
                 LIMIT 24",
                [$local, $since]
            );

            return array_map(function ($row) use ($local) {
                $dataHora = $row['data_hora'] ?? date('Y-m-d H:i:s');
                return [
                    'hora' => date('H:i', strtotime($dataHora)),
                    'umidade' => (int) ($row['umidade'] ?? 0),
                    'local' => $this->getLocalNome($local),
                ];
            }, $results ?: []);
        });
    }

    /**
     * Pressão (Barômetro) hora a hora nas últimas 24h.
     */
    public function getPressao24h(string $local = '1'): array
    {
        return $this->cache->remember("pressao_24h_{$local}", $this->getTtlHistorico(), function () use ($local) {
            $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
            $activation = '2026-04-13 15:00:00';
            $finalSince = max($since, $activation);

            $results = $this->db->rawQuery(
                "SELECT DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') AS data_hora,
                        ROUND(AVG(COALESCE(bar, 0)) / 10, 1) AS pressao
                 FROM leituras_completas
                 WHERE local = ? AND created_at >= ?
                 GROUP BY data_hora
                 ORDER BY data_hora DESC
                 LIMIT 24",
                [$local, $finalSince]
            );

            return array_map(function ($row) use ($local) {
                $dataHora = $row['data_hora'] ?? date('Y-m-d H:i:s');
                return [
                    'hora' => date('H:i', strtotime($dataHora)),
                    'pressao' => number_format((float) ($row['pressao'] ?? 0), 1, ',', '.'),
                    'local' => $this->getLocalNome($local),
                ];
            }, $results ?: []);
        });
    }


    /**
     * Pressão dos últimos 7 dias (mín/máx por dia), a partir do resumo diário.
     */
    public function getPressao7Dias(string $local = '1'): array
    {
        $dias = array_filter(
            $this->getResumoDiario($local, date('Y-m-d', strtotime('-7 days')), date('Y-m-d')),
            fn ($d) => $d['bmin'] !== null
        );
        $saida = [];
        foreach (array_slice(array_reverse($dias, true), 0, 7, true) as $dia => $d) {
            $saida[] = [
                'data' => date('d/m/Y', strtotime($dia)),
                'pressao_min' => number_format($d['bmin'], 1, ',', '.'),
                'pressao_max' => number_format($d['bmax'], 1, ',', '.'),
                'local' => $this->getLocalNome($local),
            ];
        }
        return $saida;
    }

    /**
     * Vento hora a hora nas últimas 24h.
     */
    public function getVento24h(string $local = '1'): array
    {
        return $this->cache->remember("vento_24h_{$local}", $this->getTtlHistorico(), function () use ($local) {
            $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
            $results = $this->db->rawQuery(
                "SELECT DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') AS data_hora,
                        MAX(COALESCE(wspdhi, wspd, 0)) / 10 * 3.6 AS vento,
                        MAX(wdir) as direcao
                 FROM leituras_completas
                 WHERE local = ? AND created_at >= ?
                 GROUP BY data_hora
                 ORDER BY data_hora DESC
                 LIMIT 24",
                [$local, $since]
            );

            return array_map(function ($row) use ($local) {
                $dataHora = $row['data_hora'] ?? date('Y-m-d H:i:s');
                return [
                    'hora' => date('H:i', strtotime($dataHora)),
                    'vento' => number_format((float) ($row['vento'] ?? 0), 1, ',', '.'),
                    'direcao' => $this->getDirecaoVento((int) ($row['direcao'] ?? 0)),
                    'local' => $this->getLocalNome($local),
                ];
            }, $results ?: []);
        });
    }


    /**
     * Vento hora a hora de um dia (Y-m-d): média, rajada máxima (com horário e direção)
     * e direção predominante (média vetorial das direções). Dias passados ficam 30 dias em cache.
     */
    public function getVentoDoDia(string $local, string $dia): array
    {
        $ttl = $dia < date('Y-m-d') ? 2592000 : $this->getTtlHistorico();
        return $this->cache->remember("vento_dia_v1_{$local}_{$dia}", $ttl, function () use ($local, $dia) {
            $rows = $this->db->rawQuery(
                "SELECT HOUR(created_at) AS h,
                        AVG(wspd) AS wavg, MAX(COALESCE(wspdhi, wspd)) AS wmax,
                        SUBSTRING_INDEX(GROUP_CONCAT(IF(COALESCE(wspdhi, wspd) IS NULL, NULL, CONCAT(DATE_FORMAT(created_at, '%H:%i'), '|', IFNULL(wdir, ''))) ORDER BY COALESCE(wspdhi, wspd) DESC, created_at ASC), ',', 1) AS wmax_info,
                        SUM(IF(wdir IS NULL OR wspd IS NULL OR wspd = 0, 0, SIN(RADIANS(wdir)))) AS dsen,
                        SUM(IF(wdir IS NULL OR wspd IS NULL OR wspd = 0, 0, COS(RADIANS(wdir)))) AS dcos,
                        COUNT(wspd) AS n
                 FROM leituras_completas FORCE INDEX (idx_resumo)
                 WHERE local = ? AND created_at BETWEEN ? AND ?
                 GROUP BY HOUR(created_at)
                 ORDER BY h",
                [$local, "{$dia} 00:00:00", "{$dia} 23:59:59"]
            ) ?: [];

            $horas = [];
            foreach ($rows as $r) {
                if ((int) $r['n'] === 0) {
                    continue;
                }
                [$hw, $dw] = array_pad(explode('|', (string) $r['wmax_info']), 2, null);
                $sen = (float) $r['dsen'];
                $cos = (float) $r['dcos'];
                $predominante = (abs($sen) < 1e-9 && abs($cos) < 1e-9)
                    ? null
                    : (int) round(fmod(rad2deg(atan2($sen, $cos)) + 360, 360));
                $horas[] = [
                    'hora' => sprintf('%02d:00', (int) $r['h']),
                    'media' => $r['wavg'] !== null ? (float) $r['wavg'] / 10 * 3.6 : null,
                    'rajada' => $r['wmax'] !== null ? (float) $r['wmax'] / 10 * 3.6 : null,
                    'hora_rajada' => $hw ?: null,
                    'dir_rajada' => ($dw !== null && $dw !== '') ? $this->getDirecaoVento((int) $dw) : null,
                    'dir_predominante' => $predominante !== null ? $this->getDirecaoVento($predominante) : 'Calmo',
                    'graus_predominante' => $predominante,
                ];
            }
            return $horas;
        });
    }

    /**
     * Umidade, pressão e evapotranspiração hora a hora de um dia (Y-m-d), numa consulta só.
     * Retorna [hora (0-23) => [
     *   'hmin','hmax','havg','hhmin','hhmax'  umidade (%) mín/máx/média e horário (H:i) do mín/máx
     *   'bmin','bmax','bavg','hbmin','hbmax'  pressão (hPa), idem — só depois de ATIVACAO_BAROMETRO
     *   'et'                                  evapotranspiração da hora (mm), null sem leitura
     * ]]
     * O ET usa o mesmo cálculo do resumo diário (taxa × tempo até a leitura seguinte), então a soma
     * das horas bate com o total do dia. Dias passados ficam 30 dias em cache.
     */
    public function getMedidasDoDia(string $local, string $dia): array
    {
        $ttl = $dia < date('Y-m-d') ? 2592000 : $this->getTtlHistorico();
        return $this->cache->remember("medidas_dia_v1_{$local}_{$dia}", $ttl, function () use ($local, $dia) {
            $fimDia = "{$dia} 23:59:59";
            // vai até a 1ª hora do dia seguinte só pra última leitura do dia ter a "leitura seguinte" do ET
            $fimJanela = date('Y-m-d 00:59:59', strtotime("{$dia} +1 day"));
            $rows = $this->db->rawQuery(
                "SELECT HOUR(created_at) AS h,
                        MIN(NULLIF(hum, 0)) AS hmin, MAX(NULLIF(hum, 0)) AS hmax, AVG(NULLIF(hum, 0)) AS havg,
                        SUBSTRING_INDEX(GROUP_CONCAT(IF(NULLIF(hum, 0) IS NULL, NULL, DATE_FORMAT(created_at, '%H:%i')) ORDER BY hum ASC, created_at ASC), ',', 1) AS hhmin,
                        SUBSTRING_INDEX(GROUP_CONCAT(IF(NULLIF(hum, 0) IS NULL, NULL, DATE_FORMAT(created_at, '%H:%i')) ORDER BY hum DESC, created_at ASC), ',', 1) AS hhmax,
                        MIN(b) AS bmin, MAX(b) AS bmax, AVG(b) AS bavg,
                        SUBSTRING_INDEX(GROUP_CONCAT(IF(b IS NULL, NULL, DATE_FORMAT(created_at, '%H:%i')) ORDER BY b ASC, created_at ASC), ',', 1) AS hbmin,
                        SUBSTRING_INDEX(GROUP_CONCAT(IF(b IS NULL, NULL, DATE_FORMAT(created_at, '%H:%i')) ORDER BY b DESC, created_at ASC), ',', 1) AS hbmax,
                        SUM(et * diff_seg / 36000) AS et_mm, SUM(et IS NOT NULL) AS et_n
                 FROM (
                     SELECT created_at, hum, et,
                            IF(bar > 0 AND created_at >= ?, bar, NULL) AS b,
                            TIMESTAMPDIFF(SECOND, created_at, LEAD(created_at) OVER (ORDER BY created_at)) AS diff_seg
                     FROM leituras_completas FORCE INDEX (idx_resumo)
                     WHERE local = ? AND created_at BETWEEN ? AND ?
                 ) t
                 WHERE created_at <= ?
                 GROUP BY HOUR(created_at)
                 ORDER BY h",
                [self::ATIVACAO_BAROMETRO, $local, "{$dia} 00:00:00", $fimJanela, $fimDia]
            ) ?: [];

            $horas = [];
            foreach ($rows as $r) {
                $horas[(int) $r['h']] = [
                    'hmin' => $r['hmin'] !== null ? (int) $r['hmin'] : null,
                    'hmax' => $r['hmax'] !== null ? (int) $r['hmax'] : null,
                    'havg' => $r['havg'] !== null ? (float) $r['havg'] : null,
                    'hhmin' => $r['hhmin'] ?: null,
                    'hhmax' => $r['hhmax'] ?: null,
                    'bmin' => $r['bmin'] !== null ? (int) $r['bmin'] / 10 : null,
                    'bmax' => $r['bmax'] !== null ? (int) $r['bmax'] / 10 : null,
                    'bavg' => $r['bavg'] !== null ? (float) $r['bavg'] / 10 : null,
                    'hbmin' => $r['hbmin'] ?: null,
                    'hbmax' => $r['hbmax'] ?: null,
                    'et' => (int) $r['et_n'] > 0 && $r['et_mm'] !== null ? (float) $r['et_mm'] : null,
                ];
            }
            return $horas;
        });
    }

    /**
     * Vento dos últimos 7 dias (média e rajada máxima por dia), a partir do resumo diário.
     */
    public function getVento7Dias(string $local = '1'): array
    {
        $dias = array_filter(
            $this->getResumoDiario($local, date('Y-m-d', strtotime('-7 days')), date('Y-m-d')),
            fn ($d) => $d['wmax'] !== null
        );
        $saida = [];
        foreach (array_slice(array_reverse($dias, true), 0, 7, true) as $dia => $d) {
            $saida[] = [
                'data' => date('d/m/Y', strtotime($dia)),
                'vento_avg' => number_format((float) $d['wavg'], 1, ',', '.'),
                'vento_max' => number_format($d['wmax'], 1, ',', '.'),
                'local' => $this->getLocalNome($local),
            ];
        }
        return $saida;
    }


    /**
     * Busca resumo mensal de pressão (mín/máx por dia).
     */
    public function getPressaoResumo(string $local, int $year, int $month): array
    {
        $saida = [];
        foreach ($this->resumoDoPeriodo($local, $year, $month, 'bmin') as $dia => $d) {
            $saida[] = [
                'data' => date('d/m/Y', strtotime($dia)),
                'min' => number_format($d['bmin'], 1, ',', '.'),
                'max' => number_format($d['bmax'], 1, ',', '.'),
                'min_num' => $d['bmin'],
                'max_num' => $d['bmax'],
                'hora' => $d['hbmax'] ?? '--:--',
            ];
        }
        return $saida;
    }


    /**
     * Top 5 maiores e menores pressões (uma por dia), com horário.
     */
    public function getEstatisticasPressao(string $local): array
    {
        $primeiro = $this->getPrimeiroDiaMedida($local, 'bar');
        if (!$primeiro) {
            return ['top_max' => [], 'top_min' => []];
        }
        $dias = array_filter($this->getResumoDiario($local, $primeiro, date('Y-m-d')), fn ($d) => $d['bmax'] !== null);

        $max = $dias;
        uksort($max, fn ($a, $b) => [$dias[$b]['bmax'], $a] <=> [$dias[$a]['bmax'], $b]);
        $min = $dias;
        uksort($min, fn ($a, $b) => [$dias[$a]['bmin'], $a] <=> [$dias[$b]['bmin'], $b]);

        $fmt = function (array $lista, string $campo, string $hora): array {
            $saida = [];
            foreach (array_slice($lista, 0, 5, true) as $dia => $d) {
                $saida[] = ['data_hora' => $dia . ' ' . ($d[$hora] ?? '00:00') . ':00', 'valor' => $d[$campo], 'dia' => $dia];
            }
            return $saida;
        };

        return ['top_max' => $fmt($max, 'bmax', 'hbmax'), 'top_min' => $fmt($min, 'bmin', 'hbmin')];
    }



    /**
     * Resumo diário de umidade, pressão, vento e evapotranspiração, numa consulta por mês
     * (índice idx_resumo). Meses fechados ficam 30 dias em cache; o mês atual segue o TTL curto.
     * Retorna ['Y-m-d' => [
     *   'hmin','hmax','havg'          umidade (%), null se o dia não tem umidade
     *   'bmin','bmax','hbmin','hbmax' pressão (hPa) e horários, null antes da ativação do barômetro
     *   'wavg','wmax','hwmax','dwmax' vento médio e rajada máx. (km/h), horário e direção (graus) da rajada
     *   'et'                          evapotranspiração do dia (mm), null se não há leitura de ET
     * ]]
     */
    public function getResumoDiario(string $local, string $inicio, string $fim): array
    {
        $dias = [];
        $mesAtual = date('Y-m');
        $cursor = new \DateTime(substr($inicio, 0, 7) . '-01');
        $limite = new \DateTime($fim);

        while ($cursor <= $limite) {
            $ym = $cursor->format('Y-m');
            if ($ym > $mesAtual) {
                break;
            }
            $s = $cursor->format('Y-m-01 00:00:00');
            $e = $cursor->format('Y-m-t 23:59:59');
            $ttl = $ym < $mesAtual ? 2592000 : $this->getTtlHistorico();

            $rows = $this->cache->remember("resumo_diario_v1_{$local}_{$ym}", $ttl, function () use ($local, $s, $e) {
                // montar o cache de muitos meses de uma vez pode passar do limite padrão de 30 s
                @set_time_limit(120);
                return $this->db->rawQuery(
                    "SELECT DATE(created_at) AS dia,
                            MIN(NULLIF(hum, 0)) AS hmin, MAX(NULLIF(hum, 0)) AS hmax, AVG(NULLIF(hum, 0)) AS havg,
                            MIN(b) AS bmin, MAX(b) AS bmax,
                            SUBSTRING_INDEX(GROUP_CONCAT(IF(b IS NULL, NULL, DATE_FORMAT(created_at, '%H:%i')) ORDER BY b ASC, created_at ASC), ',', 1) AS hbmin,
                            SUBSTRING_INDEX(GROUP_CONCAT(IF(b IS NULL, NULL, DATE_FORMAT(created_at, '%H:%i')) ORDER BY b DESC, created_at ASC), ',', 1) AS hbmax,
                            AVG(wspd) AS wavg, MAX(w) AS wmax,
                            SUBSTRING_INDEX(GROUP_CONCAT(IF(w IS NULL, NULL, CONCAT(DATE_FORMAT(created_at, '%H:%i'), '|', IFNULL(wdir, ''))) ORDER BY w DESC, created_at ASC), ',', 1) AS wmax_info,
                            SUM(et * diff_seg / 36000) AS et_mm, SUM(et IS NOT NULL) AS et_n
                     FROM (
                         SELECT created_at, hum, wspd, wdir, et,
                                IF(bar > 0 AND created_at >= ?, bar, NULL) AS b,
                                COALESCE(wspdhi, wspd) AS w,
                                TIMESTAMPDIFF(SECOND, created_at, LEAD(created_at) OVER (ORDER BY created_at)) AS diff_seg
                         FROM leituras_completas FORCE INDEX (idx_resumo)
                         WHERE local = ? AND created_at BETWEEN ? AND ?
                     ) t
                     GROUP BY DATE(created_at)",
                    [self::ATIVACAO_BAROMETRO, $local, $s, $e]
                );
            });

            foreach ($rows as $row) {
                [$hw, $dw] = array_pad(explode('|', (string) $row['wmax_info']), 2, null);
                $dias[$row['dia']] = [
                    'hmin' => $row['hmin'] !== null ? (int) $row['hmin'] : null,
                    'hmax' => $row['hmax'] !== null ? (int) $row['hmax'] : null,
                    'havg' => $row['havg'] !== null ? (float) $row['havg'] : null,
                    'bmin' => $row['bmin'] !== null ? (int) $row['bmin'] / 10 : null,
                    'bmax' => $row['bmax'] !== null ? (int) $row['bmax'] / 10 : null,
                    'hbmin' => $row['hbmin'] ?: null,
                    'hbmax' => $row['hbmax'] ?: null,
                    'wavg' => $row['wavg'] !== null ? (float) $row['wavg'] / 10 * 3.6 : null,
                    'wmax' => $row['wmax'] !== null ? (int) $row['wmax'] / 10 * 3.6 : null,
                    'hwmax' => $hw ?: null,
                    'dwmax' => ($dw !== null && $dw !== '') ? (int) $dw : null,
                    'et' => (int) $row['et_n'] > 0 && $row['et_mm'] !== null ? (float) $row['et_mm'] : null,
                ];
            }
            $cursor->modify('first day of next month');
        }

        $dias = array_filter($dias, fn ($dia) => $dia >= $inicio && $dia <= $fim, ARRAY_FILTER_USE_KEY);
        ksort($dias);
        return $dias;
    }

    /**
     * Primeiro dia com dado de uma medida do resumo diário ('hum', 'bar', 'vento', 'et').
     */
    public function getPrimeiroDiaMedida(string $local, string $medida): ?string
    {
        $cond = [
            'hum' => 'hum > 0',
            'bar' => "bar > 0 AND created_at >= '" . self::ATIVACAO_BAROMETRO . "'",
            'vento' => 'wspd IS NOT NULL',
            'et' => 'et IS NOT NULL',
        ][$medida] ?? null;
        if ($cond === null) {
            return null;
        }
        return $this->cache->remember("primeiro_dia_{$medida}_{$local}", 86400, function () use ($local, $cond) {
            $row = $this->db->rawQueryOne(
                "SELECT DATE(MIN(created_at)) AS dia FROM leituras_completas FORCE INDEX (idx_resumo) WHERE local = ? AND {$cond}",
                [$local]
            );
            return $row['dia'] ?? null;
        });
    }

    /**
     * Resumo diário de um mês (ou ano com $month = 'all'), do mais recente pro mais antigo,
     * só com os dias em que o campo $campo tem valor.
     */
    private function resumoDoPeriodo(string $local, int $year, mixed $month, string $campo): array
    {
        if ($month === 'all') {
            $inicio = "{$year}-01-01";
            $fim = "{$year}-12-31";
        } else {
            $inicio = sprintf('%04d-%02d-01', $year, (int) $month);
            $fim = date('Y-m-t', strtotime($inicio));
        }
        $dias = array_filter($this->getResumoDiario($local, $inicio, $fim), fn ($d) => $d[$campo] !== null);
        return array_reverse($dias, true);
    }

    /**
     * Busca resumo mensal de umidade (mín/máx por dia).
     */
    public function getUmidadeResumo(string $local, int $year, int $month): array
    {
        $saida = [];
        foreach ($this->resumoDoPeriodo($local, $year, $month, 'hmin') as $dia => $d) {
            $saida[] = [
                'data' => date('d/m/Y', strtotime($dia)),
                'min' => $d['hmin'],
                'max' => $d['hmax'],
            ];
        }
        return $saida;
    }


    /**
     * Busca resumo mensal de vento (média, rajada máxima e direção da rajada por dia).
     */
    public function getVentoResumo(string $local, int $year, int $month): array
    {
        $saida = [];
        foreach ($this->resumoDoPeriodo($local, $year, $month, 'wmax') as $dia => $d) {
            $saida[] = [
                'data' => date('d/m/Y', strtotime($dia)),
                'avg' => number_format((float) $d['wavg'], 1, ',', '.'),
                'max' => number_format($d['wmax'], 1, ',', '.'),
                'max_num' => $d['wmax'],
                'direcao' => $this->getDirecaoVento((int) ($d['dwmax'] ?? 0)),
                'hora' => $d['hwmax'] ?? '--:--',
            ];
        }
        return $saida;
    }


    /**
     * Top 5 rajadas (uma por dia), com horário e direção.
     */
    public function getEstatisticasVento(string $local): array
    {
        $primeiro = $this->getPrimeiroDiaMedida($local, 'vento');
        if (!$primeiro) {
            return ['top_max' => []];
        }
        $dias = array_filter($this->getResumoDiario($local, $primeiro, date('Y-m-d')), fn ($d) => $d['wmax'] !== null);
        uksort($dias, fn ($a, $b) => [$dias[$b]['wmax'], $a] <=> [$dias[$a]['wmax'], $b]);

        $top = [];
        foreach (array_slice($dias, 0, 5, true) as $dia => $d) {
            $top[] = [
                'data_hora' => $dia . ' ' . ($d['hwmax'] ?? '00:00') . ':00',
                'valor' => $d['wmax'],
                'direcao' => $d['dwmax'],
                'dia' => $dia,
                'direcao_label' => $this->getDirecaoVento((int) ($d['dwmax'] ?? 0)),
            ];
        }
        return ['top_max' => $top];
    }

    /**
     * Busca chuva diária filtrada por tipo (manual/automático) para um mês ou ano específico.
     */
    public function getChuvaResumo(string $local, int $year, mixed $month, string $automatic = '1'): array
    {
        $cacheKey = "chuva_resumo_{$local}_{$year}_{$month}_{$automatic}";
        return $this->cache->remember($cacheKey, $this->getTtlHistorico(), function () use ($local, $year, $month, $automatic) {

            if ($month === 'all') {
                $start = "{$year}-01-01 00:00:00";
                $end = "{$year}-12-31 23:59:59";

                // Agrupa por mês após pegar o max de cada dia
                $mensal = [];
                $totalAnual = 0;
                $mesesNomes = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];

                $correcoesAno = $automatic === '1' ? $this->chuvaCorrecao->getMapaPeriodo($local, $start, $end) : [];

                // Reagrupa por dia (com a data exata) pra poder aplicar as correções antes de somar o mês
                $diarios = $this->db->rawQuery(
                    "SELECT DATE(created_at) as dia, MONTH(created_at) as mes, MAX(rain) as max_chuva
                     FROM leituras_completas FORCE INDEX (idx_chuva_auto)
                     WHERE local = ? AND created_at BETWEEN ? AND ? AND automatic = ?
                     GROUP BY dia
                     ORDER BY dia",
                    [$local, $start, $end, $automatic]
                );

                foreach ($diarios as $row) {
                    $m = (int) $row['mes'];
                    $dia = $row['dia'];
                    $valor = (float) $row['max_chuva'] / 10 + ($correcoesAno[$dia] ?? 0);
                    if (!isset($mensal[$m]))
                        $mensal[$m] = 0;
                    $mensal[$m] += $valor;
                    $totalAnual += $valor;
                }

                $dados = [];
                foreach ($mensal as $m => $total) {
                    $dados[] = [
                        'dia' => $mesesNomes[$m] . ' / ' . $year,
                        'chuva' => number_format($total, 2),
                    ];
                }

                return ['detalhe' => $dados, 'total' => number_format($totalAnual, 2)];
            }

            $start = "{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01 00:00:00";
            $end = date("Y-m-t 23:59:59", strtotime($start));

            $results = $this->db->rawQuery(
                "SELECT DATE(created_at) as data, MAX(rain) as max_chuva
                 FROM leituras_completas FORCE INDEX (idx_chuva_auto)
                 WHERE local = ? AND created_at BETWEEN ? AND ? AND automatic = ?
                 GROUP BY DATE(created_at)
                 ORDER BY data DESC",
                [$local, $start, $end, $automatic]
            );

            $correcoesMes = $automatic === '1' ? $this->chuvaCorrecao->getMapaPeriodo($local, $start, $end) : [];

            $dados = [];
            $totalMensal = 0;
            foreach ($results as $row) {
                $dia = $row['data'];
                $valor = (float) $row['max_chuva'] / 10 + ($correcoesMes[$dia] ?? 0);
                $totalMensal += $valor;
                $dados[] = [
                    'dia' => date('d/m/Y', strtotime($dia)),
                    'chuva' => number_format($valor, 2),
                ];
            }

            return [
                'detalhe' => $dados,
                'total' => number_format($totalMensal, 2)
            ];
        });
    }

    /**
     * Busca chuva da tabela 'chuva' (Pluviômetro Manual) para um mês ou ano.
     */
    public function getChuvaManual(string $local, int $year, mixed $month): array
    {
        $cacheKey = "chuva_manual_{$local}_{$year}_{$month}";
        return $this->cache->remember($cacheKey, $this->getTtlHistorico(), function () use ($local, $year, $month) {

            $where = "local = ? AND YEAR(data) = ?";
            $params = [$local, $year];

            if ($month !== 'all') {
                $where .= " AND MONTH(data) = ?";
                $params[] = $month;
            }

            $results = $this->db->rawQuery(
                "SELECT data, medida, choveu, ph, obs
                 FROM chuva
                 WHERE $where
                 ORDER BY data DESC",
                $params
            );

            $dados = [];
            $totalGeral = 0;
            $mesesNomes = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];

            if ($month === 'all') {
                $mensal = [];
                foreach ($results as $row) {
                    $m = (int) date('n', strtotime($row['data']));
                    $valor = (float) $row['medida'];
                    if (!isset($mensal[$m]))
                        $mensal[$m] = 0;
                    $mensal[$m] += $valor;
                    $totalGeral += $valor;
                }
                foreach ($mensal as $m => $total) {
                    $dados[] = [
                        'dia' => $mesesNomes[$m] . ' / ' . $year,
                        'chuva' => number_format($total, 1),
                        'ph' => '--',
                        'obs' => 'Total mensal',
                    ];
                }
            } else {
                foreach ($results as $row) {
                    $valor = (float) $row['medida'];
                    $totalGeral += $valor;
                    $dados[] = [
                        'dia' => date('d/m/Y', strtotime($row['data'])),
                        'chuva' => number_format($valor, 2),
                        'ph' => $row['ph'] ?? '--',
                        'obs' => $row['obs'] ?? '',
                    ];
                }
            }

            return [
                'detalhe' => $dados,
                'total' => number_format($totalGeral, 2)
            ];
        });
    }

    /**
     * Chuva por dia (mm) num período, pra comparação entre meses/anos.
     * $fonte: 'manual' (tabela chuva) ou 'auto' (estação, máx. do dia / 10 + correções,
     * mesma regra do getChuvaResumo). Retorna ['Y-m-d' => mm].
     */
    public function getChuvaDiariaPeriodo(string $local, string $fonte, string $inicio, string $fim): array
    {
        $dias = [];

        if ($fonte === 'manual') {
            $rows = $this->cache->remember("chuva_diaria_manual_{$local}_{$inicio}_{$fim}", $this->getTtlHistorico(), function () use ($local, $inicio, $fim) {
                return $this->db->rawQuery(
                    "SELECT DATE(data) as dia, SUM(medida) as mm
                     FROM chuva
                     WHERE local = ? AND data BETWEEN ? AND ?
                     GROUP BY DATE(data)",
                    [$local, "{$inicio} 00:00:00", "{$fim} 23:59:59"]
                );
            });
            foreach ($rows as $row) {
                $dias[$row['dia']] = (float) $row['mm'];
            }
            return $dias;
        }

        // Automático: busca mês a mês; meses já fechados ficam 24h em cache
        $mesAtual = date('Y-m');
        $cursor = new \DateTime(substr($inicio, 0, 7) . '-01');
        $limite = new \DateTime($fim);
        while ($cursor <= $limite) {
            $ym = $cursor->format('Y-m');
            $s = $cursor->format('Y-m-01 00:00:00');
            $e = $cursor->format('Y-m-t 23:59:59');
            $ttl = $ym < $mesAtual ? 86400 : $this->getTtlHistorico();

            $rows = $this->cache->remember("chuva_diaria_auto_{$local}_{$ym}", $ttl, function () use ($local, $s, $e) {
                return $this->db->rawQuery(
                    "SELECT DATE(created_at) as dia, MAX(rain) as max_chuva
                     FROM leituras_completas FORCE INDEX (idx_chuva_auto)
                     WHERE local = ? AND automatic = '1' AND created_at BETWEEN ? AND ?
                     GROUP BY DATE(created_at)",
                    [$local, $s, $e]
                );
            });
            foreach ($rows as $row) {
                $dias[$row['dia']] = (float) $row['max_chuva'] / 10;
            }
            $cursor->modify('first day of next month');
        }

        // Correções manuais (sem cache, pra refletir na hora o que o admin lançar)
        $correcoes = $this->chuvaCorrecao->getMapaPeriodo($local, "{$inicio} 00:00:00", "{$fim} 23:59:59");
        foreach ($dias as $dia => $valor) {
            $dias[$dia] = $valor + ($correcoes[$dia] ?? 0);
        }

        return array_filter($dias, fn ($dia) => $dia >= $inicio && $dia <= $fim, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Primeiro dia com dado de chuva da fonte ('Y-m-d'), pra limitar os filtros e a média histórica.
     */
    public function getPrimeiroDiaChuva(string $local, string $fonte): ?string
    {
        return $this->cache->remember("chuva_primeiro_dia_v2_{$local}_{$fonte}", 86400, function () use ($local, $fonte) {
            // na estação automática, conta só a partir da 1ª leitura com o pluviômetro funcionando (rain preenchido)
            $row = $fonte === 'manual'
                ? $this->db->rawQueryOne("SELECT DATE(MIN(data)) as dia FROM chuva WHERE local = ? AND medida IS NOT NULL", [$local])
                : $this->db->rawQueryOne(
                    "SELECT DATE(MIN(created_at)) as dia FROM leituras_completas FORCE INDEX (idx_chuva_auto)
                     WHERE local = ? AND automatic = '1' AND rain IS NOT NULL",
                    [$local]
                );
            return $row['dia'] ?? null;
        });
    }

    /**
     * Resumo diário de temperatura: mínima, máxima e o horário (1ª ocorrência) de cada uma.
     * Busca mês a mês numa única consulta por mês; meses já fechados ficam 30 dias em cache
     * (temperatura passada não muda), o mês atual segue o TTL curto.
     * Retorna ['Y-m-d' => ['tmin' => float, 'tmax' => float, 'hmin' => 'HH:MM', 'hmax' => 'HH:MM']] (°C).
     */
    public function getTemperaturaDiaria(string $local, string $inicio, string $fim): array
    {
        $dias = [];
        $mesAtual = date('Y-m');
        $cursor = new \DateTime(substr($inicio, 0, 7) . '-01');
        $limite = new \DateTime($fim);

        while ($cursor <= $limite) {
            $ym = $cursor->format('Y-m');
            if ($ym > $mesAtual) {
                break;
            }
            $s = $cursor->format('Y-m-01 00:00:00');
            $e = $cursor->format('Y-m-t 23:59:59');
            $ttl = $ym < $mesAtual ? 2592000 : $this->getTtlHistorico();

            $rows = $this->cache->remember("temp_diaria_{$local}_{$ym}", $ttl, function () use ($local, $s, $e) {
                return $this->db->rawQuery(
                    "SELECT DATE(created_at) as dia, MIN(temp) as tmin, MAX(temp) as tmax,
                            SUBSTRING_INDEX(GROUP_CONCAT(DATE_FORMAT(created_at, '%H:%i') ORDER BY temp ASC, created_at ASC), ',', 1) as hmin,
                            SUBSTRING_INDEX(GROUP_CONCAT(DATE_FORMAT(created_at, '%H:%i') ORDER BY temp DESC, created_at ASC), ',', 1) as hmax
                     FROM leituras_completas FORCE INDEX (idx_temp)
                     WHERE local = ? AND created_at BETWEEN ? AND ? AND temp > 0
                     GROUP BY DATE(created_at)",
                    [$local, $s, $e]
                );
            });

            foreach ($rows as $row) {
                $dias[$row['dia']] = [
                    'tmin' => (int) $row['tmin'] / 10,
                    'tmax' => (int) $row['tmax'] / 10,
                    'hmin' => $row['hmin'] ?: '--:--',
                    'hmax' => $row['hmax'] ?: '--:--',
                ];
            }
            $cursor->modify('first day of next month');
        }

        $dias = array_filter($dias, fn ($dia) => $dia >= $inicio && $dia <= $fim, ARRAY_FILTER_USE_KEY);
        ksort($dias);
        return $dias;
    }

    /**
     * Primeiro dia com leitura de temperatura ('Y-m-d').
     */
    public function getPrimeiroDiaTemperatura(string $local): ?string
    {
        return $this->cache->remember("temp_primeiro_dia_{$local}", 86400, function () use ($local) {
            $row = $this->db->rawQueryOne(
                "SELECT DATE(MIN(created_at)) as dia FROM leituras_completas FORCE INDEX (idx_temp)
                 WHERE local = ? AND temp > 0",
                [$local]
            );
            return $row['dia'] ?? null;
        });
    }

    /**
     * Top 5 dias mais quentes / mais frios de um conjunto de dias do resumo diário,
     * no formato que a view espera ('data' => 'd/m/Y H:i', 'valor' => '39.3', 'dia' => 'Y-m-d').
     */
    private function topTemperaturas(array $diaria): array
    {
        $formatar = function (array $lista, string $campoValor, string $campoHora): array {
            $saida = [];
            foreach (array_slice($lista, 0, 5, true) as $dia => $d) {
                $saida[] = [
                    'data' => date('d/m/Y', strtotime($dia)) . ' ' . $d[$campoHora],
                    'valor' => number_format($d[$campoValor], 1),
                    'dia' => $dia,
                ];
            }
            return $saida;
        };

        $calor = $diaria;
        uksort($calor, fn ($a, $b) => [$diaria[$b]['tmax'], $a] <=> [$diaria[$a]['tmax'], $b]);
        $frio = $diaria;
        uksort($frio, fn ($a, $b) => [$diaria[$a]['tmin'], $a] <=> [$diaria[$b]['tmin'], $b]);

        return [
            'calor' => $formatar($calor, 'tmax', 'hmax'),
            'frio' => $formatar($frio, 'tmin', 'hmin'),
        ];
    }

    /**
     * Busca estatísticas históricas de temperatura (Top Calor / Top Frio).
     */
    public function getEstatisticasTemperatura(string $local): array
    {
        $primeiro = $this->getPrimeiroDiaTemperatura($local);
        if (!$primeiro) {
            return ['calor' => [], 'frio' => []];
        }
        return $this->topTemperaturas($this->getTemperaturaDiaria($local, $primeiro, date('Y-m-d')));
    }

    /**
     * Busca estatísticas de temperatura de um ano específico (Top Calor / Top Frio do ano).
     */
    public function getEstatisticasTemperaturaAno(string $local, int $year): array
    {
        return $this->topTemperaturas($this->getTemperaturaDiaria($local, "{$year}-01-01", "{$year}-12-31"));
    }

    /**
     * Busca resumo mensal de temperatura com horários de min/max.
     */
    public function getTemperaturaResumo(string $local, int $year, mixed $month): array
    {
        if ($month !== 'all') {
            $inicio = sprintf('%04d-%02d-01', $year, (int) $month);
            $fim = date('Y-m-t', strtotime($inicio));
        } else {
            $inicio = "{$year}-01-01";
            $fim = "{$year}-12-31";
        }

        $final = [];
        foreach (array_reverse($this->getTemperaturaDiaria($local, $inicio, $fim), true) as $dia => $d) {
            $final[] = [
                'data' => date('d/m/Y', strtotime($dia)),
                'temp_min' => number_format($d['tmin'], 1),
                'hora_min' => $d['hmin'],
                'temp_max' => number_format($d['tmax'], 1),
                'hora_max' => $d['hmax'],
            ];
        }
        return $final;
    }

    /**
     * Busca estatísticas históricas e recordes (Seca, Dias Chuvosos).
     */
    public function getEstatisticasChuva(string $local): array
    {
        $cacheKey = "chuva_estatisticas_agri_{$local}";
        return $this->cache->remember($cacheKey, $this->getTtlHistorico(), function () use ($local) {
            // 1. Dias mais chuvosos (Top 5 histórico - Geral)
            $topChuva = $this->db->rawQuery(
                "SELECT data, medida as total
                 FROM chuva
                 WHERE local = ? AND medida > 0
                 ORDER BY medida DESC
                 LIMIT 5",
                [$local]
            );

            // 2. Recorde de Seca (Estiagem > 10mm - Critério Agrícola)
            $datasChuva = $this->db->rawQuery(
                "SELECT data FROM chuva WHERE local = ? AND medida >= 10 ORDER BY data ASC",
                [$local]
            );

            $maiorGap = 0;
            $dataInicioSeca = null;
            $dataFimSeca = null;

            if (count($datasChuva) > 1) {
                for ($i = 1; $i < count($datasChuva); $i++) {
                    $d1 = new \DateTime($datasChuva[$i - 1]['data']);
                    $d2 = new \DateTime($datasChuva[$i]['data']);
                    $diff = $d1->diff($d2)->days;

                    if ($diff > $maiorGap) {
                        $maiorGap = $diff;
                        $dataInicioSeca = $datasChuva[$i - 1]['data'];
                        $dataFimSeca = $datasChuva[$i]['data'];
                    }
                }
            }

            // 3. Status Atual (Dias sem chuva acumulada > 0 hoje)
            $ultimaChuva = $this->db->rawQueryOne(
                "SELECT created_at FROM leituras_completas
                 WHERE local = ? AND rain > 0 AND automatic = '1'
                 ORDER BY created_at DESC LIMIT 1",
                [$local]
            );
            $dataUltimaSensor = $ultimaChuva ? new \DateTime($ultimaChuva['created_at']) : null;

            // Considera também a correção manual mais recente com valor > 0
            // (ex: dia em que o funil ficou entupido e o sensor não registrou nada)
            $ultimaCorrecao = $this->db->rawQueryOne(
                "SELECT data FROM chuva_correcoes WHERE local = ? AND valor_mm > 0 ORDER BY data DESC LIMIT 1",
                [$local]
            );
            $dataUltimaCorrecao = $ultimaCorrecao ? new \DateTime($ultimaCorrecao['data']) : null;

            $dataUltima = $dataUltimaSensor;
            if ($dataUltimaCorrecao && (!$dataUltima || $dataUltimaCorrecao > $dataUltima)) {
                $dataUltima = $dataUltimaCorrecao;
            }

            $diasSemChuva = 0;
            if ($dataUltima) {
                $hoje = new \DateTime();
                $diasSemChuva = $dataUltima->diff($hoje)->days;
            }

            return [
                'top_chuva' => $topChuva,
                'current_drought' => $diasSemChuva,
                'max_estiagem' => [
                    'dias' => $maiorGap,
                    'inicio' => $dataInicioSeca ? date('d/m/Y', strtotime($dataInicioSeca)) : '--',
                    'fim' => $dataFimSeca ? date('d/m/Y', strtotime($dataFimSeca)) : '--',
                ]
            ];
        });
    }

    /**
     * Busca previsões do tempo (tabela 'previsoes').
     */
    public function getPrevisoes(string $local): array
    {
        // Pega a última data de atualização disponível para este local
        $lastUpdate = $this->db->rawQueryOne(
            "SELECT MAX(atualizacao) as ultima FROM previsoes WHERE local = ?",
            [$local]
        );

        if (!$lastUpdate || !$lastUpdate['ultima']) {
            return [];
        }

        return $this->db->rawQuery(
            "SELECT * FROM previsoes 
             WHERE local = ? AND atualizacao = ? AND dia >= CURRENT_DATE()
             GROUP BY dia
             ORDER BY dia ASC",
            [$local, $lastUpdate['ultima']]
        );
    }

    /**
     * Busca a previsão específica de hoje.
     */
    public function getPrevisaoHoje(string $local): ?array
    {
        $previsoes = $this->getPrevisoes($local);
        if (empty($previsoes))
            return null;

        $p = $previsoes[0]; // Hoje
        $condition = $this->getWeatherConditionSmart($p['tempo']);

        return [
            'temp_max' => $p['maxima'],
            'temp_min' => $p['minima'],
            'texto' => $condition['description'],
            'icon' => $condition['icon'],
            'fonte' => $p['fonte'] ?? 'CPTEC/INPE',
        ];
    }

    public function getWeatherCondition($sigla)
    {
        $conditions = [
            'ec' => ['description' => 'Encoberto com Chuvas Isoladas', 'icon' => 'bi-cloud-drizzle'],
            'ci' => ['description' => 'Chuvas Isoladas', 'icon' => 'bi-cloud-drizzle'],
            'c' => ['description' => 'Chuva', 'icon' => 'bi-cloud-rain'],
            'in' => ['description' => 'Instável', 'icon' => 'bi-clouds'],
            'pp' => ['description' => 'Possibilidade de Pancadas de Chuva', 'icon' => 'bi-cloud-rain'],
            'cm' => ['description' => 'Chuva pela Manhã', 'icon' => 'bi-cloud-rain'],
            'cn' => ['description' => 'Chuva a Noite', 'icon' => 'bi-cloud-rain'],
            'pt' => ['description' => 'Pancadas de Chuva à Tarde', 'icon' => 'bi-cloud-rain'],
            'pm' => ['description' => 'Pancadas de Chuva pela Manhã', 'icon' => 'bi-cloud-sun-rain'],
            'np' => ['description' => 'Nublado e Pancadas de Chuva', 'icon' => 'bi-cloud-rain'],
            'pc' => ['description' => 'Pancadas de Chuva', 'icon' => 'bi-cloud-rain'],
            'pn' => ['description' => 'Parcialmente Nublado', 'icon' => 'bi-cloud-sun'],
            'cv' => ['description' => 'Chuvisco', 'icon' => 'bi-cloud-drizzle'],
            'ch' => ['description' => 'Chuvoso', 'icon' => 'bi-cloud-rain-heavy'],
            't' => ['description' => 'Tempestade', 'icon' => 'bi-cloud-lightning-rain'],
            'ps' => ['description' => 'Predomínio de Sol', 'icon' => 'bi-sun'],
            'e' => ['description' => 'Encoberto', 'icon' => 'bi-cloud'],
            'n' => ['description' => 'Nublado', 'icon' => 'bi-cloud'],
            'cl' => ['description' => 'Céu Claro', 'icon' => 'bi-sun'],
            'nv' => ['description' => 'Nevoeiro', 'icon' => 'bi-cloud-fog'],
            'g' => ['description' => 'Geada', 'icon' => 'bi-snow'],
            'ne' => ['description' => 'Neve', 'icon' => 'bi-snow'],
            'nd' => ['description' => 'Não Definido', 'icon' => 'bi-question-circle'],
            'pnt' => ['description' => 'Pancadas de Chuva à Noite', 'icon' => 'bi-cloud-moon-rain'],
            'psc' => ['description' => 'Possibilidade de Chuva', 'icon' => 'bi-cloud-rain'],
            'pcm' => ['description' => 'Possibilidade de Chuva pela Manhã', 'icon' => 'bi-cloud-sun'],
            'pct' => ['description' => 'Possibilidade de Chuva à Tarde', 'icon' => 'bi-cloud-sun'],
            'pcn' => ['description' => 'Possibilidade de Chuva à Noite', 'icon' => 'bi-cloud-moon'],
            'npt' => ['description' => 'Nublado com Pancadas à Tarde', 'icon' => 'bi-cloud-sun'],
            'npn' => ['description' => 'Nublado com Pancadas à Noite', 'icon' => 'bi-cloud-moon'],
            'ncn' => ['description' => 'Nublado com Poss. de Chuva à Noite', 'icon' => 'bi-cloud-moon'],
            'nct' => ['description' => 'Nublado com Poss. de Chuva à Tarde', 'icon' => 'bi-cloud-sun'],
            'ncm' => ['description' => 'Nubl. c/ Poss. de Chuva pela Manhã', 'icon' => 'bi-cloud-sun'],
            'npm' => ['description' => 'Nublado com Pancadas pela Manhã', 'icon' => 'bi-cloud-sun'],
            'npp' => ['description' => 'Nublado com Possibilidade de Chuva', 'icon' => 'bi-cloud-rain'],
            'vn' => ['description' => 'Variação de Nebulosidade', 'icon' => 'bi-cloud'],
            'ct' => ['description' => 'Chuva a Tarde', 'icon' => 'bi-cloud-lightning-rain'],
            'ppn' => ['description' => 'Poss. de Panc. de Chuva à Noite', 'icon' => 'bi-cloud-moon-fill'],
            'ppt' => ['description' => 'Poss. de Panc. de Chuva à Tarde', 'icon' => 'bi-cloud-sun'],
            'ppm' => ['description' => 'Poss. de Panc. de Chuva pela Manhã', 'icon' => 'bi-cloud-sun-rain'],
        ];

        return $conditions[$sigla] ?? ['description' => 'Sigla não encontrada', 'icon' => 'bi-question-circle'];
    }

    /**
     * Resolve a condição do tempo tanto pro formato antigo (sigla do CPTEC/INPE,
     * ex: 'pn', 'c') quanto pro novo (texto livre do INMET, ex: 'Chuva isolada').
     */
    public function getWeatherConditionSmart(string $valor): array
    {
        $conditions = $this->getWeatherCondition($valor);
        if ($conditions['description'] !== 'Sigla não encontrada') {
            return $conditions;
        }

        $texto = mb_strtolower($valor);
        $icon = 'bi-clouds';

        if (str_contains($texto, 'trovoada') || str_contains($texto, 'tempestade')) {
            $icon = 'bi-cloud-lightning-rain';
        } elseif (str_contains($texto, 'chuvisco')) {
            $icon = 'bi-cloud-drizzle';
        } elseif (str_contains($texto, 'chuva')) {
            $icon = 'bi-cloud-rain';
        } elseif (str_contains($texto, 'nevoeiro') || str_contains($texto, 'neblina')) {
            $icon = 'bi-cloud-fog';
        } elseif (str_contains($texto, 'encoberto') || str_contains($texto, 'muitas nuvens') || str_contains($texto, 'nublado')) {
            $icon = 'bi-cloud';
        } elseif (str_contains($texto, 'poucas nuvens') || str_contains($texto, 'parcialmente')) {
            $icon = 'bi-cloud-sun';
        } elseif (str_contains($texto, 'sol') || str_contains($texto, 'claro') || str_contains($texto, 'ensolarado')) {
            $icon = 'bi-sun';
        }

        return ['description' => $valor !== '' ? $valor : 'Sem dados', 'icon' => $icon];
    }

    public function getDiaSemana($data)
    {
        $dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        return $dias[date('w', strtotime($data))];
    }

    /**
     * Busca previsões estendidas (tabela 'previsoesextendidas').
     */
    public function getPrevisoesEstendidas(string $local): array
    {
        $lastUpdate = $this->db->rawQueryOne(
            "SELECT MAX(atualizacao) as ultima FROM previsoesextendidas WHERE local = ?",
            [$local]
        );

        if (!$lastUpdate || !$lastUpdate['ultima']) {
            return [];
        }

        return $this->db->rawQuery(
            "SELECT * FROM previsoesextendidas 
             WHERE local = ? AND atualizacao = ? AND dia >= CURRENT_DATE()
             GROUP BY dia
             ORDER BY dia ASC",
            [$local, $lastUpdate['ultima']]
        );
    }

    public function getDirecaoVento(int $graus): string
    {
        $direcoes = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW', 'N'];
        $idx = round($graus / 22.5);
        return $direcoes[$idx] ?? 'N';
    }

    /**
     * Converte ID local para nome.
     */
    public function getDadosGraficos24h(string $local = '1'): array
    {
        return $this->cache->remember("dados_graficos_24h_v4_{$local}", $this->getTtlHistorico(), function () use ($local) {
            $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
            $results = $this->db->rawQuery(
                "SELECT DATE_FORMAT(created_at, '%H:00') AS hora,
                        ROUND(AVG(temp) / 10, 1) AS temp,
                        ROUND(AVG(hum)) AS hum,
                        ROUND(AVG(COALESCE(bar, 0)) / 10, 1) AS pressao,
                        ROUND(AVG(COALESCE(heat, temp, 0)) / 10, 1) AS sensacao,
                        MIN(created_at) as min_created
                 FROM leituras_completas
                 WHERE local = ? AND created_at >= ?
                 GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')
                 ORDER BY min_created DESC
                 LIMIT 24",
                [$local, $since]
            );
            return $results ? array_reverse($results) : [];
        });
    }

    public function getMeses(): array
    {
        return [
            'all' => 'Todo o Ano',
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro'
        ];
    }

    /**
     * Busca o nowcasting mais recente que ainda seja válido.
     */
    public function getLatestNowcasting(string $local): ?array
    {
        // Ordenamos por ID/Created para garantir que estamos pegando a inserção mais fresca
        return $this->db->rawQueryOne(
            "SELECT mensagem, valido_ate, created_at, tipo_previsao 
             FROM nowcasting 
             WHERE local = ? AND valido_ate > NOW() 
             ORDER BY id DESC 
             LIMIT 1",
            [$local]
        );
    }

    public function saveNowcasting(string $local, string $mensagem, int $minutos, string $tipo = 'firme', ?array $componentes = null): bool
    {
        // Usamos rawQuery para garantir que o MySQL processe NOW() e INTERVAL corretamente
        $sql = "INSERT INTO nowcasting (`local`, `mensagem`, `tipo_previsao`, `valido_ate`, `created_at`, `score`, `diferenca_orvalho`, `taxa_pressao`, `taxa_vento`)
                VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW(), ?, ?, ?, ?)";

        $this->db->rawQuery($sql, [
            $local,
            $mensagem,
            $tipo,
            (int) $minutos,
            $componentes['score'] ?? null,
            $componentes['diferenca_orvalho'] ?? null,
            $componentes['taxa_pressao'] ?? null,
            $componentes['taxa_vento'] ?? null,
        ]);

        return $this->db->getLastError() === '';
    }

    /**
     * Busca previsões de chuva já validadas, com os componentes do score,
     * pra recalibração automática dos limiares (ver NowcastConfigModel).
     */
    public function getDadosParaCalibracao(int $dias = 90, int $limit = 5000): array
    {
        return $this->db->rawQuery(
            "SELECT score, acertou
             FROM nowcasting
             WHERE tipo_previsao = 'chuva'
               AND score IS NOT NULL
               AND acertou IS NOT NULL
               AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             ORDER BY created_at DESC
             LIMIT ?",
            [$dias, $limit]
        );
    }

    /**
     * Busca previsões que expiraram e ainda não foram validadas.
     */
    public function getNowcastingsPendentes(int $limit = 10): array
    {
        return $this->db->rawQuery(
            "SELECT id, local, tipo_previsao, created_at, valido_ate 
             FROM nowcasting 
             WHERE acertou IS NULL AND valido_ate < NOW()
             ORDER BY created_at ASC 
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Busca a maior taxa de chuva registrada em um intervalo de tempo.
     */
    public function getMaxRainRateNoPeriodo(string $local, string $inicio, string $fim): float
    {
        $res = $this->db->rawQueryOne(
            "SELECT MAX(rainrate) as max_rate 
             FROM leituras_completas 
             WHERE local = ? AND created_at BETWEEN ? AND ?",
            [$local, $inicio, $fim]
        );
        return round((float) (($res['max_rate'] ?? 0) / 10), 1);
    }

    /**
     * Atualiza o resultado de uma previsão.
     */
    public function updateNowcastingResultado(int $id, int $acertou, float $chuva): bool
    {
        $this->db->where('id', $id);
        return $this->db->update('nowcasting', [
            'acertou' => $acertou,
            'chuva_registrada' => $chuva
        ]);
    }

    /**
     * Retorna estatísticas de assertividade.
     */
    public function getNowcastingStats(): array
    {
        $stats = $this->db->rawQueryOne(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN acertou = 1 THEN 1 ELSE 0 END) as acertos,
                SUM(CASE WHEN acertou = 0 THEN 1 ELSE 0 END) as erros
             FROM nowcasting 
             WHERE acertou IS NOT NULL"
        );

        $porTipo = $this->db->rawQuery(
            "SELECT 
                tipo_previsao,
                COUNT(*) as total,
                SUM(CASE WHEN acertou = 1 THEN 1 ELSE 0 END) as acertos
             FROM nowcasting 
             WHERE acertou IS NOT NULL
             GROUP BY tipo_previsao"
        );

        return [
            'geral' => $stats,
            'por_tipo' => $porTipo
        ];
    }

    /**
     * Verifica se houve registro de chuva (rainrate > 0) nos últimos X minutos.
     */
    public function wasRainingRecently(string $local, int $minutos = 10): bool
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$minutos} minutes"));
        $res = $this->db->rawQueryOne(
            "SELECT COUNT(*) as total 
             FROM leituras_completas 
             WHERE local = ? AND created_at >= ? AND rainrate > 0",
            [$local, $since]
        );
        return ($res['total'] ?? 0) > 0;
    }

    /**
     * Busca uma leitura de aproximadamente X minutos atrás.
     */
    public function getLeituraAnterior(string $local, int $minutos = 60): ?array
    {
        $targetTime = date('Y-m-d H:i:s', strtotime("-{$minutos} minutes"));

        $result = $this->db->rawQueryOne(
            "SELECT l.temp, l.hum, l.bar, l.wspd, l.dew, l.rainrate, l.created_at
             FROM leituras_completas l
             WHERE l.local = ? AND l.created_at <= ?
             ORDER BY l.created_at DESC
             LIMIT 1",
            [$local, $targetTime]
        );

        if (!$result)
            return null;

        return [
            'temperatura' => round(($result['temp'] ?? 0) / 10, 1),
            'umidade' => (int) ($result['hum'] ?? 0),
            'barometro' => ($result['bar'] ?? 0) / 10,
            'vento' => ($result['wspd'] ?? 0) / 10 * 3.6,
            'ponto_orvalho' => round((float) (($result['dew'] ?? 0) / 10), 1),
            'rainrate' => round((float) (($result['rainrate'] ?? 0) / 10), 1),
        ];
    }

    /**
     * Calcula a Evapotranspiração (ET) acumulada do dia.
     */
    public function getEvapotranspiracaoHoje(string $local): float
    {
        return $this->cache->remember("et_hoje_v1_{$local}", $this->ttlRapido, function () use ($local) {
            $sql = "SELECT SUM(et * diff_seg / 36000) AS total_mm
                    FROM (
                        SELECT et,
                               TIMESTAMPDIFF(
                                   SECOND,
                                   created_at,
                                   LEAD(created_at) OVER (ORDER BY created_at)
                               ) AS diff_seg
                        FROM leituras_completas
                        WHERE local = ? AND DATE(created_at) = CURDATE()
                    ) t
                    WHERE diff_seg IS NOT NULL";

            $res = $this->db->rawQueryOne($sql, [$local]);
            return round((float) ($res['total_mm'] ?? 0), 2);
        });
    }


    /**
     * Evapotranspiração: total por dia (mês escolhido) ou por mês (ano todo, $month = 'all').
     */
    public function getEtResumo(string $local, int $year, mixed $month): array
    {
        $dias = $this->resumoDoPeriodo($local, $year, $month, 'et');

        if ($month === 'all') {
            $porMes = [];
            foreach ($dias as $dia => $d) {
                $m = (int) substr($dia, 5, 2);
                $porMes[$m] = ($porMes[$m] ?? 0) + $d['et'];
            }
            krsort($porMes);
            $saida = [];
            foreach ($porMes as $m => $total) {
                $saida[] = [
                    'mes' => $m,
                    'mes_nome' => $this->getMesNome($m),
                    'total' => number_format($total, 2, ',', '.'),
                ];
            }
            return $saida;
        }

        $saida = [];
        foreach ($dias as $dia => $d) {
            $saida[] = [
                'data' => date('d/m/Y', strtotime($dia)),
                'dia' => date('d', strtotime($dia)),
                'total' => number_format($d['et'], 2, ',', '.'),
            ];
        }
        return $saida;
    }

    private function getLocalNome(string $local): string
    {
        $locais = [
            '1' => 'Sede',
            '2' => 'Zona Rural',
        ];
        return $locais[$local] ?? "Local {$local}";
    }

    private function getMesNome(int $mes): string
    {
        $meses = [
            1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr',
            5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'
        ];
        return $meses[$mes] ?? '';
    }
}
