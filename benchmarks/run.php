<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use VeloxRouter\Benchmark\Benchmark;
use VeloxRouter\Router\Router;

echo "\033[36m\033[1m========================================\033[0m\n";
echo "\033[1m     VeloxRouter - Benchmark Suite      \033[0m\n";
echo "\033[36m\033[1m========================================\033[0m\n";

// Example A: Test a generic "heavy" function (e.g., array data processing)
Benchmark::measure('Heavy Array Processing (10k items)', function() {
    $data = range(1, 1000);
    array_map(fn($n) => $n * 2, $data);
}, 5000);

// Example B: Test Router performance (Registration + Match)
$router = new class extends Router {
    public function runMatch(string $m, string $u) {
        return $this->matchRoute($m, $u);
    }
};

$router->get('/users/{id}/posts/{postId}', fn() => 'ok');

Benchmark::measure('VeloxRouter Dynamic Match', function() use ($router) {
    $router->runMatch('GET', '/users/123/posts/456');
}, 20000); // Runs 20,000 times for a realistic average
