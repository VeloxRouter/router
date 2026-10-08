<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use VeloxRouter\Router;
use VeloxRouter\Testing\Test;

/**
 * Testable subclass to expose protected routing match methods for low-level testing.
 */
class TestableHttpRouter extends Router 
{
    public function testMatch(string $method, string $uri): array 
    {
        return $this->matchRoute(strtoupper($method), $uri);
    }
}

Test::describe('Router HTTP Methods Tests', function() {
    $router = new TestableHttpRouter();

    // Register distinct handlers for each HTTP verb on the same URI path
    $router->get('/api/test', fn() => 'GET_HANDLER');
    $router->post('/api/test', fn() => 'POST_HANDLER');
    $router->put('/api/test', fn() => 'PUT_HANDLER');
    $router->patch('/api/test', fn() => 'PATCH_HANDLER');
    $router->delete('/api/test', fn() => 'DELETE_HANDLER');
    $router->options('/api/test', fn() => 'OPTIONS_HANDLER');
    $router->head('/api/test', fn() => 'HEAD_HANDLER');

    $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];

    // Verify that each method correctly resolves its specific handler
    foreach ($methods as $method) {
        [$handler, $middleware, $params] = $router->testMatch($method, '/api/test');
        
        Test::assert($handler !== null, "Handler for HTTP verb [{$method}] should not be null.");
        
        // Execute the handler to confirm it returns the expected identifier string
        $result = $handler();
        $expected = "{$method}_HANDLER";
        
        Test::assertEquals($expected, $result, "Handler response mismatch for verb [{$method}].");
    }

    Test::ok("All HTTP verbs registered and matched successfully with their respective handlers.");
});