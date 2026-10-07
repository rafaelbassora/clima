<?php

namespace App\Models;

class EstacaoConfigModel
{
    private \MysqliDb $db;

    private const PADRAO = [
        'fonte_dados' => 'spoofing',
        'wunderground_api_key' => null,
        'wunderground_station_id' => null,
        'frequencia_minutos' => 5,
        'ultima_sincronizacao' => null,
        'ultimo_erro' => null,
        'atualizado_em' => null,
    ];

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
    }

    public function getConfig(): array
    {
        $this->db->where('id', 1);
        $row = $this->db->getOne('estacao_config');
        return $row ?: self::PADRAO;
    }

    public function atualizar(array $dados): void
    {
        $this->db->where('id', 1);
        $this->db->update('estacao_config', [
            'fonte_dados' => $dados['fonte_dados'],
            'wunderground_api_key' => $dados['wunderground_api_key'] ?: null,
            'wunderground_station_id' => $dados['wunderground_station_id'] ?: null,
            'frequencia_minutos' => (int) $dados['frequencia_minutos'],
            'atualizado_em' => date('Y-m-d H:i:s'),
        ]);
    }

    public function registrarSincronizacao(bool $sucesso, ?string $erro = null): void
    {
        $this->db->where('id', 1);
        $dados = ['ultima_sincronizacao' => date('Y-m-d H:i:s')];
        $dados['ultimo_erro'] = $sucesso ? null : $erro;
        $this->db->update('estacao_config', $dados);
    }
}
