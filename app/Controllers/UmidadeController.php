<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\LeituraModel;

class UmidadeController extends Controller
{
    use ComparaMedidaTrait;

    public function index(): void
    {
        $local = $this->getParam('local', '1');
        $year  = (int) $this->getParam('year', date('Y'));
        $month = (int) $this->getParam('month', date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        $model = new LeituraModel();
        [$dia, $primeiroDia] = $this->diaPesquisado($model, 'umidade', $local);

        $data = [
            'pageTitle'  => 'Umidade',
            'local'      => $local,
            'year'       => $year,
            'month'      => $month,
            'umidade24h'   => $model->getUmidade24h($local),
            'resumoMensal' => $model->getUmidadeResumo($local, $year, $month),
            'medias'       => $this->mediasDaMedida($model, 'umidade', $local, $year, $month),
            'meses'        => $model->getMeses(),
            'activeMenu'   => 'umidade',
            'dia'         => $dia,
            'primeiroDia' => $primeiroDia,
            'medidasDia'  => $model->getMedidasDoDia($local, $dia),
        ];

        $this->view('umidade/index', $data);
    }

    public function comparar(): void
    {
        $this->compararMedida('umidade', 'umidade');
    }
}
