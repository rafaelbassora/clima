<?php

namespace App\Services;

use App\Models\LeituraModel;

/**
 * Gera rascunhos de notícias a partir dos dados do próprio sistema (sem IA, números exatos):
 * resumo do mês, resumo do ano e eventos extremos.
 *
 * Fontes: chuva do pluviômetro manual (série desde 2018), temperatura e umidade da estação automática.
 * O texto segue o formato das notícias do portal: texto puro, parágrafos separados por linha em branco,
 * subtítulos e listas em linhas próprias (a página exibe com nl2br).
 */
class NoticiaAutomaticaService
{
    private const MESES = [1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho',
        7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'];

    /** limites dos eventos extremos */
    public const CHUVA_FORTE_MM = 50.0;
    public const CALOR_C = 38.0;
    public const FRIO_C = 12.0;
    public const UMIDADE_ALERTA = 20;
    public const UMIDADE_ATENCAO = 30;
    public const ESTIAGEM_DIAS = 20;

    private LeituraModel $model;
    private string $local;
    private string $cidade;
    private string $dominio;
    private string $hoje;
    private ?array $chuva = null;
    private ?array $temp = null;
    private ?array $resumo = null;

    public function __construct(LeituraModel $model, array $config, string $local = '1')
    {
        $this->model = $model;
        $this->local = $local;
        $this->cidade = $config['cidade']['nome'] ?? 'Pereira Barreto';
        $this->dominio = $config['app']['dominio'] ?? '';
        $this->hoje = date('Y-m-d');
    }

    // ------------------------------------------------------------------ dados

    /** chuva diária do pluviômetro manual ['Y-m-d' => mm] */
    private function chuva(): array
    {
        if ($this->chuva === null) {
            $ini = $this->model->getPrimeiroDiaChuva($this->local, 'manual') ?? '2018-01-01';
            $this->chuva = $this->model->getChuvaDiariaPeriodo($this->local, 'manual', $ini, $this->hoje);
            ksort($this->chuva);
        }
        return $this->chuva;
    }

    /** temperatura diária ['Y-m-d' => ['tmin','tmax','hmin','hmax']] */
    private function temperatura(): array
    {
        if ($this->temp === null) {
            $ini = $this->model->getPrimeiroDiaTemperatura($this->local);
            $this->temp = $ini ? $this->model->getTemperaturaDiaria($this->local, $ini, $this->hoje) : [];
        }
        return $this->temp;
    }

    /** umidade mínima diária ['Y-m-d' => %] */
    private function umidade(): array
    {
        if ($this->resumo === null) {
            $ini = $this->model->getPrimeiroDiaMedida($this->local, 'hum');
            $dias = $ini ? $this->model->getResumoDiario($this->local, $ini, $this->hoje) : [];
            $this->resumo = [];
            foreach ($dias as $dia => $d) {
                // abaixo de 5% é defeito do sensor (ex.: fev–mar/2026 gravou 1% por dias seguidos), não clima real
                if ($d['hmin'] !== null && $d['hmin'] >= 5) {
                    $this->resumo[$dia] = (int) $d['hmin'];
                }
            }
        }
        return $this->resumo;
    }

    private static function filtrar(array $dias, string $prefixo): array
    {
        return array_filter($dias, fn ($d) => str_starts_with($d, $prefixo), ARRAY_FILTER_USE_KEY);
    }

    // ------------------------------------------------------------------ formatação

    private static function n(float $v, int $casas = 1): string
    {
        return number_format($v, $casas, ',', '.');
    }

    private static function mm(float $v): string
    {
        return self::n($v, 2) . ' mm';
    }

    private static function dataBR(string $d, bool $ano = false): string
    {
        $t = strtotime($d);
        $dia = (int) date('j', $t);
        return ($dia === 1 ? '1º' : $dia) . ' de ' . self::MESES[(int) date('n', $t)] . ($ano ? ' de ' . date('Y', $t) : '');
    }

    private static function mesAno(int $m, int $a): string
    {
        return self::MESES[$m] . ' de ' . $a;
    }

    private static function maiuscula(string $s): string
    {
        return mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
    }

    private function encerramento(): string
    {
        $site = $this->dominio ? " em {$this->dominio}" : '';
        return "Acompanhamento e Monitoramento\n"
            . "Os dados de chuva, temperatura, umidade e vento são registrados diariamente pelo sistema de monitoramento climático municipal e ficam disponíveis para consulta pública{$site}. "
            . 'As informações apoiam o planejamento da infraestrutura urbana, a manutenção de estradas rurais, a Defesa Civil e o setor agropecuário. '
            . 'Os dados têm caráter informativo e não substituem os alertas oficiais da Defesa Civil, do INMET ou do CPTEC/INPE.';
    }

    private function montar(string $titulo, string $resumo, array $blocos, array $dados): array
    {
        $blocos[] = $this->encerramento();
        return [
            'titulo' => mb_substr($titulo, 0, 255),
            'resumo' => mb_substr($resumo, 0, 500),
            'conteudo' => implode("\n\n", array_filter($blocos, fn ($b) => trim($b) !== '')),
            'dados' => $dados,
        ];
    }

    // ------------------------------------------------------------------ apoio

    /** total de chuva de um mês em cada ano da série ([ano => mm]), só anos com o mês completo */
    private function totaisDoMesPorAno(int $mes): array
    {
        $chuva = $this->chuva();
        if (!$chuva) {
            return [];
        }
        $primeiro = array_key_first($chuva);
        $totais = [];
        for ($a = (int) substr($primeiro, 0, 4); $a <= (int) date('Y'); $a++) {
            $ym = sprintf('%04d-%02d', $a, $mes);
            if ($ym < substr($primeiro, 0, 7) || $ym > date('Y-m')) {
                continue;
            }
            $dias = self::filtrar($chuva, $ym . '-');
            if ($dias) {
                $totais[$a] = array_sum($dias);
            }
        }
        return $totais;
    }

    /** maior sequência de dias seguidos com 0 mm dentro de um conjunto ordenado de dias */
    private static function maiorSequenciaSeca(array $dias): array
    {
        $melhor = ['dias' => 0, 'inicio' => null, 'fim' => null];
        $atual = 0;
        $ini = null;
        $anterior = null;
        foreach ($dias as $dia => $mm) {
            $continuo = $anterior !== null && strtotime($dia) - strtotime($anterior) === 86400;
            if ($mm <= 0) {
                if (!$continuo || $atual === 0) {
                    $atual = 0;
                    $ini = $dia;
                }
                $atual++;
                if ($atual > $melhor['dias']) {
                    $melhor = ['dias' => $atual, 'inicio' => $ini, 'fim' => $dia];
                }
            } else {
                $atual = 0;
            }
            $anterior = $dia;
        }
        return $melhor;
    }

    private function blocoTemperatura(string $prefixo, string $quando): string
    {
        $dias = self::filtrar($this->temperatura(), $prefixo);
        if (!$dias) {
            return '';
        }
        $maxs = array_column($dias, 'tmax');
        $mins = array_column($dias, 'tmin');
        $maior = null;
        $menor = null;
        foreach ($dias as $dia => $d) {
            if ($maior === null || $d['tmax'] > $maior[1]) {
                $maior = [$dia, $d['tmax'], $d['hmax']];
            }
            if ($menor === null || $d['tmin'] < $menor[1]) {
                $menor = [$dia, $d['tmin'], $d['hmin']];
            }
        }
        $calor = count(array_filter($maxs, fn ($v) => $v >= 35));
        $txt = "Temperatura\n"
            . "{$quando}, a média das temperaturas máximas foi de " . self::n(array_sum($maxs) / count($maxs)) . ' °C e a das mínimas, de '
            . self::n(array_sum($mins) / count($mins)) . ' °C. '
            . 'A maior temperatura foi de ' . self::n($maior[1]) . ' °C, em ' . self::dataBR($maior[0]) . ($maior[2] && $maior[2] !== '--:--' ? " (às {$maior[2]})" : '')
            . ', e a menor, de ' . self::n($menor[1]) . ' °C, em ' . self::dataBR($menor[0]) . ($menor[2] && $menor[2] !== '--:--' ? " (às {$menor[2]})" : '') . '.';
        if ($calor > 0) {
            $txt .= " Ao todo, {$calor} " . ($calor === 1 ? 'dia teve' : 'dias tiveram') . ' máxima igual ou acima de 35 °C.';
        }
        return $txt;
    }

    private function blocoUmidade(string $prefixo, string $quando): string
    {
        $dias = self::filtrar($this->umidade(), $prefixo);
        if (!$dias) {
            return '';
        }
        $min = min($dias);
        $diaMin = array_search($min, $dias, true);
        $atencao = count(array_filter($dias, fn ($v) => $v <= self::UMIDADE_ATENCAO));
        $txt = "Umidade do Ar\n"
            . "{$quando}, a menor umidade relativa registrada foi de {$min}%, em " . self::dataBR($diaMin) . '.';
        if ($atencao > 0) {
            $txt .= " Em {$atencao} " . ($atencao === 1 ? 'dia' : 'dias') . ' a umidade ficou em ' . self::UMIDADE_ATENCAO
                . '% ou menos, nível considerado de atenção pela Defesa Civil — a recomendação é beber bastante água, evitar atividades físicas nos horários mais quentes e não fazer queimadas.';
        }
        return $txt;
    }

    // ------------------------------------------------------------------ resumo do mês

    public function gerarMes(int $ano, int $mes): array
    {
        $ym = sprintf('%04d-%02d', $ano, $mes);
        $dias = self::filtrar($this->chuva(), $ym . '-');
        if (!$dias) {
            throw new \RuntimeException('Não há leituras de chuva para ' . self::mesAno($mes, $ano) . '.');
        }
        $parcial = $ym === date('Y-m');
        $nomeMes = self::MESES[$mes];
        $total = array_sum($dias);
        $comChuva = array_filter($dias, fn ($v) => $v > 0);
        arsort($comChuva);

        $porAno = $this->totaisDoMesPorAno($mes);
        $outros = array_diff_key($porAno, [$ano => 0]);
        $anoIni = $porAno ? min(array_keys($porAno)) : $ano;
        $media = $outros ? array_sum($outros) / count($outros) : null;
        $dif = $media ? ($total - $media) / $media * 100 : null;
        $menor = !$parcial && $outros && $total < min($outros);
        $maior = $outros && $total > max($outros);

        // título conforme o que mais chama atenção
        $cidade = $this->cidade;
        if ($menor && count($outros) >= 2) {
            $titulo = self::maiuscula($nomeMes) . " de {$ano} registra o menor volume de chuvas em {$cidade} desde {$anoIni}";
        } elseif ($maior && count($outros) >= 2) {
            $titulo = self::maiuscula($nomeMes) . " de {$ano} é o mais chuvoso em {$cidade} desde {$anoIni}";
        } elseif ($dif !== null && $dif >= 30 && !$parcial) {
            $titulo = self::maiuscula($nomeMes) . " de {$ano} tem chuva " . self::n($dif, 0) . "% acima da média em {$cidade}";
        } elseif ($dif !== null && $dif <= -30 && !$parcial) {
            $titulo = self::maiuscula($nomeMes) . " de {$ano} tem chuva " . self::n(abs($dif), 0) . "% abaixo da média em {$cidade}";
        } else {
            $titulo = self::maiuscula($nomeMes) . " de {$ano} " . ($parcial ? 'acumula ' : 'termina com ') . self::mm($total) . " de chuva em {$cidade}";
        }

        $ultimoDia = self::dataBR(array_key_last($dias));
        $quando = $parcial ? "Até {$ultimoDia}" : 'Em ' . $nomeMes;
        if ($parcial) {
            // mês incompleto: não compara com a média do mês inteiro (daria um "% abaixo" enganoso)
            $comparacao = $media === null ? '' : ' (a média histórica para o mês inteiro é de ' . self::mm($media) . ')';
            $resumo = "Até {$ultimoDia}, {$nomeMes} de {$ano} acumula " . self::mm($total) . " de chuva em {$cidade}{$comparacao}, segundo o monitoramento pluviométrico municipal.";
            $intro = "Com o mês ainda em andamento, a Estância Turística de {$cidade} acumula " . self::mm($total) . " de chuva em {$nomeMes} de {$ano}, até {$ultimoDia}"
                . $comparacao . ", de acordo com os dados do pluviômetro da Sede (série histórica iniciada em {$anoIni}).";
        } else {
            $comparacao = $media === null ? '' : (abs($dif) < 10
                ? ', próximo da média histórica do mês (' . self::mm($media) . ')'
                : ', ' . self::n(abs($dif), 0) . '% ' . ($dif > 0 ? 'acima' : 'abaixo') . ' da média histórica do mês (' . self::mm($media) . ')');
            $resumo = self::maiuscula($nomeMes) . " de {$ano} registrou " . self::mm($total) . " de chuva em {$cidade}{$comparacao}, segundo o monitoramento pluviométrico municipal.";
            $intro = "A Estância Turística de {$cidade} encerrou {$nomeMes} de {$ano} com " . self::mm($total) . ' de chuva' . $comparacao
                . ", de acordo com os dados do pluviômetro da Sede (série histórica iniciada em {$anoIni}).";
        }
        if ($menor) {
            $intro .= " É o menor volume para o mês de {$nomeMes} desde o início da série.";
        } elseif ($maior) {
            $intro .= ' É o maior volume para o mês desde o início da série' . ($parcial ? ', mesmo com o mês ainda em andamento.' : '.');
        }

        $lista = '';
        if (count($porAno) >= 2) {
            krsort($porAno);
            $lista = 'Comparativo de ' . self::maiuscula($nomeMes) . " (Série Histórica)\n"
                . "Confira o histórico de chuvas em {$nomeMes} (Sede):\n\n"
                . implode("\n", array_map(fn ($a, $v) => "{$a}: " . self::mm($v) . ($a === $ano && $parcial ? ' (parcial)' : ''), array_keys($porAno), $porAno));
        }

        $distrib = 'Distribuição das Chuvas' . "\n";
        if (!$comChuva) {
            $distrib .= "Não houve registro de chuva em nenhum dia do período.";
        } else {
            $top = array_slice($comChuva, 0, 2, true);
            $somaTop = array_sum($top);
            $distrib .= 'Choveu em ' . count($comChuva) . ' de ' . count($dias) . ' dias com leitura. A maior chuva em um único dia foi de '
                . self::mm(reset($top)) . ', em ' . self::dataBR(array_key_first($top)) . '.';
            if (count($comChuva) > 2 && $total > 0 && $somaTop / $total >= 0.6) {
                $diasTop = array_keys($top);
                sort($diasTop);
                $distrib .= ' As chuvas se concentraram em poucos dias: ' . self::n($somaTop / $total * 100, 0) . '% de todo o volume caiu em apenas dois dias ('
                    . implode(' e ', array_map(fn ($d) => self::dataBR($d), $diasTop)) . ').';
            }
            $seca = self::maiorSequenciaSeca($dias);
            if ($seca['dias'] >= 7) {
                $distrib .= " O maior período sem chuva no mês foi de {$seca['dias']} dias seguidos, entre " . self::dataBR($seca['inicio']) . ' e ' . self::dataBR($seca['fim']) . '.';
            }
        }

        return $this->montar($titulo, $resumo, [
            $intro,
            $lista,
            $distrib,
            $this->blocoTemperatura($ym . '-', $quando),
            $this->blocoUmidade($ym . '-', $quando),
        ], ['total_mm' => $total, 'media_mm' => $media, 'dif_pct' => $dif, 'parcial' => $parcial]);
    }

    // ------------------------------------------------------------------ resumo do ano

    public function gerarAno(int $ano): array
    {
        $chuva = $this->chuva();
        $dias = self::filtrar($chuva, $ano . '-');
        if (!$dias) {
            throw new \RuntimeException("Não há leituras de chuva para {$ano}.");
        }
        $parcial = $ano === (int) date('Y');
        $cidade = $this->cidade;
        $total = array_sum($dias);

        $porAno = [];
        foreach ($chuva as $dia => $mm) {
            $a = (int) substr($dia, 0, 4);
            $porAno[$a] = ($porAno[$a] ?? 0) + $mm;
        }
        $anoIni = min(array_keys($porAno));
        $completos = array_diff_key($porAno, [(int) date('Y') => 0, $ano => 0]);
        $media = $completos ? array_sum($completos) / count($completos) : null;
        $dif = $media ? ($total - $media) / $media * 100 : null;
        $menor = !$parcial && $completos && $total < min($completos);
        $maior = $completos && $total > max($completos);

        $porMes = [];
        foreach ($dias as $dia => $mm) {
            $m = (int) substr($dia, 5, 2);
            $porMes[$m] = ($porMes[$m] ?? 0) + $mm;
        }
        $mesMaisChuvoso = array_search(max($porMes), $porMes, true);
        $mesMaisSeco = array_search(min($porMes), $porMes, true);
        $maiorDia = array_search(max($dias), $dias, true);
        $seca = self::maiorSequenciaSeca($dias);

        if ($menor && count($completos) >= 2) {
            $titulo = "{$cidade} fecha {$ano} com o menor volume de chuvas desde {$anoIni}";
        } elseif ($maior && count($completos) >= 2) {
            $titulo = "{$ano} é o ano mais chuvoso em {$cidade} desde {$anoIni}" . ($parcial ? ', mesmo antes de terminar' : '');
        } else {
            $titulo = "{$cidade} " . ($parcial ? 'acumula ' : 'fecha ') . "{$ano} com " . self::mm($total) . ' de chuva';
        }
        $comparacao = $media === null ? '' : (abs($dif) < 10
            ? ', próximo da média anual da série (' . self::mm($media) . ')'
            : ', ' . self::n(abs($dif), 0) . '% ' . ($dif > 0 ? 'acima' : 'abaixo') . ' da média anual da série (' . self::mm($media) . ')');

        $resumo = "{$cidade} " . ($parcial ? 'acumula ' : 'registrou ') . self::mm($total) . " de chuva em {$ano}{$comparacao}. "
            . self::maiuscula(self::MESES[$mesMaisChuvoso]) . ' foi o mês mais chuvoso, com ' . self::mm($porMes[$mesMaisChuvoso]) . '.';

        $intro = ($parcial ? 'Com o ano ainda em andamento, a' : 'A') . " Estância Turística de {$cidade} " . ($parcial ? 'acumula' : 'encerrou')
            . " {$ano} com " . self::mm($total) . " de chuva{$comparacao}, segundo o pluviômetro da Sede (série iniciada em {$anoIni}).";
        if ($menor) {
            $intro .= ' É o ano menos chuvoso de toda a série.';
        } elseif ($maior) {
            $intro .= ' É o ano mais chuvoso de toda a série.';
        }

        krsort($porAno);
        $lista = "Chuva Acumulada por Ano (Sede)\n\n" . implode("\n", array_map(
            fn ($a, $v) => "{$a}: " . self::mm($v) . ($a === (int) date('Y') ? ' (parcial)' : ''),
            array_keys($porAno), $porAno
        ));

        $meses = "Mês a Mês\n"
            . 'O mês mais chuvoso foi ' . self::MESES[$mesMaisChuvoso] . ' (' . self::mm($porMes[$mesMaisChuvoso]) . ') e o mais seco, '
            . self::MESES[$mesMaisSeco] . ' (' . self::mm($porMes[$mesMaisSeco]) . '). A maior chuva em um único dia foi de '
            . self::mm($dias[$maiorDia]) . ', em ' . self::dataBR($maiorDia) . '.';
        if ($seca['dias'] >= 15) {
            $meses .= " O período mais longo sem chuva durou {$seca['dias']} dias, de " . self::dataBR($seca['inicio']) . ' a ' . self::dataBR($seca['fim']) . '.';
        }

        return $this->montar($titulo, $resumo, [
            $intro,
            $lista,
            $meses,
            $this->blocoTemperatura($ano . '-', 'Ao longo de ' . $ano),
            $this->blocoUmidade($ano . '-', 'Ao longo de ' . $ano),
        ], ['total_mm' => $total, 'media_mm' => $media, 'dif_pct' => $dif, 'parcial' => $parcial]);
    }

    // ------------------------------------------------------------------ eventos extremos

    /**
     * Eventos marcantes num período. Cada um tem uma 'chave' que gerarEvento() entende.
     */
    public function detectarEventos(string $inicio, string $fim): array
    {
        $eventos = [];
        $chuva = $this->chuva();
        $temp = $this->temperatura();
        $umid = $this->umidade();

        foreach (self::filtrar($chuva, '') as $dia => $mm) {
            if ($dia >= $inicio && $dia <= $fim && $mm >= self::CHUVA_FORTE_MM) {
                $eventos[] = ['chave' => "chuva:{$dia}", 'dia' => $dia, 'tipo' => 'Chuva forte', 'icone' => 'bi-cloud-rain-heavy-fill',
                    'descricao' => self::mm($mm) . ' em ' . self::dataBR($dia, true)];
            }
        }

        // calor, frio e umidade: junta dias seguidos (folga de 1 dia) num evento só, pelo dia de pico
        $brutos = ['calor' => [], 'frio' => [], 'umidade' => []];
        $recordeMax = null;
        $recordeMin = null;
        foreach ($temp as $dia => $d) {
            $antesMax = $recordeMax;
            $antesMin = $recordeMin;
            $recordeMax = $recordeMax === null ? $d['tmax'] : max($recordeMax, $d['tmax']);
            $recordeMin = $recordeMin === null ? $d['tmin'] : min($recordeMin, $d['tmin']);
            if ($dia < $inicio || $dia > $fim) {
                continue;
            }
            $recMax = $antesMax !== null && $d['tmax'] > $antesMax;
            $recMin = $antesMin !== null && $d['tmin'] < $antesMin;
            if ($d['tmax'] >= self::CALOR_C || $recMax) {
                $brutos['calor'][$dia] = ['valor' => $d['tmax'], 'recorde' => $recMax];
            }
            if ($d['tmin'] <= self::FRIO_C || $recMin) {
                $brutos['frio'][$dia] = ['valor' => $d['tmin'], 'recorde' => $recMin];
            }
        }
        foreach ($umid as $dia => $h) {
            if ($dia >= $inicio && $dia <= $fim && $h <= self::UMIDADE_ALERTA) {
                $brutos['umidade'][$dia] = ['valor' => $h, 'recorde' => false];
            }
        }

        $rotulos = [
            'calor'   => ['Calor intenso', 'Recorde de calor', 'Onda de calor', 'bi-thermometer-sun', fn ($v) => self::n($v) . ' °C', true],
            'frio'    => ['Frio intenso', 'Recorde de frio', 'Onda de frio', 'bi-thermometer-snow', fn ($v) => self::n($v) . ' °C', false],
            'umidade' => ['Umidade muito baixa', 'Umidade muito baixa', 'Tempo muito seco', 'bi-droplet-half', fn ($v) => "{$v}%", false],
        ];
        foreach ($brutos as $grupo => $dias) {
            ksort($dias);
            [$rotulo, $rotuloRecorde, $rotuloOnda, $icone, $fmt, $maiorEhPico] = $rotulos[$grupo];
            $blocos = [];
            $anterior = null;
            foreach ($dias as $dia => $info) {
                if ($anterior === null || strtotime($dia) - strtotime($anterior) > 2 * 86400) {
                    $blocos[] = [];
                }
                $blocos[count($blocos) - 1][$dia] = $info;
                $anterior = $dia;
            }
            foreach ($blocos as $bloco) {
                $pico = null;
                foreach ($bloco as $dia => $info) {
                    if ($pico === null || ($maiorEhPico ? $info['valor'] > $bloco[$pico]['valor'] : $info['valor'] < $bloco[$pico]['valor'])) {
                        $pico = $dia;
                    }
                }
                $recorde = (bool) array_filter(array_column($bloco, 'recorde'));
                $n = count($bloco);
                $primeiro = array_key_first($bloco);
                $ultimo = array_key_last($bloco);
                $tipo = $recorde ? $rotuloRecorde : ($n >= 3 ? $rotuloOnda : $rotulo);
                $descricao = $fmt($bloco[$pico]['valor']) . ' em ' . self::dataBR($pico, true)
                    . ($n > 1 ? " — {$n} dias entre " . self::dataBR($primeiro) . ' e ' . self::dataBR($ultimo) : '');
                $eventos[] = ['chave' => "{$grupo}:{$pico}", 'dia' => $pico, 'tipo' => $tipo, 'icone' => $icone, 'descricao' => $descricao];
            }
        }

        // estiagem: maior sequência sem chuva dentro do período (estendida pra trás até o começo real)
        $periodo = array_filter($chuva, fn ($d) => $d >= $inicio && $d <= $fim, ARRAY_FILTER_USE_KEY);
        $secaNoPeriodo = self::maiorSequenciaSeca($periodo);
        if ($secaNoPeriodo['dias'] >= self::ESTIAGEM_DIAS) {
            // estende pra trás até o começo real da sequência
            $ini = $secaNoPeriodo['inicio'];
            while (isset($chuva[date('Y-m-d', strtotime("{$ini} -1 day"))]) && $chuva[date('Y-m-d', strtotime("{$ini} -1 day"))] <= 0) {
                $ini = date('Y-m-d', strtotime("{$ini} -1 day"));
            }
            $n = (int) ((strtotime($secaNoPeriodo['fim']) - strtotime($ini)) / 86400) + 1;
            $eventos[] = ['chave' => "estiagem:{$ini}:{$secaNoPeriodo['fim']}", 'dia' => $secaNoPeriodo['fim'], 'tipo' => 'Estiagem', 'icone' => 'bi-sun',
                'descricao' => "{$n} dias seguidos sem chuva (" . self::dataBR($ini) . ' a ' . self::dataBR($secaNoPeriodo['fim'], true) . ')'];
        }

        usort($eventos, fn ($a, $b) => strcmp($b['dia'], $a['dia']));
        return $eventos;
    }

    public function gerarEvento(string $chave): array
    {
        $p = explode(':', $chave);
        $tipo = $p[0] ?? '';
        $valida = fn ($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && checkdate((int) substr($d, 5, 2), (int) substr($d, 8, 2), (int) substr($d, 0, 4));
        if (!$valida($p[1] ?? null) || ($tipo === 'estiagem' && !$valida($p[2] ?? null))) {
            throw new \RuntimeException('Evento inválido.');
        }
        $dia = $p[1];
        $cidade = $this->cidade;

        switch ($tipo) {
            case 'chuva':
                $chuva = $this->chuva();
                if (!isset($chuva[$dia])) {
                    throw new \RuntimeException('Não há leitura de chuva nesse dia.');
                }
                $mm = $chuva[$dia];
                $anteriorMaior = null;
                foreach ($chuva as $d => $v) {
                    if ($d < $dia && $v >= $mm) {
                        $anteriorMaior = $d;
                    }
                }
                $doAno = array_filter(self::filtrar($chuva, substr($dia, 0, 4) . '-'), fn ($v) => $v > $mm);
                $destaque = $anteriorMaior === null
                    ? 'a maior chuva em um único dia desde o início da série histórica'
                    : (!$doAno ? 'a maior chuva diária do ano' : '');
                $titulo = "{$cidade} registra " . self::mm($mm) . ' de chuva em ' . self::dataBR($dia);
                $resumo = "O pluviômetro da Sede registrou " . self::mm($mm) . ' em ' . self::dataBR($dia, true) . ($destaque ? ", {$destaque}" : '') . '.';
                $mes = (int) substr($dia, 5, 2);
                $acumMes = array_sum(array_filter(self::filtrar($chuva, substr($dia, 0, 7) . '-'), fn ($d) => $d <= $dia, ARRAY_FILTER_USE_KEY));
                $intro = "Uma chuva forte atingiu {$cidade}: o pluviômetro da Sede registrou " . self::mm($mm) . ' na medição de ' . self::dataBR($dia, true)
                    . ($destaque ? ", {$destaque}" : '') . '. ';
                if ($anteriorMaior !== null) {
                    $intro .= 'Um volume igual ou maior não era registrado desde ' . self::dataBR($anteriorMaior, true) . ' (' . self::mm($chuva[$anteriorMaior]) . ').';
                }
                $contexto = "Contexto do Mês\nCom essa chuva, " . self::MESES[$mes] . ' acumulou ' . self::mm($acumMes) . ' até ' . self::dataBR($dia) . '. '
                    . 'Em situações de chuva forte, a orientação é evitar áreas alagadas, não atravessar córregos e acionar a Defesa Civil (199) em caso de emergência.';
                return $this->montar($titulo, $resumo, [$intro, $contexto], ['mm' => $mm]);

            case 'calor':
            case 'frio':
                $temp = $this->temperatura();
                if (!isset($temp[$dia])) {
                    throw new \RuntimeException('Não há leitura de temperatura nesse dia.');
                }
                $d = $temp[$dia];
                $calor = $tipo === 'calor';
                $v = $calor ? $d['tmax'] : $d['tmin'];
                $hora = $calor ? $d['hmax'] : $d['hmin'];
                $recorde = true;
                $anterior = null;
                foreach ($temp as $k => $x) {
                    if ($k >= $dia) {
                        break;
                    }
                    $xv = $calor ? $x['tmax'] : $x['tmin'];
                    if ($calor ? $xv >= $v : $xv <= $v) {
                        $recorde = false;
                        $anterior = $k;
                    }
                }
                $inicioSerie = self::dataBR(array_key_first($temp), true);
                $palavra = $calor ? 'maior' : 'menor';
                $titulo = $recorde
                    ? "{$cidade} tem a " . ($calor ? 'maior' : 'menor') . ' temperatura já registrada pela estação: ' . self::n($v) . ' °C'
                    : "{$cidade} registra " . self::n($v) . ' °C em ' . self::dataBR($dia);
                $resumo = 'A estação meteorológica municipal marcou ' . self::n($v) . ' °C em ' . self::dataBR($dia, true)
                    . ($hora && $hora !== '--:--' ? ", às {$hora}" : '') . ($recorde ? ", a {$palavra} temperatura desde o início das medições ({$inicioSerie})." : '.');
                $intro = ($calor ? 'O calor' : 'O frio') . " foi intenso em {$cidade}: a estação meteorológica da Sede registrou " . ($calor ? 'máxima' : 'mínima') . ' de '
                    . self::n($v) . ' °C em ' . self::dataBR($dia, true) . ($hora && $hora !== '--:--' ? ", às {$hora}" : '') . '. '
                    . ($recorde ? "É a {$palavra} temperatura registrada desde o início das medições, em {$inicioSerie}."
                        : ($anterior ? 'Uma temperatura assim não era registrada desde ' . self::dataBR($anterior, true) . '.' : ''));
                $orient = $calor
                    ? "Cuidados com o Calor\nEm dias de calor intenso, a recomendação é beber bastante água, evitar exposição ao sol entre 10h e 16h, usar roupas leves e protetor solar, e redobrar a atenção com crianças, idosos e animais."
                    : "Cuidados com o Frio\nEm dias de frio intenso, a recomendação é manter-se agasalhado, proteger crianças, idosos e animais, e evitar aquecedores improvisados em ambientes fechados.";
                return $this->montar($titulo, $resumo, [$intro, $orient], ['valor' => $v, 'recorde' => $recorde]);

            case 'umidade':
                $umid = $this->umidade();
                if (!isset($umid[$dia])) {
                    throw new \RuntimeException('Não há leitura de umidade nesse dia.');
                }
                $h = $umid[$dia];
                $nivel = $h <= 12 ? 'emergência' : ($h <= self::UMIDADE_ALERTA ? 'alerta' : 'atenção');
                $titulo = "Umidade do ar chega a {$h}% em {$cidade}";
                $resumo = "A estação municipal registrou umidade relativa de {$h}% em " . self::dataBR($dia, true) . ", nível de {$nivel} segundo os critérios da Defesa Civil.";
                $intro = "O tempo seco se intensificou em {$cidade}: a umidade relativa do ar chegou a {$h}% em " . self::dataBR($dia, true)
                    . ", segundo a estação meteorológica da Sede. Pelos critérios da Defesa Civil, valores entre 12% e 20% indicam estado de alerta, e abaixo de 12%, de emergência.";
                $orient = "Cuidados com o Tempo Seco\nBeba bastante água, evite atividades físicas ao ar livre entre 10h e 17h, use soro fisiológico nos olhos e narinas, umidifique os ambientes e não faça queimadas — o fogo se espalha rapidamente com o ar seco.";
                return $this->montar($titulo, $resumo, [$intro, $orient], ['umidade' => $h]);

            case 'estiagem':
                $ini = $p[1];
                $fimSeca = $p[2];
                $n = (int) ((strtotime($fimSeca) - strtotime($ini)) / 86400) + 1;
                $titulo = "{$cidade} passa {$n} dias seguidos sem chuva";
                $resumo = "Entre " . self::dataBR($ini) . ' e ' . self::dataBR($fimSeca, true) . ", o pluviômetro da Sede não registrou chuva em {$n} dias seguidos.";
                $intro = "A estiagem se prolonga em {$cidade}: de " . self::dataBR($ini, true) . ' a ' . self::dataBR($fimSeca, true)
                    . ", foram {$n} dias seguidos sem chuva no pluviômetro da Sede.";
                $orient = "Atenção às Queimadas\nCom o tempo seco prolongado, o risco de incêndios em vegetação aumenta. Não faça queimadas, não jogue bitucas de cigarro em terrenos e beira de estradas e, ao ver fogo, acione o Corpo de Bombeiros (193) ou a Defesa Civil (199).";
                return $this->montar($titulo, $resumo, [$intro, $orient], ['dias' => $n]);
        }
        throw new \RuntimeException('Tipo de evento desconhecido.');
    }
}
