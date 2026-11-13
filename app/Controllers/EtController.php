<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\LeituraModel;

class EtController extends Controller
{
    use ComparaMedidaTrait;

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
        [$dia, $primeiroDia] = $this->diaPesquisado($model, 'et', $local);

        $etResumo = $model->getEtResumo($local, $year, $month);

        $data = [
            'pageTitle'  => 'Evapotranspiração',
            'local'      => $local,
            'year'       => $year,
            'month'      => $month,
            'etResumo'   => $etResumo,
            'medias'     => $this->mediasDaMedida($model, 'et', $local, $year, $month),
            'meses'      => $model->getMeses(),
            'activeMenu' => 'et',
            'dia'         => $dia,
            'primeiroDia' => $primeiroDia,
            'medidasDia'  => $model->getMedidasDoDia($local, $dia),
        ];

        $this->view('et/index', $data);
    }

    public function comparar(): void
    {
        $this->compararMedida('et', 'et');
    }
}
