<?php

namespace SecureAuth\Security;

class RemembermeToken
{
    private $token;
    private $tokenName = 'remember_token';
    public function __construct()
    {
        if (session_status() != PHP_SESSION_ACTIVE) {
            session_start();
            session_regenerate_id();
        }
    }

    public function generateRemembermeToken()
    {
        $this->token = bin2hex(random_bytes(32)); // 64 hex characters
        return $this;
    }

    public function setcookie()
    {
        setcookie($this->tokenName, $this->token, [
            'expires' => time() + (86400 * 30),
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }

    public function getgeneratedtoken()
    {
        return $this->token;
    }

    public function gettokencontent()
    {
        return isset($_COOKIE[$this->tokenName]);
    }

    // rotate rememberme token (replace the old one to new one)
    public function  RotateTokenContent()
    {
        $this->generateRemembermeToken()->setcookie();
    }

    public function Destroyremembermetoken()
    {
        // Set the cookie to expire in the past
        setcookie($this->tokenName, '', time() - 3600, '/');

        // Optionally unset it from the PHP global array
        unset($_COOKIE[$this->tokenName]);
    }
}
