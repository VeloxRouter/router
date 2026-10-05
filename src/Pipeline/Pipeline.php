<?php

declare(strict_types=1);

namespace VeloxRouter\Router\Pipeline;

use VeloxRouter\Router\Contracts\MiddlewareInterface;
use VeloxRouter\Router\Http\Request;
use VeloxRouter\Router\Http\Response;

class Pipeline
{
    protected mixed $passable;
    protected array $pipes = [];

    /**
     * Set the object being sent through the pipeline.
     */
    public function send(mixed $passable): self
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * Set the array of middlewares/pipes.
     */
    public function through(array $pipes): self
    {
        $this->pipes = $pipes;
        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     */
    public function then(callable $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    /**
     * Prepare the final destination callback.
     */
    protected function prepareDestination(callable $destination): \Closure
    {
        return function (mixed $passable) use ($destination) {
            // If passable is the [Request, Response] pair
            if (is_array($passable) && count($passable) === 2) {
                [$request, $response] = $passable;
                return $destination($request, $response);
            }

            return $destination($passable);
        };
    }

    /**
     * Create a closure that maps the next onion layer.
     */
    protected function carry(): \Closure
    {
        return function (callable $next, mixed $pipe) {
            return function (mixed $passable) use ($next, $pipe) {
                // If passable is the [Request, Response] array
                $request = $passable;
                $response = null;

                if (is_array($passable) && count($passable) === 2) {
                    [$request, $response] = $passable;
                }

                // If the pipe is a string (class name), instantiate it
                if (is_string($pipe) && class_exists($pipe)) {
                    $pipe = new $pipe();
                }

                // Create the 'next' closure adapted for (Request, Response, next) or (passable, next) signature
                $destination = function ($req, $res = null) use ($next) {
                    $payload = ($res !== null) ? [$req, $res] : $req;
                    return $next($payload);
                };

                // If the pipe implements MiddlewareInterface or has a handle method
                if ($pipe instanceof MiddlewareInterface || method_exists($pipe, 'handle')) {
                    if ($response !== null) {
                        return $pipe->handle($request, $response, $destination);
                    }
                    return $pipe->handle($passable, $destination);
                }

                // If it's just a generic Closure
                if (is_callable($pipe)) {
                    if ($response !== null) {
                        return $pipe($request, $response, $destination);
                    }
                    return $pipe($passable, $destination);
                }

                throw new \InvalidArgumentException('Invalid middleware handler provided.');
            };
        };
    }
}
