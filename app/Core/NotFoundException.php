<?php

declare(strict_types=1);

namespace CheckMaster\Core;

final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Ressource introuvable.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}
