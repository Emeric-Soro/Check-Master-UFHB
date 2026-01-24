<?php

namespace CheckMaster\Core;

final class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    public static function token(): string
    {
        Session::start();
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate(?string $token): bool
    {
        Session::start();
        $expected = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($expected) || !is_string($token)) {
            return false;
        }
        return hash_equals($expected, $token);
    }
}

