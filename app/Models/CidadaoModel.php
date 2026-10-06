<?php

namespace App\Models;

class CidadaoModel
{
    private \MysqliDb $db;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
    }

    public function findByEmail(string $email): ?array
    {
        $this->db->where('email', $email);
        $item = $this->db->getOne('cidadaos');
        return $item ?: null;
    }

    public function emailExists(string $email): bool
    {
        $this->db->where('email', $email);
        return (bool) $this->db->getOne('cidadaos', 'id');
    }

    public function getById(int $id): ?array
    {
        $this->db->where('id', $id);
        $item = $this->db->getOne('cidadaos');
        return $item ?: null;
    }

    public function getPendentes(): array
    {
        $this->db->where('status', 'pendente');
        return $this->db->orderBy('created_at', 'ASC')->get('cidadaos');
    }

    public function getAprovados(): array
    {
        $this->db->where('status', 'aprovado');
        return $this->db->orderBy('station_code', 'ASC')->get('cidadaos');
    }

    public function criar(array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        return (bool) $this->db->insert('cidadaos', [
            'tipo' => $data['tipo'],
            'nome' => $data['nome'],
            'usa_nome_social' => $data['usa_nome_social'] ? 1 : 0,
            'nome_social' => $data['nome_social'] ?: null,
            'sexo' => $data['sexo'] ?: null,
            'data_nascimento' => $data['data_nascimento'] ?: null,
            'cnpj' => $data['cnpj'] ?: null,
            'email' => $data['email'],
            'senha' => password_hash($data['senha'], PASSWORD_BCRYPT, ['cost' => 12]),
            'cep' => $data['cep'],
            'bairro' => $data['bairro'],
            'cidade' => $data['cidade'],
            'uf' => $data['uf'],
            'status' => 'pendente',
            'created_at' => $now,
        ]);
    }

    /**
     * Aprova o cadastro e gera o código da estação (ex: 001-JARDIM COLORADO),
     * sequencial por bairro, sem revelar o nome/endereço da pessoa.
     */
    public function aprovar(int $id): ?string
    {
        $cidadao = $this->getById($id);
        if (!$cidadao) {
            return null;
        }

        $bairro = mb_strtoupper($cidadao['bairro']);

        $this->db->where('bairro', $cidadao['bairro']);
        $this->db->where('status', 'aprovado');
        $count = $this->db->getValue('cidadaos', 'COUNT(*)');
        $seq = ((int) $count) + 1;

        $code = str_pad((string) $seq, 3, '0', STR_PAD_LEFT) . '-' . $bairro;

        $this->db->where('id', $id);
        $this->db->update('cidadaos', [
            'status' => 'aprovado',
            'station_code' => $code,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        return $code;
    }

    public function rejeitar(int $id): bool
    {
        $this->db->where('id', $id);
        return $this->db->update('cidadaos', ['status' => 'rejeitado']);
    }

    public function gerarTokenReset(string $email): ?string
    {
        $cidadao = $this->findByEmail($email);
        if (!$cidadao) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $this->db->where('id', $cidadao['id']);
        $this->db->update('cidadaos', [
            'reset_token' => $token,
            'reset_expires' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        return $token;
    }

    public function findByResetToken(string $token): ?array
    {
        $this->db->where('reset_token', $token);
        $this->db->where('reset_expires', date('Y-m-d H:i:s'), '>=');
        $item = $this->db->getOne('cidadaos');
        return $item ?: null;
    }

    public function redefinirSenha(int $id, string $novaSenha): bool
    {
        $this->db->where('id', $id);
        return $this->db->update('cidadaos', [
            'senha' => password_hash($novaSenha, PASSWORD_BCRYPT, ['cost' => 12]),
            'reset_token' => null,
            'reset_expires' => null,
        ]);
    }
}
