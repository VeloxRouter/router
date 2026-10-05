<?php

declare(strict_types=1);

namespace VeloxRouter\Router;

use VeloxRouter\Router\Contracts\Handler;
use VeloxRouter\Router\Http\Request;
use VeloxRouter\Router\Http\Response;
use VeloxRouter\Router\Http\HttpStatus;
use VeloxRouter\Router\Pipeline\Pipeline;

class Router
{
    /** @var array<string, array<string, mixed>> */
    protected array $routes = [];

    /** @var array<int, mixed> */
    protected array $globalMiddleware = [];

    /**
     * Run the application.
     * In development mode, it can spin up PHP's built-in development server.
     * In production (Apache/Nginx + PHP-FPM), it dispatches the incoming HTTP request.
     */
    public function run(string $host = 'localhost', int $port = 8000): void
    {
        // If running from the CLI, start PHP's built-in development server for local testing
        if (PHP_SAPI === 'cli' && isset($_SERVER['argv'][0])) {
            $serverAddress = "{$host}:{$port}";
            echo "VeloxRouter running at http://{$serverAddress}\n";
            echo "Press Ctrl+C to quit.\n\n";
            
            // Spin up the internal PHP server pointing to the current directory
            passthru(sprintf('php -S %s', $serverAddress));
            return;
        }

        // In a traditional web server environment, dispatch the request through the router
        $this->dispatch();
    }

    /**
     * Register a GET route.
     */
    public function get(string $uri, callable|string $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $uri, $handler, $middleware);
    }

    /**
     * Register a POST route.
     */
    public function post(string $uri, callable|string $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $uri, $handler, $middleware);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $uri, callable|string $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $uri, $handler, $middleware);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $uri, callable|string $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $uri, $handler, $middleware);
    }

    /**
     * Add global middleware to the application.
     */
    public function addMiddleware(callable|string $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    /**
     * Internal method to store routes.
     */
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

    /**
     * Dispatch the incoming request through the router and pipelines.
     */
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

        [$handler, $routeMiddleware, $params] = $this->matchRoute($method, $uri);

        if (!$handler) {
            $response->status(HttpStatus::NOT_FOUND)->json(['error' => 'Route not found'])->send();
            return;
        }

        // Inject dynamic route parameters into the Request attributes
        foreach ($params as $key => $value) {
            $request->setAttribute($key, $value);
        }

        // Resolve Handler interface class if passed as a string
        $handler = $this->resolveHandler($handler);

        // Combine global and route-specific middleware
        $allMiddleware = array_merge($this->globalMiddleware, $routeMiddleware);

        try {
            // Pass both Request and Response through the Pipeline (Onion pattern)
            $result = (new Pipeline())
                ->send([$request, $response])
                ->through($allMiddleware)
                ->then(function ($req, $res) use ($handler) {
                    return $handler($req, $res);
                });

            $this->handleResponse($result, $response);
        } catch (\Throwable $e) {
            $response->status(HttpStatus::INTERNAL_SERVER_ERROR)->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage()
            ])->send();
        }
    }

    /**
     * Resolve string handler classes that implement the Handler contract.
     */
    protected function resolveHandler(callable|string $handler): callable
    {
        if (is_string($handler) && class_exists($handler)) {
            $instance = new $handler();
            
            if ($instance instanceof Handler) {
                return [$instance, 'handle'];
            }

            throw new \InvalidArgumentException("Handler class [{$handler}] must implement " . Handler::class);
        }

        return $handler;
    }

    /**
     * Match the current request against registered routes.
     */
    protected function matchRoute(string $method, string $uri): array
    {
        if (!isset($this->routes[$method])) {
            return [null, [], []];
        }

        // 1. Exact match
        if (isset($this->routes[$method][$uri])) {
            return [
                $this->routes[$method][$uri]['handler'],
                $this->routes[$method][$uri]['middleware'],
                []
            ];
        }

        // 2. Dynamic pattern matching (e.g., /users/{id})
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

    /**
     * Handle the response returned by the pipeline/handler.
     */
    protected function handleResponse(mixed $result, Response $response): void
    {
        // If the handler explicitly returned a Response instance, send it
        if ($result instanceof Response) {
            $result->send();
            return;
        }

        // If the response body was mutated fluently but nothing was explicitly returned, send the injected response
        if ($result === null && !empty($response->getBody())) {
            $response->send();
            return;
        }

        // If an array or object was returned, treat it as JSON using the instance
        if (is_array($result) || is_object($result)) {
            $response->json($result)->send();
            return;
        }

        // Otherwise output string directly
        if ($result !== null) {
            echo (string) $result;
        }
    }
}
