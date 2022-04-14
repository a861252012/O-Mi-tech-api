<?php

if (!function_exists('env_assoc')) {
    function env_assoc($key)
    {
        $data = [];
        foreach ($_ENV as $k => $v) {
            $matches = [];
            if (preg_match('/^' . $key . '_([0-9]+)$/', $k, $matches)) {
                if (isset($matches[1])) {
                    $data[$matches[1]] = $v;
                }
            }
        }
        return $data;
    }
}
if (!function_exists('env_array')) {
    function env_array($key)
    {
        return array_values(env_assoc($key));
    }
}
if (!function_exists('env_map')) {
    function env_map($string)
    {
        $data = [];
        foreach (explode(',', $string) as $kv) {
            list($k, $v) = explode(':', $kv);
            $data[$k] = $v;
        }
        return $data;
    }
}

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
        ],

//        'mysql' => [
//            'read' => env_map(env('DB_READ')),
//            'write' => env_map(env('DB_WRITE')),
//            'driver' => 'mysql',
//            'database' => env('DB_DATABASE', 'forge'),
//            'username' => env('DB_USERNAME', 'forge'),
//            'password' => env('DB_PASSWORD', ''),
//            'unix_socket' => env('DB_SOCKET', ''),
//            'sticky' => true,
//            'charset' => 'utf8mb4',
//            'collation' => 'utf8mb4_unicode_ci',
//            'prefix' => '',
//            'strict' => false,
//            'engine' => null,
//        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [
        'client' => 'phpredis',

        'clusters' => [
            'options' => [
                'timeout' =>  3,
                'read_timeout' => 3,
                'persistent' => true,
                'password' => env('REDIS_PASSWORD', null),
            ],
            'default' => [
                'servers' => [
                    'redis-cluster:7000',
                    'redis-cluster:7001',
                    'redis-cluster:7002',
                    'redis-cluster:7003',
                    'redis-cluster:7004',
                    'redis-cluster:7005',
                ],
            ],
            'redis_cache' => [
                'servers' => [
                    'redis-cluster:7000',
                    'redis-cluster:7001',
                    'redis-cluster:7002',
                    'redis-cluster:7003',
                    'redis-cluster:7004',
                    'redis-cluster:7005',
                ],
            ],
            'ceri' => [
                'servers' => [
                    'redis-cluster:7000',
                    'redis-cluster:7001',
                    'redis-cluster:7002',
                    'redis-cluster:7003',
                    'redis-cluster:7004',
                    'redis-cluster:7005',
                ],
            ],
            'session' => [
                'servers' => [
                    'redis-cluster:7000',
                    'redis-cluster:7001',
                    'redis-cluster:7002',
                    'redis-cluster:7003',
                    'redis-cluster:7004',
                    'redis-cluster:7005',
                ],
                'prefix' => 'PHPREDIS_SESSION:',
            ],
        ],
    ],
];
