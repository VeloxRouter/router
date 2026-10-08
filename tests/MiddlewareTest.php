<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use VeloxRouter\Router;
use VeloxRouter\Testing\Test;

/**
 * Testable subclass to inspect protected global middleware properties.
 */
class TestableMiddlewareRouter extends Router 
{
    public function getGlobalMiddlewareCount(): int 
    {
        return count($this->globalMiddleware);
    }
}

Test::describe('Middleware Fluent Syntax & Registration Tests', function() {
    $router = new TestableMiddlewareRouter();

    $dummyMiddleware = function($request, $response, $next) {
        return $next($request, $response);
    };

    // Test fluent method chaining for both ->use() and ->addMiddleware()
    $instanceUse = $router->use($dummyMiddleware);
    Test::assert($instanceUse instanceof Router, "Fluent use() method must return Router instance for chaining.");

    $instanceAdd = $router->addMiddleware($dummyMiddleware);
    Test::assert($instanceAdd instanceof Router, "Fluent addMiddleware() method must return Router instance for chaining.");

    // Verify that middleware instances were correctly registered internally
    Test::assertEquals(2, $router->getGlobalMiddlewareCount(), "Expected 2 global middlewares to be registered.");

    Test::ok("Fluent method chaining and global middleware registration work perfectly.");
});