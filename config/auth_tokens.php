<?php

return [
    'access_token_ttl_seconds' => (int) env('JWT_TTL', 15) * 60,

    'refresh_cookie' => [
        'name' => env('REFRESH_TOKEN_COOKIE_NAME', 'refresh_token'),
        'ttl' => (int) env('REFRESH_TOKEN_COOKIE_TTL', 20160),
        'path' => env('REFRESH_TOKEN_COOKIE_PATH', '/'),
        'domain' => env('REFRESH_TOKEN_COOKIE_DOMAIN'),
        'secure' => filter_var(env('REFRESH_TOKEN_COOKIE_SECURE', env('APP_ENV') !== 'local'), FILTER_VALIDATE_BOOL),
        'same_site' => env('REFRESH_TOKEN_COOKIE_SAME_SITE', 'lax'),
    ],
];
