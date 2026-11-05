<?php

namespace App\Services;

/**
 * Backup do banco inteiro em SQL (estrutura + dados), gerado em fluxo e compactado,
 * direto para o navegador — nada fica gravado no servidor.
 *
 * Segurança: senhas, segredos de 2FA, tokens e a chave da API saem SEMPRE vazios
 * (ver SEGREDOS). Opcionalmente, os dados pessoais dos cidadãos também são removidos.
 */
class BackupBancoService
{
    /**
     * Colunas que nunca saem no backup: tabela => [coluna => valor gravado no lugar].
     * (colunas NOT NULL recebem '' em vez de NULL, para o arquivo continuar importável)
     */
    public const SEGREDOS = [
        'usuarios' => [
            'password' => '',
            'remember_token' => null,
            'totp_secret' => null,
            'totp_enabled' => '0',       // sem o segredo, o 2FA precisa ser ativado de novo
            'reset_token' => null,
            'reset_expires' => null,
        ],
        'cidadaos' => [
            'senha' => '',
            'reset_token' => null,
            'reset_expires' => null,
            'email_pendente' => null,
            'email_token' => null,
            'email_token_expires' => null,
        ],
        'estacao_config' => [
            'wunderground_api_key' => null,
        ],
    ];

    /** Removidos só com a opção "anonimizar cidadãos". '{id}' é trocado pelo id da linha. */
    public const DADOS_PESSOAIS = [
        'cidadaos' => [
            'nome' => 'Cidadão {id}',
            'nome_social' => null,
            'usa_nome_social' => '0',
            'data_nascimento' => null,
            'cpf' => null,
            'cnpj' => null,
            'celular' => null,
            'email' => 'cidadao{id}@removido.invalid',
        ],
    ];

    /** Tabelas grandes de histórico, que podem ficar de fora no backup "leve". */
    public const HISTORICAS = ['leituras_completas', 'umidade', 'temperatura', 'previsoes', 'previsoesextendidas', 'nowcasting'];

    private const LOTE = 500;

    /** Pasta FORA do Apache (nenhum site do servidor alcança). Pode ser trocada em config['backup']['pasta']. */
    public const PASTA_PADRAO = 'C:/backupclima';

    /** Nome dos arquivos gerados (só esses são listados, baixados ou apagados). */
    public const PADRAO_ARQUIVO = '/^backup-clima-(completo|sem-historico)-\d{4}-\d{2}-\d{2}_\d{4}\.sql\.gz$/';

    private \mysqli $conn;

    // ------------------------------------------------------------ pasta, status e log

    public static function pasta(array $config): string
    {
        return rtrim((string) ($config['backup']['pasta'] ?? self::PASTA_PADRAO), '/\\');
    }

    public static function garantirPasta(string $pasta): void
    {
        if (!is_dir($pasta) && !@mkdir($pasta, 0700, true) && !is_dir($pasta)) {
            throw new \RuntimeException("Não foi possível criar a pasta de backup ({$pasta}).");
        }
    }

    /** Backups prontos na pasta, do mais novo para o mais antigo. */
    public static function arquivos(string $pasta): array
    {
        $lista = [];
        foreach (glob($pasta . '/backup-clima-*.sql.gz') ?: [] as $caminho) {
            $nome = basename($caminho);
            if (preg_match(self::PADRAO_ARQUIVO, $nome)) {
                $lista[] = ['nome' => $nome, 'bytes' => filesize($caminho), 'data' => filemtime($caminho)];
            }
        }
        usort($lista, fn ($a, $b) => $b['data'] <=> $a['data']);
        return $lista;
    }

    /** Caminho seguro de um backup pelo nome (null se o nome não for de um backup existente). */
    public static function caminhoSeguro(string $pasta, string $nome): ?string
    {
        if (!preg_match(self::PADRAO_ARQUIVO, $nome)) {
            return null;
        }
        $caminho = $pasta . '/' . $nome;
        return is_file($caminho) ? $caminho : null;
    }

    public static function lerStatus(string $pasta): ?array
    {
        $f = $pasta . '/status.json';
        if (!is_file($f)) {
            return null;
        }
        $s = json_decode((string) @file_get_contents($f), true);
        return is_array($s) ? $s : null;
    }

