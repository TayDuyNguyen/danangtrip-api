<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting (Throttling)
    |--------------------------------------------------------------------------
    | These values represent the number of requests per minute by default.
    | They can be overridden via .env file.
    */
    'rate_limits' => [
        'auth' => env('API_RATE_LIMIT_AUTH', 10),
        'strict' => env('API_RATE_LIMIT_STRICT', 5),
        'standard' => env('API_RATE_LIMIT_STANDARD', 60),
        'uploads' => env('API_RATE_LIMIT_UPLOADS', 20),
        'admin' => env('API_RATE_LIMIT_ADMIN', 30),
        'exports' => env('API_RATE_LIMIT_EXPORTS', 10),
        'resend' => env('API_RATE_LIMIT_RESEND', 3),
        'callbacks' => env('API_RATE_LIMIT_CALLBACKS', 20),
    ],
];
