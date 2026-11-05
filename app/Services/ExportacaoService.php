<?php

namespace App\Services;

/**
 * Exportação das tabelas de leitura para CSV ou SQL, gerada em fluxo (linha a linha),
 * para aguentar tabelas grandes sem estourar memória.
 *
 * Só as tabelas listadas em TABELAS podem ser exportadas (usuários e cidadãos ficam de fora).
 */
class ExportacaoService
{
    /** tabela => [rótulo, coluna de data usada no filtro, tem coluna `local`?] */
    public const TABELAS = [
        'leituras_completas'  => ['rotulo' => 'Leituras da estação automática', 'data' => 'created_at', 'local' => true],
        'chuva'               => ['rotulo' => 'Pluviômetro manual', 'data' => 'data', 'local' => true],
        'chuva_correcoes'     => ['rotulo' => 'Correções de chuva automática', 'data' => 'data', 'local' => true],
        'chuva_cidada'        => ['rotulo' => 'Pluviômetro Cidadão (lançamentos)', 'data' => 'data', 'local' => false],
        'umidade_solo'        => ['rotulo' => 'Umidade do solo', 'data' => 'data', 'local' => true],
        'nowcasting'          => ['rotulo' => 'Nowcasting (previsões de curto prazo)', 'data' => 'created_at', 'local' => true],
        'previsoes'           => ['rotulo' => 'Previsões do tempo', 'data' => 'atualizacao', 'local' => true],
        'previsoesextendidas' => ['rotulo' => 'Previsões estendidas', 'data' => 'atualizacao', 'local' => true],
        'umidade'             => ['rotulo' => 'Umidade (histórico antigo)', 'data' => 'data', 'local' => true],
        'temperatura'         => ['rotulo' => 'Temperatura (histórico antigo)', 'data' => 'data', 'local' => true],
    ];

    /** linhas por comando INSERT no formato SQL */
    private const LOTE_SQL = 500;

    private \mysqli $conn;

    public function __construct(\MysqliDb $db)
    {
        $this->conn = $db->mysqli();
    }

    /**
     * Valida e normaliza os parâmetros. Retorna [erro|null, parametros].
     */
    public static function validar(array $in): array
    {
        $tabela = (string) ($in['tabela'] ?? '');
        if (!isset(self::TABELAS[$tabela])) {
            return ['Escolha uma tabela válida.', []];
        }
        $inicio = (string) ($in['inicio'] ?? '');
        $fim = (string) ($in['fim'] ?? '');
        foreach ([$inicio, $fim] as $d) {
            if ($d === '') {
                continue;
            }
            // formato AAAA-MM-DD e data que existe no calendário (ex.: recusa 2026-13-99 e 2026-02-30)
            if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                return ['Datas inválidas.', []];
            }
        }
        if ($inicio !== '' && $fim !== '' && $inicio > $fim) {
            return ['A data inicial é depois da data final.', []];
        }
        $local = (string) ($in['local'] ?? '');
        if ($local !== '' && !in_array($local, ['1', '2', '3', '4'], true)) {
            return ['Local inválido.', []];
        }
        $formato = ($in['formato'] ?? 'csv') === 'sql' ? 'sql' : 'csv';

