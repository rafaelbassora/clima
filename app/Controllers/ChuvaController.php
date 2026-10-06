<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\LeituraModel;

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
        
        // Busca dados automáticos (tabela 'leituras_completas', automatic = '1')
        $chuvaAuto = $model->getChuvaResumo($local, $year, $month, '1');

        // Estatísticas e Recordes
        $estatisticas = $model->getEstatisticasChuva($local);

        $data = [
            'pageTitle'    => 'Índice Pluviométrico',
            'local'        => $local,
            'year'         => $year,
            'month'        => $month,
            'chuvaAuto'    => $chuvaAuto,
            'chuvaManual'  => $chuvaManual,
            'estatisticas' => $estatisticas,
            'meses'        => $model->getMeses(),
            'activeMenu'   => 'chuva',
        ];

        $this->view('chuva/index', $data);
    }
}
