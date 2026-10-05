<?php

declare(strict_types=1);

namespace VeloxRouter\Exceptions;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    public function __construct(
        protected int $statusCode,
        protected string $errorTitle,
        string $message = '',
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorTitle(): string
    {
        return $this->errorTitle;
    }
}
