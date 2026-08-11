<?php

declare(strict_types=1);

namespace CheckMaster\Core;

/**
 * Exception HTTP de base. Porte un code de statut et un message public
 * (jamais de détails SQL / chemins physiques en production).
 */
class HttpException extends \RuntimeException
{
    public function __construct(
        string $message = 'Erreur HTTP.',
        protected int $statusCode = 500,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function toResponse(): Response
    {
        $factory = ResponseFactory::class;
        return match ($this->statusCode) {
            400 => $factory::badRequest($this->getMessage()),
            401 => $factory::unauthorized($this->getMessage()),
            403 => $factory::forbidden($this->getMessage()),
            404 => $factory::notFound($this->getMessage()),
            422 => $factory::unprocessable($this->getMessage()),
            429 => $factory::tooManyRequests($this->getMessage()),
            default => $factory::serverError(),
        };
    }
}
