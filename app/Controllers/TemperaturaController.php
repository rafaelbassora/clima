<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\LeituraModel;

class TemperaturaController extends Controller
{
    public function index(): void
    {
        $local = $this->getParam('local', '1');
        $year  = (int)$this->getParam('year', date('Y'));
        $month = $this->getParam('month', date('m'));
        if (is_numeric($month)) $month = (int)$month;

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
}
