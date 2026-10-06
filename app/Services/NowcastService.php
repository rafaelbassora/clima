<?php

namespace App\Services;

class NowcastService
{
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

        // 🌡️ Saturação do ar (peso alto)
        $diferencaOrvalho = $tempAtual - $orvalhoAtual;

        if ($diferencaOrvalho <= 1.0)
            $score += 4;
        elseif ($diferencaOrvalho <= 2.0)
            $score += 3;
        elseif ($diferencaOrvalho <= 3.0)
            $score += 2;
        elseif ($diferencaOrvalho <= 4.0)
            $score += 1;

        // 📉 Tendência de pressão
        $taxaQuedaPressao = ($pressaoAnt - $pressaoAtual) / $horasDelta;

        if ($taxaQuedaPressao >= 2.0)
            $score += 3;
        elseif ($taxaQuedaPressao >= 1.0)
            $score += 2;
        elseif ($taxaQuedaPressao >= 0.5)
            $score += 1;

        // 🌬️ Tendência de vento
        $taxaVento = ($ventoAtual - $ventoAnt) / $horasDelta;

        if ($taxaVento >= 20)
            $score += 3;
        elseif ($taxaVento >= 10)
            $score += 2;
        elseif ($taxaVento >= 5)
            $score += 1;

        // =========================
        // 3. DECISÃO FINAL
        // =========================

        if ($score >= 7) {
            return ['mensagem' => "Alta probabilidade de chuva", 'tipo' => 'chuva'];
        }

        if ($score >= 4) {
            return ['mensagem' => "Chance moderada de chuva", 'tipo' => 'chuva'];
        }

        if ($score >= 2) {
            return ['mensagem' => "Baixa chance de chuva", 'tipo' => 'chuva'];
        }

        return ['mensagem' => "Tempo firme", 'tipo' => 'firme'];
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