    public static function gravarStatus(string $pasta, array $status): void
    {
        $tmp = $pasta . '/status.json.tmp';
        file_put_contents($tmp, json_encode($status, JSON_UNESCAPED_UNICODE));
        @rename($tmp, $pasta . '/status.json') || @copy($tmp, $pasta . '/status.json');
    }

    /** Há um backup rodando agora? (o processo segura backup.lock enquanto trabalha) */
    public static function emAndamento(string $pasta): bool
    {
        $f = $pasta . '/backup.lock';
        if (!is_file($f)) {
            return false;
        }
        $h = @fopen($f, 'c');
        if (!$h) {
            return false;
        }
        $livre = flock($h, LOCK_EX | LOCK_NB);
        if ($livre) {
            flock($h, LOCK_UN);
        }
        fclose($h);
        return !$livre;
    }

    /** Log de auditoria (quem gerou, baixou ou apagou, quando e de onde). */
    public static function registrarLog(string $pasta, string $mensagem): void
    {
        @file_put_contents($pasta . '/backup.log', date('Y-m-d H:i:s') . ' | ' . str_replace(["\r", "\n"], ' ', $mensagem) . "\n", FILE_APPEND | LOCK_EX);
    }

    public static function ultimosLogs(string $pasta, int $n = 15): array
    {
        $f = $pasta . '/backup.log';
        if (!is_file($f)) {
            return [];
        }
        $linhas = file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        return array_reverse(array_slice($linhas, -$n));
    }

    public function __construct(\MysqliDb $db)
    {
        $this->conn = $db->mysqli();
    }

    /** Tabelas do banco com linhas e tamanho aproximados (information_schema). */
    public function tabelas(): array
    {
        $r = $this->conn->query(
            "SELECT TABLE_NAME AS nome, TABLE_ROWS AS linhas, DATA_LENGTH + INDEX_LENGTH AS bytes
               FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
              ORDER BY TABLE_NAME"
        );
        $saida = [];
        while ($t = $r->fetch_assoc()) {
            $saida[$t['nome']] = [
                'linhas' => (int) $t['linhas'],
                'bytes' => (int) $t['bytes'],
                'historica' => in_array($t['nome'], self::HISTORICAS, true),
                'protegida' => isset(self::SEGREDOS[$t['nome']]),
            ];
        }
        return $saida;
    }

    public function nomeArquivo(bool $completo): string
    {
        return 'backup-clima-' . ($completo ? 'completo' : 'sem-historico') . '-' . date('Y-m-d_Hi') . '.sql.gz';
    }

