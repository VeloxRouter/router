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
     * Factory method for XML responses.
     */
    public static function xml(string|array $data, int $status = HttpStatus::OK, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/xml; charset=UTF-8';

        // Se passares um array, podes convertê-lo ou aceitar uma string XML pronta
        $xmlBody = is_array($data) ? self::arrayToXml($data, new \SimpleXMLElement('<response/>'))->asXML() : $data;

        return new self($xmlBody, $status, $headers);
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
     * Helper to recursively convert arrays to SimpleXMLElement.
     */
    private static function arrayToXml(array $data, \SimpleXMLElement $xml): \SimpleXMLElement
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild(is_numeric($key) ? 'item' : $key);
                self::arrayToXml($value, $subnode);
            } else {
                $xml->addChild(is_numeric($key) ? 'item' : $key, htmlspecialchars((string) $value));
            }
        }
        return $xml;
    }

    // Getters...
    public function getBody(): mixed { return $this->body; }
    public function getStatus(): int { return $this->status; }
    public function getHeaders(): array { return $this->headers; }
}
