<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use VeloxRouter\Router\Router;

echo "\033[36m[TEST] Running Middleware Fluent Syntax Tests...\033[0m\n";

$router = new Router();

$dummyMiddleware = function($request, $response, $next) {
    return $next($request, $response);
};

// Testar encadeamento fluido (fluent interface)
$instance = $router->use($dummyMiddleware);

assert($instance instanceof Router, "Fluent use() method must return Router instance for chaining.");

echo "  \033[32m✔\033[0m Fluent method chaining \$router->use() works perfectly.\n\n";
