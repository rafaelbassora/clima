<?php

namespace App\Services;

use App\Models\LeituraModel;

/**
 * Comparação (anos x anos, meses x meses) e médias gerais para as medidas do resumo diário
 * (umidade, pressão, vento, evapotranspiração), a partir de LeituraModel::getResumoDiario().
 *
 * Cada medida tem até 2 séries. Uma série é 'media' (média dos valores diários, ex.: máxima do dia)
 * ou 'soma' (total do período, ex.: evapotranspiração).
 */
class ComparacaoMedidaService
{
    private LeituraModel $model;
    private string $hoje;
    private array $cfg;

    public function __construct(LeituraModel $model, string $medida)
    {
        $this->model = $model;
        $this->hoje = date('Y-m-d');
        $this->cfg = self::config($medida);
    }

    /**
     * Definição de cada medida.
     */
    public static function config(string $medida): array
    {
        $todas = [
            'umidade' => [
                'medida' => 'umidade', 'primeiro' => 'hum', 'titulo' => 'Umidade', 'rota' => '/inicio-umidade',
                'icone' => 'bi-droplet-half', 'unidade' => '%', 'casas' => 0, 'grafico' => 'line', 'cor_acima' => 'text-primary', 'cor_abaixo' => 'text-danger',
                'series' => [
                    ['campo' => 'hmax', 'nome' => 'máx.', 'titulo' => 'Média das máximas', 'agg' => 'media', 'classe' => 'text-accent-warm', 'icone' => 'bi-droplet-fill'],
                    ['campo' => 'hmin', 'nome' => 'mín.', 'titulo' => 'Média das mínimas', 'agg' => 'media', 'classe' => 'text-primary', 'icone' => 'bi-droplet'],
                ],
                'maior' => ['campo' => 'hmax', 'hora' => null],
                'menor' => ['campo' => 'hmin', 'hora' => null],
                'contagem' => ['campo' => 'hmin', 'op' => '<=', 'valor' => 30, 'texto' => 'com mínima ≤ 30% (atenção)'],
            ],
            'pressao' => [
                'medida' => 'pressao', 'primeiro' => 'bar', 'titulo' => 'Pressão Atmosférica', 'rota' => '/inicio-pressao',
                'icone' => 'bi-speedometer2', 'unidade' => ' hPa', 'casas' => 1, 'grafico' => 'line', 'cor_acima' => 'text-accent-warm', 'cor_abaixo' => 'text-primary',
                'series' => [
                    ['campo' => 'bmax', 'nome' => 'máx.', 'titulo' => 'Média das máximas', 'agg' => 'media', 'classe' => 'text-accent-warm', 'icone' => 'bi-arrow-up-circle'],
                    ['campo' => 'bmin', 'nome' => 'mín.', 'titulo' => 'Média das mínimas', 'agg' => 'media', 'classe' => 'text-primary', 'icone' => 'bi-arrow-down-circle'],
                ],
                'maior' => ['campo' => 'bmax', 'hora' => 'hbmax'],
                'menor' => ['campo' => 'bmin', 'hora' => 'hbmin'],
                'contagem' => null,
            ],
            'vento' => [
                'medida' => 'vento', 'primeiro' => 'vento', 'titulo' => 'Ventos', 'rota' => '/inicio-vento',
                'icone' => 'bi-wind', 'unidade' => ' km/h', 'casas' => 1, 'grafico' => 'line', 'cor_acima' => 'text-danger', 'cor_abaixo' => 'text-primary',
                'series' => [
                    ['campo' => 'wmax', 'nome' => 'rajada', 'titulo' => 'Média das rajadas máximas', 'agg' => 'media', 'classe' => 'text-accent-warm', 'icone' => 'bi-tornado'],
                    ['campo' => 'wavg', 'nome' => 'média', 'titulo' => 'Vento médio', 'agg' => 'media', 'classe' => 'text-primary', 'icone' => 'bi-wind'],
                ],
                'maior' => ['campo' => 'wmax', 'hora' => 'hwmax'],
                'menor' => null,
                'contagem' => ['campo' => 'wmax', 'op' => '>=', 'valor' => 40, 'texto' => 'com rajada ≥ 40 km/h'],
            ],
            'et' => [
                'medida' => 'et', 'primeiro' => 'et', 'titulo' => 'Evapotranspiração', 'rota' => '/inicio-et',
                'icone' => 'bi-moisture', 'unidade' => ' mm', 'casas' => 1, 'grafico' => 'bar', 'cor_acima' => 'text-danger', 'cor_abaixo' => 'text-primary',
                'series' => [
                    ['campo' => 'et', 'nome' => 'total', 'titulo' => 'Evapotranspiração total', 'agg' => 'soma', 'classe' => 'text-success', 'icone' => 'bi-moisture'],
                    ['campo' => 'et', 'nome' => 'média/dia', 'titulo' => 'Média por dia', 'agg' => 'media', 'classe' => 'text-primary', 'icone' => 'bi-calendar-day'],
                ],
                'maior' => ['campo' => 'et', 'hora' => null],
                'menor' => null,
                'contagem' => null,
            ],
        ];
        if (!isset($todas[$medida])) {
            throw new \InvalidArgumentException("Medida desconhecida: {$medida}");
        }
        return $todas[$medida];
    }

