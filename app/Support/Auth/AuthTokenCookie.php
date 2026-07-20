<?php

namespace App\Support\Auth;

use Symfony\Component\HttpFoundation\Cookie;

class AuthTokenCookie
{
    public static function make(string $token): Cookie
    {
        return cookie(
            config('auth_cookie.name'),
            $token,
            config('auth_cookie.minutes'),
            config('auth_cookie.path'),
            config('auth_cookie.domain'),
            config('auth_cookie.secure'),
            true,
            false,
            config('auth_cookie.same_site')
        );
    }

    public static function forget(): Cookie
    {
        return cookie()->forget(
            config('auth_cookie.name'),
            config('auth_cookie.path'),
            config('auth_cookie.domain')
        );
    }
}
