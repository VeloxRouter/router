<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use VeloxRouter\Router;
use VeloxRouter\Testing\Test;

/**
 * Testable subclass to expose protected routing match methods for low-level testing 
 * with automatic URI normalization matching the Router's internal behavior.
 */
class TestableRouter extends Router 
{
    public function testMatch(string $method, string $uri): array 
    {
        // Apply identical normalization as Router::dispatch()
        $uri = '/' . trim($uri, '/');
        if ($uri === '/') {
            $uri = '';
        }
        return $this->matchRoute(strtoupper($method), $uri);
    }
}

Test::describe('Advanced Parameters & Enterprise Features Tests', function() {
    $router = new TestableRouter();

    // Register standard dynamic routes with explicit parameter types and typed regex routes
    $router->get('/users/{userId:[0-9]+}/posts/{postId:[0-9]+}', fn() => 'dynamic');
    $router->get('/articles/{id:[0-9]+}', fn() => 'typed');
    $router->get('/dashboard', fn() => 'dashboard');

    // -------------------------------------------------------------------------
    // Scenario 1: Standard Dynamic Parameters Parsing
    // -------------------------------------------------------------------------
    $match1 = $router->testMatch('GET', '/users/99/posts/15');
    $handler1 = $match1[0] ?? null;
    $params1 = $match1[2] ?? [];

    Test::assert($handler1 !== null, "Scenario 1 failed: Route should match.");
    Test::assertEquals('99', $params1['userId'] ?? null, "Scenario 1 failed: userId mismatch.");
    Test::assertEquals('15', $params1['postId'] ?? null, "Scenario 1 failed: postId mismatch.");
    Test::ok("Scenario 1 passed: Dynamic parameters parsed correctly (userId: 99, postId: 15).");

    // -------------------------------------------------------------------------
    // Scenario 2: Strict Regex Parameter Type Validation ({id:[0-9]+})
    // -------------------------------------------------------------------------
    $validMatch = $router->testMatch('GET', '/articles/123');
    Test::assert($validMatch[0] !== null, "Scenario 2 failed: Numeric ID should match.");

    $invalidMatch = $router->testMatch('GET', '/articles/abc');
    Test::assert($invalidMatch[0] === null, "Scenario 2 failed: Non-numeric ID should not match typed route.");
    Test::ok("Scenario 2 passed: Strict parameter type validation successfully blocked invalid types.");

    // -------------------------------------------------------------------------
    // Scenario 3: Trailing Slash Normalization
    // -------------------------------------------------------------------------
    $slashMatch = $router->testMatch('GET', '/dashboard/');
    Test::assert($slashMatch[0] !== null, "Scenario 3 failed: Trailing slash should be normalized and matched.");
    Test::ok("Scenario 3 passed: Trailing slash normalization handled successfully.");
});