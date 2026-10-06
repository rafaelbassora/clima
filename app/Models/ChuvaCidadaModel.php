<?php

namespace App\Models;

class ChuvaCidadaModel
{
    private \MysqliDb $db;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
    }

    public function lancar(int $cidadaoId, string $data, float $medidaMm, bool $granizo = false, bool $chuvaMansa = false, ?string $observacao = null): bool
    {
        return (bool) $this->db->insert('chuva_cidada', [
            'cidadao_id' => $cidadaoId,
            'data' => $data,
            'medida_mm' => $medidaMm,
            'granizo' => $granizo ? 1 : 0,
            'chuva_mansa' => $chuvaMansa ? 1 : 0,
            'observacao' => $observacao ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getHistorico(int $cidadaoId, int $limit = 60): array
    {
        $this->db->where('cidadao_id', $cidadaoId);
        return $this->db->orderBy('data', 'DESC')->get('chuva_cidada', $limit);
    }

    /**
     * Últimos lançamentos de todas as estações aprovadas, pra exibição pública
     * (sem nenhum dado pessoal — só código da estação e bairro).
     */
    public function getUltimosPublicos(int $limit = 100): array
    {
        return $this->db->rawQuery(
            "SELECT c.station_code, c.bairro, cc.data, cc.medida_mm
             FROM chuva_cidada cc
             INNER JOIN cidadaos c ON c.id = cc.cidadao_id
             WHERE c.status = 'aprovado'
             ORDER BY cc.data DESC, cc.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Total acumulado do mês por estação, pra exibição pública.
     */
    public function getResumoMensalPublico(int $year, int $month): array
    {
        $start = "{$year}-" . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . "-01";
        $end = date('Y-m-t', strtotime($start));

        return $this->db->rawQuery(
            "SELECT c.station_code, c.bairro, SUM(cc.medida_mm) as total_mm, MAX(cc.data) as ultima_data
             FROM chuva_cidada cc
             INNER JOIN cidadaos c ON c.id = cc.cidadao_id
             WHERE c.status = 'aprovado' AND cc.data BETWEEN ? AND ?
             GROUP BY c.id
             ORDER BY total_mm DESC",
            [$start, $end]
        );
    }
}
