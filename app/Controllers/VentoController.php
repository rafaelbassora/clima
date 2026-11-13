<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\LeituraModel;

class VentoController extends Controller
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

        $stats = $model->getEstatisticasVento($local);

        // Vento hora a hora de um dia escolhido (padrão: hoje)
        [$dia, $primeiroDia] = $this->diaPesquisado($model, 'vento', $local);

        $data = [
            'pageTitle'    => 'Ventos',
            'local'        => $local,
            'year'         => $year,
            'month'        => $month,
            'vento24h'     => $model->getVento24h($local),
            'vento7dias'   => $model->getVento7Dias($local),
            'resumoMensal' => $model->getVentoResumo($local, $year, $month),
            'estatisticas' => $stats,
            'dia'          => $dia,
            'primeiroDia'  => $primeiroDia,
            'ventoDia'     => $model->getVentoDoDia($local, $dia),
            'medias'       => $this->mediasDaMedida($model, 'vento', $local, $year, $month),
            'meses'        => $model->getMeses(),
            'activeMenu'   => 'vento',
        ];

        $this->view('vento/index', $data);
    }

    public function comparar(): void
    {
        $this->compararMedida('vento', 'vento');
    }
}
