<?php

/**
 * CORS Configuration
 *
 * Configure Cross-Origin Resource Sharing (CORS) settings.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | List of origins that are allowed to make cross-origin requests.
    | Use ['*'] to allow all origins (not recommended for production).
    |
    | Examples:
    | - ['*'] - Allow all origins
    | - ['https://example.com'] - Allow specific origin
    | - ['https://example.com', 'https://app.example.com'] - Multiple origins
    |
    */
    'allowed_origins' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    |
    | HTTP methods that are allowed for cross-origin requests.
    |
    */
    'allowed_methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    |
    | HTTP headers that are allowed in cross-origin requests.
    |
    */
    'allowed_headers' => 'Content-Type, Authorization, X-CSRF-TOKEN, X-Requested-With',
];
