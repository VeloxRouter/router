<?php

declare(strict_types=1);

namespace VeloxRouter\Router\Contracts;

interface MiddlewareInterface
{
    public function handle(object $request, \Closure $next): mixed;
}
