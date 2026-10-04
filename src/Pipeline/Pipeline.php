<?php

declare(strict_types=1);

namespace VeloxRouter\Router\Pipeline;

use VeloxRouter\Router\Contracts\MiddlewareInterface;
use VeloxRouter\Router\Http\Request;

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
                // Se o pipe for uma string (nome da classe), podemos instanciá-lo se necessário,
                // ou assumir que é um objeto/callable que implementa a interface.
                if (is_string($pipe) && class_exists($pipe)) {
                    $pipe = new $pipe();
                }

                // Se o pipe implementar a MiddlewareInterface ou tiver o método handle
                if ($pipe instanceof MiddlewareInterface || method_exists($pipe, 'handle')) {
                    return $pipe->handle($passable, $next);
                }

                // Se for apenas uma Closure genérica
                if (is_callable($pipe)) {
                    return $pipe($passable, $next);
                }

                throw new \InvalidArgumentException('Invalid middleware handler provided.');
            };
        };
    }
}
