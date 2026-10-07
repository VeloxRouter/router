<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use VeloxRouter\Router\Router;

// Expõe temporariamente o match para teste de benchmark (ou usa via reflexão/subclasse)
class BenchmarkRouter extends Router {
    public function benchmarkMatch(string $method, string $uri): array {
        return $this->matchRoute($method, $uri);
    }
}

echo "\033[36m\033[1m========================================\033[0m\n";
echo "\033[1m     VeloxRouter v1.0.0 - Benchmark     \033[0m\n";
echo "\033[1m========================================\033[0m\n\n";

$router = new BenchmarkRouter();

// 1. Registar um conjunto variado de rotas (estáticas e dinâmicas)
$startTime = microtime(true);
$startMemory = memory_get_usage();

$router->get('/', fn() => 'home');
$router->get('/users', fn() => 'users.index');
$router->get('/users/{id}', fn() => 'users.show');
$router->get('/users/{id}/posts/{postId}', fn() => 'posts.show');
$router->post('/users', fn() => 'users.store');
$router->put('/users/{id}', fn() => 'users.update');
$router->delete('/users/{id}', fn() => 'users.destroy');

$registrationTime = (microtime(true) - $startTime) * 1000;

echo "  \033[32m✔\033[0m Registo de rotas: " . number_format($registrationTime, 4) . " ms\n";

// 2. Executar 10.000 iterações de correspondência de rotas (Matching)
$iterations = 10000;
$matchStartTime = microtime(true);

for ($i = 0; $i < $iterations; $i++) {
    $router->benchmarkMatch('GET', '/');
    $router->benchmarkMatch('GET', '/users/42');
    $router->benchmarkMatch('GET', '/users/99/posts/15');
    $router->benchmarkMatch('POST', '/users');
}

$totalMatches = $iterations * 4;
$matchDuration = (microtime(true) - $matchStartTime) * 1000;
$memoryPeak = memory_get_peak_usage() - $startMemory;

echo "  \033[32m✔\033[0m Total de Route Matches: " . number_format($totalMatches) . " operações\n";
echo "  \033[32m✔\033[0m Tempo Total de Match:   " . number_format($matchDuration, 2) . " ms\n";
echo "  \033[32m✔\033[0m Média por Match:        " . number_format(($matchDuration / $totalMatches) * 1000, 4) . " µs (microssegundos)\n";
echo "  \033[32m✔\033[0m Memória Consumida (Peak): " . number_format($memoryPeak / 1024, 2) . " KB\n\n";

echo "\033[32m\033[1mBenchmark concluído com sucesso! Desempenho de alta velocidade.\033[0m\n";
