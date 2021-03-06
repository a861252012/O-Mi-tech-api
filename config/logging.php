<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path().'/logs/laravel-'.php_sapi_name().'.log',
            'level' => 'debug',
        ],
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path().'/logs/laravel-'.php_sapi_name().'.log',
            'level' => 'debug',
            'days' => 7,
        ],
        'charge' => [
            'driver' => 'daily',
            'path' => storage_path('logs/charge.log'),
            'level' => 'debug',
            'days' => 7,
        ],
        'room' => [
            'driver' => 'daily',
            'path' => storage_path('logs/room.log'),
            'level' => 'debug',
            'days' => 7,
        ],
        'plat' => [
            'driver' => 'daily',
            'path' => storage_path('logs/plat.log'),
            'level' => 'debug',
            'days' => 7,
        ],
        'cron' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cron.log'),
            'level' => 'debug',
            'days' => 7,
        ],
        'csrf' => [
            'driver' => 'daily',
            'path' => storage_path('logs/csrf.log'),
            'level' => 'debug',
            'days' => 7,
        ],
        'login' => [
            'driver' => 'daily',
            'tap' => [App\Services\Logging\PureJsonFormatter::class],
            'path' => storage_path('logs/login.log'),
            'level' => 'debug',
            'days' => 7,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
        ],

        'athena' => [
            'driver' => 'daily',
            'path' => storage_path('logs/athena.log'),
            'tap' => [App\Services\Logging\LogService::class],
            'level' => 'debug',
        ]
    ],

];
