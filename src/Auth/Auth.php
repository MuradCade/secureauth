<?php

namespace SecureAuth\Auth;

class Auth
{
    public static function AuthenticateUser(?array $db, string $email, string $password)
    {


        if ($db != null && $db['email'] == $email && password_verify($password, $db['password'])) {
            return true;
        } else {
            return false;
        }
    }
}
