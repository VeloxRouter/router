<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use VeloxRouter\Benchmark\Benchmark;
use VeloxRouter\Router\Router;

echo "\033[36m\033[1m========================================\033[0m\n";
echo "\033[1m     VeloxRouter - Benchmark Suite      \033[0m\n";
echo "\033[36m\033[1m========================================\033[0m\n";

// 1. Configure a router with multiple routes to simulate a real-world environment
$router = new class extends Router {
    public function runMatch(string $m, string $u) {
        return $this->matchRoute($m, $u);
    }
};

$router->get('/home', fn() => 'home');
$router->get('/about', fn() => 'about');
$router->get('/contact', fn() => 'contact');
$router->get('/users', fn() => 'users.index');
$router->get('/users/{id}', fn() => 'users.show');
$router->get('/users/{id}/posts/{postId}', fn() => 'posts.show');
$router->post('/users', fn() => 'users.store');
$router->put('/users/{id}', fn() => 'users.update');
$router->delete('/users/{id}', fn() => 'users.destroy');

// Benchmark 1: Static route match (direct array key lookup)
Benchmark::measure('Static Route Match (/users)', function() use ($router) {
    $router->runMatch('GET', '/users');
}, 50000);

// Benchmark 2: Dynamic route match with parameters (regex evaluation)
Benchmark::measure('Dynamic Route Match (/users/{id}/posts/{postId})', function() use ($router) {
    $router->runMatch('GET', '/users/123/posts/456');
}, 20000);

// Benchmark 3: Not found route match (tests fallback/full cycle behavior)
Benchmark::measure('Not Found Route Match (404)', function() use ($router) {
    $router->runMatch('GET', '/not-found-route-path');
}, 20000);
