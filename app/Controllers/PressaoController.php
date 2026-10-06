<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\LeituraModel;

class PressaoController extends Controller
{
    public function index(): void
    {
        $local = $this->getParam('local', '1');
        $year  = (int) $this->getParam('year', date('Y'));
        $month = (int) $this->getParam('month', date('n'));
        
        $model = new LeituraModel();

        $data = [
            'pageTitle'    => 'Pressão Atmosférica',
            'local'        => $local,
            'year'         => $year,
            'month'        => $month,
            'pressao24h'   => $model->getPressao24h($local),
            'pressao7dias' => $model->getPressao7Dias($local),
            'resumoMensal' => $model->getPressaoResumo($local, $year, $month),
            'estatisticas' => $model->getEstatisticasPressao($local),
            'meses'        => $model->getMeses(),
            'activeMenu'   => 'pressao',
        ];

        $this->view('pressao/index', $data);
    }
}
