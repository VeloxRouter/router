<?php

declare(strict_types=1);

namespace VeloxRouter;

use VeloxRouter\Http\Request;
use VeloxRouter\Http\Response;
use VeloxRouter\Support\ConsoleBanner;
use InvalidArgumentException;

class Router
{
    /** @var array<string, array{static: array, dynamic: array}> */
    protected array $routes = [
        'GET' => ['static' => [], 'dynamic' => []],
        'POST' => ['static' => [], 'dynamic' => []],
        'PUT' => ['static' => [], 'dynamic' => []],
        'PATCH' => ['static' => [], 'dynamic' => []],
        'DELETE' => ['static' => [], 'dynamic' => []],
        'OPTIONS' => ['static' => [], 'dynamic' => []],
        'HEAD' => ['static' => [], 'dynamic' => []],
    ];

    /** @var array<int, mixed> */
    protected array $globalMiddleware = [];

    /** @var array<string, string> Map of named routes to their URIs */
    protected array $namedRoutes = [];

    /** @var string|null Stores the signature or URI of the last registered route for chaining ->name() */
    protected ?string $lastRegisteredUri = null;

    /** @var string|null Stores the method of the last registered route for chaining ->name() */
    protected ?string $lastRegisteredMethod = null;

    public function run(string $host = 'localhost', int $port = 8000): void
    {
        if (PHP_SAPI === 'cli') {
            ConsoleBanner::render($host, $port);
            
            // Capture the script that triggered run() and use it as the built-in server router script
            $script = $_SERVER['SCRIPT_FILENAME'];
            
            passthru(sprintf('php -S %s:%d %s', $host, $port, escapeshellarg($script)));
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

    public function any(string $uri, callable|string $handler, array $middleware = []): self
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'] as $method) {
            $this->addRoute($method, $uri, $handler, $middleware);
        }
        return $this;
    }

    public function addMiddleware(callable|string $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    public function use(callable|string $middleware): self
    {
        return $this->addMiddleware($middleware);
    }

    /**
     * Assign a name to the last registered route for reverse routing.
     */
    public function name(string $name): self
    {
        if ($this->lastRegisteredUri !== null) {
            $this->namedRoutes[$name] = $this->lastRegisteredUri;
        }
        return $this;
    }

    /**
     * Generate a URL for a named route with optional parameters.
     */
    public function route(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new InvalidArgumentException("Route [{$name}] is not defined.");
        }

        $uri = $this->namedRoutes[$name];

        // Replace route parameters (supporting both standard {param} and typed {param:regex})
        foreach ($params as $key => $value) {
            $uri = preg_replace('/\{' . $key . '(?::[^}]+)?\}/', (string)$value, $uri);
        }

        return $uri;
    }

    protected function addRoute(string $method, string $uri, callable|string $handler, array $middleware): self
    {
        // Normalize trailing slashes and URI structure
        $uri = '/' . trim($uri, '/');
        if ($uri === '/') {
            $uri = '';
        }

        $method = strtoupper($method);

        // Ensure the HTTP method array exists for safety
        if (!isset($this->routes[$method])) {
            $this->routes[$method] = ['static' => [], 'dynamic' => []];
        }

        // Track for named route association
        $this->lastRegisteredUri = $uri === '' ? '/' : $uri;
        $this->lastRegisteredMethod = $method;

        // Separate static routes from dynamic routes based on the presence of bracket parameters {}
        if (str_contains($uri, '{')) {
            // Support typed route parameters e.g., {id:[0-9]+} or fallback to default [^/]+
            $pattern = preg_replace('/\{([a-zA-Z0-9_-]+)(?::([^}]+))?\}/', '(?P<$1>$2)', $uri);
            $pattern = preg_replace('/\{([a-zA-Z0-9_-]+)\}/', '(?P<$1>[^/]+)', $pattern);
            $pattern = "#^{$pattern}$#";

            // Extract parameter names for matching resolution
            preg_match_all('/\{([a-zA-Z0-9_-]+)(?::[^}]+)?\}/', $uri, $paramNames);
            $paramNames = $paramNames[1] ?? [];

            $this->routes[$method]['dynamic'][] = [
                'pattern' => $pattern,
                'paramNames' => $paramNames,
                'handler' => $handler,
                'middleware' => $middleware,
            ];
        } else {
            $this->routes[$method]['static'][$uri] = [
                'handler' => $handler,
                'middleware' => $middleware,
            ];
        }

        return $this;
    }

    public function dispatch(?Request $request = null, ?Response $response = null): void
    {
        $request = $request ?? Request::capture();
        $response = $response ?? new Response();
        
        $method = strtoupper($request->method());
        $uri = $request->uri();

        // Normalize trailing slashes for lookup consistency
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

        // Attempt instantaneous O(1) static route lookup
        if (isset($this->routes[$method]['static'][$uri])) {
            $route = $this->routes[$method]['static'][$uri];
            return [
                $route['handler'],
                $route['middleware'],
                []
            ];
        }

        // If not static, evaluate only registered dynamic routes via regex
        foreach ($this->routes[$method]['dynamic'] as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches);

                $params = [];
                foreach ($route['paramNames'] as $index => $name) {
                    // Extract named capture groups cleanly
                    if (is_string($name) && isset($matches[$name])) {
                        $params[$name] = $matches[$name];
                    } else {
                        $params[$name] = $matches[$index] ?? null;
                    }
                }

                return [
                    $route['handler'],
                    $route['middleware'],
                    $params
                ];
            }
        }

        return [null, [], []];
    }
}