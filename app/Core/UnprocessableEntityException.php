<?php

declare(strict_types=1);

namespace CheckMaster\Core;

final class UnprocessableEntityException extends HttpException
{
    public function __construct(string $message = 'Données invalides.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 422, $previous);
    }
}