    /**
     * Escreve o backup em $out. Retorna [tabelas, linhas].
     * $completo = false deixa de fora as tabelas de HISTORICAS (só a estrutura delas vai).
     */
    public function gerar($out, bool $completo, bool $anonimizar, string $autor, ?callable $progresso = null): array
    {
        $this->progresso = $progresso;
        $banco = $this->conn->query('SELECT DATABASE()')->fetch_row()[0];
        $tabelas = array_keys($this->tabelas());

        // foto consistente de todas as tabelas (InnoDB), sem travar o site
        $this->conn->query('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $this->conn->query('START TRANSACTION WITH CONSISTENT SNAPSHOT');

        $w = fn (string $s) => fwrite($out, $s);
        $w("-- ------------------------------------------------------------\n");
        $w("-- Backup do banco `{$banco}` — Clima Pereira Barreto\n");
        $w('-- Gerado em ' . date('d/m/Y H:i:s') . ' por ' . str_replace(["\n", "\r"], ' ', $autor) . "\n");
        $w('-- Conteúdo: ' . ($completo ? 'completo' : 'sem as tabelas de histórico (só a estrutura delas)') . "\n");
        $w("-- SEGURANÇA: senhas, segredos de 2FA, tokens e a chave da API foram REMOVIDOS.\n");
        $w("--   Depois de restaurar, redefina as senhas (\"Esqueci a senha\" ou pelo admin)\n");
        $w("--   e cadastre de novo a chave do Wunderground e o 2FA.\n");
        if ($anonimizar) {
            $w("-- Dados pessoais dos cidadãos (nome, CPF/CNPJ, celular, e-mail, nascimento) foram anonimizados.\n");
        } else {
            $w("-- ATENÇÃO (LGPD): este arquivo contém dados pessoais de cidadãos. Guarde em local seguro.\n");
        }
        $w("-- Restaurar: mysql -u USUARIO -p NOME_DO_BANCO < arquivo.sql\n");
        $w("-- ------------------------------------------------------------\n\n");
        $w("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET UNIQUE_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

        $totalLinhas = 0;
        foreach ($tabelas as $i => $t) {
            $this->tabelaAtual = ['nome' => $t, 'indice' => $i + 1, 'total' => count($tabelas), 'linhas_antes' => $totalLinhas];
            $this->avisar(0);
            $create = $this->conn->query("SHOW CREATE TABLE `{$t}`")->fetch_row()[1];
            $w("-- ----------------------------\n-- Tabela `{$t}`\n-- ----------------------------\n");
            $w("DROP TABLE IF EXISTS `{$t}`;\n{$create};\n\n");

            if (!$completo && in_array($t, self::HISTORICAS, true)) {
                $w("-- (dados de `{$t}` não incluídos neste backup)\n\n");
                continue;
            }
            $totalLinhas += $this->dados($out, $t, $anonimizar);
        }

        $this->conn->query('COMMIT');
        $w("SET FOREIGN_KEY_CHECKS=1;\nSET UNIQUE_CHECKS=1;\n");
        $w('-- Fim do backup: ' . count($tabelas) . " tabela(s), {$totalLinhas} linha(s).\n");
        return [count($tabelas), $totalLinhas];
    }

    /** INSERTs de uma tabela, linha a linha (consulta sem buffer), com os segredos limpos. */
    private function dados($out, string $tabela, bool $anonimizar): int
    {
        if (!$this->conn->real_query("SELECT * FROM `{$tabela}`")) {
            throw new \RuntimeException("Erro ao ler `{$tabela}`: " . $this->conn->error);
        }
        $res = $this->conn->use_result();
        $campos = array_map(fn ($f) => $f->name, $res->fetch_fields());

        // posição da coluna => valor de substituição
        $trocas = [];
        $regras = self::SEGREDOS[$tabela] ?? [];
        if ($anonimizar) {
            $regras += self::DADOS_PESSOAIS[$tabela] ?? [];
        }
        foreach ($regras as $col => $valor) {
            $pos = array_search($col, $campos, true);
            if ($pos !== false) {
                $trocas[$pos] = $valor;
            }
        }
        $posId = array_search('id', $campos, true);

        $cabecalho = "INSERT INTO `{$tabela}` (`" . implode('`, `', $campos) . "`) VALUES\n";
        $lote = [];
        $n = 0;
        while ($linha = $res->fetch_row()) {
            foreach ($trocas as $pos => $valor) {
                $linha[$pos] = is_string($valor) && $posId !== false
                    ? str_replace('{id}', (string) $linha[$posId], $valor)
                    : $valor;
            }
            $lote[] = '(' . implode(', ', array_map(
                fn ($v) => $v === null ? 'NULL' : "'" . $this->conn->real_escape_string((string) $v) . "'",
                $linha
            )) . ')';
            $n++;
            if (count($lote) >= self::LOTE) {
                fwrite($out, $cabecalho . implode(",\n", $lote) . ";\n");
                $lote = [];
            }
            if ($n % 50000 === 0) {
                $this->avisar($n);
            }
        }
        if ($lote) {
            fwrite($out, $cabecalho . implode(",\n", $lote) . ";\n");
        }
        $res->free();
        fwrite($out, "-- {$n} linha(s) em `{$tabela}`\n\n");
        return $n;
    }

    /** @var callable|null */
    private $progresso = null;
    private array $tabelaAtual = [];

    private function avisar(int $linhasNaTabela): void
    {
        if ($this->progresso) {
            ($this->progresso)($this->tabelaAtual + ['linhas_tabela' => $linhasNaTabela]);
        }
    }
}
