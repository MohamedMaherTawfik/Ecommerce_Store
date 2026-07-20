<?php

return [
    'name' => env('AUTH_COOKIE_NAME', 'store_auth_token'),
    'minutes' => (int) env('AUTH_COOKIE_LIFETIME', 120),
    'path' => env('AUTH_COOKIE_PATH', '/'),
    'domain' => env('AUTH_COOKIE_DOMAIN', env('SESSION_DOMAIN')),
    'secure' => (bool) env('AUTH_COOKIE_SECURE', env('APP_ENV') === 'production'),
    'same_site' => env('AUTH_COOKIE_SAME_SITE', 'lax'),
];
