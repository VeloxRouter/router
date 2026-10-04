<?php

declare(strict_types=1);

namespace VeloxRouter\Router\Http;

class Response
{
    /**
     * @param mixed $body The response body (string, array, object)
     * @param int $status The HTTP status code
     * @param array<string, string> $headers Additional HTTP headers
     */
    public function __construct(
        protected mixed $body = '',
        protected int $status = HttpStatus::OK,
        protected array $headers = []
    ) {}

    /**
     * Factory method for JSON responses.
     */
    public static function json(mixed $data, int $status = HttpStatus::OK, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=UTF-8';
        return new self(json_encode($data), $status, $headers);
    }

    /**
     * Factory method for plain text or HTML responses.
     */
    public static function make(mixed $body, int $status = HttpStatus::OK, array $headers = []): self
    {
        return new self($body, $status, $headers);
    }

    /**
     * Adds or overrides a header in an immutable way.
     */
    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    /**
     * Sends the response to the client (status code, headers, and body).
     */
    public function send(): void
    {
        HttpStatus::send($this->status);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->body;
    }

    /**
     * Returns the response body.
     */
    public function getBody(): mixed
    {
        return $this->body;
    }

    /**
     * Returns the HTTP status code.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Returns the headers array.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