    public function getConfig(): array
    {
        return $this->cfg;
    }

    public function primeiroDia(string $local): ?string
    {
        return $this->model->getPrimeiroDiaMedida($local, $this->cfg['primeiro']);
    }

    /**
     * Lê modo, anos e meses da URL (mesmas regras dos outros comparadores: até 3 de cada).
     */
    public static function parametros(array $get, string $primeiroDia): array
    {
        $anoInicial = (int) substr($primeiroDia, 0, 4);
        $anoAtual = (int) date('Y');
        $mesAtual = date('Y-m');
        $primeiroMes = substr($primeiroDia, 0, 7);
        $modo = ($get['modo'] ?? '') === 'meses' ? 'meses' : 'anos';

        $anos = [];
        foreach ((array) ($get['anos'] ?? []) as $a) {
            $a = (int) $a;
            if ($a >= $anoInicial && $a <= $anoAtual && !in_array($a, $anos, true)) {
                $anos[] = $a;
            }
        }
        $anos = array_slice($anos, 0, 3);
        if (!$anos) {
            $anos = range($anoAtual, max($anoInicial, $anoAtual - 2));
        }
        rsort($anos);

        $entrada = (array) ($get['itens'] ?? []);
        $ms = (array) ($get['m'] ?? []);
        $as = (array) ($get['a'] ?? []);
        foreach ($ms as $i => $m) {
            if ($m !== '' && isset($as[$i]) && is_numeric($m) && is_numeric($as[$i])) {
                $entrada[] = sprintf('%04d-%02d', (int) $as[$i], (int) $m);
            }
        }
        $itens = [];
        foreach ($entrada as $ym) {
            if (!is_string($ym) || !preg_match('/^(\d{4})-(\d{2})$/', $ym, $mt)) {
                continue;
            }
            [$a, $m] = [(int) $mt[1], (int) $mt[2]];
            $ym = sprintf('%04d-%02d', $a, $m);
            if ($m >= 1 && $m <= 12 && $ym >= $primeiroMes && $ym <= $mesAtual && !in_array([$a, $m], $itens, true)) {
                $itens[] = [$a, $m];
            }
        }
        $itens = array_slice($itens, 0, 3);
        if (!$itens) {
            foreach ([$mesAtual, date('Y-m', strtotime('-1 year')), date('Y-m', strtotime('first day of last month'))] as $ym) {
                if ($ym >= $primeiroMes) {
                    $itens[] = array_map('intval', explode('-', $ym));
                }
            }
        }

        return [$modo, $anos, $itens, $anoInicial];
    }

