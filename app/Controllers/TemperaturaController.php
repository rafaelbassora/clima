<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\LeituraModel;
use App\Services\ComparacaoTemperaturaService;

class TemperaturaController extends Controller
{
    public function index(): void
    {
        $local = $this->getParam('local', '1');
        $year  = (int)$this->getParam('year', date('Y'));
        $month = $this->getParam('month', date('m'));
        if (is_numeric($month)) $month = (int)$month;
        // mês inválido na URL volta pro mês atual
        if ($month !== 'all' && (!is_int($month) || $month < 1 || $month > 12)) {
            $month = (int) date('m');
        }

        $model = new LeituraModel();

        $data = [
            'pageTitle'         => 'Monitoramento de Temperatura',
            'local'             => $local,
            'year'              => $year,
            'month'             => $month,
            'temperaturaMensal' => $model->getTemperaturaResumo($local, $year, $month),
            'estatisticasAno'   => $model->getEstatisticasTemperaturaAno($local, $year),
            'estatisticas'      => $model->getEstatisticasTemperatura($local),
            'meses'             => $model->getMeses(),
            'activeMenu'        => 'temperatura',
        ];

        $this->view('temperatura/index', $data);
    }

    /**
     * Comparação de temperatura: anos x anos (mês a mês) ou meses x meses (dia a dia).
     * GET /inicio-temperatura/comparar?modo=anos|meses&anos[]=2025&m[]=10&a[]=2026
     */
    public function comparar(): void
    {
        $local = in_array($this->getParam('local', '1'), ['1', '2', '3', '4'], true) ? $this->getParam('local', '1') : '1';
        $modo = $this->getParam('modo', 'anos') === 'meses' ? 'meses' : 'anos';

        $model = new LeituraModel();
        $service = new ComparacaoTemperaturaService($model);

        $primeiroDia = $model->getPrimeiroDiaTemperatura($local) ?? date('Y-m-d');
        $anoInicial = (int) substr($primeiroDia, 0, 4);
        $anoAtual = (int) date('Y');
        $mesAtual = date('Y-m');
        $primeiroMes = substr($primeiroDia, 0, 7);

        // Anos: só anos com dados, sem repetir, no máximo 3
        $anos = [];
        foreach ((array) ($_GET['anos'] ?? []) as $a) {
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

        // Meses: pares m[]/a[] (ou itens[] AAAA-MM), dentro do período com dados, no máximo 3
        $entrada = (array) ($_GET['itens'] ?? []);
        $ms = (array) ($_GET['m'] ?? []);
        $as = (array) ($_GET['a'] ?? []);
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

        $resultado = $modo === 'anos'
            ? $service->compararAnos($local, $anos, $primeiroDia)
            : $service->compararMeses($local, $itens, $primeiroDia);

        $this->view('temperatura/comparar', [
            'pageTitle'   => 'Comparar Temperatura',
            'local'       => $local,
            'modo'        => $modo,
            'anos'        => $anos,
            'itens'       => $itens,
            'anoInicial'  => $anoInicial,
            'primeiroDia' => $primeiroDia,
            'resultado'   => $resultado,
            'meses'       => array_diff_key($model->getMeses(), ['all' => '']),
            'limiteCalor' => ComparacaoTemperaturaService::LIMITE_CALOR,
            'activeMenu'  => 'temperatura',
        ]);
    }
}
