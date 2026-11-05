<?php
/**
 * Gera o backup do banco em C:/backupclima (fora do Apache), em segundo plano.
 * Disparado pelo admin (Admin › Backup do Banco); também pode rodar à mão ou agendado:
 *
 *   C:\php\php.exe cron\backup-banco.php --modo=completo --anonimizar=0 --usuario=1
 *
 *   --modo=completo|leve   leve = sem os dados das tabelas grandes de histórico
 *   --anonimizar=0|1       1 = remove os dados pessoais dos cidadãos
 *   --usuario=ID           quem pediu (vai para o log e para o cabeçalho do arquivo)
 *
 * Senhas, 2FA, tokens e a chave da API saem sempre vazios. Ao terminar com sucesso,
 * apaga os backups anteriores: fica sempre só o mais recente.
 */

use App\Services\BackupBancoService;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['timezone'] ?? 'America/Sao_Paulo');
@set_time_limit(0);
ini_set('memory_limit', '512M');

$opts = getopt('', ['modo:', 'anonimizar:', 'usuario:']);
$completo = ($opts['modo'] ?? 'completo') !== 'leve';
$anonimizar = ($opts['anonimizar'] ?? '0') === '1';
$usuarioId = (int) ($opts['usuario'] ?? 0);

$pasta = BackupBancoService::pasta($config);
BackupBancoService::garantirPasta($pasta);

// um backup por vez
$lock = fopen($pasta . '/backup.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Já existe um backup em andamento.\n");
    exit(1);
}

$db = new MysqliDb($config['db']['host'], $config['db']['username'], $config['db']['password'], $config['db']['database']);
$db->rawQuery("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
$GLOBALS['db'] = $db;

$autor = 'linha de comando';
if ($usuarioId > 0) {
    $db->where('id', $usuarioId);
    $u = $db->getOne('usuarios', ['name', 'email']);
    $autor = $u ? "{$u['name']} <{$u['email']}> (id {$usuarioId})" : "usuário id {$usuarioId}";
}

$svc = new BackupBancoService($db);
$nome = $svc->nomeArquivo($completo);
$parcial = $pasta . '/' . $nome . '.parcial';
$inicio = time();

$status = [
    'estado' => 'rodando',
    'inicio' => $inicio,
    'modo' => $completo ? 'completo' : 'sem histórico',
    'anonimizar' => $anonimizar,
    'autor' => $autor,
    'arquivo' => $nome,
    'tabela' => null,
    'indice' => 0,
    'total' => 0,
    'linhas' => 0,
    'pid' => getmypid(),
];
BackupBancoService::gravarStatus($pasta, $status);
BackupBancoService::registrarLog($pasta, "INÍCIO geração {$nome} | {$status['modo']}" . ($anonimizar ? ' | anonimizado' : '') . " | {$autor}");

$out = fopen($parcial, 'wb');
// window 31 = formato gzip (abre no 7-Zip, WinRAR, gunzip)
stream_filter_append($out, 'zlib.deflate', STREAM_FILTER_WRITE, ['window' => 31, 'level' => 6]);

try {
    [$nTabelas, $nLinhas] = $svc->gerar($out, $completo, $anonimizar, $autor, function (array $p) use (&$status, $pasta) {
        $status['tabela'] = $p['nome'];
        $status['indice'] = $p['indice'];
        $status['total'] = $p['total'];
        $status['linhas'] = $p['linhas_antes'] + $p['linhas_tabela'];
        BackupBancoService::gravarStatus($pasta, $status);
    });
    fclose($out);
    if (!rename($parcial, $pasta . '/' . $nome)) {
        throw new RuntimeException('Não foi possível finalizar o arquivo.');
    }

    // sucesso: apaga os anteriores (fica só o mais recente)
    $apagados = 0;
    foreach (BackupBancoService::arquivos($pasta) as $a) {
        if ($a['nome'] !== $nome && @unlink($pasta . '/' . $a['nome'])) {
            $apagados++;
        }
    }

    $status = array_merge($status, [
        'estado' => 'pronto',
        'fim' => time(),
        'tabela' => null,
        'indice' => $nTabelas,
        'total' => $nTabelas,
        'linhas' => $nLinhas,
        'bytes' => filesize($pasta . '/' . $nome),
    ]);
    BackupBancoService::gravarStatus($pasta, $status);
    BackupBancoService::registrarLog($pasta, "PRONTO {$nome} | {$nTabelas} tabelas, {$nLinhas} linhas, "
        . round($status['bytes'] / 1048576, 1) . ' MB em ' . (time() - $inicio) . "s | {$apagados} backup(s) anterior(es) apagado(s)");
    echo "Backup pronto: {$pasta}/{$nome}\n";
} catch (Throwable $e) {
    if (is_resource($out)) {
        fclose($out);
    }
    @unlink($parcial);
    $status = array_merge($status, ['estado' => 'erro', 'fim' => time(), 'erro' => $e->getMessage()]);
    BackupBancoService::gravarStatus($pasta, $status);
    BackupBancoService::registrarLog($pasta, "ERRO {$nome} | " . $e->getMessage());
    fwrite(STDERR, 'Erro: ' . $e->getMessage() . "\n");
    exit(1);
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
