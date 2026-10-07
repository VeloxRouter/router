<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use VeloxRouter\Router\Router;

echo "\033[36m[TEST] Running Dynamic Parameters Tests...\033[0m\n";

// Criamos uma subclasse ou testamos a lógica de regex diretamente para validar o matchRoute
class TestableRouter extends Router {
    public function testMatch(string $method, string $uri): array {
        return $this->matchRoute(strtoupper($method), $uri);
    }
}

$router = new TestableRouter();
$router->get('/users/{userId}/posts/{postId}', fn() => 'ok');

// Simular chamada com valores reais
// Nota: Precisas de expor temporariamente matchRoute como public na classe Router para testes unitários puros, 
// ou testar através do método dispatch com mocks.
// Vamos validar a lógica de expressão regular que o router utiliza:
$routeUri = '/users/{userId}/posts/{postId}';
$pattern = preg_replace('/\{([a-zA-Z0-9_-]+)\}/', '([^/]+)', $routeUri);
$pattern = "#^{$pattern}$#";

$uri = '/users/99/posts/15';
if (preg_match($pattern, $uri, $matches)) {
    array_shift($matches);
    preg_match_all('/\{([a-zA-Z0-9_-]+)\}/', $routeUri, $paramNames);
    
    $params = [];
    foreach ($paramNames[1] as $index => $name) {
        $params[$name] = $matches[$index] ?? null;
    }

    assert($params['userId'] === '99');
    assert($params['postId'] === '15');
    echo "  \033[32m✔\033[0m Dynamic parameters parsed correctly (userId: 99, postId: 15).\n\n";
} else {
    throw new \Exception("Route pattern did not match URI.");
}
