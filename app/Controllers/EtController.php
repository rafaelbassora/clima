<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\LeituraModel;

class EtController extends Controller
{
    public function index(): void
    {
        $local = $this->getParam('local', '1');
        $year  = (int)$this->getParam('year', date('Y'));
        $month = $this->getParam('month', date('m'));
        if (is_numeric($month)) $month = (int)$month;
        
        $model = new LeituraModel();

        $etResumo = $model->getEtResumo($local, $year, $month);

        $data = [
            'pageTitle'  => 'Evapotranspiração',
            'local'      => $local,
            'year'       => $year,
            'month'      => $month,
            'etResumo'   => $etResumo,
            'meses'      => $model->getMeses(),
            'activeMenu' => 'et',
        ];

        $this->view('et/index', $data);
    }
}
