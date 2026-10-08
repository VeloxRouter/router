<?php

declare(strict_types=1);

namespace VeloxRouter\Http;

class Response
{
    /**
     * @param mixed $body The response body (string, array, object, or callback for streams)
     * @param int $status The HTTP status code
     * @param array<string, string> $headers Additional HTTP headers
     */
    public function __construct(
        protected mixed $body = '',
        protected int $status = 200,
        protected array $headers = []
    ) {}

    /**
     * Set the HTTP status code fluently.
     */
    public function status(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Set or override a header fluently.
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set a JSON response body.
     */
    public function json(mixed $data, ?int $status = null): self
    {
        if ($status !== null) {
            $this->status = $status;
        }

        $this->headers['Content-Type'] = 'application/json; charset=UTF-8';
        $this->body = json_encode($data);
        
        return $this;
    }

    /**
     * Set a plain text response body.
     */
    public function text(string $body, ?int $status = null): self
    {
        if ($status !== null) {
            $this->status = $status;
        }

        $this->headers['Content-Type'] = 'text/plain; charset=UTF-8';
        $this->body = $body;

        return $this;
    }

    /**
     * Set an HTML response body.
     */
    public function html(string $html, ?int $status = null): self
    {
        if ($status !== null) {
            $this->status = $status;
        }

        $this->headers['Content-Type'] = 'text/html; charset=UTF-8';
        $this->body = $html;

        return $this;
    }

    /**
     * Set an XML response body.
     */
    public function xml(string|array $data, ?int $status = null): self
    {
        if ($status !== null) {
            $this->status = $status;
        }

        $this->headers['Content-Type'] = 'application/xml; charset=UTF-8';
        $this->body = is_array($data) 
            ? self::arrayToXml($data, new \SimpleXMLElement('<response/>'))->asXML() 
            : $data;

        return $this;
    }

    /**
     * Send a file as a download or inline response (Fiber-inspired SendFile).
     */
    public function sendFile(string $path, ?string $filename = null): self
    {
        if (!file_exists($path)) {
            $this->status = 404;
            $this->body = 'File not found';
            return $this;
        }

        $filename = $filename ?? basename($path);
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';

        $this->headers['Content-Type'] = $mimeType;
        $this->headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
        $this->headers['Content-Length'] = (string) filesize($path);

        $this->body = file_get_contents($path);

        return $this;
    }

    /**
     * Stream data using a callback function (Fiber-inspired SendStream).
     */
    public function sendStream(callable $callback, int $status = 200, array $headers = []): self
    {
        $this->status = $status;
        foreach ($headers as $key => $value) {
            $this->headers[$key] = $value;
        }
        
        // Armazena o callback para ser executado no momento do send()
        $this->body = $callback;

        return $this;
    }

    /**
     * Sends the response to the client (status code, headers, and body/stream).
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        if (is_callable($this->body)) {
            // Executa o stream se o corpo for um callable
            ($this->body)();
        } else {
            echo $this->body;
        }
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

    // Getters básicos
    public function getBody(): mixed { return $this->body; }
    public function getStatus(): int { return $this->status; }
    public function getHeaders(): array { return $this->headers; }
}
