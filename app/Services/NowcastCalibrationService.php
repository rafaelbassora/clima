<?php

namespace App\Services;

use App\Models\LeituraModel;
use App\Models\NowcastConfigModel;

class NowcastCalibrationService
{
    /**
     * Recalcula os limiares de decisão (alta/moderada/baixa) com base na precisão real
     * recente de cada valor de score, e aplica (salva) o resultado.
     */
    public function recalibrar(LeituraModel $model, NowcastConfigModel $configModel, bool $forcar = false): array
    {
        $dados = $model->getDadosParaCalibracao(90, 5000);
        $total = count($dados);

        if ($total === 0) {
            return ['aplicado' => false, 'motivo' => 'Ainda não há amostras com score registrado (aguardando novas previsões)', 'amostras' => 0];
        }

        if ($total < 200 && !$forcar) {
            return ['aplicado' => false, 'motivo' => 'Dados insuficientes (menos de 200 amostras)', 'amostras' => $total];
        }

        // Agrupa por valor exato de score e calcula a precisão (taxa de acerto) de cada um
        $porScore = [];
        foreach ($dados as $d) {
            $s = (int) $d['score'];
            if (!isset($porScore[$s])) {
                $porScore[$s] = ['total' => 0, 'acertos' => 0];
            }
            $porScore[$s]['total']++;
            $porScore[$s]['acertos'] += (int) $d['acertou'];
        }
        ksort($porScore);

        $scoreMax = empty($porScore) ? 5 : max(array_keys($porScore));
        $amostraMinimaPorFaixa = 20;

        $precisaoGeral = 0;
        if ($total > 0) {
            $acertosGerais = array_sum(array_column($dados, 'acertou'));
            $precisaoGeral = $acertosGerais / $total;
        }

        $buscarLimiar = function (float $metaPrecisao) use ($porScore, $amostraMinimaPorFaixa, $scoreMax): int {
            for ($s = 0; $s <= $scoreMax; $s++) {
                if (!isset($porScore[$s]) || $porScore[$s]['total'] < $amostraMinimaPorFaixa) {
                    continue;
                }
                $precisao = $porScore[$s]['acertos'] / $porScore[$s]['total'];
                if ($precisao >= $metaPrecisao) {
                    return $s;
                }
            }
            return $scoreMax + 1; // Nenhum score atingiu a meta — desativa essa categoria
        };

        $limiarAlta = $buscarLimiar(0.40);
        $limiarModerada = $buscarLimiar(0.15);
        $limiarBaixa = $buscarLimiar(max($precisaoGeral, 0.05));

        // Garante ordem lógica: baixa <= moderada <= alta
        $limiarBaixa = max(1, min($limiarBaixa, $limiarModerada));
        $limiarModerada = min($limiarModerada, $limiarAlta);

        $configModel->atualizar($limiarAlta, $limiarModerada, $limiarBaixa, $total);

        return [
            'aplicado' => true,
            'amostras' => $total,
            'precisao_geral' => round($precisaoGeral * 100, 1),
            'limiar_alta' => $limiarAlta,
            'limiar_moderada' => $limiarModerada,
            'limiar_baixa' => $limiarBaixa,
            'precisao_por_score' => $porScore,
        ];
    }
}