        return [null, [
            'tabela' => $tabela,
            'inicio' => $inicio,
            'fim' => $fim,
            'local' => self::TABELAS[$tabela]['local'] ? $local : '',
            'formato' => $formato,
            'separador' => ($in['separador'] ?? ';') === ',' ? ',' : ';',
            'estrutura' => !empty($in['estrutura']),
            'gzip' => !empty($in['gzip']),
        ]];
    }

    /**
     * WHERE com os filtros (valores já validados: datas AAAA-MM-DD, local 1–4, tabela da lista).
     */
    private function where(array $p): string
    {
        $col = '`' . self::TABELAS[$p['tabela']]['data'] . '`';
        $cond = [];
        if ($p['inicio'] !== '') {
            $cond[] = "{$col} >= '{$p['inicio']} 00:00:00'";
        }
        if ($p['fim'] !== '') {
            $cond[] = "{$col} <= '{$p['fim']} 23:59:59'";
        }
        if ($p['local'] !== '') {
            $cond[] = '`local` = ' . (int) $p['local'];
        }
        return $cond ? ' WHERE ' . implode(' AND ', $cond) : '';
    }

    public function contar(array $p): int
    {
        $r = $this->conn->query("SELECT COUNT(*) AS n FROM `{$p['tabela']}`" . $this->where($p));
        return (int) $r->fetch_assoc()['n'];
    }

    /**
     * Primeira e última data de cada tabela (pela ordem do id, que é rápido).
     */
    public function periodos(): array
    {
        $saida = [];
        foreach (self::TABELAS as $t => $cfg) {
            $col = $cfg['data'];
            $ini = $this->conn->query("SELECT `{$col}` AS d FROM `{$t}` ORDER BY id ASC LIMIT 1")->fetch_assoc();
            $fim = $this->conn->query("SELECT `{$col}` AS d FROM `{$t}` ORDER BY id DESC LIMIT 1")->fetch_assoc();
            $tam = $this->conn->query("SELECT TABLE_ROWS AS n FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$t}'")->fetch_assoc();
            $saida[$t] = [
                'inicio' => $ini ? substr((string) $ini['d'], 0, 10) : null,
                'fim' => $fim ? substr((string) $fim['d'], 0, 10) : null,
                'linhas_aprox' => (int) ($tam['n'] ?? 0),
            ];
        }
        return $saida;
    }

    public function nomeArquivo(array $p): string
    {
        $partes = [$p['tabela']];
        if ($p['local'] !== '') {
            $partes[] = 'local' . $p['local'];
        }
        $partes[] = ($p['inicio'] ?: 'inicio') . '_a_' . ($p['fim'] ?: date('Y-m-d'));
        return implode('_', $partes) . '.' . $p['formato'] . ($p['gzip'] ? '.gz' : '');
    }

    /**
     * Escreve a exportação no stream $out, linha a linha (consulta sem buffer).
     * Retorna o número de linhas exportadas.
     */
    public function exportar(array $p, $out): int
    {
        $tabela = $p['tabela'];
        $sql = "SELECT * FROM `{$tabela}`" . $this->where($p) . ' ORDER BY id';

        if ($p['formato'] === 'sql') {
            fwrite($out, "-- Exportação da tabela `{$tabela}` — " . date('d/m/Y H:i:s') . "\n");
            fwrite($out, '-- Filtros: ' . $this->descreverFiltros($p) . "\n\n");
            fwrite($out, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
            if ($p['estrutura']) {
                $create = $this->conn->query("SHOW CREATE TABLE `{$tabela}`")->fetch_row()[1];
                fwrite($out, "DROP TABLE IF EXISTS `{$tabela}`;\n{$create};\n\n");
            }
        }

        if (!$this->conn->real_query($sql)) {
            throw new \RuntimeException('Erro ao consultar a tabela: ' . $this->conn->error);
        }
        $res = $this->conn->use_result(); // sem buffer: não carrega a tabela inteira na memória
        $campos = array_map(fn ($f) => $f->name, $res->fetch_fields());
        $n = 0;

        if ($p['formato'] === 'csv') {
            fwrite($out, "\xEF\xBB\xBF"); // BOM: o Excel reconhece os acentos (UTF-8)
            fputcsv($out, $campos, $p['separador'], '"', '');
            while ($linha = $res->fetch_row()) {
                fputcsv($out, $linha, $p['separador'], '"', '');
                $n++;
            }
        } else {
            $cabecalho = "INSERT INTO `{$tabela}` (`" . implode('`, `', $campos) . "`) VALUES\n";
            $lote = [];
            while ($linha = $res->fetch_row()) {
                $lote[] = '(' . implode(', ', array_map(
                    fn ($v) => $v === null ? 'NULL' : "'" . $this->conn->real_escape_string((string) $v) . "'",
                    $linha
                )) . ')';
                $n++;
                if (count($lote) >= self::LOTE_SQL) {
                    fwrite($out, $cabecalho . implode(",\n", $lote) . ";\n");
                    $lote = [];
                }
            }
            if ($lote) {
                fwrite($out, $cabecalho . implode(",\n", $lote) . ";\n");
            }
            fwrite($out, "\nSET FOREIGN_KEY_CHECKS=1;\n-- {$n} linha(s) exportada(s)\n");
        }
        $res->free();
        return $n;
    }

    public function descreverFiltros(array $p): string
    {
        $txt = [];
        $txt[] = 'período ' . ($p['inicio'] ? date('d/m/Y', strtotime($p['inicio'])) : 'desde o início') . ' a ' . ($p['fim'] ? date('d/m/Y', strtotime($p['fim'])) : 'hoje');
        $txt[] = $p['local'] !== '' ? "local {$p['local']}" : 'todos os locais';
        return implode(', ', $txt);
    }
}
