<?php

declare(strict_types=1);

namespace VeloxRouter\Http;

class HttpStatus
{
    // Main Success Codes
    public const OK = 200;
    public const CREATED = 201;
    public const ACCEPTED = 202;
    public const NO_CONTENT = 204;

    // Redirection Codes
    public const MOVED_PERMANENTLY = 301;
    public const FOUND = 302;
    public const SEE_OTHER = 303;
    public const NOT_MODIFIED = 304;
    public const TEMPORARY_REDIRECT = 307;
    public const PERMANENT_REDIRECT = 308;

    // Main Client Error Codes
    public const BAD_REQUEST = 400;
    public const UNAUTHORIZED = 401;
    public const FORBIDDEN = 403;
    public const NOT_FOUND = 404;
    public const METHOD_NOT_ALLOWED = 405;
    public const NOT_ACCEPTABLE = 406;
    public const CONFLICT = 409;
    public const GONE = 410;
    public const PAYLOAD_TOO_LARGE = 413;
    public const UNSUPPORTED_MEDIA_TYPE = 415;
    public const UNPROCESSABLE_ENTITY = 422;
    public const TOO_MANY_REQUESTS = 429;

    // Main Server Error Codes
    public const INTERNAL_SERVER_ERROR = 500;
    public const NOT_IMPLEMENTED = 501;
    public const BAD_GATEWAY = 502;
    public const SERVICE_UNAVAILABLE = 503;
    public const GATEWAY_TIMEOUT = 504;

    /**
     * Helper 1: Returns the default descriptive message for a status code.
     */
    public static function getMessage(int $code): string
    {
        return match ($code) {
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            204 => 'No Content',
            
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',
            
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            409 => 'Conflict',
            410 => 'Gone',
            413 => 'Payload Too Large',
            415 => 'Unsupported Media Type',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            
            default => 'Unknown Status',
        };
    }

    /**
     * Helper 2: Checks if the status code indicates success (200-299).
     */
    public static function isSuccess(int $code): bool
    {
        return $code >= 200 && $code < 300;
    }

    /**
     * Helper 3: Checks if the status code indicates redirection (300-399).
     */
    public static function isRedirection(int $code): bool
    {
        return $code >= 300 && $code < 400;
    }

    /**
     * Helper 4: Checks if the status code indicates an error (400 or higher).
     */
    public static function isError(int $code): bool
    {
        return $code >= 400;
    }

    /**
     * Helper 5: Forces the immediate sending of the HTTP status code to the header.
     */
    public static function send(int $code): int
    {
        http_response_code($code);
        return $code;
    }
}
