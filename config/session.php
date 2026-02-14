<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default session "driver" that will be used on
    | requests. By default, we will use the lightweight file driver but
    | you may specify any of the other supported drivers.
    |
    | Supported: "file", "database", "cookie", "array"
    |
    | - "file": Stores sessions in files (default, simple setup)
    | - "database": Stores sessions in database (best for load-balanced apps)
    | - "cookie": Stores encrypted sessions in cookies (stateless, ~4KB limit)
    | - "array": In-memory only (testing purposes)
    |
    */

    'driver' => env('SESSION_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that you wish the session
    | to be allowed to remain idle before it expires. If you want them
    | to immediately expire on the browser closing, set that option.
    |
    */

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    |
    | This option determines whether session data should be encrypted.
    | When enabled, both the session ID cookie AND the session payload
    | (stored in database/file) will be encrypted using your application's
    | encryption key (APP_KEY).
    |
    | Note: Session payloads are always base64 encoded regardless of this
    | setting. Encryption adds an additional layer of security.
    |
    */

    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Session File Location
    |--------------------------------------------------------------------------
    |
    | When using the file session driver, we need a location where session
    | files may be stored. A default has been set for you but a different
    | location may be specified. This is only needed for file sessions.
    |
    */

    'files' => dirname(__DIR__) . '/storage/framework/sessions',

    /*
    |--------------------------------------------------------------------------
    | Session Database Table
    |--------------------------------------------------------------------------
    |
    | When using the "database" session driver, you may specify the table
    | used to store sessions. The table should have the following structure:
    |
    | CREATE TABLE sessions (
    |     id VARCHAR(128) NOT NULL PRIMARY KEY,
    |     payload TEXT NOT NULL,
    |     last_activity INT UNSIGNED NOT NULL,
    |     user_id INT UNSIGNED NULL,
    |     ip_address VARCHAR(45) NULL,
    |     user_agent VARCHAR(255) NULL,
    |     INDEX sessions_last_activity_index (last_activity)
    | ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    |
    */

    'table' => env('SESSION_TABLE', 'sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name
    |--------------------------------------------------------------------------
    |
    | Here you may change the name of the cookie used to identify a session
    | instance by ID. The name specified here will get used every time a
    | new session cookie is created by the framework for every driver.
    |
    */

    'cookie' => env('SESSION_COOKIE', 'cfxp_session'),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Data Name
    |--------------------------------------------------------------------------
    |
    | When using the "cookie" driver, session data is stored in this cookie.
    | This is separate from the session ID cookie to keep concerns isolated.
    |
    */

    'cookie_data' => env('SESSION_COOKIE_DATA', 'cfxp_session_data'),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Path
    |--------------------------------------------------------------------------
    |
    | The session cookie path determines the path for which the cookie will
    | be regarded as available. Typically, this will be the root path of
    | your application but you are free to change this when necessary.
    |
    */

    'path' => '/',

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain
    |--------------------------------------------------------------------------
    |
    | Here you may change the domain of the cookie used to identify a session
    | in your application. This will determine which domains the cookie is
    | available to in your application.
    |
    */

    'domain' => env('SESSION_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Only Cookies
    |--------------------------------------------------------------------------
    |
    | By setting this option to true, session cookies will only be sent back
    | to the server if the browser has a HTTPS connection. This will keep
    | the cookie from being sent to you when it can't be done securely.
    |
    */

    'secure' => env('SESSION_SECURE_COOKIE', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Access Only
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will prevent JavaScript from accessing the
    | value of the cookie and the cookie will only be accessible through
    | the HTTP protocol. You are free to modify this option if needed.
    |
    */

    'http_only' => true,

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookies
    |--------------------------------------------------------------------------
    |
    | This option determines how your cookies behave when cross-site requests
    | take place, and can be used to mitigate CSRF attacks.
    |
    | Supported: "lax", "strict", "none"
    |
    */

    'same_site' => 'lax',

    /*
    |--------------------------------------------------------------------------
    | Session Lottery
    |--------------------------------------------------------------------------
    |
    | Some session drivers must manually sweep their storage location to get
    | rid of old sessions from storage. Here are the odds that it will
    | happen on a given request. By default, it's 2 out of 100.
    |
    */

    'lottery' => [2, 100],
];
