<?php

declare(strict_types=1);

namespace VeloxRouter\Exceptions;

use Throwable;
use VeloxRouter\Router\Http\HttpStatus;

class InternalServerErrorHttpException extends HttpException
{
    public function __construct(string $message = 'Internal Server Error', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::INTERNAL_SERVER_ERROR, HttpStatus::getMessage(HttpStatus::INTERNAL_SERVER_ERROR), $message, $previous);
    }
}

class NotImplementedHttpException extends HttpException
{
    public function __construct(string $message = 'Not Implemented', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::NOT_IMPLEMENTED, HttpStatus::getMessage(HttpStatus::NOT_IMPLEMENTED), $message, $previous);
    }
}

class BadGatewayHttpException extends HttpException
{
    public function __construct(string $message = 'Bad Gateway', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::BAD_GATEWAY, HttpStatus::getMessage(HttpStatus::BAD_GATEWAY), $message, $previous);
    }
}

class ServiceUnavailableHttpException extends HttpException
{
    public function __construct(string $message = 'Service Unavailable', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::SERVICE_UNAVAILABLE, HttpStatus::getMessage(HttpStatus::SERVICE_UNAVAILABLE), $message, $previous);
    }
}

class GatewayTimeoutHttpException extends HttpException
{
    public function __construct(string $message = 'Gateway Timeout', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::GATEWAY_TIMEOUT, HttpStatus::getMessage(HttpStatus::GATEWAY_TIMEOUT), $message, $previous);
    }
}
