<?php

namespace App\Models;

class ChuvaManualModel
{
    private \MysqliDb $db;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
    }

    public function getPaginated(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        return $this->db->orderBy('data', 'DESC')->get('chuva', [$offset, $perPage]);
    }

    public function countAll(): int
    {
        return $this->db->getValue('chuva', 'count(*)');
    }

    public function getAll(): array
    {
        return $this->db->orderBy('data', 'DESC')->get('chuva');
    }

    public function getById(int $id): ?array
    {
        $this->db->where('id', $id);
        return $this->db->getOne('chuva');
    }

    public function create(array $data): bool
    {
        return (bool)$this->db->insert('chuva', $data);
    }

    public function update(int $id, array $data): bool
    {
        $this->db->where('id', $id);
        return $this->db->update('chuva', $data);
    }

    public function delete(int $id): bool
    {
        $this->db->where('id', $id);
        return $this->db->delete('chuva');
    }
}
