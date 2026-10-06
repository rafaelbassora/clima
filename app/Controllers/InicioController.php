<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\LeituraModel;

class InicioController extends Controller
{
    public function index(): void
    {
        $local = $this->getParam('local', '1');
        $model = new LeituraModel();
        $alertaModel = new \App\Models\AlertaModel();

        $data = [
            'pageTitle'       => 'Início',
            'local'           => $local,
            'ultimaLeitura'   => $model->getUltimaLeitura($local),
            'chuva7dias'      => $model->getChuva7Dias($local),
            'et7dias'         => $model->getEt7Dias($local),
            'temperatura7dias'=> $model->getTemperatura7Dias($local),
            'temperatura24h'  => $model->getTemperatura24h($local),
            'umidade24h'      => $model->getUmidade24h($local),
            'vento7dias'      => $model->getVento7Dias($local),
            'pressao7dias'    => $model->getPressao7Dias($local),
            'previsaoHoje'    => $model->getPrevisaoHoje($local),
            'estatisticas'    => $model->getEstatisticasChuva($local),
            'nowcasting'      => $model->getLatestNowcasting($local),
            'activeMenu'      => 'inicio',
            'alertasAtivos'   => $alertaModel->getAtivos(),
            'dadosGraficos'   => $model->getDadosGraficos24h($local),
        ];

        $this->view('inicio/index', $data);
    }
}
