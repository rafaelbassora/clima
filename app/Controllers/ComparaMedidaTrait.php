<?php

namespace App\Controllers;

use App\Models\LeituraModel;
use App\Services\ComparacaoMedidaService;

/**
 * Comparador (anos x anos / meses x meses) e médias gerais compartilhados pelas páginas
 * de umidade, pressão, vento e evapotranspiração.
 */
trait ComparaMedidaTrait
{
    /**
     * Médias do período escolhido + média geral, pro bloco de médias no topo da página.
     */
    protected function mediasDaMedida(LeituraModel $model, string $medida, string $local, int $year, mixed $month): array
    {
        $service = new ComparacaoMedidaService($model, $medida);
        $primeiro = $service->primeiroDia($local);
        $medias = $primeiro
            ? $service->mediasDoPeriodo($local, $year, $month, $primeiro)
            : ['resumo' => null, 'geral' => null, 'anos' => null];
        $medias['cfg'] = $service->getConfig();
        return $medias;
    }

    protected function compararMedida(string $medida, string $activeMenu): void
    {
        $local = in_array($this->getParam('local', '1'), ['1', '2', '3', '4'], true) ? $this->getParam('local', '1') : '1';
        $model = new LeituraModel();
        $service = new ComparacaoMedidaService($model, $medida);
        $cfg = $service->getConfig();

        $primeiroDia = $service->primeiroDia($local) ?? date('Y-m-d');
        [$modo, $anos, $itens, $anoInicial] = ComparacaoMedidaService::parametros($_GET, $primeiroDia);

        $resultado = $modo === 'anos'
            ? $service->compararAnos($local, $anos, $primeiroDia)
            : $service->compararMeses($local, $itens, $primeiroDia);

        $this->view('comparar/medida', [
            'pageTitle'   => 'Comparar ' . $cfg['titulo'],
            'cfg'         => $cfg,
            'local'       => $local,
            'modo'        => $modo,
            'anos'        => $anos,
            'itens'       => $itens,
            'anoInicial'  => $anoInicial,
            'primeiroDia' => $primeiroDia,
            'resultado'   => $resultado,
            'meses'       => array_diff_key($model->getMeses(), ['all' => '']),
            'activeMenu'  => $activeMenu,
        ]);
    }
}
