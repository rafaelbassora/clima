<?php

namespace App\Models;

class ChuvaCidadaModel
{
    private \MysqliDb $db;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
    }

    public function lancar(int $cidadaoId, string $data, float $medidaMm, bool $granizo = false, bool $chuvaMansa = false, bool $chuvaForte = false, ?string $observacao = null): bool
    {
        return (bool) $this->db->insert('chuva_cidada', [
            'cidadao_id' => $cidadaoId,
            'data' => $data,
            'medida_mm' => $medidaMm,
            'granizo' => $granizo ? 1 : 0,
            'chuva_mansa' => $chuvaMansa ? 1 : 0,
            'chuva_forte' => $chuvaForte ? 1 : 0,
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
     * Busca um lançamento garantindo que pertence ao cidadão (evita editar/ver de outra pessoa).
     */
    public function getByIdDoCidadao(int $id, int $cidadaoId): ?array
    {
        $this->db->where('id', $id);
        $this->db->where('cidadao_id', $cidadaoId);
        $item = $this->db->getOne('chuva_cidada');
        return $item ?: null;
    }

    public function atualizar(int $id, int $cidadaoId, string $data, float $medidaMm, bool $granizo = false, bool $chuvaMansa = false, bool $chuvaForte = false, ?string $observacao = null): bool
    {
        $this->db->where('id', $id);
        $this->db->where('cidadao_id', $cidadaoId);
        return $this->db->update('chuva_cidada', [
            'data' => $data,
            'medida_mm' => $medidaMm,
            'granizo' => $granizo ? 1 : 0,
            'chuva_mansa' => $chuvaMansa ? 1 : 0,
            'chuva_forte' => $chuvaForte ? 1 : 0,
            'observacao' => $observacao ?: null,
        ]);
    }

    public function excluir(int $id, int $cidadaoId): bool
    {
        $this->db->where('id', $id);
        $this->db->where('cidadao_id', $cidadaoId);
        return $this->db->delete('chuva_cidada');
    }

    /**
     * Amostra aleatória de estações aprovadas com a situação do dia de hoje
     * (se já lançaram dado hoje ou não), pra exibição pública na home.
     */
    public function getAmostraStatusHoje(int $limit = 10): array
    {
        return $this->db->rawQuery(
            "SELECT c.station_code, c.bairro, cc.medida_mm, cc.granizo, cc.chuva_mansa, cc.chuva_forte
             FROM cidadaos c
             LEFT JOIN chuva_cidada cc ON cc.cidadao_id = c.id AND cc.data = CURDATE()
             WHERE c.status = 'aprovado'
             ORDER BY RAND()
             LIMIT ?",
            [$limit]
        );
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
     * Histórico público de uma estação específica (só se estiver aprovada).
     */
    public function getHistoricoPorEstacao(string $stationCode, int $limit = 90): array
    {
        return $this->db->rawQuery(
            "SELECT cc.data, cc.medida_mm, cc.granizo, cc.chuva_mansa, cc.chuva_forte, cc.observacao
             FROM chuva_cidada cc
             INNER JOIN cidadaos c ON c.id = cc.cidadao_id
             WHERE c.status = 'aprovado' AND c.station_code = ?
             ORDER BY cc.data DESC
             LIMIT ?",
            [$stationCode, $limit]
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
