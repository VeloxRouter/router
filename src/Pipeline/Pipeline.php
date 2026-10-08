<?php

declare(strict_types=1);

namespace VeloxRouter\Pipeline;

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
                $request = $passable;
                $response = null;

                if (is_array($passable) && count($passable) === 2) {
                    [$request, $response] = $passable;
                }

                // If the pipe is a string (class name), instantiate it
                if (is_string($pipe) && class_exists($pipe)) {
                    $pipe = new $pipe();
                }

                $destination = function ($req, $res = null) use ($next) {
                    $payload = ($res !== null) ? [$req, $res] : $req;
                    return $next($payload);
                };

                // Verifica se a classe tem o método handle (duck typing)
                if (is_object($pipe) && method_exists($pipe, 'handle')) {
                    if ($response !== null) {
                        return $pipe->handle($request, $response, $destination);
                    }
                    return $pipe->handle($passable, $destination);
                }

                // If it's just a generic Closure or callable
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