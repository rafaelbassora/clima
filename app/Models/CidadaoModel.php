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

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $this->db->where('email', $email);
        if ($exceptId !== null) {
            $this->db->where('id', $exceptId, '!=');
        }
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

    /**
     * Busca uma estação aprovada pelo código (sem nenhum dado pessoal), pra página pública.
     */
    public function findEstacaoAprovadaPorCodigo(string $stationCode): ?array
    {
        $this->db->where('station_code', $stationCode);
        $this->db->where('status', 'aprovado');
        $item = $this->db->getOne('cidadaos', 'station_code, bairro, cidade, uf, approved_at');
        return $item ?: null;
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
            'cpf' => $data['cpf'] ?: null,
            'celular' => $data['celular'],
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
     * Atualiza os dados de perfil do próprio cidadão (área restrita), exceto o e-mail
     * (que passa por confirmação em separado — ver solicitarTrocaEmail).
     * CPF e CNPJ nunca são alterados aqui — são imutáveis após o cadastro.
     */
    public function atualizarPerfil(int $id, array $data): bool
    {
        $this->db->where('id', $id);
        return $this->db->update('cidadaos', [
            'nome' => $data['nome'],
            'usa_nome_social' => $data['usa_nome_social'] ? 1 : 0,
            'nome_social' => $data['nome_social'] ?: null,
            'sexo' => $data['sexo'] ?: null,
            'data_nascimento' => $data['data_nascimento'] ?: null,
            'celular' => $data['celular'],
        ]);
    }

    /**
     * Gera um token de confirmação pro novo e-mail (só passa a valer quando confirmado).
     */
    public function solicitarTrocaEmail(int $id, string $novoEmail): string
    {
        $token = bin2hex(random_bytes(32));
        $this->db->where('id', $id);
        $this->db->update('cidadaos', [
            'email_pendente' => $novoEmail,
            'email_token' => $token,
            'email_token_expires' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);
        return $token;
    }

    public function confirmarTrocaEmail(string $token): ?array
    {
        $this->db->where('email_token', $token);
        $this->db->where('email_token_expires', date('Y-m-d H:i:s'), '>=');
        $cidadao = $this->db->getOne('cidadaos');

        if (!$cidadao || empty($cidadao['email_pendente'])) {
            return null;
        }

        // Revalida unicidade no momento da confirmação (evita corrida com outro cadastro/edição)
        if ($this->emailExists($cidadao['email_pendente'], (int) $cidadao['id'])) {
            return null;
        }

        $this->db->where('id', $cidadao['id']);
        $this->db->update('cidadaos', [
            'email' => $cidadao['email_pendente'],
            'email_pendente' => null,
            'email_token' => null,
            'email_token_expires' => null,
        ]);

        $cidadao['email'] = $cidadao['email_pendente'];
        return $cidadao;
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

    /**
     * Gera token de redefinição de senha, exigindo que o CPF (pessoa física) ou
     * CNPJ (empresa) informado bata com o cadastro — segunda verificação além do e-mail.
     */
    public function gerarTokenReset(string $email, string $documento): ?string
    {
        $cidadao = $this->findByEmail($email);
        if (!$cidadao) {
            return null;
        }

        $documentoCadastrado = $cidadao['tipo'] === 'pj' ? ($cidadao['cnpj'] ?? '') : ($cidadao['cpf'] ?? '');
        if ($documentoCadastrado === '' || $documento !== $documentoCadastrado) {
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
