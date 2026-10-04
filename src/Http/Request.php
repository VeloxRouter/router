<?php

declare(strict_types=1);

namespace VeloxRouter\Router\Http;

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
        
        // Parse JSON body if applicable, fallback to $_POST
        $parsedBody = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            $parsedBody = json_decode($input, true) ?? [];
        } else {
            $parsedBody = $_POST;
        }

        // Fetch headers safely
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
     * Fallback method to extract headers if getallheaders() is unavailable (e.g. CLI or specific SAPIs).
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

    // --- Getters & Attribute Management ---

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function getParsedBody(): array
    {
        return $this->parsedBody;
    }

    public function getBodyParam(string $key, mixed $default = null): mixed
    {
        return $this->parsedBody[$key] ?? $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }
        return null;
    }

    public function getServer(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Get all route or middleware attributes.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get a specific attribute.
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Set a custom attribute (useful for routers injecting path parameters or middleware data).
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }
}
