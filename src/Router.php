<?php

declare(strict_types=1);

namespace VeloxRouter\Router;

use VeloxRouter\Router\Http\Request;
use VeloxRouter\Router\Http\Response;

class Router
{
    /** @var array<string, array<string, mixed>> */
    protected array $routes = [];

    /** @var array<int, mixed> */
    protected array $globalMiddleware = [];

    public function run(string $host = 'localhost', int $port = 8000): void
    {
        if (PHP_SAPI === 'cli') {
            // Renderiza o banner corporativo de forma limpa e isolada
            ConsoleBanner::render($host, $port);
            
            passthru(sprintf('php -S %s:%d', $host, $port));
            return;
        }

        $this->dispatch();
    }

    public function get(string $uri, callable|string $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $uri, $handler, $middleware);
    }

    public function post(string $uri, callable|string $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $uri, $handler, $middleware);
    }

    public function put(string $uri, callable|string $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $uri, $handler, $middleware);
    }

    public function patch(string $uri, callable|string $handler, array $middleware = []): self
    {
        return $this->addRoute('PATCH', $uri, $handler, $middleware);
    }

    public function delete(string $uri, callable|string $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $uri, $handler, $middleware);
    }

    public function options(string $uri, callable|string $handler, array $middleware = []): self
    {
        return $this->addRoute('OPTIONS', $uri, $handler, $middleware);
    }

    public function head(string $uri, callable|string $handler, array $middleware = []): self
    {
        return $this->addRoute('HEAD', $uri, $handler, $middleware);
    }

    /**
     * Register a route that responds to any HTTP method.
     */
    public function any(string $uri, callable|string $handler, array $middleware = []): self
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'] as $method) {
            $this->addRoute($method, $uri, $handler, $middleware);
        }
        return $this;
    }

    /**
     * Register a global middleware.
     */
    public function addMiddleware(callable|string $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    /**
     * Alias for addMiddleware for expressive fluent syntax (e.g., $router->use(...)).
     */
    public function use(callable|string $middleware): self
    {
        return $this->addMiddleware($middleware);
    }

    protected function addRoute(string $method, string $uri, callable|string $handler, array $middleware): self
    {
        $uri = '/' . trim($uri, '/');
        if ($uri === '/') {
            $uri = '';
        }

        $this->routes[strtoupper($method)][$uri] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];

        return $this;
    }

    public function dispatch(?Request $request = null, ?Response $response = null): void
    {
        $request = $request ?? Request::capture();
        $response = $response ?? new Response();
        
        $method = strtoupper($request->method());
        $uri = $request->uri();

        $uri = '/' . trim($uri, '/');
        if ($uri === '/') {
            $uri = '';
        }

        $routeData = $this->matchRoute($method, $uri);

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch($request, $response, $routeData, $this->globalMiddleware);
    }

    protected function matchRoute(string $method, string $uri): array
    {
        if (!isset($this->routes[$method])) {
            return [null, [], []];
        }

        if (isset($this->routes[$method][$uri])) {
            return [
                $this->routes[$method][$uri]['handler'],
                $this->routes[$method][$uri]['middleware'],
                []
            ];
        }

        foreach ($this->routes[$method] as $routeUri => $routeData) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_-]+)\}/', '([^/]+)', $routeUri);
            $pattern = "#^{$pattern}$#";

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);

                preg_match_all('/\{([a-zA-Z0-9_-]+)\}/', $routeUri, $paramNames);
                $paramNames = $paramNames[1] ?? [];

                $params = [];
                foreach ($paramNames as $index => $name) {
                    $params[$name] = $matches[$index] ?? null;
                }

                return [
                    $routeData['handler'],
                    $routeData['middleware'],
                    $params
                ];
            }
        }

        return [null, [], []];
    }
}
