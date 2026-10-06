<?php

namespace App\Models;

class NoticiaModel
{
    private \MysqliDb $db;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
    }

    public function getAll(bool $onlyActive = true): array
    {
        if ($onlyActive) {
            $this->db->where('ativo', 1);
            $this->db->where('data_agendamento', date('Y-m-d H:i:s'), '<=');
        }
        return $this->db->orderBy('data_noticia', 'DESC')->get('noticias');
    }

    public function getPaginated(int $page = 1, int $perPage = 6): array
    {
        $this->db->where('ativo', 1);
        $this->db->where('data_agendamento', date('Y-m-d H:i:s'), '<=');
        $this->db->orderBy('data_noticia', 'DESC');
        return $this->db->get('noticias', [($page - 1) * $perPage, $perPage]);
    }

    public function countAll(bool $onlyActive = true): int
    {
        if ($onlyActive) {
            $this->db->where('ativo', 1);
            $this->db->where('data_agendamento', date('Y-m-d H:i:s'), '<=');
        }
        return $this->db->getValue('noticias', 'count(*)');
    }

    public function getById(int $id): ?array
    {
        $this->db->where('id', $id);
        return $this->db->getOne('noticias');
    }

    public function create(array $data): int
    {
        return $this->db->insert('noticias', $data);
    }

    public function update(int $id, array $data): bool
    {
        $this->db->where('id', $id);
        return $this->db->update('noticias', $data);
    }

    public function delete(int $id): bool
    {
        $this->db->where('id', $id);
        return $this->db->delete('noticias');
    }
}
