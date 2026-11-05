<?php

namespace App\Controllers;

use Core\Controller;
use App\Services\AstronomiaService as Astro;

/**
 * Páginas públicas de astronomia (pensadas também para busca no Google):
 * /fases-da-lua e /nascer-e-por-do-sol, com navegação por mês (?ano=&mes=).
 */
class AstronomiaController extends Controller
{
    public const MESES = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
        'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

    /**
     * Lua e pesca segundo a TRADIÇÃO POPULAR (sem comprovação científica consistente).
     * [nível 1–4, rótulo]
     */
    public const PESCA_POR_FASE = [
        'Lua nova'          => [2, 'Regular'],
        'Lua crescente'     => [3, 'Boa'],
        'Quarto crescente'  => [3, 'Boa'],
        'Crescente gibosa'  => [4, 'Ótima'],
        'Lua cheia'         => [4, 'Ótima'],
        'Minguante gibosa'  => [3, 'Boa'],
        'Quarto minguante'  => [2, 'Regular'],
        'Lua minguante'     => [1, 'Fraca'],
    ];

    /**
     * Piracema na bacia do Rio Paraná (onde está Pereira Barreto): normalmente de 1º/nov a 28/fev.
     * Só usado para destacar o aviso — as regras oficiais podem mudar e devem ser conferidas.
     */
    public static function periodoPiracema(int $ts): bool
    {
        return in_array((int) date('n', $ts), [11, 12, 1, 2], true);
    }

    /** Mês pedido na URL (só aceita 2000–2100); sem parâmetros = mês atual. */
    private function mesAno(): array
    {
        $ano = (int) ($_GET['ano'] ?? date('Y'));
        $mes = (int) ($_GET['mes'] ?? date('n'));
        $valido = $ano >= 2000 && $ano <= 2100 && $mes >= 1 && $mes <= 12;
        if (!$valido) {
            $ano = (int) date('Y');
            $mes = (int) date('n');
        }
        $temParametro = isset($_GET['ano']) || isset($_GET['mes']);
        $ehMesAtual = $ano === (int) date('Y') && $mes === (int) date('n');
        return [$ano, $mes, $temParametro && $valido && !$ehMesAtual];
    }

    private function cidade(): string
    {
        $c = $this->config['cidade'] ?? [];
        return ($c['nome'] ?? 'Pereira Barreto') . (isset($c['uf']) ? ' (' . $c['uf'] . ')' : '');
    }

    private function base(): string
    {
        return 'https://' . ($this->config['app']['dominio'] ?? ($_SERVER['HTTP_HOST'] ?? ''));
    }

    /** Links de mês anterior/seguinte (dentro de 2000–2100). */
    private function navegacao(string $caminho, int $ano, int $mes): array
    {
        $ant = mktime(0, 0, 0, $mes - 1, 1, $ano);
        $prox = mktime(0, 0, 0, $mes + 1, 1, $ano);
        $link = fn ($ts) => ((int) date('Y', $ts) >= 2000 && (int) date('Y', $ts) <= 2100)
            ? ['url' => $caminho . '?ano=' . date('Y', $ts) . '&mes=' . date('n', $ts), 'rotulo' => ucfirst(self::MESES[(int) date('n', $ts)]) . ' ' . date('Y', $ts)]
            : null;
        return ['anterior' => $link($ant), 'proximo' => $link($prox)];
    }

