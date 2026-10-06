<?php

namespace App\Models;

class UmidadeSoloModel
{
    private \MysqliDb $db;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
    }

    /**
     * Insere uma nova leitura de umidade do solo.
     *
     * @param array $data ['data' => datetime, 'local' => int, 'medida' => decimal, 'obs' => string|null]
     * @return bool
     */
    public function create(array $data): bool
    {
        return (bool) $this->db->insert('umidade_solo', $data);
    }

    /**
     * Retorna a última leitura de umidade do solo para um local.
     */
    public function getUltima(int $local): ?array
    {
        $this->db->where('local', $local);
        return $this->db->orderBy('data', 'DESC')->getOne('umidade_solo');
    }

    /**
     * Retorna todas as leituras de um local (paginável).
     */
    public function getByLocal(int $local, int $limit = 100): array
    {
        $this->db->where('local', $local);
        return $this->db->orderBy('data', 'DESC')->get('umidade_solo', $limit);
    }
}
