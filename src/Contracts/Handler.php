<?php

declare(strict_types=1);

namespace VeloxRouter\Router\Contracts;

use VeloxRouter\Router\Http\Request;

interface Handler
{
    public function handle(Request $request): mixed;
}
