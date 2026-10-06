<?php

namespace App\Models;

class AlertaModel
{
    private \MysqliDb $db;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
    }

    /**
     * Retorna alertas ativos no momento atual
     */
    public function getAtivos(): array
    {
        $now = date('Y-m-d H:i:s');
        $this->db->where('ativo', 1);
        $this->db->where('data_inicio', $now, '<=');
        $this->db->where('data_fim', $now, '>=');
        return $this->db->orderBy('data_inicio', 'DESC')->get('alertas');
    }

    public function getAll(): array
    {
        return $this->db->orderBy('created_at', 'DESC')->get('alertas');
    }

    public function getById(int $id): ?array
    {
        $this->db->where('id', $id);
        return $this->db->getOne('alertas');
    }

    public function create(array $data): bool
    {
        return (bool)$this->db->insert('alertas', $data);
    }

    public function update(int $id, array $data): bool
    {
        $this->db->where('id', $id);
        return $this->db->update('alertas', $data);
    }

    public function delete(int $id): bool
    {
        $this->db->where('id', $id);
        return $this->db->delete('alertas');
    }
}
