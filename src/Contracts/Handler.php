<?php

declare(strict_types=1);

namespace VeloxRouter\Router\Contracts;

use VeloxRouter\Router\Http\Request;
use VeloxRouter\Router\Http\Response;

interface HandlerInterface
{
    /**
     * Handle the incoming request and return a response or mixed payload.
     */
    public function handle(Request $request, Response $response): mixed;
}