    /** GET /fases-da-lua */
    public function lua(): void
    {
        [$ano, $mes, $mesEspecifico] = $this->mesAno();
        $agora = time();
        $cidade = $this->cidade();
        $nomeMes = self::MESES[$mes];

        $hoje = Astro::lua($agora);
        $proximas = array_slice(Astro::fasesPrincipais($agora, $agora + 40 * 86400), 0, 4);

        // calendário do mês: fase de cada dia (meio-dia) + instante das fases principais
        $diasNoMes = (int) date('t', mktime(0, 0, 0, $mes, 1, $ano));
        $dias = [];
        for ($d = 1; $d <= $diasNoMes; $d++) {
            $ts = mktime(12, 0, 0, $mes, $d, $ano);
            $dias[$d] = Astro::lua($ts);
        }
        $inicioMes = mktime(0, 0, 0, $mes, 1, $ano);
        $fimMes = mktime(23, 59, 59, $mes, $diasNoMes, $ano);
        $fasesMes = Astro::fasesPrincipais($inicioMes, $fimMes);

        // tabela do ano, agrupada por mês
        $fasesAno = [];
        foreach (Astro::fasesPrincipais(mktime(0, 0, 0, 1, 1, $ano), mktime(23, 59, 59, 12, 31, $ano)) as $f) {
            $fasesAno[(int) date('n', $f['ts'])][] = $f;
        }

        $url = $this->base() . '/fases-da-lua' . ($mesEspecifico ? "?ano={$ano}&mes={$mes}" : '');
        $resumoFases = implode('; ', array_map(
            fn ($f) => mb_strtolower($f['nome']) . ' em ' . date('d/m', $f['ts']),
            $fasesMes
        ));
        $titulo = $mesEspecifico
            ? "Fases da Lua em {$nomeMes} de {$ano} — {$cidade}"
            : "Fase da Lua hoje em {$cidade}";
        $descricao = $mesEspecifico
            ? "Calendário lunar de {$nomeMes} de {$ano} em {$cidade}: {$resumoFases}. Datas e horários de Brasília."
            : "Fase da lua hoje em {$cidade}: {$hoje['nome']}, {$hoje['iluminacao']}% iluminada. Calendário lunar de {$nomeMes} de {$ano} com {$resumoFases}.";

        $this->view('astronomia/lua', [
            'pageTitle'     => $titulo,
            'activeMenu'    => 'lua',
            'metaDescricao' => $descricao,
            'ogTitulo'      => $titulo,
            'ogDescricao'   => $descricao,
            'ogUrl'         => $url,
            'cidade'        => $cidade,
            'ano'           => $ano,
            'mes'           => $mes,
            'nomeMes'       => $nomeMes,
            'mesEspecifico' => $mesEspecifico,
            'hoje'          => $hoje,
            'proximas'      => $proximas,
            'dias'          => $dias,
            'fasesMes'      => $fasesMes,
            'fasesAno'      => $fasesAno,
            'navegacao'     => $this->navegacao('/fases-da-lua', $ano, $mes),
            'hemisferioSul' => Astro::hemisferioSul(),
            'piracemaAgora' => self::periodoPiracema($agora),
            'jsonLd'        => $this->faqJsonLd([
                ['Qual é a fase da lua hoje em ' . $cidade . '?',
                    'Hoje (' . date('d/m/Y', $agora) . ') a lua está na fase ' . $hoje['nome'] . ', com cerca de ' . $hoje['iluminacao'] . '% do disco iluminado.'],
                ['Quando é a próxima lua cheia?', $this->proximaDescricao(Astro::FASE_CHEIA)],
                ['Quando é a próxima lua nova?', $this->proximaDescricao(Astro::FASE_NOVA)],
                ['Quantos dias dura cada fase da lua?',
                    'O ciclo completo (lunação) dura cerca de 29,5 dias. Entre uma fase principal e a seguinte (nova, quarto crescente, cheia e quarto minguante) passam cerca de 7,4 dias.'],
                ['A lua de hoje é boa para pescar?',
                    'Pela tradição popular dos pescadores, a ' . mb_strtolower($hoje['nome']) . ' é considerada "' . mb_strtolower(self::PESCA_POR_FASE[$hoje['nome']][1] ?? 'regular') . '" para a pesca. Não há comprovação científica consistente: chuva, temperatura da água, vento e pressão costumam influenciar mais. Antes de pescar, observe a legislação e o defeso da piracema (na bacia do Rio Paraná, normalmente de 1º de novembro a 28 de fevereiro).'],
            ]),
        ]);
    }

