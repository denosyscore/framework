<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'DenoSys Framework'),
    'key' => env('APP_KEY'),
    'description' => env('APP_DESCRIPTION'),
    'address' => env('APP_ADDRESS', '123 Framework Street, Example City'),
    'email' => env('APP_EMAIL', 'support@example.com'),
    'careers_email' => env('APP_CAREERS_EMAIL', 'careers@example.com'),
    'url' => env('APP_URL', 'http://localhost'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'version' => env('APP_VERSION', '1.0.0'),
];
