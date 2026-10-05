<?php

declare(strict_types=1);

namespace VeloxRouter\Router;

use VeloxRouter\Router\Contracts\Handler;
use VeloxRouter\Router\Http\Request;
use VeloxRouter\Router\Http\Response;
use VeloxRouter\Router\Http\HttpStatus;
use VeloxRouter\Router\Pipeline\Pipeline;

class Dispatcher
{
    public function dispatch(Request $request, Response $response, array $routeData, array $globalMiddleware): void
    {
        [$handler, $routeMiddleware, $params] = $routeData;

        if (!$handler) {
            $response->status(HttpStatus::NOT_FOUND)->json(['error' => 'Route not found'])->send();
            return;
        }

        foreach ($params as $key => $value) {
            $request->setAttribute($key, $value);
        }

        $handler = $this->resolveHandler($handler);
        $allMiddleware = array_merge($globalMiddleware, $routeMiddleware);

        try {
            $result = (new Pipeline())
                ->send([$request, $response])
                ->through($allMiddleware)
                ->then(function ($req, $res) use ($handler) {
                    return $handler($req, $res);
                });

            $this->handleResponse($result, $response);
        } catch (\Throwable $e) {
            // Se o ErrorMiddleware estiver ativo, ele apanha isto; caso contrário, fallback de segurança:
            $response->status(HttpStatus::INTERNAL_SERVER_ERROR)->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage()
            ])->send();
        }
    }

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

    protected function handleResponse(mixed $result, Response $response): void
    {
        if ($result instanceof Response) {
            $result->send();
            return;
        }

        if ($result === null && !empty($response->getBody())) {
            $response->send();
            return;
        }

        if (is_array($result) || is_object($result)) {
            $response->json($result)->send();
            return;
        }

        if ($result !== null) {
            echo (string) $result;
        }
    }
}
