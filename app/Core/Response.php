<?php

namespace CheckMaster\Core;

final class Response
{
    public int $status = 200;
    /** @var array<string,string> */
    public array $headers = [];
    public string $body = '';

    public function __construct(string $body = '', int $status = 200, array $headers = [])
    {
        $this->body = $body;
        $this->status = $status;
        $this->headers = $headers;
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self('', $status, ['Location' => $location]);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $k => $v) {
            // Nettoyer les en-têtes pour prévenir l'injection d'en-têtes HTTP
            $k = str_replace(["\r", "\n"], '', $k);
            $v = str_replace(["\r", "\n"], '', $v);
            header($k . ': ' . $v);
        }
        echo $this->body;
    }
}

