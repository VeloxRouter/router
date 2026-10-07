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
            $serverAddress = "{$host}:{$port}";
            
            // ANSI Escape Codes for Styling
            $cyan = "\033[36m";
            $green = "\033[32m";
            $bold = "\033[1m";
            $reset = "\033[0m";
            $dim = "\033[2m";

            echo "{$cyan}{$bold}";
            echo " __     __   _           ____             _            \n";
            echo " \\ \\   / /__| | _____  _|  _ \\ ___  _   _| |_ ___ _ __ \n";
            echo "  \\ \\ / / _ \\ |/ _ \\ \\/ / |_) / _ \\| | | | __/ _ \\ '__|\n";
            echo "   \\ V /  __/ | (_) >  <|  _ < (_) | |_| | ||  __/ |   \n";
            echo "    \\_/ \\___|_|\___/_/\_\_| \\_\___/ \__,_|\__\\___|_|   \n";
            echo "                             v1.0.0                      \n";
            echo "{$reset}\n";

            echo " {$green}➜  {$bold}Local:{$reset}   http://{$serverAddress}\n";
            echo " {$dim}➜  Press {$bold}Ctrl+C{$reset}{$dim} to stop the server{$reset}\n\n";
            
            passthru(sprintf('php -S %s', $serverAddress));
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

    public function delete(string $uri, callable|string $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $uri, $handler, $middleware);
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

        $this->routes[$method][$uri] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];

        return $this;
    }

    public function dispatch(?Request $request = null, ?Response $response = null): void
    {
        $request = $request ?? Request::capture();
        $response = $response ?? new Response();
        
        $method = $request->method();
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
