<?php

/**
 * Front Controller — Ponto de entrada da aplicação
 * Clima Pereira Barreto - SMCPB
 */

// Exibe erros em dev (remover em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Helper global para gerar URLs do sistema
 */
function url(string $path): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $assetsBase = rtrim(dirname($scriptName), '/\\');
    return $assetsBase . '/' . ltrim($path, '/');
}

// Autoload do Composer
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('Erro: vendor/autoload.php não encontrado. Execute "composer install".');
}
require_once $autoloadPath;

// Configurações
$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    die('Erro: config/config.php não encontrado.');
}
$config = require $configPath;

// Timezone
date_default_timezone_set($config['timezone']);

// Inicia sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inicializa o banco de dados
try {
    $db = new \MysqliDb(
        $config['db']['host'],
        $config['db']['username'],
        $config['db']['password'],
        $config['db']['database']
    );
    $db->setPrefix('');
    $db->rawQuery("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
    $GLOBALS['db'] = $db;
} catch (\Exception $e) {
    die('Erro de conexão com o banco de dados: ' . $e->getMessage());
}

$GLOBALS['config'] = $config;

// Inicializa o cache
$cache = new \Core\Cache($config);
$GLOBALS['cache'] = $cache;

// Router
$router = new \Core\Router();

// === Rotas ===
$router->get('/',                   'InicioController',      'index');
$router->get('/inicio',             'InicioController',      'index');
$router->get('/termos',               'TermosController',      'index');
$router->get('/alertas',              'InicioController',      'alertas');
$router->get('/inicio-chuva',       'ChuvaController',       'index');
$router->get('/inicio-et',          'EtController',          'index');
$router->get('/inicio-temperatura', 'TemperaturaController', 'index');
$router->get('/inicio-umidade',     'UmidadeController',      'index');
$router->get('/inicio-pressao',     'PressaoController',      'index');
$router->get('/inicio-vento',       'VentoController',        'index');
$router->get('/noticias',            'NoticiaController',     'index');
$router->get('/noticias/view',       'NoticiaController',     'show');
$router->get('/sobre-nos',          'SobreController',        'index');
$router->get('/previsao-do-tempo',  'PrevisaoController',     'index');

// Pluviômetro Cidadão (público)
$router->get('/pluviometro-cidadao',                        'CidadaoController', 'index');
$router->get('/pluviometro-cidadao/cadastro',                'CidadaoController', 'cadastro');
$router->post('/pluviometro-cidadao/cadastro',               'CidadaoController', 'cadastro');
$router->get('/pluviometro-cidadao/cep-lookup',               'CidadaoController', 'cepLookup');
$router->get('/pluviometro-cidadao/login',                   'CidadaoController', 'login');
$router->post('/pluviometro-cidadao/login',                  'CidadaoController', 'loginAuth');
$router->get('/pluviometro-cidadao/logout',                  'CidadaoController', 'logout');
$router->get('/pluviometro-cidadao/painel',                  'CidadaoController', 'painel');
$router->post('/pluviometro-cidadao/painel/lancar',          'CidadaoController', 'painelLancar');
$router->get('/pluviometro-cidadao/esqueci-senha',            'CidadaoController', 'esqueciSenha');
$router->post('/pluviometro-cidadao/esqueci-senha',           'CidadaoController', 'esqueciSenhaEnviar');
$router->get('/pluviometro-cidadao/redefinir-senha',          'CidadaoController', 'redefinirSenha');
$router->post('/pluviometro-cidadao/redefinir-senha',         'CidadaoController', 'redefinirSenhaSalvar');

// Auth
$router->get('/login',              'LoginController',        'index');
$router->post('/login',             'LoginController',        'auth');
$router->get('/login/2fa',          'LoginController',        'twofa');
$router->post('/login/2fa',         'LoginController',        'twofaVerify');
$router->get('/login/esqueci-senha',    'LoginController',    'esqueciSenha');
$router->post('/login/esqueci-senha',   'LoginController',    'esqueciSenhaEnviar');
$router->get('/login/redefinir-senha',  'LoginController',    'redefinirSenha');
$router->post('/login/redefinir-senha', 'LoginController',    'redefinirSenhaSalvar');
$router->get('/logout',             'LoginController',        'logout');

// Admin
$router->get('/admin',              'AdminController',        'index');
$router->get('/admin/add',          'AdminController',        'add');
$router->post('/admin/add',         'AdminController',        'add');
$router->get('/admin/edit',         'AdminController',        'edit');
$router->post('/admin/edit',        'AdminController',        'edit');
$router->get('/admin/delete',       'AdminController',        'delete');

// Alertas Admin
$router->get('/admin/alertas',        'AdminController',        'alertas');
$router->get('/admin/alerta/add',     'AdminController',        'alertaAdd');
$router->post('/admin/alerta/add',    'AdminController',        'alertaAdd');
$router->get('/admin/alerta/edit',    'AdminController',        'alertaEdit');
$router->post('/admin/alerta/edit',   'AdminController',        'alertaEdit');
$router->get('/admin/alerta/delete',  'AdminController',        'alertaDelete');

// Notícias Admin
$router->get('/admin/noticias',       'AdminController',        'noticias');
$router->get('/admin/noticia/add',    'AdminController',        'noticiaAdd');
$router->post('/admin/noticia/add',   'AdminController',        'noticiaAdd');
$router->get('/admin/noticia/edit',   'AdminController',        'noticiaEdit');
$router->post('/admin/noticia/edit',  'AdminController',        'noticiaEdit');
$router->get('/admin/noticia/delete', 'AdminController',        'noticiaDelete');
$router->get('/admin/nowcasting',     'AdminController',        'nowcasting');

// Usuários Admin
$router->get('/admin/usuarios',        'AdminController',        'usuarios');
$router->get('/admin/usuario/add',     'AdminController',        'usuarioAdd');
$router->post('/admin/usuario/add',    'AdminController',        'usuarioAdd');
$router->get('/admin/usuario/edit',    'AdminController',        'usuarioEdit');
$router->post('/admin/usuario/edit',   'AdminController',        'usuarioEdit');
$router->get('/admin/usuario/delete',  'AdminController',        'usuarioDelete');

// Correção de Chuva Automática Admin
$router->get('/admin/chuva-automatica',         'AdminController', 'chuvaAutomatica');
$router->post('/admin/chuva-automatica/salvar', 'AdminController', 'chuvaAutomaticaSalvar');
$router->get('/admin/chuva-automatica/delete',  'AdminController', 'chuvaAutomaticaDelete');

// 2FA Admin (autosserviço)
$router->get('/admin/2fa',          'AdminController', 'twofa');
$router->post('/admin/2fa/enable',  'AdminController', 'twofaEnable');
$router->post('/admin/2fa/disable', 'AdminController', 'twofaDisable');

// Pluviômetro Cidadão (moderação admin)
$router->get('/admin/cidadaos',         'AdminController', 'cidadaos');
$router->get('/admin/cidadao/aprovar',  'AdminController', 'cidadaoAprovar');
$router->get('/admin/cidadao/rejeitar', 'AdminController', 'cidadaoRejeitar');

// API (AJAX)
$router->get('/api/clima-atual',      'ApiController',         'climaAtual');
$router->post('/api/nowcasting',     'ApiController',         'postNowcasting');
$router->get('/api/nowcasting-test', 'ApiController',         'testNowcasting');
$router->get('/api/calculate-nowcast', 'ApiController',      'calculateNowcast');
$router->get('/api/validate-nowcasting', 'ApiController',      'validateNowcastings');
$router->get('/api/circuit-breaker',   'ApiController',      'circuitBreaker');
$router->get('/api/preload-cache',     'ApiController',      'preloadCache');
$router->post('/api/umidade-solo',     'ApiController',      'postUmidadeSolo');

// Despacha
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Remove query string da URI
$uri = parse_url($uri, PHP_URL_PATH);

// Remove base path se aplicação não está na raiz do domínio
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = rtrim(dirname($scriptName), '/\\');

// Só remove o basePath se a URI realmente começa com ele
if ($basePath && $basePath !== '/' && $basePath !== '\\' && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}

// Remove o /index.php da URI se estiver presente (permitindo fallback manual)
if (str_starts_with($uri, '/index.php')) {
    $uri = substr($uri, 10);
}

// Garante que URI começa com /
$uri = '/' . ltrim($uri, '/');

// Normaliza: remove trailing slash (exceto raiz)
$uri = rtrim($uri, '/') ?: '/';

$router->dispatch($uri, $method);
