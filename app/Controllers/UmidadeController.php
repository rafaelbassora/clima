<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\LeituraModel;

class UmidadeController extends Controller
{
    public function index(): void
    {
        $local = $this->getParam('local', '1');
        $year  = (int) $this->getParam('year', date('Y'));
        $month = (int) $this->getParam('month', date('n'));
        $model = new LeituraModel();

        $data = [
            'pageTitle'  => 'Umidade',
            'local'      => $local,
            'year'       => $year,
            'month'      => $month,
            'umidade24h'   => $model->getUmidade24h($local),
            'resumoMensal' => $model->getUmidadeResumo($local, $year, $month),
            'meses'        => $model->getMeses(),
            'activeMenu'   => 'umidade',
        ];

        $this->view('umidade/index', $data);
    }
}