    /**
     * @param int[] $anos
     */
    public function compararAnos(string $local, array $anos, string $primeiroDia): array
    {
        $historico = $this->historico($local, $primeiroDia);
        [$medias, $anosMedia] = $this->mediaMensalHistorica($historico, $primeiroDia);

        $series = [];
        foreach ($anos as $ano) {
            $porMes = [];
            foreach ($historico as $dia => $d) {
                if ((int) substr($dia, 0, 4) === $ano) {
                    $porMes[(int) substr($dia, 5, 2)][$dia] = $d;
                }
            }
            $doAno = $porMes ? array_merge(...array_values($porMes)) : [];
            $mensal = [];
            for ($m = 1; $m <= 12; $m++) {
                $mensal[$m] = isset($porMes[$m]) ? $this->resumo($porMes[$m]) : null;
            }
            $series[$ano] = [
                'mensal' => $mensal,
                'resumo' => $doAno ? $this->resumo($doAno) : null,
                'geral' => $doAno ? $this->mediaGeral($doAno, $medias) : null,
                'parcial' => $ano === (int) date('Y')
                    || ($ano === (int) substr($primeiroDia, 0, 4) && substr($primeiroDia, 5) !== '01-01'),
            ];
        }

        // média geral de cada mês (valor esperado pra um mês completo)
        $mediaMensal = [];
        for ($m = 1; $m <= 12; $m++) {
            $mediaMensal[$m] = $medias[$m] === null ? null : array_map(
                fn ($i) => $this->cfg['series'][$i]['agg'] === 'soma'
                    ? $medias[$m][$i] * (int) date('t', mktime(0, 0, 0, $m, 1, 2001))
                    : $medias[$m][$i],
                array_keys($this->cfg['series'])
            );
        }

        return ['series' => $series, 'media_mensal' => $mediaMensal, 'anos_media' => $anosMedia];
    }

    /**
     * @param array<int, array{0:int,1:int}> $itens [[ano, mes], ...]
     */
    public function compararMeses(string $local, array $itens, string $primeiroDia): array
    {
        $historico = $this->historico($local, $primeiroDia);
        [$medias, $anosMedia] = $this->mediaMensalHistorica($historico, $primeiroDia);

        $series = [];
        foreach ($itens as [$ano, $mes]) {
            $ym = sprintf('%04d-%02d', $ano, $mes);
            $diasNoMes = (int) date('t', strtotime("{$ym}-01"));
            $diario = [];
            $doMes = [];
            for ($d = 1; $d <= $diasNoMes; $d++) {
                $data = sprintf('%s-%02d', $ym, $d);
                $diario[$d] = $historico[$data] ?? null;
                if (isset($historico[$data])) {
                    $doMes[$data] = $historico[$data];
                }
            }
            $series[$ym] = [
                'ano' => $ano,
                'mes' => $mes,
                'diario' => $diario,
                'resumo' => $doMes ? $this->resumo($doMes) : null,
                'geral' => $doMes ? $this->mediaGeral($doMes, $medias) : null,
                'parcial' => $ym === substr($this->hoje, 0, 7),
            ];
        }

        return ['series' => $series, 'anos_media' => $anosMedia];
    }

    /**
     * Médias de um mês (ou ano, com $month = 'all') e a média geral esperada pro mesmo período.
     */
    public function mediasDoPeriodo(string $local, int $year, mixed $month, string $primeiroDia): array
    {
        $historico = $this->historico($local, $primeiroDia);
        [$medias, $anosMedia] = $this->mediaMensalHistorica($historico, $primeiroDia);

        $prefixo = $month === 'all' ? sprintf('%04d-', $year) : sprintf('%04d-%02d-', $year, (int) $month);
        $doPeriodo = array_filter($historico, fn ($dia) => str_starts_with($dia, $prefixo), ARRAY_FILTER_USE_KEY);
        if (!$doPeriodo) {
            return ['resumo' => null, 'geral' => null, 'anos' => $anosMedia];
        }

        return ['resumo' => $this->resumo($doPeriodo), 'geral' => $this->mediaGeral($doPeriodo, $medias), 'anos' => $anosMedia];
    }

    /**
     * Dias com dado da medida (pelo campo da 1ª série), desde o primeiro dia até hoje.
     */
    private function historico(string $local, string $primeiroDia): array
    {
        $campo = $this->cfg['series'][0]['campo'];
        return array_filter(
            $this->model->getResumoDiario($local, $primeiroDia, $this->hoje),
            fn ($d) => $d[$campo] !== null
        );
    }

