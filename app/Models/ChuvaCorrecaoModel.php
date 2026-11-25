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
     * Chuva (mm) que a própria estação mediu na 1 hora a partir de $hora, somando os
     * incrementos do contador (mesmo critério do LeituraModel: ignora saltos > 5 mm
     * numa leitura, que são reset/memória do console e não chuva).
     */
    private function chuvaEstacaoNaHora(string $local, string $data, string $hora): float
    {
        $inicio = "{$data} {$hora}:00";
        $fim = date('Y-m-d H:i:s', strtotime($inicio) + 3599);

        $rows = $this->db->rawQuery(
            "SELECT rain FROM leituras_completas
             WHERE local = ? AND created_at BETWEEN ? AND ? AND wid = 'Local'
             ORDER BY created_at",
            [$local, $inicio, $fim]
        );

        $total = 0;
        $anterior = null;
        foreach ($rows as $r) {
            $atual = (int) $r['rain'];
            if ($anterior !== null) {
                $delta = $atual - $anterior;
                if ($delta > 0 && $delta <= 50) {
                    $total += $delta;
                }
            }
            $anterior = $atual;
        }
        return $total / 10;
    }

    /**
     * Calcula o efeito líquido (mm) de uma correção. Correção com hora desconta o que a
     * estação mediu naquela mesma hora (pra não somar em dobro), mas nunca é descartada
     * inteira só porque a estação pegou umas gotas (funil entupido mede um fio d'água).
     */
    private function efeitoLiquido(array $row): float
    {
        // abs(): o sinal vem só do "choveu" (valor gravado negativo não pode inverter a correção).
        $valor = abs((float) $row['valor_mm']);
        $sinal = ((int) $row['choveu']) === 0 ? -1 : 1;

        // Correção de dia inteiro (sem hora definida): sempre se aplica.
        if (empty($row['hora'])) {
            return $sinal * $valor;
        }

        // Correção negativa (choveu = 0) não desconta nada: é o próprio ajuste de leitura falsa.
        if ($sinal < 0) {
            return $sinal * $valor;
        }

        // Correção com hora: desconta só o que a estação mediu nessa hora.
        $hora = substr($row['hora'], 0, 5);
        $medido = $this->chuvaEstacaoNaHora((string) $row['local'], (string) $row['data'], $hora);

        return max(0.0, $valor - $medido);
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
