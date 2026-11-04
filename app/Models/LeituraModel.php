<?php

namespace App\Models;

use Core\Cache;

class LeituraModel
{
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
     * Pressão (Barômetro) nos últimos 7 dias (mín/máx).
     */
    public function getPressao7Dias(string $local = '1'): array
    {
        return $this->cache->remember("pressao_7dias_{$local}", $this->getTtlHistorico(), function () use ($local) {
            $activation = '2026-04-13 15:00:00';
            $since = date('Y-m-d', strtotime('-7 days'));

            $results = $this->db->rawQuery(
                "SELECT data_leitura AS data,
                        MIN(COALESCE(bar, 0)) / 10 AS bar_min,
                        MAX(COALESCE(bar, 0)) / 10 AS bar_max
                 FROM leituras_completas
                 WHERE local = ? AND created_at >= ? AND created_at >= ?
                 GROUP BY data_leitura
                 ORDER BY data DESC
                 LIMIT 7",
                [$local, $since, $activation]
            );

            return array_map(function ($row) use ($local) {
                $data = $row['data'] ?? date('Y-m-d');
                return [
                    'data' => date('d/m/Y', strtotime($data)),
                    'pressao_min' => number_format((float) ($row['bar_min'] ?? 0), 1, ',', '.'),
                    'pressao_max' => number_format((float) ($row['bar_max'] ?? 0), 1, ',', '.'),
                    'local' => $this->getLocalNome($local),
                ];
            }, $results ?: []);
        });
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
     * Vento nos últimos 7 dias (média/máx).
     */
    public function getVento7Dias(string $local = '1'): array
    {
        return $this->cache->remember("vento_7dias_{$local}", $this->getTtlHistorico(), function () use ($local) {
            $since = date('Y-m-d', strtotime('-7 days'));
            $results = $this->db->rawQuery(
                "SELECT data_leitura AS data,
                        AVG(COALESCE(wspd, 0)) / 10 * 3.6 AS vento_avg,
                        MAX(COALESCE(wspdhi, wspd, 0)) / 10 * 3.6 AS vento_max
                 FROM leituras_completas
                 WHERE local = ? AND created_at >= ?
                 GROUP BY data_leitura
                 ORDER BY data DESC
                 LIMIT 7",
                [$local, $since]
            );

            return array_map(function ($row) use ($local) {
                $data = $row['data'] ?? date('Y-m-d');
                return [
                    'data' => date('d/m/Y', strtotime($data)),
                    'vento_avg' => number_format((float) ($row['vento_avg'] ?? 0), 1, ',', '.'),
                    'vento_max' => number_format((float) ($row['vento_max'] ?? 0), 1, ',', '.'),
                    'local' => $this->getLocalNome($local),
                ];
            }, $results ?: []);
        });
    }

    /**
     * Histórico de Pressão por mês/ano.
     */
    public function getPressaoResumo(string $local, int $year, int $month): array
    {
        $since = "{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $until = date('Y-m-t', strtotime($since));
        $activation = '2026-04-13 15:00:00';

        $results = $this->db->rawQuery(
            "SELECT DATE(created_at) as data,
                    MIN(COALESCE(bar, 0)) / 10 as bar_min,
                    MAX(COALESCE(bar, 0)) / 10 as bar_max,
                    created_at as full_date
             FROM leituras_completas
             WHERE local = ? AND created_at >= ? AND DATE(created_at) BETWEEN ? AND ?
             GROUP BY data
             ORDER BY data DESC",
            [$local, $activation, $since, $until]
        );

        return array_map(function ($row) {
            $data = $row['data'] ?? date('Y-m-d');
            $full = $row['full_date'] ?? $data;
            return [
                'data' => date('d/m/Y', strtotime($data)),
                'min' => number_format((float) ($row['bar_min'] ?? 0), 1, ',', '.'),
                'max' => number_format((float) ($row['bar_max'] ?? 0), 1, ',', '.'),
                'hora' => date('H:i', strtotime($full))
            ];
        }, $results ?: []);
    }

    /**
     * Recordes de Pressão.
     * TTL estendido para 1 hora para evitar lentidão em DBs na nuvem.
     */
    public function getEstatisticasPressao(string $local): array
    {
        return $this->cache->remember("stats_pressao_{$local}", 3600, function () use ($local) {
            $activation = '2026-04-13 15:00:00';
            // 1. Pega as extremas de cada dia
            $maximasRaw = $this->db->rawQuery(
                "SELECT data_leitura as dia, MAX(COALESCE(bar, 0)) as max_bar
                 FROM leituras_completas
                 WHERE local = ? AND created_at >= ?
                 GROUP BY dia
                 ORDER BY max_bar DESC
                 LIMIT 5",
                [$local, $activation]
            );

            $minimasRaw = $this->db->rawQuery(
                "SELECT data_leitura as dia, MIN(COALESCE(bar, 0)) as min_bar
                 FROM leituras_completas
                 WHERE local = ? AND created_at >= ? AND bar > 0
                 GROUP BY dia
                 ORDER BY min_bar ASC
                 LIMIT 5",
                [$local, $activation]
            );

            // 2. Busca o horário exato
            $maximas = [];
            foreach ($maximasRaw as $row) {
                $item = $this->db->rawQueryOne(
                    "SELECT created_at as data_hora, bar / 10 as valor
                     FROM leituras_completas
                     WHERE local = ? AND data_leitura = ? AND bar = ?
                     LIMIT 1",
                    [$local, $row['dia'], $row['max_bar']]
                );
                if ($item) {
                    $item['dia'] = $row['dia'];
                    $maximas[] = $item;
                }
            }

            $minimas = [];
            foreach ($minimasRaw as $row) {
                $item = $this->db->rawQueryOne(
                    "SELECT created_at as data_hora, bar / 10 as valor
                     FROM leituras_completas
                     WHERE local = ? AND data_leitura = ? AND bar = ?
                     LIMIT 1",
                    [$local, $row['dia'], $row['min_bar']]
                );
                if ($item) {
                    $item['dia'] = $row['dia'];
                    $minimas[] = $item;
                }
            }

            return [
                'top_max' => $maximas,
                'top_min' => $minimas
            ];
        });
    }

    public function getUmidadeResumo(string $local, int $year, int $month): array
    {
        return $this->cache->remember("umidade_resumo_{$local}_{$year}_{$month}", $this->getTtlHistorico(), function () use ($local, $year, $month) {
            $since = "{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
            $until = date('Y-m-t', strtotime($since));

            $results = $this->db->rawQuery(
                "SELECT data_leitura as data,
                        MIN(hum) as hum_min,
                        MAX(hum) as hum_max
                 FROM leituras_completas
                 WHERE local = ? AND data_leitura BETWEEN ? AND ?
                 GROUP BY data
                 ORDER BY data DESC",
                [$local, $since, $until]
            );

            return array_map(function ($row) {
                $data = $row['data'] ?? date('Y-m-d');
                return [
                    'data' => date('d/m/Y', strtotime($data)),
                    'min' => (int) ($row['hum_min'] ?? 0),
                    'max' => (int) ($row['hum_max'] ?? 0)
                ];
            }, $results ?: []);
        });
    }

    /**
     * Histórico de Ventos por mês/ano.
     */
    public function getVentoResumo(string $local, int $year, int $month): array
    {
        $since = "{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $until = date('Y-m-t', strtotime($since));

        $results = $this->db->rawQuery(
            "SELECT DATE(created_at) as data,
                    AVG(COALESCE(wspd, 0)) / 10 * 3.6 as vento_avg,
                    MAX(COALESCE(wspdhi, wspd, 0)) / 10 * 3.6 as vento_max,
                    MAX(wdir) as direcao,
                    created_at as full_date
             FROM leituras_completas
             WHERE local = ? AND DATE(created_at) BETWEEN ? AND ?
             GROUP BY data
             ORDER BY data DESC",
            [$local, $since, $until]
        );

        return array_map(function ($row) {
            $data = $row['data'] ?? date('Y-m-d');
            $full = $row['full_date'] ?? $data;
            return [
                'data' => date('d/m/Y', strtotime($data)),
                'avg' => number_format((float) ($row['vento_avg'] ?? 0), 1, ',', '.'),
                'max' => number_format((float) ($row['vento_max'] ?? 0), 1, ',', '.'),
                'direcao' => $this->getDirecaoVento((int) ($row['direcao'] ?? 0)),
                'hora' => date('H:i', strtotime($full))
            ];
        }, $results ?: []);
    }

    /**
     * Recordes de Vento.
     * TTL estendido para 1 hora para evitar lentidão em DBs na nuvem.
     */
    public function getEstatisticasVento(string $local): array
    {
        return $this->cache->remember("stats_vento_{$local}", 3600, function () use ($local) {
            // 1. Pega as máximas de cada dia
            $maximasRaw = $this->db->rawQuery(
                "SELECT DATE(created_at) as dia, MAX(COALESCE(wspdhi, wspd, 0)) as max_wspd
                 FROM leituras_completas
                 WHERE local = ?
                 GROUP BY dia
                 ORDER BY max_wspd DESC
                 LIMIT 5",
                [$local]
            );

            // 2. Busca o horário exato e direção de cada máxima
            $maximas = [];
            foreach ($maximasRaw as $row) {
                $item = $this->db->rawQueryOne(
                    "SELECT created_at as data_hora, COALESCE(wspdhi, wspd, 0) / 10 * 3.6 as valor, wdir as direcao
                     FROM leituras_completas
                     WHERE local = ? AND DATE(created_at) = ? AND COALESCE(wspdhi, wspd, 0) = ?
                     LIMIT 1",
                    [$local, $row['dia'], $row['max_wspd']]
                );
                if ($item) {
                    $item['dia'] = $row['dia'];
                    $item['direcao_label'] = $this->getDirecaoVento((int) ($item['direcao'] ?? 0));
                    $maximas[] = $item;
                }
            }

            return [
                'top_max' => $maximas
            ];
        });
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
     * Busca estatísticas históricas de temperatura (Top Calor / Top Frio).
     */
    public function getEstatisticasTemperatura(string $local): array
    {
        $cacheKey = "temp_estatisticas_daily_{$local}";
        return $this->cache->remember($cacheKey, $this->getTtlHistorico(), function () use ($local) {

            // Top 5 Dias Mais Quentes (Uma leitura por dia)
            $topCalorRaw = $this->db->rawQuery(
                "SELECT DATE(created_at) as dia, MAX(temp) as max_temp
                 FROM leituras_completas
                 WHERE local = ? AND temp > 0
                 GROUP BY dia
                 ORDER BY max_temp DESC
                 LIMIT 5",
                [$local]
            );

            // Busca o horário exato daquela máxima para cada dia (Top Calor)
            $topCalor = [];
            foreach ($topCalorRaw as $row) {
                $diaStart = $row['dia'] . ' 00:00:00';
                $diaEnd   = $row['dia'] . ' 23:59:59';
                $item = $this->db->rawQueryOne(
                    "SELECT created_at as data, temp / 10 as valor
                     FROM leituras_completas
                     WHERE local = ? AND created_at >= ? AND created_at <= ? AND temp = ?
                     LIMIT 1",
                    [$local, $diaStart, $diaEnd, (int) $row['max_temp']]
                );
                if ($item)
                    $topCalor[] = $item;
            }

            // Top 5 Dias Mais Frios (Uma leitura por dia)
            $topFrioRaw = $this->db->rawQuery(
                "SELECT DATE(created_at) as dia, MIN(temp) as min_temp
                 FROM leituras_completas
                 WHERE local = ? AND temp > 0
                 GROUP BY dia
                 ORDER BY min_temp ASC
                 LIMIT 5",
                [$local]
            );

            // Busca o horário exato daquela mínima para cada dia (Top Frio)
            $topFrio = [];
            foreach ($topFrioRaw as $row) {
                $diaStart = $row['dia'] . ' 00:00:00';
                $diaEnd   = $row['dia'] . ' 23:59:59';
                $item = $this->db->rawQueryOne(
                    "SELECT created_at as data, temp / 10 as valor
                     FROM leituras_completas
                     WHERE local = ? AND created_at >= ? AND created_at <= ? AND temp = ?
                     LIMIT 1",
                    [$local, $diaStart, $diaEnd, (int) $row['min_temp']]
                );
                if ($item)
                    $topFrio[] = $item;
            }

            return [
                'calor' => array_map(function ($r) {
                    $r['valor'] = number_format($r['valor'], 1);
                    $r['data'] = date('d/m/Y H:i', strtotime($r['data']));
                    return $r;
                }, $topCalor),
                'frio' => array_map(function ($r) {
                    $r['valor'] = number_format($r['valor'], 1);
                    $r['data'] = date('d/m/Y H:i', strtotime($r['data']));
                    return $r;
                }, $topFrio),
            ];
        });
    }

    /**
     * Busca estatísticas de temperatura de um ano específico (Top Calor / Top Frio do ano).
     */
    public function getEstatisticasTemperaturaAno(string $local, int $year): array
    {
        $cacheKey = "temp_estatisticas_ano_{$local}_{$year}";
        return $this->cache->remember($cacheKey, $this->getTtlHistorico(), function () use ($local, $year) {
            $start = "{$year}-01-01 00:00:00";
            $end   = "{$year}-12-31 23:59:59";

            // Top 5 Dias Mais Quentes do Ano
            $topCalorRaw = $this->db->rawQuery(
                "SELECT DATE(created_at) as dia, MAX(temp) as max_temp
                 FROM leituras_completas
                 WHERE local = ? AND created_at >= ? AND created_at <= ? AND temp > 0
                 GROUP BY dia
                 ORDER BY max_temp DESC
                 LIMIT 5",
                [$local, $start, $end]
            );

            $topCalor = [];
            foreach ($topCalorRaw as $row) {
                $diaStart = $row['dia'] . ' 00:00:00';
                $diaEnd   = $row['dia'] . ' 23:59:59';
                $item = $this->db->rawQueryOne(
                    "SELECT created_at as data, temp / 10 as valor
                     FROM leituras_completas
                     WHERE local = ? AND created_at >= ? AND created_at <= ? AND temp = ?
                     LIMIT 1",
                    [$local, $diaStart, $diaEnd, (int) $row['max_temp']]
                );
                if ($item) {
                    $item['dia'] = $row['dia'];
                    $topCalor[] = $item;
                }
            }

            // Top 5 Dias Mais Frios do Ano
            $topFrioRaw = $this->db->rawQuery(
                "SELECT DATE(created_at) as dia, MIN(temp) as min_temp
                 FROM leituras_completas
                 WHERE local = ? AND created_at >= ? AND created_at <= ? AND temp > 0
                 GROUP BY dia
                 ORDER BY min_temp ASC
                 LIMIT 5",
                [$local, $start, $end]
            );

            $topFrio = [];
            foreach ($topFrioRaw as $row) {
                $diaStart = $row['dia'] . ' 00:00:00';
                $diaEnd   = $row['dia'] . ' 23:59:59';
                $item = $this->db->rawQueryOne(
                    "SELECT created_at as data, temp / 10 as valor
                     FROM leituras_completas
                     WHERE local = ? AND created_at >= ? AND created_at <= ? AND temp = ?
                     LIMIT 1",
                    [$local, $diaStart, $diaEnd, (int) $row['min_temp']]
                );
                if ($item) {
                    $item['dia'] = $row['dia'];
                    $topFrio[] = $item;
                }
            }

            return [
                'calor' => array_map(function ($r) {
                    $r['valor'] = number_format($r['valor'], 1);
                    $r['data'] = date('d/m/Y H:i', strtotime($r['data']));
                    return $r;
                }, $topCalor),
                'frio' => array_map(function ($r) {
                    $r['valor'] = number_format($r['valor'], 1);
                    $r['data'] = date('d/m/Y H:i', strtotime($r['data']));
                    return $r;
                }, $topFrio),
            ];
        });
    }

    /**
     * Busca resumo mensal de temperatura com horários de min/max.
     */
    public function getTemperaturaResumo(string $local, int $year, mixed $month): array
    {
        $cacheKey = "temp_resumo_{$local}_{$year}_{$month}";
        return $this->cache->remember($cacheKey, $this->getTtlHistorico(), function () use ($local, $year, $month) {

            if ($month !== 'all') {
                $m = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
                $start = "{$year}-{$m}-01 00:00:00";
                $end   = date('Y-m-t 23:59:59', strtotime($start));
            } else {
                $start = "{$year}-01-01 00:00:00";
                $end   = "{$year}-12-31 23:59:59";
            }

            // Busca as máximas e mínimas diárias
            $results = $this->db->rawQuery(
                "SELECT 
                    DATE(created_at) as data_dia,
                    MIN(temp) / 10 as tmin,
                    MAX(temp) / 10 as tmax
                 FROM leituras_completas
                 WHERE local = ? AND created_at >= ? AND created_at <= ?
                 GROUP BY data_dia
                 ORDER BY data_dia DESC",
                [$local, $start, $end]
            );

            // Para cada dia, buscar a primeira ocorrência do horário da min e max
            $final = [];
            foreach ($results as $row) {
                $dia = $row['data_dia'];
                $diaStart = "{$dia} 00:00:00";
                $diaEnd   = "{$dia} 23:59:59";

                // Horário da Min (SQL escalado por 10)
                $hMin = $this->db->rawQueryOne(
                    "SELECT DATE_FORMAT(created_at, '%H:%i') as hora 
                     FROM leituras_completas 
                     WHERE local = ? AND created_at >= ? AND created_at <= ? AND temp = ?
                     LIMIT 1",
                    [$local, $diaStart, $diaEnd, (int) round($row['tmin'] * 10)]
                );

                // Horário da Max
                $hMax = $this->db->rawQueryOne(
                    "SELECT DATE_FORMAT(created_at, '%H:%i') as hora 
                     FROM leituras_completas 
                     WHERE local = ? AND created_at >= ? AND created_at <= ? AND temp = ?
                     LIMIT 1",
                    [$local, $diaStart, $diaEnd, (int) round($row['tmax'] * 10)]
                );

                $final[] = [
                    'data' => date('d/m/Y', strtotime($dia)),
                    'temp_min' => number_format($row['tmin'], 1),
                    'hora_min' => $hMin['hora'] ?? '--:--',
                    'temp_max' => number_format($row['temp_max'] ?? $row['tmax'], 1),
                    'hora_max' => $hMax['hora'] ?? '--:--',
                ];
            }

            return $final;
        });
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
     * Busca ET diária filtrada para um mês ou ano específico.
     */
    public function getEtResumo(string $local, int $year, mixed $month): array
    {
        $cacheKey = "et_resumo_v1_{$local}_{$year}_{$month}";
        return $this->cache->remember($cacheKey, $this->getTtlHistorico(), function () use ($local, $year, $month) {

            if ($month === 'all') {
                $start = "{$year}-01-01 00:00:00";
                $end = "{$year}-12-31 23:59:59";

                $results = $this->db->rawQuery(
                    "SELECT MONTH(data) as mes, SUM(total_mm) as max_et
                     FROM (
                         SELECT DATE(created_at) AS data,
                                SUM(et * diff_seg / 36000) AS total_mm
                         FROM (
                             SELECT created_at, et,
                                    TIMESTAMPDIFF(
                                        SECOND,
                                        created_at,
                                        LEAD(created_at) OVER (ORDER BY created_at)
                                    ) AS diff_seg
                             FROM leituras_completas
                             WHERE local = ? AND created_at BETWEEN ? AND ?
                         ) t
                         WHERE diff_seg IS NOT NULL
                         GROUP BY DATE(created_at)
                     ) t2
                     GROUP BY mes
                     ORDER BY mes DESC",
                    [$local, $start, $end]
                );

                return array_map(function ($row) {
                    $mesNum = (int) ($row['mes'] ?? 1);
                    return [
                        'mes' => $mesNum,
                        'mes_nome' => $this->getMesNome($mesNum),
                        'total' => number_format((float) ($row['max_et'] ?? 0), 2, ',', '.')
                    ];
                }, $results ?: []);
            } else {
                $start = "{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01 00:00:00";
                $end = date('Y-m-t 23:59:59', strtotime($start));

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
                         WHERE local = ? AND created_at BETWEEN ? AND ?
                     ) t
                     WHERE diff_seg IS NOT NULL
                     GROUP BY DATE(created_at)
                     ORDER BY data DESC",
                    [$local, $start, $end]
                );

                return array_map(function ($row) {
                    $data = $row['data'] ?? date('Y-m-d');
                    return [
                        'data' => date('d/m/Y', strtotime($data)),
                        'dia' => date('d', strtotime($data)),
                        'total' => number_format((float) ($row['total_mm'] ?? 0), 2, ',', '.')
                    ];
                }, $results ?: []);
            }
        });
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
