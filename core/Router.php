<?php

namespace Core;

class Router
{
    private array $routes = [];

    public function addRoute(string $method, string $path, string $controller, string $action): void
    {
        $this->routes[] = [
            'method'     => strtoupper($method),
            'path'       => $path,
            'controller' => $controller,
            'action'     => $action,
        ];
    }

    public function get(string $path, string $controller, string $action): void
    {
        $this->addRoute('GET', $path, $controller, $action);
    }

    public function post(string $path, string $controller, string $action): void
    {
        $this->addRoute('POST', $path, $controller, $action);
    }

    public function dispatch(string $uri, string $method): void
    {
        // Remove query string
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $uri) {
                $controllerClass = "App\\Controllers\\{$route['controller']}";

                if (!class_exists($controllerClass)) {
                    http_response_code(500);
                    echo "Controller {$route['controller']} não encontrado.";
                    return;
                }

                $controller = new $controllerClass();
                $action = $route['action'];

                if (!method_exists($controller, $action)) {
                    http_response_code(500);
                    echo "Action {$action} não encontrada em {$route['controller']}.";
                    return;
                }

                $controller->$action();
                return;
            }
        }

        // 404
        http_response_code(404);
        require_once __DIR__ . '/../app/Views/errors/404.php';
    }
}
