<?php

declare(strict_types=1);

namespace CheckMaster\Core;

final class BadRequestException extends HttpException
{
    public function __construct(string $message = 'Requête invalide.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 400, $previous);
    }
}
