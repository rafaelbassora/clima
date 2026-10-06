<?php

namespace App\Models;

class ChuvaCorrecaoModel
{
    private \MysqliDb $db;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
    }

    public function getAll(): array
    {
        return $this->db->orderBy('data', 'DESC')->orderBy('created_at', 'DESC')->get('chuva_correcoes');
    }

    public function getById(int $id): ?array
    {
        $this->db->where('id', $id);
        $item = $this->db->getOne('chuva_correcoes');
        return $item ?: null;
    }

    /**
     * Verifica se a estação já captou chuva de verdade (rainrate > 0) numa hora específica.
     */
    private function estacaoCaptouNaHora(string $local, string $data, string $hora): bool
    {
        $inicio = "{$data} {$hora}:00";
        $fim = date('Y-m-d H:i:s', strtotime($inicio) + 3599);

        $res = $this->db->rawQueryOne(
            "SELECT COUNT(*) as total FROM leituras_completas
             WHERE local = ? AND created_at BETWEEN ? AND ? AND rainrate > 0",
            [$local, $inicio, $fim]
        );
        return ($res['total'] ?? 0) > 0;
    }

    /**
     * Calcula o efeito líquido (mm) de uma correção, considerando se a estação
     * já capturou chuva real naquela hora (nesse caso a correção é ignorada/sobreposta).
     */
    private function efeitoLiquido(array $row): float
    {
        $valor = (float) $row['valor_mm'];
        $sinal = ((int) $row['choveu']) === 0 ? -1 : 1;

        // Correção de dia inteiro (sem hora definida): sempre se aplica.
        if (empty($row['hora'])) {
            return $sinal * $valor;
        }

        // Correção pontual de uma hora: se a estação já captou chuva real
        // nessa mesma hora, a correção manual é sobreposta (ignorada).
        $hora = substr($row['hora'], 0, 5);
        if ($this->estacaoCaptouNaHora((string) $row['local'], (string) $row['data'], $hora)) {
            return 0.0;
        }

        return $sinal * $valor;
    }

    /**
     * Retorna o efeito líquido total (mm) de todas as correções de um local/data.
     */
    public function getValor(string $local, string $data): ?float
    {
        $this->db->where('local', $local);
        $this->db->where('data', $data);
        $rows = $this->db->get('chuva_correcoes');

        if (empty($rows)) {
            return null;
        }

        $total = 0.0;
        foreach ($rows as $row) {
            $total += $this->efeitoLiquido($row);
        }
        return $total;
    }

    /**
     * Efeito líquido das correções de um local num intervalo, indexado por data (Y-m-d).
     */
    public function getMapaPeriodo(string $local, string $inicio, string $fim): array
    {
        $this->db->where('local', $local);
        $this->db->where('data', [$inicio, $fim], 'BETWEEN');
        $rows = $this->db->get('chuva_correcoes');

        $mapa = [];
        foreach ($rows as $row) {
            $d = $row['data'];
            if (!isset($mapa[$d])) {
                $mapa[$d] = 0.0;
            }
            $mapa[$d] += $this->efeitoLiquido($row);
        }
        return $mapa;
    }

    /**
     * Lança uma nova correção (soma/subtrai com as já existentes, não substitui).
     */
    public function salvar(string $local, string $data, float $valorMm, ?string $obs = null, ?string $hora = null, bool $choveu = true): bool
    {
        $now = date('Y-m-d H:i:s');
        return (bool) $this->db->insert('chuva_correcoes', [
            'local' => $local,
            'data' => $data,
            'hora' => $hora ?: null,
            'valor_mm' => $valorMm,
            'choveu' => $choveu ? 1 : 0,
            'obs' => $obs,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function delete(int $id): bool
    {
        $this->db->where('id', $id);
        return $this->db->delete('chuva_correcoes');
    }
}
