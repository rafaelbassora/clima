<?php

namespace App\Models;

class ChuvaCidadaModel
{
    public const MESES = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];

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
     * Últimos lançamentos com observação, nome e e-mail do cidadão — só pro admin moderar.
     */
    public function getUltimosParaAdmin(int $limit = 50): array
    {
        return $this->db->rawQuery(
            "SELECT c.station_code, c.nome, c.email, cc.data, cc.medida_mm, cc.granizo, cc.chuva_mansa, cc.chuva_forte, cc.observacao
             FROM chuva_cidada cc
             INNER JOIN cidadaos c ON c.id = cc.cidadao_id
             WHERE cc.observacao IS NOT NULL AND cc.observacao != ''
             ORDER BY cc.created_at DESC
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
            "SELECT c.station_code, c.bairro, SUM(cc.medida_mm) as total_mm, MAX(cc.data) as ultima_data,
                    COUNT(cc.id) as dias_lancados, SUM(cc.medida_mm > 0) as dias_chuva, MAX(cc.medida_mm) as maior_mm
             FROM chuva_cidada cc
             INNER JOIN cidadaos c ON c.id = cc.cidadao_id
             WHERE c.status = 'aprovado' AND cc.data BETWEEN ? AND ?
             GROUP BY c.id, c.station_code, c.bairro
             ORDER BY total_mm DESC",
            [$start, $end]
        );
    }

    /**
     * Lançamentos de uma estação aprovada dentro de um mês específico.
     */
    public function getHistoricoPorEstacaoMes(string $stationCode, int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));

        return $this->db->rawQuery(
            "SELECT cc.data, cc.medida_mm, cc.granizo, cc.chuva_mansa, cc.chuva_forte
             FROM chuva_cidada cc
             INNER JOIN cidadaos c ON c.id = cc.cidadao_id
             WHERE c.status = 'aprovado' AND c.station_code = ? AND cc.data BETWEEN ? AND ?
             ORDER BY cc.data DESC",
            [$stationCode, $start, $end]
        );
    }

    /**
     * Total por mês de uma estação aprovada (meses mais recentes primeiro).
     */
    public function getTotaisMensaisPorEstacao(string $stationCode, int $limit = 12): array
    {
        return $this->db->rawQuery(
            "SELECT YEAR(cc.data) as ano, MONTH(cc.data) as mes, SUM(cc.medida_mm) as total_mm,
                    COUNT(cc.id) as dias_lancados, SUM(cc.medida_mm > 0) as dias_chuva
             FROM chuva_cidada cc
             INNER JOIN cidadaos c ON c.id = cc.cidadao_id
             WHERE c.status = 'aprovado' AND c.station_code = ?
             GROUP BY YEAR(cc.data), MONTH(cc.data)
             ORDER BY ano DESC, mes DESC
             LIMIT ?",
            [$stationCode, $limit]
        );
    }

    /**
     * Ano do lançamento mais antigo (pra montar o filtro de ano).
     */
    public function getAnoInicial(): int
    {
        $row = $this->db->rawQueryOne("SELECT MIN(YEAR(data)) as ano FROM chuva_cidada");
        $ano = (int) ($row['ano'] ?? 0);
        return $ano > 0 ? $ano : (int) date('Y');
    }
}
