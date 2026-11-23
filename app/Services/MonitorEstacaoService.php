<?php

namespace App\Services;

/**
 * Vigia a estação e avisa os administradores por e-mail:
 *  - quando passa LIMITE_MIN minutos sem leitura nova (e lembra a cada LEMBRETE_HORAS se continuar fora);
 *  - quando ela volta a mandar dados (com quanto tempo ficou fora);
 *  - quando a estação informa bateria diferente de "ok" (campo battery da leitura).
 *
 * Roda dentro do cron de 1 em 1 min (/api/circuit-breaker). O estado fica em
 * cache/monitor-estacao-{local}.json — fora do padrão *.cache, então "limpar cache" não apaga.
 * Se o e-mail falhar, o estado não é gravado e ele tenta de novo no minuto seguinte.
 */
class MonitorEstacaoService
{
    public const LIMITE_MIN = 30;
    private const LEMBRETE_HORAS = 12;

    private \MysqliDb $db;
    private string $local;
    private string $arquivo;

    public function __construct(string $local = '1')
    {
        $this->db = $GLOBALS['db'];
        $this->local = in_array($local, ['1', '2', '3', '4'], true) ? $local : '1';
        $this->arquivo = __DIR__ . '/../../cache/monitor-estacao-' . $this->local . '.json';
    }

    /** Situação atual, pra mostrar no admin. */
    public function status(): array
    {
        $ultima = $this->ultimaLeitura();
        $minutos = $ultima ? $this->minutosDesde($ultima['created_at']) : null;
        return [
            'ultima'   => $ultima['created_at'] ?? null,
            'minutos'  => $minutos,
            'offline'  => $minutos === null || $minutos >= self::LIMITE_MIN,
            'bateria'  => $ultima['battery'] ?? null,
            'limite'   => self::LIMITE_MIN,
            'estado'   => $this->lerEstado(),
            'destinos' => count($this->destinatarios()),
        ];
    }

    /** Chamado pelo cron. Retorna os eventos disparados (pra log/JSON). */
    public function verificar(): array
    {
        $ultima = $this->ultimaLeitura();
        if (!$ultima) {
            return [];
        }

        $estado = $this->lerEstado();
        $novo = $estado;
        $agora = time();
        $minutos = $this->minutosDesde($ultima['created_at']);
        $eventos = [];

        if ($minutos >= self::LIMITE_MIN) {
            if (!$estado['offline']) {
                $novo['offline'] = true;
                $novo['desde'] = $ultima['created_at'];
                $novo['avisado_em'] = date('Y-m-d H:i:s', $agora);
                $eventos[] = 'offline';
            } elseif ($agora - strtotime((string) $estado['avisado_em']) >= self::LEMBRETE_HORAS * 3600) {
                $novo['avisado_em'] = date('Y-m-d H:i:s', $agora);
                $eventos[] = 'lembrete';
            }
        } else {
            if ($estado['offline']) {
                $novo['offline'] = false;
                $novo['avisado_em'] = null;
                $eventos[] = 'voltou';
            }
            // Bateria só vale com leitura fresca (WU e leituras antigas vêm com battery NULL).
            $bateria = strtolower(trim((string) ($ultima['battery'] ?? '')));
            if ($bateria !== '') {
                if ($bateria !== 'ok' && $estado['bateria'] === 'ok') {
                    $eventos[] = 'bateria_baixa';
                } elseif ($bateria === 'ok' && $estado['bateria'] !== 'ok') {
                    $eventos[] = 'bateria_ok';
                }
                $novo['bateria'] = $bateria;
            }
        }

        foreach ($eventos as $evento) {
            if (!$this->avisar($evento, $estado, $ultima, $minutos)) {
                return ['erro_email' => $evento];
            }
        }

        if ($novo !== $estado) {
            $this->gravarEstado($novo);
        }
        return $eventos;
    }

    protected function ultimaLeitura(): ?array
    {
        // Sem cache de propósito: getUltimaLeitura() fica em cache e aqui precisa ser o dado real.
        return $this->db->rawQueryOne(
            "SELECT created_at, battery FROM leituras_completas WHERE local = ? ORDER BY created_at DESC LIMIT 1",
            [$this->local]
        ) ?: null;
    }

