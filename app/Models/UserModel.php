<?php

namespace App\Models;

class UserModel
{
    private \MysqliDb $db;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
    }

    public function findByEmail(string $email): ?array
    {
        $this->db->where('email', $email);
        $user = $this->db->getOne('usuarios');
        return $user ?: null;
    }

    public function getAll(): array
    {
        return $this->db->orderBy('name', 'ASC')->get('usuarios');
    }

    public function getById(int $id): ?array
    {
        $this->db->where('id', $id);
        $user = $this->db->getOne('usuarios');
        return $user ?: null;
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $this->db->where('email', $email);
        if ($exceptId !== null) {
            $this->db->where('id', $exceptId, '!=');
        }
        return (bool) $this->db->getOne('usuarios', 'id');
    }

    public function create(array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        return (bool) $this->db->insert('usuarios', [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'is_admin' => $data['is_admin'] ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'is_admin' => $data['is_admin'] ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($data['password'])) {
            $update['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        $this->db->where('id', $id);
        return $this->db->update('usuarios', $update);
    }

    public function delete(int $id): bool
    {
        $this->db->where('id', $id);
        return $this->db->delete('usuarios');
    }

    /**
     * Salva o segredo TOTP gerado, mas ainda NÃO ativa 2FA (só após confirmar o 1º código).
     */
    public function setTotpSecret(int $id, string $secret): bool
    {
        $this->db->where('id', $id);
        return $this->db->update('usuarios', ['totp_secret' => $secret]);
    }

    public function enableTotp(int $id): bool
    {
        $this->db->where('id', $id);
        return $this->db->update('usuarios', ['totp_enabled' => 1]);
    }

    public function disableTotp(int $id): bool
    {
        $this->db->where('id', $id);
        return $this->db->update('usuarios', ['totp_enabled' => 0, 'totp_secret' => null]);
    }

    /**
     * Gera um token de redefinição de senha válido por 1 hora.
     */
    public function gerarTokenReset(string $email): ?string
    {
        $user = $this->findByEmail($email);
        if (!$user) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $this->db->where('id', $user['id']);
        $this->db->update('usuarios', [
            'reset_token' => $token,
            'reset_expires' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        return $token;
    }

    public function findByResetToken(string $token): ?array
    {
        $this->db->where('reset_token', $token);
        $this->db->where('reset_expires', date('Y-m-d H:i:s'), '>=');
        $user = $this->db->getOne('usuarios');
        return $user ?: null;
    }

    public function redefinirSenha(int $id, string $novaSenha): bool
    {
        $this->db->where('id', $id);
        return $this->db->update('usuarios', [
            'password' => password_hash($novaSenha, PASSWORD_BCRYPT, ['cost' => 12]),
            'reset_token' => null,
            'reset_expires' => null,
        ]);
    }
}
