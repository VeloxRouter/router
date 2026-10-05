<?php

declare(strict_types=1);

namespace VeloxRouter\Http;

class Request
{
    /**
     * @param string $method HTTP Method (GET, POST, etc.)
     * @param string $uri Request URI path
     * @param array<string, mixed> $queryParams Query string parameters ($_GET)
     * @param array<string, mixed> $parsedBody Parsed request body (JSON or $_POST)
     * @param array<string, string> $headers Request headers
     * @param array<string, mixed> $server Server parameters ($_SERVER)
     * @param array<string, mixed> $attributes Dynamic route parameters or middleware data
     */
    public function __construct(
        protected string $method,
        protected string $uri,
        protected array $queryParams = [],
        protected array $parsedBody = [],
        protected array $headers = [],
        protected array $server = [],
        protected array $attributes = []
    ) {}

    /**
     * Factory method to capture the current HTTP request from global state.
     */
    public static function capture(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
        
        $parsedBody = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            $parsedBody = json_decode($input, true) ?? [];
        } else {
            $parsedBody = $_POST;
        }

        $headers = function_exists('getallheaders') ? getallheaders() : self::extractHeadersFromServer($_SERVER);

        return new self(
            method: strtoupper($method),
            uri: $uri,
            queryParams: $_GET,
            parsedBody: $parsedBody,
            headers: $headers,
            server: $_SERVER
        );
    }

    /**
     * Fallback method to extract headers if getallheaders() is unavailable.
     */
    private static function extractHeadersFromServer(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }

    // --- Fiber-Inspired API Methods ---

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Get query parameters.
     * If $key is null, returns all query parameters.
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->queryParams;
        }
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Get parsed request body (JSON or form data).
     * If $key is null, returns the entire body array.
     */
    public function body(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->parsedBody;
        }
        return $this->parsedBody[$key] ?? $default;
    }

    /**
     * Get raw form data ($_POST explicitly).
     * If $key is null, returns all POST data.
     */
    public function form(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Get route parameters (e.g., /user/{id} -> param('id')).
     * If $key is null, returns all attributes/params.
     */
    public function param(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->attributes;
        }
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Get a specific header value.
     */
    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Get all headers.
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Get server parameters.
     */
    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Set a custom attribute (used by the router to inject path parameters).
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }
}
