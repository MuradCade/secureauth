<?php

namespace SecureAuth\Auth;

class SessionHelper
{

    // Ensure session is started before any session action
    private static function ensureSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
            session_regenerate_id(true); // safer: regenerate with delete old ID
        }
    }

    public static function SetUserSession($username, $email, $role = null, $id = null): void
    {
        self::ensureSessionStarted();
        $_SESSION['userid'] = $id ?? 0;
        $_SESSION['username'] = $username;
        $_SESSION['useremail'] = $email;
        $_SESSION['userrole'] = $role ?? 0;
    }

    public static function getSessionVariable($veriablename): ?string
    {
        self::ensureSessionStarted();
        return $_SESSION[$veriablename] ?? null;
    }
    public static function destroySession(): void
    {
        self::ensureSessionStarted();
        session_destroy();
    }

    public static function flash(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }

    public static function getFlash(string $key): ?string
    {
        if (isset($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }
        return null;
    }
}
