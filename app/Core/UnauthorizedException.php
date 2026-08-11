<?php

declare(strict_types=1);

namespace CheckMaster\Core;

final class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Authentification requise.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