    /**
     * Valor de cada série, extremos e contagem pra um conjunto de dias.
     */
    private function resumo(array $dias): array
    {
        $valores = [];
        foreach ($this->cfg['series'] as $i => $s) {
            $lista = array_map('floatval', array_column($dias, $s['campo']));
            $valores[$i] = $s['agg'] === 'soma' ? array_sum($lista) : array_sum($lista) / count($lista);
        }

        $extremo = function (?array $def, bool $maior) use ($dias): ?array {
            if (!$def) {
                return null;
            }
            $melhor = null;
            foreach ($dias as $dia => $d) {
                $v = $d[$def['campo']];
                if ($v !== null && ($melhor === null || ($maior ? $v > $melhor['valor'] : $v < $melhor['valor']))) {
                    $melhor = ['valor' => $v, 'dia' => $dia, 'hora' => $def['hora'] ? ($d[$def['hora']] ?? null) : null];
                }
            }
            return $melhor;
        };

        $contagem = null;
        if ($c = $this->cfg['contagem']) {
            $contagem = count(array_filter($dias, fn ($d) => $d[$c['campo']] !== null
                && ($c['op'] === '>=' ? $d[$c['campo']] >= $c['valor'] : $d[$c['campo']] <= $c['valor'])));
        }

        return [
            'valores' => $valores,
            'maior' => $extremo($this->cfg['maior'], true),
            'menor' => $extremo($this->cfg['menor'], false),
            'contagem' => $contagem,
            'dias' => count($dias),
        ];
    }

    /**
     * Valor esperado de cada série pra um conjunto de dias, a partir da média diária histórica de cada mês.
     * Série 'media': média das médias diárias dos meses envolvidos (pesada pelos dias).
     * Série 'soma': soma das médias diárias (ou seja, o total esperado pros mesmos dias).
     * Null se algum mês do período ainda não tem média geral.
     */
    private function mediaGeral(array $dias, array $medias): ?array
    {
        $soma = array_fill_keys(array_keys($this->cfg['series']), 0.0);
        $n = 0;
        foreach ($dias as $dia => $d) {
            $hist = $medias[(int) substr($dia, 5, 2)];
            if ($hist === null) {
                return null;
            }
            foreach ($hist as $i => $v) {
                $soma[$i] += $v;
            }
            $n++;
        }
        if ($n === 0) {
            return null;
        }
        $saida = [];
        foreach ($this->cfg['series'] as $i => $s) {
            $saida[$i] = $s['agg'] === 'soma' ? $soma[$i] : $soma[$i] / $n;
        }
        return $saida;
    }

    /**
     * Média diária de cada série por mês, só com meses completos e pelo menos 2 anos.
     * Retorna [[1..12 => [i => média diária]|null], [anoIni, anoFim]|null].
     */
    private function mediaMensalHistorica(array $historico, string $primeiroDia): array
    {
        $mesAtual = substr($this->hoje, 0, 7);
        $primeiroMesCompleto = substr($primeiroDia, 8, 2) === '01'
            ? substr($primeiroDia, 0, 7)
            : date('Y-m', strtotime(substr($primeiroDia, 0, 7) . '-01 +1 month'));

        $porMesAno = []; // [mes][ano][i] => [valores diários]
        foreach ($historico as $dia => $d) {
            $ym = substr($dia, 0, 7);
            if ($ym < $primeiroMesCompleto || $ym >= $mesAtual) {
                continue;
            }
            $m = (int) substr($dia, 5, 2);
            $a = (int) substr($dia, 0, 4);
            foreach ($this->cfg['series'] as $i => $s) {
                if ($d[$s['campo']] !== null) {
                    $porMesAno[$m][$a][$i][] = (float) $d[$s['campo']];
                }
            }
        }

        $medias = [];
        $anos = [];
        for ($m = 1; $m <= 12; $m++) {
            $porAno = $porMesAno[$m] ?? [];
            if (count($porAno) < 2) {
                $medias[$m] = null;
                continue;
            }
            $medias[$m] = [];
            foreach (array_keys($this->cfg['series']) as $i) {
                $porAnoI = array_map(fn ($v) => array_sum($v[$i] ?? [0]) / max(1, count($v[$i] ?? [])), $porAno);
                $medias[$m][$i] = array_sum($porAnoI) / count($porAnoI);
            }
            $anos = array_merge($anos, array_keys($porAno));
        }

        return [$medias, $anos ? [min($anos), max($anos)] : null];
    }
}
