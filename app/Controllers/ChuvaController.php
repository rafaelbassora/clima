<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\LeituraModel;
use App\Services\ComparacaoChuvaService;

class ChuvaController extends Controller
{
    public function index(): void
    {
        $local = $this->getParam('local', '1');
        $year  = (int)$this->getParam('year', date('Y'));
        $month = $this->getParam('month', date('m'));
        // Se for número, converte para int, se for 'all', mantém
        if (is_numeric($month)) $month = (int)$month;
        
        $model = new LeituraModel();

        // Busca dados manuais (tabela 'chuva')
        $chuvaManual = $model->getChuvaManual($local, $year, $month);
        
        // Busca dados automáticos (tabela 'leituras_completas', automatic = '1'),
        // só se o período escolhido alcança o início da coleta automática de chuva
        $primeiroDiaAuto = $model->getPrimeiroDiaChuva($local, 'auto');
        $fimPeriodo = $month === 'all'
            ? "{$year}-12-31"
            : date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, (int) $month)));
        $temAuto = $primeiroDiaAuto !== null && $fimPeriodo >= $primeiroDiaAuto;
        $chuvaAuto = $temAuto
            ? $model->getChuvaResumo($local, $year, $month, '1')
            : ['detalhe' => [], 'total' => '0.00'];

        // Estatísticas e Recordes
        $estatisticas = $model->getEstatisticasChuva($local);

        $data = [
            'pageTitle'    => 'Índice Pluviométrico',
            'local'        => $local,
            'year'         => $year,
            'month'        => $month,
            'chuvaAuto'    => $chuvaAuto,
            'temAuto'      => $temAuto,
            'primeiroDiaAuto' => $primeiroDiaAuto,
            'chuvaManual'  => $chuvaManual,
            'estatisticas' => $estatisticas,
            'meses'        => $model->getMeses(),
            'activeMenu'   => 'chuva',
        ];

        $this->view('chuva/index', $data);
    }

    /**
     * Ferramenta de comparação: anos x anos (mês a mês) ou meses x meses (dia a dia).
     * GET /inicio-chuva/comparar?modo=anos|meses&fonte=manual|auto&anos[]=2025&itens[]=2026-10
     */
    public function comparar(): void
    {
        $local = in_array($this->getParam('local', '1'), ['1', '2', '3', '4'], true) ? $this->getParam('local', '1') : '1';
        $modo = $this->getParam('modo', 'anos') === 'meses' ? 'meses' : 'anos';
        $fonte = $this->getParam('fonte', 'manual') === 'auto' ? 'auto' : 'manual';

        $model = new LeituraModel();
        $service = new ComparacaoChuvaService($model);

        $primeiroDia = $model->getPrimeiroDiaChuva($local, $fonte) ?? date('Y-m-d');
        $anoInicial = (int) substr($primeiroDia, 0, 4);
        $anoAtual = (int) date('Y');
        $mesAtual = date('Y-m');
        $primeiroMes = substr($primeiroDia, 0, 7);

        // Anos: aceita só anos com dados, sem repetir, no máximo 6
        $anos = [];
        foreach ((array) ($_GET['anos'] ?? []) as $a) {
            $a = (int) $a;
            if ($a >= $anoInicial && $a <= $anoAtual && !in_array($a, $anos, true)) {
                $anos[] = $a;
            }
        }
        $anos = array_slice($anos, 0, 6);
        if (!$anos) {
            $anos = range($anoAtual, max($anoInicial, $anoAtual - 2));
        }
        rsort($anos);

        // Meses: itens no formato AAAA-MM (ou pares m[]/a[] vindos do formulário), no máximo 3
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
            // padrão: este mês, o mesmo mês do ano passado e o mês anterior
            foreach ([$mesAtual, date('Y-m', strtotime('-1 year')), date('Y-m', strtotime('first day of last month'))] as $ym) {
                if ($ym >= $primeiroMes) {
                    $itens[] = array_map('intval', explode('-', $ym));
                }
            }
        }

        $resultado = $modo === 'anos'
            ? $service->compararAnos($local, $fonte, $anos, $primeiroDia)
            : $service->compararMeses($local, $fonte, $itens, $primeiroDia);

        $this->view('chuva/comparar', [
            'pageTitle'   => 'Comparar Chuva',
            'local'       => $local,
            'modo'        => $modo,
            'fonte'       => $fonte,
            'anos'        => $anos,
            'itens'       => $itens,
            'anoInicial'  => $anoInicial,
            'primeiroDia' => $primeiroDia,
            'resultado'   => $resultado,
            'meses'       => array_diff_key($model->getMeses(), ['all' => '']),
            'activeMenu'  => 'chuva',
        ]);
    }
}
