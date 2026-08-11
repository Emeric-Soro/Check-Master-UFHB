<?php

declare(strict_types=1);

namespace CheckMaster\Core;

final class TooManyRequestsException extends HttpException
{
    public function __construct(string $message = 'Trop de requêtes.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 429, $previous);
    }
}
