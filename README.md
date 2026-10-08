# VeloxRouter - Router Package

> A lightning-fast, lightweight HTTP routing engine built for modern PHP 8.2+.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.2-indigo.svg)](https://php.net)

## 🚀 About

`veloxrouter/router` is the core routing engine of the VeloxRouter ecosystem. Inspired by high-performance frameworks like Go Fiber and Python FastAPI, it is designed for maximum speed, clean architecture, and zero unnecessary bloat.

## 📦 Installation

Install the package via Composer:

```bash
composer require veloxrouter/router

```

---

## 🌐 Server Configuration (Apache / `.htaccess`)

To ensure that all incoming HTTP requests are properly handled by your application, point your web server document root to the `public/` directory and use the following configuration setup:

### 1. `public/index.php` (Front Controller)

Create your main entry point inside the `public/` folder:

```php
<?php

declare(strict_types=1);

// Load the Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

use VeloxRouter\Router;

$router = new Router();

// Define your routes here...
$router->get('/', function ($request, $response) {
    return $response->json(['message' => 'Welcome to VeloxRouter!']);
});

// Dispatch the application
$router->run();

```

### 2. `public/.htaccess` (URL Rewriting for Apache)

Place this `.htaccess` file inside your `public/` directory to enable clean URLs:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Handle Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

```

*(Note: For local testing and development, you can also let VeloxRouter automatically spin up PHP's built-in development server by calling `$router->run()` via CLI).*

---

## ⚙️ Usage Example

Here is a practical example demonstrating how to set up routes handling different HTTP verbs (GET, POST, PUT, DELETE), dynamic path parameters, and strict regex validation:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use VeloxRouter\Router;

$router = new Router();

// 1. Static GET route
$router->get('/users', function ($request, $response) {
    $users = [
        ['id' => 1, 'name' => 'Ana Silva'],
        ['id' => 2, 'name' => 'Carlos Santos']
    ];

    return $response->json($users);
});

// 2. Dynamic route with path parameters and strict regex validation
$router->get('/users/{id:[0-9]+}', function ($request, $response, array $params) {
    $userId = $params['id'] ?? null;
    $includeProfile = $request->query('include', false);

    return $response->json([
        'success' => true,
        'user_id' => $userId,
        'include_profile' => $includeProfile,
        'message' => "Details for user {$userId}"
    ]);
})->name('users.show');

// 3. POST route (Creation)
$router->post('/users', function ($request, $response) {
    $data = $request->body();

    return $response->status(201)->json([
        'message' => 'User created successfully!',
        'data' => $data
    ]);
});

// 4. PUT route (Full update)
$router->put('/users/{id:[0-9]+}', function ($request, $response, array $params) {
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

// 5. DELETE route (Removal)
$router->delete('/users/{id:[0-9]+}', function ($request, $response, array $params) {
    $userId = $params['id'] ?? null;

    return $response->status(200)->json([
        'success' => true,
        'action' => 'DELETE',
        'user_id' => $userId,
        'message' => "User {$userId} deleted successfully!"
    ]);
});

// Start the server or dispatch the request
$router->run();

```

---

## 📊 Benchmark Performance

`VeloxRouter` uses a dual-storage strategy (an $O(1)$ hash dictionary for static routes and an optimized regex engine for dynamic routes), ensuring maximum throughput and zero extra memory allocation at peak loads.

Results obtained from a simulated enterprise environment containing hundreds of registered routes:

| Test Scenario | Iterations | Average per Exec | Throughput (Ops/sec) | Memory (Peak) |
| --- | --- | --- | --- | --- |
| **Static Route Match** | 50,000 | 1.43 µs | **~698,529 ops/s** | 0.00 KB |
| **Typed Parameter Match (`/users/42`)** | 20,000 | 14.65 µs | **~68,251 ops/s** | 0.00 KB |
| **Complex Dynamic Route Match** | 20,000 | 13.95 µs | **~71,707 ops/s** | 0.00 KB |
| **Named Route URL Generation** | 50,000 | 2.82 µs | **~354,977 ops/s** | 0.00 KB |
| **Not Found Match (404 / Large Table)** | 20,000 | 13.16 µs | **~75,984 ops/s** | 0.00 KB |

## 📄 License

The VeloxRouter package is open-sourced software licensed under the [MIT license](https://www.google.com/search?q=LICENSE).
