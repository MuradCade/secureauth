<?php

namespace SecureAuth\Auth;

class Authorization
{

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function Islogedin($userid, $callback_route, $usercookie = null)
    {
        if (isset($userid) || isset($usercookie)) {
            header('location:' . $callback_route);
            exit();
        }
    }
    public function Isnotlogedin($userid, $callback_route, $usercookie = null)
    {
        if (empty($userid) && empty($usercookie)) {
            header('Location: ' . $callback_route);
            exit();
        }
    }
    public function AuthorizedUser($userRole, $condition, $callback_route, $gettokencontent = null)
    {

        if ($userRole != null && $gettokencontent != null) {
            header('location:' . $callback_route);
            exit();
        } else if ($userRole != $condition && $gettokencontent != null) {
            header('location:' . $callback_route);
            exit();
        }
        return 'poo';
    }

    public function CheckSessionandCookie($session, $cookie)
    {
        return empty($session) && !empty($cookie);
    }
}
