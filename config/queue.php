<?php

declare(strict_types=1);

/**
 * Queue Configuration
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection
    |--------------------------------------------------------------------------
    |
    | Options: sync, database, array
    |
    */

    'default' => env('QUEUE_CONNECTION', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'failed_table' => 'failed_jobs',
        ],

        'array' => [
            'driver' => 'array',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Job Settings
    |--------------------------------------------------------------------------
    */

    'failed' => [
        'table' => 'failed_jobs',
    ],

];
