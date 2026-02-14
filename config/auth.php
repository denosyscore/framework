<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Model
    |--------------------------------------------------------------------------
    |
    | The model class used for authentication. This model should implement
    | the AuthenticatableInterface.
    |
    */
    'model' => App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Identifier Field
    |--------------------------------------------------------------------------
    |
    | The field used to identify users during login (e.g., 'email', 'username').
    |
    */
    'identifier' => 'email',

    /*
    |--------------------------------------------------------------------------
    | Unauthenticated Redirect
    |--------------------------------------------------------------------------
    |
    | Where to redirect unauthenticated users. This can be a route name
    | (e.g., 'login.form') or a URL path (e.g., '/login'). Set to null
    | to return a 401 JSON response instead (useful for API-only apps).
    |
    */
    'redirect' => 'login.form',

    /*
    |--------------------------------------------------------------------------
    | Guest Redirect
    |--------------------------------------------------------------------------
    |
    | Where to redirect authenticated users when they visit guest-only pages
    | (e.g., login, register). Set to null to return a 403 JSON response.
    |
    */
    'guest_redirect' => 'office.dashboard',

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for authentication attempts. This helps protect
    | against brute-force attacks on login, registration, and password reset.
    |
    | - max_attempts: Maximum requests allowed in the decay period
    | - decay_minutes: Time window in minutes before attempts reset
    |
    */
    'rate_limiting' => [
        'login' => [
            'max_attempts' => (int) env('AUTH_LOGIN_MAX_ATTEMPTS', 5),
            'decay_minutes' => (int) env('AUTH_LOGIN_DECAY_MINUTES', 1),
        ],
        'register' => [
            'max_attempts' => (int) env('AUTH_REGISTER_MAX_ATTEMPTS', 3),
            'decay_minutes' => (int) env('AUTH_REGISTER_DECAY_MINUTES', 1),
        ],
        'password_reset' => [
            'max_attempts' => (int) env('AUTH_PASSWORD_RESET_MAX_ATTEMPTS', 3),
            'decay_minutes' => (int) env('AUTH_PASSWORD_RESET_DECAY_MINUTES', 1),
        ],
    ],
];
