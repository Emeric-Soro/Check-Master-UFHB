<?php

namespace CheckMaster\Core;

final class Router
{
    /** @var array<int,array{method:string,path:string,handler:callable}> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->map('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->map('POST', $path, $handler);
    }

    public function map(string $method, string $path, callable $handler): void
    {
        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/') ?: '/';
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $req): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $req->method) {
                continue;
            }
            if ($route['path'] !== $req->path) {
                continue;
            }
            $handler = $route['handler'];
            $resp = $handler($req);
            if ($resp instanceof Response) {
                return $resp;
            }
            return new Response((string) $resp);
        }

        return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}

