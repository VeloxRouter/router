<?php

declare(strict_types=1);

namespace VeloxRouter;

use VeloxRouter\Contracts\Handler;
use VeloxRouter\Http\Request;
use VeloxRouter\Http\Response;
use VeloxRouter\Http\HttpStatus;
use VeloxRouter\Pipeline\Pipeline;

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
                ->then(function ($req, $res) use ($handler, $params) {
                    // Verifica se existem parâmetros na rota antes de os passar
                    if (!empty($params)) {
                        return $handler($req, $res, $params);
                    }
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