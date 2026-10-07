<?php
/**
 * Às 7h da manhã, garante que exista um registro de chuva manual (0mm/não choveu)
 * pra cada local, caso ninguém tenha lançado a leitura manual daquele dia até lá.
 * Evita "buracos" no histórico do Pluviômetro Manual.
 *
 * Deploy: agende pra rodar a cada 1 minuto (o próprio script só age entre 7:00
 * e 7:04 — ajuste o horário/janela abaixo conforme a rotina da sua equipe).
 */

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/config.php';

$hora_atual = date('H:i');
$horarios_permitidos = ['07:00', '07:01', '07:02', '07:03', '07:04'];
if (!in_array($hora_atual, $horarios_permitidos)) {
    echo "Ainda não é hora de executar a tarefa.\n";
    exit;
}

$db = new MysqliDb($config['db']['host'], $config['db']['username'], $config['db']['password'], $config['db']['database']);

function existeRegistro(MysqliDb $db, $local): bool
{
    $db->where('local', $local);
    $db->where('data', date('Y-m-d 07:00:00'), '>=');
    $db->where('data', date('Y-m-d 07:05:00'), '<=');
    $result = $db->getOne('chuva');
    return $result !== null;
}

// Ajuste a quantidade de locais conforme sua cidade tiver estações cadastradas.
$totalLocais = 4;
for ($i = 1; $i <= $totalLocais; $i++) {
    if (!existeRegistro($db, $i)) {
        $db->insert('chuva', [
            'data' => date('Y-m-d 07:00:00'),
            'local' => $i,
            'choveu' => 0,
            'medida' => 0.00,
            'obs' => '',
        ]);
        echo "Local {$i}: registro padrão criado.\n";
    }
}
