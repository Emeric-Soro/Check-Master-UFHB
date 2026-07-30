<?php

namespace CheckMaster\Core;

final class Request
{
    public string $method;
    public string $path;
    public array $query;
    public array $post;
    public array $server;

    public function __construct(array $server, array $query, array $post)
    {
        $this->server = $server;
        $this->query = $query;
        $this->post = $post;
        $this->method = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        $this->path = self::resolvePath($server, $query);
    }

    public static function fromGlobals(): self
    {
        return new self($_SERVER, $_GET, $_POST);
    }

    private static function resolvePath(array $server, array $query): string
    {
        // Mode sans rewrite: /public/index.php?_path=/login
        if (isset($query['_path']) && is_string($query['_path']) && $query['_path'] !== '') {
            // Protection contre la traversée de répertoire
            if (str_contains($query['_path'], '..') || str_contains($query['_path'], "\0")) {
                return '/';
            }
            $p = '/' . ltrim($query['_path'], '/');
            return rtrim($p, '/') ?: '/';
        }

        // PATH_INFO (si configuré): /public/index.php/login
        if (isset($server['PATH_INFO']) && is_string($server['PATH_INFO']) && $server['PATH_INFO'] !== '') {
            if (str_contains($server['PATH_INFO'], '..') || str_contains($server['PATH_INFO'], "\0")) {
                return '/';
            }
            $p = '/' . ltrim($server['PATH_INFO'], '/');
            return rtrim($p, '/') ?: '/';
        }

        $uri = (string) ($server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '/';
        }
        // Protection contre la traversée de répertoire et les null bytes
        if (str_contains($path, "\0")) {
            return '/';
        }

        // Retirer le “base path” du script (ex: /Check-Master-UFHB/public)
        $scriptName = (string) ($server['SCRIPT_NAME'] ?? '');
        $baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        $pathNorm = str_replace('\\', '/', $path);
        if ($baseDir !== '' && $baseDir !== '.' && strpos($pathNorm, $baseDir) === 0) {
            $pathNorm = substr($pathNorm, strlen($baseDir));
            if ($pathNorm === '') {
                $pathNorm = '/';
            }
        }

        // Retirer /index.php si présent
        if ($pathNorm === '/index.php') {
            $pathNorm = '/';
        } elseif (strpos($pathNorm, '/index.php/') === 0) {
            $pathNorm = substr($pathNorm, strlen('/index.php'));
        }

        $pathNorm = '/' . ltrim($pathNorm, '/');
        return rtrim($pathNorm, '/') ?: '/';
    }
}

