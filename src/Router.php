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
    public function dispatch(?Request $request = null): void
    {
        $request = $request ?? Request::capture();
        $method = $request->getMethod();
        $uri = $request->getUri();

        $uri = '/' . trim($uri, '/');
        if ($uri === '/') {
            $uri = '';
        }

        [$handler, $routeMiddleware, $params] = $this->matchRoute($method, $uri);

        if (!$handler) {
            Response::json(['error' => 'Route not found'], HttpStatus::NOT_FOUND)->send();
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
            $response = (new Pipeline())
                ->send($request)
                ->through($allMiddleware)
                ->then($handler);

            $this->handleResponse($response);
        } catch (\Throwable $e) {
            Response::json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage()
            ], HttpStatus::INTERNAL_SERVER_ERROR)->send();
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
    protected function handleResponse(mixed $response): void
    {
        if ($response instanceof Response) {
            $response->send();
            return;
        }

        if (is_array($response) || is_object($response)) {
            Response::json($response)->send();
            return;
        }

        echo (string) $response;
    }
}
