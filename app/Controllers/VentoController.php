<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\LeituraModel;

class VentoController extends Controller
{
    public function index(): void
    {
        $local = $this->getParam('local', '1');
        $year  = (int) $this->getParam('year', date('Y'));
        $month = (int) $this->getParam('month', date('n'));
        
        $model = new LeituraModel();

        $stats = $model->getEstatisticasVento($local);

        $data = [
            'pageTitle'    => 'Ventos',
            'local'        => $local,
            'year'         => $year,
            'month'        => $month,
            'vento24h'     => $model->getVento24h($local),
            'vento7dias'   => $model->getVento7Dias($local),
            'resumoMensal' => $model->getVentoResumo($local, $year, $month),
            'estatisticas' => $stats,
            'meses'        => $model->getMeses(),
            'activeMenu'   => 'vento',
        ];

        $this->view('vento/index', $data);
    }
}