    /** GET /nascer-e-por-do-sol */
    public function sol(): void
    {
        [$ano, $mes, $mesEspecifico] = $this->mesAno();
        $agora = time();
        $cidade = $this->cidade();
        $nomeMes = self::MESES[$mes];

        $hoje = Astro::sol($agora);
        $ontem = Astro::sol($agora - 86400);
        $diferenca = ($hoje['duracao'] !== null && $ontem['duracao'] !== null) ? $hoje['duracao'] - $ontem['duracao'] : null;

        $diasNoMes = (int) date('t', mktime(0, 0, 0, $mes, 1, $ano));
        $dias = [];
        for ($d = 1; $d <= $diasNoMes; $d++) {
            $dias[$d] = Astro::sol(mktime(12, 0, 0, $mes, $d, $ano));
        }

        // dia mais longo e mais curto do ano
        $maisLongo = $maisCurto = null;
        $diasNoAno = (int) date('z', mktime(0, 0, 0, 12, 31, $ano)) + 1;
        for ($i = 0; $i < $diasNoAno; $i++) {
            $ts = mktime(12, 0, 0, 1, 1 + $i, $ano);
            $s = Astro::sol($ts);
            if ($s['duracao'] === null) {
                continue;
            }
            if ($maisLongo === null || $s['duracao'] > $maisLongo['duracao']) {
                $maisLongo = $s + ['dia' => $ts];
            }
            if ($maisCurto === null || $s['duracao'] < $maisCurto['duracao']) {
                $maisCurto = $s + ['dia' => $ts];
            }
        }

        $hm = fn ($ts) => $ts ? date('H:i', $ts) : '--';
        $url = $this->base() . '/nascer-e-por-do-sol' . ($mesEspecifico ? "?ano={$ano}&mes={$mes}" : '');
        $titulo = $mesEspecifico
            ? "Nascer e pôr do sol em {$nomeMes} de {$ano} — {$cidade}"
            : "Nascer e pôr do sol hoje em {$cidade}";
        $primeiro = $dias[1];
        $ultimo = $dias[$diasNoMes];
        $descricao = $mesEspecifico
            ? "Horários do nascer e do pôr do sol em {$cidade} em {$nomeMes} de {$ano}: de {$hm($primeiro['nascer'])}–{$hm($primeiro['por'])} no dia 1 a {$hm($ultimo['nascer'])}–{$hm($ultimo['por'])} no dia {$diasNoMes}, com a duração do dia."
            : "Nascer do sol hoje em {$cidade}: {$hm($hoje['nascer'])}; pôr do sol: {$hm($hoje['por'])} (" . Astro::formatarDuracao($hoje['duracao']) . " de sol). Tabela com o nascer e o pôr do sol de todos os dias de {$nomeMes} de {$ano}.";

        $this->view('astronomia/sol', [
            'pageTitle'     => $titulo,
            'activeMenu'    => 'sol',
            'metaDescricao' => $descricao,
            'ogTitulo'      => $titulo,
            'ogDescricao'   => $descricao,
            'ogUrl'         => $url,
            'cidade'        => $cidade,
            'ano'           => $ano,
            'mes'           => $mes,
            'nomeMes'       => $nomeMes,
            'mesEspecifico' => $mesEspecifico,
            'hoje'          => $hoje,
            'diferenca'     => $diferenca,
            'dias'          => $dias,
            'maisLongo'     => $maisLongo,
            'maisCurto'     => $maisCurto,
            'navegacao'     => $this->navegacao('/nascer-e-por-do-sol', $ano, $mes),
            'jsonLd'        => $this->faqJsonLd([
                ['Que horas o sol nasce hoje em ' . $cidade . '?',
                    'Hoje (' . date('d/m/Y', $agora) . ') o sol nasce às ' . $hm($hoje['nascer']) . ' e o dia começa a clarear às ' . $hm($hoje['claro_inicio']) . ' (horário de Brasília).'],
                ['Que horas o sol se põe hoje em ' . $cidade . '?',
                    'Hoje o pôr do sol é às ' . $hm($hoje['por']) . ' e fica escuro por volta das ' . $hm($hoje['claro_fim']) . '. São ' . Astro::formatarDuracao($hoje['duracao']) . ' de sol.'],
                ['Qual é o dia mais longo do ano em ' . $cidade . '?',
                    $maisLongo ? 'Em ' . $ano . ', o dia mais longo é ' . date('d/m', $maisLongo['dia']) . ', com ' . Astro::formatarDuracao($maisLongo['duracao']) . ' de sol, perto do solstício de verão. O mais curto é ' . date('d/m', $maisCurto['dia']) . ', com ' . Astro::formatarDuracao($maisCurto['duracao']) . '.' : ''],
            ]),
        ]);
    }

    private function proximaDescricao(int $tipo): string
    {
        foreach (Astro::fasesPrincipais(time(), time() + 40 * 86400) as $f) {
            if ($f['tipo'] === $tipo) {
                $dia = (int) date('w', $f['ts']);
                $semana = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'][$dia];
                return 'A próxima ' . mb_strtolower($f['nome']) . ' será em ' . $semana . ', ' . date('d/m/Y', $f['ts']) . ', às ' . date('H:i', $f['ts']) . ' (horário de Brasília).';
            }
        }
        return '';
    }

    /** Dados estruturados de perguntas frequentes (schema.org FAQPage). */
    private function faqJsonLd(array $perguntas): array
    {
        $itens = [];
        foreach ($perguntas as [$pergunta, $resposta]) {
            if ($resposta === '') {
                continue;
            }
            $itens[] = [
                '@type' => 'Question',
                'name' => $pergunta,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $resposta],
            ];
        }
        return ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $itens];
    }
}
