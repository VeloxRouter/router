<?php

declare(strict_types=1);

namespace VeloxRouter\Exceptions;

use Throwable;
use VeloxRouter\Router\Http\HttpStatus;

class BadRequestHttpException extends HttpException
{
    public function __construct(string $message = 'Bad Request', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::BAD_REQUEST, HttpStatus::getMessage(HttpStatus::BAD_REQUEST), $message, $previous);
    }
}

class UnauthorizedHttpException extends HttpException
{
    public function __construct(string $message = 'Unauthorized', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::UNAUTHORIZED, HttpStatus::getMessage(HttpStatus::UNAUTHORIZED), $message, $previous);
    }
}

class ForbiddenHttpException extends HttpException
{
    public function __construct(string $message = 'Forbidden', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::FORBIDDEN, HttpStatus::getMessage(HttpStatus::FORBIDDEN), $message, $previous);
    }
}

class NotFoundHttpException extends HttpException
{
    public function __construct(string $message = 'Resource not found', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::NOT_FOUND, HttpStatus::getMessage(HttpStatus::NOT_FOUND), $message, $previous);
    }
}

class MethodNotAllowedHttpException extends HttpException
{
    public function __construct(string $message = 'Method Not Allowed', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::METHOD_NOT_ALLOWED, HttpStatus::getMessage(HttpStatus::METHOD_NOT_ALLOWED), $message, $previous);
    }
}

class NotAcceptableHttpException extends HttpException
{
    public function __construct(string $message = 'Not Acceptable', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::NOT_ACCEPTABLE, HttpStatus::getMessage(HttpStatus::NOT_ACCEPTABLE), $message, $previous);
    }
}

class ConflictHttpException extends HttpException
{
    public function __construct(string $message = 'Conflict', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::CONFLICT, HttpStatus::getMessage(HttpStatus::CONFLICT), $message, $previous);
    }
}

class GoneHttpException extends HttpException
{
    public function __construct(string $message = 'Gone', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::GONE, HttpStatus::getMessage(HttpStatus::GONE), $message, $previous);
    }
}

class PayloadTooLargeHttpException extends HttpException
{
    public function __construct(string $message = 'Payload Too Large', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::PAYLOAD_TOO_LARGE, HttpStatus::getMessage(HttpStatus::PAYLOAD_TOO_LARGE), $message, $previous);
    }
}

class UnsupportedMediaTypeHttpException extends HttpException
{
    public function __construct(string $message = 'Unsupported Media Type', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::UNSUPPORTED_MEDIA_TYPE, HttpStatus::getMessage(HttpStatus::UNSUPPORTED_MEDIA_TYPE), $message, $previous);
    }
}

class UnprocessableEntityHttpException extends HttpException
{
    public function __construct(string $message = 'Unprocessable Entity', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::UNPROCESSABLE_ENTITY, HttpStatus::getMessage(HttpStatus::UNPROCESSABLE_ENTITY), $message, $previous);
    }
}

class TooManyRequestsHttpException extends HttpException
{
    public function __construct(string $message = 'Too Many Requests', ?Throwable $previous = null)
    {
        parent::__construct(HttpStatus::TOO_MANY_REQUESTS, HttpStatus::getMessage(HttpStatus::TOO_MANY_REQUESTS), $message, $previous);
    }
}
