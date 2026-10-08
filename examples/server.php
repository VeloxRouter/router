<?php

declare(strict_types=1);

// Load the Composer autoloader to automatically handle all namespaces and classes
require_once __DIR__ . '/../vendor/autoload.php';

use VeloxRouter\Router;

$router = new Router();

// Simple GET route
$router->get('/users', function ($request, $response) {
    $users = [
        ['id' => 1, 'name' => 'Ana Silva'],
        ['id' => 2, 'name' => 'Carlos Santos']
    ];

    return $response->json($users);
});

// 1. Dedicated endpoint to retrieve PATH parameters (e.g., /products/{category}/{id})
$router->get('/products/{category}/{id}', function ($request, $response, array $params) {
    $category = $params['category'] ?? null;
    $id = $params['id'] ?? null;

    return $response->json([
        'source' => 'path_parameters',
        'category' => $category,
        'product_id' => $id,
        'message' => "Product {$id} found in category {$category}"
    ]);
});

// 2. Dedicated endpoint to retrieve QUERY STRING parameters (e.g., /search?q=velox&sort=desc)
$router->get('/search', function ($request, $response) {
    // Get individual parameters with default values
    $keyword = $request->query('q', 'default_query');
    $sort = $request->query('sort', 'asc');
    
    // Or get all query string parameters at once
    $allQueryParameters = $request->query();

    return $response->json([
        'source' => 'query_string',
        'keyword' => $keyword,
        'sort' => $sort,
        'all_params' => $allQueryParameters
    ]);
});

// GET route with parameters and query string combined
$router->get('/users/{id}', function ($request, $response, array $params) {
    $userId = $params['id'] ?? null;
    $includeProfile = $request->query('include', false);

    return $response->json([
        'success' => true,
        'user_id' => $userId,
        'include_profile' => $includeProfile,
        'message' => "Details for user {$userId}"
    ]);
});

// POST route (Creation)
$router->post('/users', function ($request, $response) {
    $data = $request->body();

    return $response->status(201)->json([
        'message' => 'User created successfully!',
        'data' => $data
    ]);
});

// PUT route (Full update)
$router->put('/users/{id}', function ($request, $response, array $params) {
    $userId = $params['id'] ?? null;
    $data = $request->body();

    return $response->json([
        'success' => true,
        'action' => 'PUT',
        'user_id' => $userId,
        'message' => "User {$userId} fully updated successfully!",
        'data' => $data
    ]);
});

// PATCH route (Partial update)
$router->patch('/users/{id}', function ($request, $response, array $params) {
    $userId = $params['id'] ?? null;
    $data = $request->body();

    return $response->json([
        'success' => true,
        'action' => 'PATCH',
        'user_id' => $userId,
        'message' => "User {$userId} partially updated successfully!",
        'data' => $data
    ]);
});

// DELETE route (Removal)
$router->delete('/users/{id}', function ($request, $response, array $params) {
    $userId = $params['id'] ?? null;

    return $response->status(200)->json([
        'success' => true,
        'action' => 'DELETE',
        'user_id' => $userId,
        'message' => "User {$userId} deleted successfully!"
    ]);
});

$router->run();