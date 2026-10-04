<?php

declare(strict_types=1);

namespace VeloxRouter\Router\Contracts;

use VeloxRouter\Router\Http\Request;
use VeloxRouter\Router\Http\Response;

interface MiddlewareInterface
{
    /**
     * Process an incoming request and handle or pass it to the next layer.
     */
    public function handle(Request $request, callable $next): Response|mixed;
}
