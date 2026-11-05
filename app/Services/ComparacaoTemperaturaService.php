<?php

namespace App\Services;

use App\Models\LeituraModel;

/**
 * Monta as comparações de temperatura entre anos (mês a mês) e entre meses (dia a dia),
 * a partir do resumo diário LeituraModel::getTemperaturaDiaria().
 */
class ComparacaoTemperaturaService
{
    /** Dias com máxima a partir deste valor contam como "calor intenso". */
    public const LIMITE_CALOR = 35.0;

    private LeituraModel $model;
    private string $hoje;

    public function __construct(LeituraModel $model)
    {
        $this->model = $model;
        $this->hoje = date('Y-m-d');
    }

    /**
     * @param int[] $anos
     */
    public function compararAnos(string $local, array $anos, string $primeiroDia): array
    {
        $historico = $this->model->getTemperaturaDiaria($local, $primeiroDia, $this->hoje);

        $series = [];
        foreach ($anos as $ano) {
            $porMes = [];
            foreach ($historico as $dia => $d) {
                if ((int) substr($dia, 0, 4) === $ano) {
                    $porMes[(int) substr($dia, 5, 2)][$dia] = $d;
                }
            }
            $doAno = array_merge(...array_values($porMes ?: [[]]));

            $mensal = [];
            for ($m = 1; $m <= 12; $m++) {
                $mensal[$m] = isset($porMes[$m]) ? $this->resumo($porMes[$m]) : null;
            }

            $series[$ano] = [
                'mensal' => $mensal,
                'ano' => $doAno ? $this->resumo($doAno) : null,
                'parcial' => $ano === (int) date('Y')
                    || ($ano === (int) substr($primeiroDia, 0, 4) && substr($primeiroDia, 5) !== '01-01'),
            ];
        }

        [$mediaMensal, $anosMedia] = $this->mediaMensalHistorica($historico, $primeiroDia);

        return ['series' => $series, 'media_mensal' => $mediaMensal, 'anos_media' => $anosMedia];
    }

    /**
     * @param array<int, array{0:int,1:int}> $itens [[ano, mes], ...]
     */
    public function compararMeses(string $local, array $itens, string $primeiroDia): array
    {
        $historico = $this->model->getTemperaturaDiaria($local, $primeiroDia, $this->hoje);
        [$mediaMensal, $anosMedia] = $this->mediaMensalHistorica($historico, $primeiroDia);

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
                'media_historica' => $mediaMensal[$mes],
                'parcial' => $ym === substr($this->hoje, 0, 7),
            ];
        }

        return ['series' => $series, 'anos_media' => $anosMedia];
    }

    /**
     * Médias de um mês (ou ano, com $month = 'all') e a média geral esperada pro mesmo período.
     * No ano, a média geral é ponderada pelos dias que o ano escolhido tem em cada mês
     * (assim um ano incompleto não é comparado com um ano cheio).
     * Retorna ['resumo' => array|null, 'geral' => ['max','min','media']|null, 'anos' => [ini, fim]|null].
     */
    public function mediasDoPeriodo(string $local, int $year, mixed $month, string $primeiroDia): array
    {
        $historico = $this->model->getTemperaturaDiaria($local, $primeiroDia, $this->hoje);
        [$mediaMensal, $anosMedia] = $this->mediaMensalHistorica($historico, $primeiroDia);

        $prefixo = $month === 'all' ? sprintf('%04d-', $year) : sprintf('%04d-%02d-', $year, (int) $month);
        $doPeriodo = array_filter($historico, fn ($dia) => str_starts_with($dia, $prefixo), ARRAY_FILTER_USE_KEY);
        if (!$doPeriodo) {
            return ['resumo' => null, 'geral' => null, 'anos' => $anosMedia];
        }

        $resumo = $this->resumo($doPeriodo);
        $resumo['media'] = ($resumo['media_max'] + $resumo['media_min']) / 2;

        // média geral esperada: média histórica de cada mês, pesada pelos dias com dado no período
        $somaMax = $somaMin = $peso = 0;
        foreach ($doPeriodo as $dia => $d) {
            $hist = $mediaMensal[(int) substr($dia, 5, 2)];
            if ($hist === null) {
                continue;
            }
            $somaMax += $hist['max'];
            $somaMin += $hist['min'];
            $peso++;
        }
        // só compara se a média geral cobre o período inteiro
        $geral = $peso === count($doPeriodo)
            ? ['max' => $somaMax / $peso, 'min' => $somaMin / $peso, 'media' => ($somaMax + $somaMin) / 2 / $peso]
            : null;

        return ['resumo' => $resumo, 'geral' => $geral, 'anos' => $anosMedia];
    }

    /**
     * Médias e extremos de um conjunto de dias.
     */
    private function resumo(array $dias): array
    {
        $maxs = array_column($dias, 'tmax');
        $mins = array_column($dias, 'tmin');

        $maior = null;
        $menor = null;
        foreach ($dias as $dia => $d) {
            if ($maior === null || $d['tmax'] > $maior['valor']) {
                $maior = ['valor' => $d['tmax'], 'dia' => $dia, 'hora' => $d['hmax']];
            }
            if ($menor === null || $d['tmin'] < $menor['valor']) {
                $menor = ['valor' => $d['tmin'], 'dia' => $dia, 'hora' => $d['hmin']];
            }
        }

        return [
            'media_max' => array_sum($maxs) / count($maxs),
            'media_min' => array_sum($mins) / count($mins),
            'maior' => $maior,
            'menor' => $menor,
            'dias_calor' => count(array_filter($maxs, fn ($v) => $v >= self::LIMITE_CALOR)),
            'dias' => count($dias),
        ];
    }

    /**
     * Média das máximas e das mínimas de cada mês, só com meses completos e pelo menos 2 anos.
     * Retorna [[1..12 => ['max' => float, 'min' => float]|null], [anoIni, anoFim]|null].
     */
    private function mediaMensalHistorica(array $historico, string $primeiroDia): array
    {
        $mesAtual = substr($this->hoje, 0, 7);
        $primeiroMesCompleto = substr($primeiroDia, 8, 2) === '01'
            ? substr($primeiroDia, 0, 7)
            : date('Y-m', strtotime(substr($primeiroDia, 0, 7) . '-01 +1 month'));

        $porMesAno = []; // [mes][ano] => ['max' => [...], 'min' => [...]]
        foreach ($historico as $dia => $d) {
            $ym = substr($dia, 0, 7);
            if ($ym < $primeiroMesCompleto || $ym >= $mesAtual) {
                continue;
            }
            $m = (int) substr($dia, 5, 2);
            $a = (int) substr($dia, 0, 4);
            $porMesAno[$m][$a]['max'][] = $d['tmax'];
            $porMesAno[$m][$a]['min'][] = $d['tmin'];
        }

        $media = [];
        $anos = [];
        for ($m = 1; $m <= 12; $m++) {
            $porAno = $porMesAno[$m] ?? [];
            if (count($porAno) < 2) {
                $media[$m] = null;
                continue;
            }
            $mx = array_map(fn ($v) => array_sum($v['max']) / count($v['max']), $porAno);
            $mn = array_map(fn ($v) => array_sum($v['min']) / count($v['min']), $porAno);
            $media[$m] = ['max' => array_sum($mx) / count($mx), 'min' => array_sum($mn) / count($mn)];
            $anos = array_merge($anos, array_keys($porAno));
        }

        return [$media, $anos ? [min($anos), max($anos)] : null];
    }
}
