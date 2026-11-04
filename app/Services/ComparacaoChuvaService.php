<?php

namespace App\Services;

use App\Models\LeituraModel;

/**
 * Monta as comparações de chuva entre anos (mês a mês) e entre meses (dia a dia).
 * Os dados diários vêm do LeituraModel::getChuvaDiariaPeriodo().
 */
class ComparacaoChuvaService
{
    private LeituraModel $model;
    private string $hoje;

    public function __construct(LeituraModel $model)
    {
        $this->model = $model;
        $this->hoje = date('Y-m-d');
    }

    /**
     * Compara anos inteiros mês a mês.
     *
     * @param int[] $anos
     */
    public function compararAnos(string $local, string $fonte, array $anos, string $primeiroDia): array
    {
        $historico = $this->model->getChuvaDiariaPeriodo($local, $fonte, $primeiroDia, $this->hoje);
        $hojeMd = substr($this->hoje, 5); // "mm-dd": pra comparar o acumulado até a mesma data

        $series = [];
        foreach ($anos as $ano) {
            $mensal = array_fill(1, 12, 0.0);
            $ateHoje = 0.0;
            $diasChuva = 0;
            $maior = ['dia' => null, 'mm' => 0.0];

            foreach ($historico as $dia => $mm) {
                if ((int) substr($dia, 0, 4) !== $ano) {
                    continue;
                }
                $mensal[(int) substr($dia, 5, 2)] += $mm;
                if (substr($dia, 5) <= $hojeMd) {
                    $ateHoje += $mm;
                }
                if ($mm > 0) {
                    $diasChuva++;
                }
                if ($mm > $maior['mm']) {
                    $maior = ['dia' => $dia, 'mm' => $mm];
                }
            }

            // meses ainda não chegados (ou antes do início dos dados) ficam vazios, não zero
            foreach ($mensal as $m => $v) {
                $ym = sprintf('%04d-%02d', $ano, $m);
                if ($ym > substr($this->hoje, 0, 7) || $ym < substr($primeiroDia, 0, 7)) {
                    $mensal[$m] = null;
                }
            }

            $series[$ano] = [
                'mensal' => $mensal,
                'total' => array_sum(array_filter($mensal, fn ($v) => $v !== null)),
                'ate_hoje' => $ateHoje,
                'dias_chuva' => $diasChuva,
                'maior' => $maior,
                'parcial' => $ano === (int) date('Y') || $ano === (int) substr($primeiroDia, 0, 4) && substr($primeiroDia, 5) !== '01-01',
            ];
        }

        [$mediaMensal, $anosMedia] = $this->mediaMensalHistorica($historico, $primeiroDia);

        return [
            'series' => $series,
            'media_mensal' => $mediaMensal,
            'media_total' => array_sum(array_filter($mediaMensal, fn ($v) => $v !== null)),
            'anos_media' => $anosMedia,
            'hoje' => $this->hoje,
        ];
    }

    /**
     * Compara meses específicos dia a dia (acumulado).
     *
     * @param array<int, array{0:int,1:int}> $itens [[ano, mes], ...]
     */
    public function compararMeses(string $local, string $fonte, array $itens, string $primeiroDia): array
    {
        $historico = $this->model->getChuvaDiariaPeriodo($local, $fonte, $primeiroDia, $this->hoje);
        [$mediaMensal, $anosMedia] = $this->mediaMensalHistorica($historico, $primeiroDia);

        $series = [];
        foreach ($itens as [$ano, $mes]) {
            $ym = sprintf('%04d-%02d', $ano, $mes);
            $diasNoMes = (int) date('t', strtotime("{$ym}-01"));
            $diario = [];
            $acumulado = [];
            $soma = 0.0;
            $diasChuva = 0;
            $maior = ['dia' => null, 'mm' => 0.0];

            for ($d = 1; $d <= $diasNoMes; $d++) {
                $data = sprintf('%s-%02d', $ym, $d);
                if ($data > $this->hoje) {
                    $diario[$d] = null;
                    $acumulado[$d] = null;
                    continue;
                }
                $mm = $historico[$data] ?? 0.0;
                $soma += $mm;
                $diario[$d] = $mm;
                $acumulado[$d] = round($soma, 2);
                if ($mm > 0) {
                    $diasChuva++;
                }
                if ($mm > $maior['mm']) {
                    $maior = ['dia' => $data, 'mm' => $mm];
                }
            }

            $series[$ym] = [
                'ano' => $ano,
                'mes' => $mes,
                'diario' => $diario,
                'acumulado' => $acumulado,
                'total' => $soma,
                'dias_chuva' => $diasChuva,
                'maior' => $maior,
                'media_historica' => $mediaMensal[$mes],
                'parcial' => $ym === substr($this->hoje, 0, 7),
            ];
        }

        return [
            'series' => $series,
            'anos_media' => $anosMedia,
            'hoje' => $this->hoje,
        ];
    }

    /**
     * Média de cada mês considerando só meses completos (depois do início dos dados e antes do mês atual).
     * Retorna [[1..12 => mm|null], [anoInicial, anoFinal]|null].
     */
    private function mediaMensalHistorica(array $historico, string $primeiroDia): array
    {
        $mesAtual = substr($this->hoje, 0, 7);
        // o mês do primeiro dado só conta se começou no dia 1
        $primeiroMesCompleto = substr($primeiroDia, 8, 2) === '01'
            ? substr($primeiroDia, 0, 7)
            : date('Y-m', strtotime(substr($primeiroDia, 0, 7) . '-01 +1 month'));

        $totais = []; // [mes][ano] => mm
        $anoIni = $anoFim = null;
        for ($ym = $primeiroMesCompleto; $ym < $mesAtual; $ym = date('Y-m', strtotime("{$ym}-01 +1 month"))) {
            [$a, $m] = array_map('intval', explode('-', $ym));
            $totais[$m][$a] = 0.0;
            $anoIni = $anoIni ?? $a;
            $anoFim = $a;
        }
        foreach ($historico as $dia => $mm) {
            $a = (int) substr($dia, 0, 4);
            $m = (int) substr($dia, 5, 2);
            if (isset($totais[$m][$a])) {
                $totais[$m][$a] += $mm;
            }
        }

        // média só faz sentido com pelo menos 2 anos daquele mês
        $media = [];
        for ($m = 1; $m <= 12; $m++) {
            $media[$m] = count($totais[$m] ?? []) >= 2 ? array_sum($totais[$m]) / count($totais[$m]) : null;
        }
        $temMedia = array_filter($media, fn ($v) => $v !== null) !== [];

        return [$media, $temMedia && $anoIni ? [$anoIni, $anoFim] : null];
    }
}
