<?php

namespace App\Controllers;

use Core\Controller;

use App\Models\LeituraModel;

class PrevisaoController extends Controller
{
    public function index(): void
    {
        $local = $this->getParam('local', '1');
        $model = new LeituraModel();
        
        $rawPrevisoes = $model->getPrevisoes($local);
        $rawExtendidas = $model->getPrevisoesEstendidas($local);

        $previsoes = $this->formatForecastData($rawPrevisoes, $model);
        $extendidas = $this->formatForecastData($rawExtendidas, $model);

        $data = [
            'pageTitle'  => 'Previsão do Tempo',
            'local'      => $local,
            'previsoes'  => $previsoes,
            'extendidas' => $extendidas,
            'activeMenu' => 'previsao',
        ];

        $this->view('previsao/index', $data);
    }

    private function formatForecastData(array $raw, LeituraModel $model): array
    {
        $formatted = [];
        foreach ($raw as $p) {
            $condition = $model->getWeatherCondition($p['tempo']);
            $formatted[] = [
                'dia'        => $p['dia'],
                'data_fmt'   => date('d/m', strtotime($p['dia'])),
                'semana'     => $model->getDiaSemana($p['dia']),
                'minima'     => $p['minima'],
                'maxima'     => $p['maxima'],
                'iuv'        => $p['iuv'],
                'tempo'      => $condition['description'],
                'icon'       => $condition['icon'],
                'cidade'     => $p['cidade'] ?? '',
                'atualizado' => isset($p['atualizacao']) ? date('d/m/Y H:i', strtotime($p['atualizacao'])) : ''
            ];
        }
        return $formatted;
    }
}
