<?php

declare(strict_types=1);

/**
 * Mail Configuration
 * 
 * Configure the mail driver and transport settings.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | The default mailer to use for sending emails. Supported: "smtp",
    | "sendmail", "mailgun", "log", "array"
    |
    */
    'default' => env('MAIL_MAILER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | The default "from" address for all outgoing emails.
    |
    */
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Denosys')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Configure each mailer driver with its specific settings.
    |
    */
    'mailers' => [
        'smtp' => [
            'host' => env('MAIL_HOST', 'localhost'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'), // tls, ssl, or null
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        'mailgun' => [
            'domain' => env('MAILGUN_DOMAIN', ''),
            'api_key' => env('MAILGUN_API_KEY', ''),
            'region' => env('MAILGUN_REGION', 'us'), // us or eu
        ],

        'sendmail' => [
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs'),
        ],

        'log' => [
            'channel' => env('MAIL_LOG_CHANNEL', 'mail'),
        ],

        'array' => [
            // Used for testing - messages are captured and stored
        ],
    ],
];
