<?php

namespace Core;

class Controller
{
    protected array $config;
    protected \Core\Cache $cache;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/config.php';
        $this->cache = $GLOBALS['cache'];
    }

    /**
     * Renderiza uma view com o layout principal.
     */
    protected function view(string $viewName, array $data = [], string $layout = 'main'): void
    {
        // Extrai variáveis para a view
        extract($data);
        $config = $this->config;

        // Captura o conteúdo da view
        ob_start();
        $viewPath = __DIR__ . "/../app/Views/{$viewName}.php";
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "<p>View '{$viewName}' não encontrada.</p>";
        }
        $content = ob_get_clean();

        // Renderiza o layout com o conteúdo
        $layoutPath = __DIR__ . "/../app/Views/layouts/{$layout}.php";
        if (file_exists($layoutPath)) {
            require $layoutPath;
        } else {
            echo $content;
        }
    }

    /**
     * Retorna resposta JSON (para endpoints da API).
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Pega parâmetro GET com sanitização.
     */
    protected function getParam(string $key, mixed $default = null): mixed
    {
        return isset($_GET[$key]) ? htmlspecialchars(trim($_GET[$key]), ENT_QUOTES, 'UTF-8') : $default;
    }
}
