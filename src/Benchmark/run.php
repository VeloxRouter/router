<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use VeloxRouter\Router;
use VeloxRouter\Benchmark\Benchmark;

echo "\033[36m\033[1m==================================================\033[0m\n";
echo "\033[1m     VeloxRouter - Enterprise Benchmark Suite    \033[0m\n";
echo "\033[36m\033[1m==================================================\033[0m\n";

$router = new class extends Router {
    public function runMatch(string $method, string $uri) {
        return $this->matchRoute($method, $uri);
    }
    
    public function testRouteGeneration(string $name, array $params) {
        return $this->route($name, $params);
    }
};

// 1. Simular uma aplicação real de grande escala populando várias rotas
$router->get('/home', fn() => 'home');
$router->get('/about', fn() => 'about');
$router->get('/contact', fn() => 'contact');

// Gerar rotas em massa para simular uma aplicação enterprise com centenas de rotas
for ($i = 1; $i <= 50; $i++) {
    $router->get("/category/{$i}/products", fn() => "category.products");
    $router->get("/category/{$i}/products/{productId}", fn() => "category.product.show");
}

$router->get('/users', fn() => 'users.index')->name('users.index');
$router->get('/users/{id:[0-9]+}', fn() => 'users.show')->name('users.show'); // Rota com tipo estrito
$router->get('/users/{id:[0-9]+}/posts/{postId:[0-9]+}', fn() => 'posts.show')->name('posts.show');
$router->post('/users', fn() => 'users.store');
$router->put('/users/{id:[0-9]+}', fn() => 'users.update');
$router->delete('/users/{id:[0-9]+}', fn() => 'users.destroy');

// 2. Testes de Benchmark Avançados e Corporativos
Benchmark::measure('Static Route Match (Large Route Table)', function() use ($router) {
    $router->runMatch('GET', '/users');
}, 50000);

Benchmark::measure('Typed Parameter Route Match (/users/42)', function() use ($router) {
    $router->runMatch('GET', '/users/42');
}, 20000);

Benchmark::measure('Deep Dynamic Route Match (/category/25/products/999)', function() use ($router) {
    $router->runMatch('GET', '/category/25/products/999');
}, 20000);

Benchmark::measure('Complex Typed Dynamic Route Match (/users/123/posts/456)', function() use ($router) {
    $router->runMatch('GET', '/users/123/posts/456');
}, 20000);

Benchmark::measure('Trailing Slash Normalized Match (/users/)', function() use ($router) {
    $router->runMatch('GET', '/users/');
}, 50000);

Benchmark::measure('Named Route URL Generation (Reverse Routing)', function() use ($router) {
    $router->testRouteGeneration('posts.show', ['id' => 777, 'postId' => 888]);
}, 50000);

Benchmark::measure('Not Found Route Match (404 / Large Table)', function() use ($router) {
    $router->runMatch('GET', '/not-found-route-path');
}, 20000);