    private function minutosDesde(string $dataHora): int
    {
        return (int) floor((time() - strtotime($dataHora)) / 60);
    }

    private function lerEstado(): array
    {
        $padrao = ['offline' => false, 'desde' => null, 'avisado_em' => null, 'bateria' => 'ok'];
        $json = is_file($this->arquivo) ? json_decode((string) file_get_contents($this->arquivo), true) : null;
        return is_array($json) ? array_merge($padrao, array_intersect_key($json, $padrao)) : $padrao;
    }

    private function gravarEstado(array $estado): void
    {
        file_put_contents($this->arquivo, json_encode($estado, JSON_PRETTY_PRINT), LOCK_EX);
    }

    private function destinatarios(): array
    {
        return $this->db->where('is_admin', 1)->get('usuarios', null, ['name', 'email']);
    }

    private function avisar(string $evento, array $estado, array $ultima, int $minutos): bool
    {
        $fmt = fn (?string $d) => $d ? date('d/m/Y H:i', strtotime($d)) : '—';
        $nomeSite = $GLOBALS['config']['app']['nome_sistema'] ?? 'Clima';
        $link = 'https://' . ($GLOBALS['config']['app']['dominio'] ?? 'clima.pereirabarreto.sp.gov.br') . '/admin/estacao';
        $dicas = '<p>O que conferir no local:</p><ul>'
            . '<li>Pilhas/bateria do console e do sensor externo;</li>'
            . '<li>Se o console e o módulo Wi-Fi estão ligados na tomada (luz acesa);</li>'
            . '<li>Se ainda não voltar: tirar da tomada por 30 segundos e religar.</li></ul>';

        switch ($evento) {
            case 'offline':
            case 'lembrete':
                $horas = intdiv($minutos, 60);
                $tempo = $horas > 0 ? "{$horas} h " . ($minutos % 60) . ' min' : "{$minutos} min";
                $assunto = ($evento === 'lembrete' ? 'Lembrete: estação' : 'Estação')
                    . ' sem dados desde ' . $fmt($ultima['created_at']);
                $corpo = "<p>A estação meteorológica não envia leituras há <strong>{$tempo}</strong>.</p>"
                    . '<p>Última leitura recebida: <strong>' . $fmt($ultima['created_at']) . '</strong>.</p>'
                    . $dicas;
                break;
            case 'voltou':
                $fora = $this->minutosDesde((string) $estado['desde']);
                $horas = intdiv($fora, 60);
                $assunto = 'Estação voltou a enviar dados';
                $corpo = '<p>A estação voltou a enviar leituras. Última recebida: <strong>' . $fmt($ultima['created_at']) . '</strong>.</p>'
                    . '<p>Ficou sem dados desde ' . $fmt($estado['desde'])
                    . " (cerca de {$horas} h " . ($fora % 60) . ' min). Esse período vai aparecer como falha nos gráficos.</p>';
                break;
            case 'bateria_baixa':
                $assunto = 'Estação informa bateria baixa';
                $corpo = '<p>A estação informou bateria <strong>' . htmlspecialchars((string) $ultima['battery']) . '</strong> '
                    . 'na leitura de ' . $fmt($ultima['created_at']) . '.</p>'
                    . '<p>Troque as pilhas do console e do sensor externo antes que ela pare de enviar dados.</p>';
                break;
            case 'bateria_ok':
                $assunto = 'Bateria da estação normalizada';
                $corpo = '<p>A estação voltou a informar bateria <strong>ok</strong> (leitura de ' . $fmt($ultima['created_at']) . ').</p>';
                break;
            default:
                return true;
        }

        $corpo .= '<p><a href="' . $link . '">Abrir Admin › Estação</a></p>'
            . '<p style="color:#666;font-size:12px">Aviso automático do ' . htmlspecialchars($nomeSite) . '.</p>';

        $mailer = new MailerService();
        $ok = true;
        foreach ($this->destinatarios() as $admin) {
            if (!empty($admin['email'])) {
                $ok = $mailer->enviar($admin['email'], (string) $admin['name'], "[{$nomeSite}] {$assunto}", $corpo) && $ok;
            }
        }
        return $ok;
    }
}
