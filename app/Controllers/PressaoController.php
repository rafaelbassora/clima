<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\LeituraModel;

class PressaoController extends Controller
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

        $data = [
            'pageTitle'    => 'Pressão Atmosférica',
            'local'        => $local,
            'year'         => $year,
            'month'        => $month,
            'pressao24h'   => $model->getPressao24h($local),
            'pressao7dias' => $model->getPressao7Dias($local),
            'resumoMensal' => $model->getPressaoResumo($local, $year, $month),
            'estatisticas' => $model->getEstatisticasPressao($local),
            'medias'       => $this->mediasDaMedida($model, 'pressao', $local, $year, $month),
            'meses'        => $model->getMeses(),
            'activeMenu'   => 'pressao',
        ];

        $this->view('pressao/index', $data);
    }

    public function comparar(): void
    {
        $this->compararMedida('pressao', 'pressao');
    }
}
