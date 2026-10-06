<?php

namespace App\Models;

class NowcastConfigModel
{
    private \MysqliDb $db;

    private const PADRAO = [
        'limiar_alta' => 4,
        'limiar_moderada' => 2,
        'limiar_baixa' => 1,
        'amostras_usadas' => 0,
        'atualizado_em' => null,
    ];

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
    }

    public function getConfig(): array
    {
        $this->db->where('id', 1);
        $row = $this->db->getOne('nowcasting_config');
        return $row ?: self::PADRAO;
    }

    public function atualizar(int $limiarAlta, int $limiarModerada, int $limiarBaixa, int $amostras): void
    {
        $this->db->where('id', 1);
        $this->db->update('nowcasting_config', [
            'limiar_alta' => $limiarAlta,
            'limiar_moderada' => $limiarModerada,
            'limiar_baixa' => $limiarBaixa,
            'amostras_usadas' => $amostras,
            'atualizado_em' => date('Y-m-d H:i:s'),
        ]);
    }
}
