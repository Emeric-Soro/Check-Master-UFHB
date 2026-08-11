<?php

declare(strict_types=1);

namespace CheckMaster\Core;

final class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'Accès refusé.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}
