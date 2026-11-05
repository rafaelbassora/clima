<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\NoticiaModel;

/**
 * GET /sitemap.xml — páginas públicas para os buscadores (Google etc.).
 * Fica fora: admin, login, painel do cidadão e telas com dados pessoais.
 */
class SitemapController extends Controller
{
    public function index(): void
    {
        $base = 'https://' . ($this->config['app']['dominio'] ?? ($_SERVER['HTTP_HOST'] ?? ''));
        $hoje = date('Y-m-d');

        // [caminho, frequência de mudança, prioridade]
        $paginas = [
            ['/inicio', 'hourly', '1.0'],
            ['/previsao-do-tempo?local=1', 'daily', '0.9'],
            ['/fases-da-lua', 'daily', '0.8'],
            ['/nascer-e-por-do-sol', 'daily', '0.8'],
            ['/inicio-chuva', 'daily', '0.8'],
            ['/inicio-temperatura', 'daily', '0.7'],
            ['/inicio-umidade', 'daily', '0.6'],
            ['/inicio-vento', 'daily', '0.6'],
            ['/inicio-pressao', 'daily', '0.6'],
            ['/inicio-et', 'daily', '0.6'],
            ['/pluviometro-cidadao', 'weekly', '0.6'],
            ['/noticias', 'weekly', '0.7'],
            ['/sobre-nos', 'monthly', '0.5'],
            ['/termos', 'yearly', '0.2'],
            ['/privacidade', 'yearly', '0.2'],
        ];

        $urls = [];
        foreach ($paginas as [$caminho, $freq, $prio]) {
            $urls[] = ['loc' => $base . $caminho, 'lastmod' => $hoje, 'changefreq' => $freq, 'priority' => $prio];
        }

        try {
            foreach ((new NoticiaModel())->getAll(true) as $n) {
                $urls[] = [
                    'loc' => $base . '/noticias/view?id=' . (int) $n['id'],
                    'lastmod' => date('Y-m-d', strtotime($n['data_noticia'])),
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                ];
            }
        } catch (\Throwable $e) {
            // sem banco: o sitemap sai só com as páginas fixas
        }

        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            echo '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc><lastmod>' . $u['lastmod']
                . '</lastmod><changefreq>' . $u['changefreq'] . '</changefreq><priority>' . $u['priority'] . "</priority></url>\n";
        }
        echo "</urlset>\n";
    }
}
