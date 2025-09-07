<?php

namespace SecureAuth\Security;

class Csrf
{
    protected static string $tokenKey = 'csrf_token';

    public static function getToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!isset($_SESSION[self::$tokenKey])) {
            self::generateToken();
        }
        return $_SESSION[self::$tokenKey];
    }

    public static function generateToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION[self::$tokenKey] = bin2hex(random_bytes(32));
        return $_SESSION[self::$tokenKey];
    }

    public static function verify(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return hash_equals($_SESSION[self::$tokenKey] ?? '', $token ?? '');
    }

    public static function regenerateToken(): string
    {
        return self::generateToken();
    }
}
