<?php

namespace App\Services;

class NowcastService
{
    private int $limiarAlta;
    private int $limiarModerada;
    private int $limiarBaixa;

    /**
     * $limiares: ['limiar_alta' => int, 'limiar_moderada' => int, 'limiar_baixa' => int].
     * Se não informado, usa os padrões — mas o normal é vir da nowcasting_config,
     * que se autoajusta com base no desempenho real (ver ApiController::recalibrarNowcasting).
     */
    public function __construct(array $limiares = [])
    {
        $this->limiarAlta = (int) ($limiares['limiar_alta'] ?? 4);
        $this->limiarModerada = (int) ($limiares['limiar_moderada'] ?? 2);
        $this->limiarBaixa = (int) ($limiares['limiar_baixa'] ?? 1);
    }

    /**
     * Pesos recalibrados em 06/10/2026 com base em análise de 30 dias de dados reais
     * (leituras_completas, ~43 mil amostras, local=1):
     *
     * - Diferença Temp-Orvalho: sinal real, mas modesto. <=1.0°C deu 16,6% de chance de
     *   chuva na hora seguinte (vs. ~4,5% de base geral) — por isso é o sinal principal,
     *   mas sozinho não justifica "alta probabilidade".
     * - Queda de pressão em 1h: sinal fraco e, nos dados, até invertido (pressão SUBINDO
     *   teve mais chuva na sequência, 5,4%, que a maioria das faixas de queda). Peso
     *   reduzido bastante — não é mais usado como driver principal.
     * - Tendência de vento: mantido como sinal terciário de menor peso (não testado com
     *   a mesma profundidade; preferiu-se ser conservador).
     *
     * "Alta probabilidade" agora exige o sinal principal (orvalho) CONFIRMADO por pelo
     * menos mais um sinal, não dispara mais sozinho.
     *
     * Ajuste em 07/10/2026: o sinal de orvalho é MUITO mais fraco à noite. Análise
     * separada por período mostrou que <=1.0°C dá 36,1% de chance de chuva de dia,
     * mas só 13,1% à noite — porque em noites de céu limpo a temperatura cai perto
     * do orvalho por resfriamento radiativo normal, sem relação com chuva chegando.
     * Por isso o peso do orvalho à noite é bem menor (metade, arredondado pra baixo).
     */
    public function calcularTendencia(array $atual, array $anterior, int $minutos = 60, bool $chuvaRecente = false): array
    {
        // Normalização de tempo
        $horasDelta = $minutos / 60;
        if ($horasDelta <= 0)
            $horasDelta = 1;

        // Sanitização
        $pressaoAtual = $this->sanitize($atual['barometro'] ?? 0);
        $pressaoAnt = $this->sanitize($anterior['barometro'] ?? 0);

        $tempAtual = $this->sanitize($atual['temperatura'] ?? 0);
        $orvalhoAtual = $this->sanitize($atual['ponto_orvalho'] ?? 0);

        $ventoAtual = $this->sanitize($atual['vento'] ?? 0);
        $ventoAnt = $this->sanitize($anterior['vento'] ?? 0);

        $rainAtual = $this->sanitize($atual['rainrate'] ?? 0);
        $rainAnterior = $this->sanitize($anterior['rainrate'] ?? 0);

        // =========================
        // 1. CHUVA ATUAL (PRIORIDADE)
        // =========================
        if ($rainAtual > 0) {
            $taxaPressao = ($pressaoAtual - $pressaoAnt) / $horasDelta;
            $diferencaOrvalho = $tempAtual - $orvalhoAtual;

            if ($rainAtual < $rainAnterior && $taxaPressao > 0.5) {
                return ['mensagem' => "Instabilidade perdendo intensidade", 'tipo' => 'melhoria'];
            }

            if ($diferencaOrvalho > 3.0) {
                return ['mensagem' => "Chuva em fase final", 'tipo' => 'melhoria'];
            }

            return ['mensagem' => "Chovendo no momento", 'tipo' => 'chuva'];
        }

        if ($chuvaRecente) {
            return ['mensagem' => "Céu com muitas nuvens", 'tipo' => 'chuva'];
        }

        // =========================
        // 2. SCORE DE CHUVA
        // =========================
        $score = 0;

        // 🌡️ Saturação do ar (sinal principal, mas com peso realista)
        // À noite (resfriamento radiativo) esse sinal é bem menos confiável — peso reduzido.
        $diferencaOrvalho = $tempAtual - $orvalhoAtual;
        $hora = (int) date('H');
        $ehNoite = ($hora < 7 || $hora >= 19);

        if ($diferencaOrvalho <= 1.0)
            $score += $ehNoite ? 1 : 3;
        elseif ($diferencaOrvalho <= 3.0)
            $score += $ehNoite ? 1 : 2;
        elseif ($diferencaOrvalho <= 4.0)
            $score += $ehNoite ? 0 : 1;

        // 📉 Tendência de pressão (sinal secundário, peso bem reduzido)
        $taxaQuedaPressao = ($pressaoAnt - $pressaoAtual) / $horasDelta;

        if ($taxaQuedaPressao >= 1.0)
            $score += 1;

        // 🌬️ Tendência de vento (sinal terciário)
        $taxaVento = ($ventoAtual - $ventoAnt) / $horasDelta;

        if ($taxaVento >= 20)
            $score += 1;

        // =========================
        // 3. DECISÃO FINAL
        // Máximo possível: 5 de dia (3 orvalho + 1 pressão + 1 vento), 3 à noite.
        // Limiares vêm da nowcasting_config (se autoajustam com o tempo).
        // =========================

        if ($score >= $this->limiarAlta) {
            $mensagem = "Alta probabilidade de chuva";
        } elseif ($score >= $this->limiarModerada) {
            $mensagem = "Chance moderada de chuva";
        } elseif ($score >= $this->limiarBaixa) {
            $mensagem = "Baixa chance de chuva";
        } else {
            return ['mensagem' => "Tempo firme", 'tipo' => 'firme'];
        }

        return [
            'mensagem' => $mensagem,
            'tipo' => 'chuva',
            'score' => $score,
            'diferenca_orvalho' => $diferencaOrvalho,
            'taxa_pressao' => $taxaQuedaPressao,
            'taxa_vento' => $taxaVento,
        ];
    }

    private function sanitize($value): float
    {
        if (is_numeric($value))
            return (float) $value;

        $clean = str_replace('.', '', (string) $value);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }
}
