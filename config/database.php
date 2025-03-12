<?php

use Illuminate\Support\Str;

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

        'oracle' => [
            'driver'        => 'oracle',
            'tns'           => env('DB_TNS', ''),
            'host'          => env('DB_HOST', ''),
            'port'          => env('DB_PORT', '1521'),
            'database'      => env('DB_DATABASE', ''),
            'username'      => env('DB_USERNAME', ''),
            'password'      => env('DB_PASSWORD', ''),
            'service_name'  => env('DB_SERVICE_NAME', 'SMART'),
            'charset'       => env('DB_CHARSET', 'AL32UTF8'),
            'prefix'        => env('DB_PREFIX', ''),
            'prefix_schema' => env('DB_SCHEMA_PREFIX', ''),
            'server_version' => env('DB_SERVER_VERSION','12c'),
            'edition'       => env('DB_EDITION', 'ora$base'),
        ],

        'oracle_dbtodb' => [
            'driver'        => 'oracle',
            'tns'           => env('DB2_TNS', ''),
            'host'          => env('DB2_HOST', ''),
            'port'          => env('DB2_PORT', '1521'),
            'database'      => env('DB2_DATABASE', ''),
            'username'      => env('DB2_USERNAME', ''),
            'password'      => env('DB2_PASSWORD', ''),
            'service_name'  => env('DB2_SERVICE_NAME', 'SMART'),
            'charset'       => env('DB2_CHARSET', 'AL32UTF8'),
            'prefix'        => env('DB2_PREFIX', ''),
            'prefix_schema' => env('DB2_SCHEMA_PREFIX', ''),
            'server_version' => env('DB2_SERVER_VERSION','12c'),
            'edition'       => env('DB2_EDITION', 'ora$base'),
        ],

        'oracle_reptdm' => [
            'driver'        => 'oracle',
            'tns'           => env('DB3_TNS', ''),
            'host'          => env('DB3_HOST', ''),
            'port'          => env('DB3_PORT', '1521'),
            'database'      => env('DB3_DATABASE', ''),
            'username'      => env('DB3_USERNAME', ''),
            'password'      => env('DB3_PASSWORD', ''),
            'service_name'  => env('DB3_SERVICE_NAME', 'SMART'),
            'charset'       => env('DB3_CHARSET', 'AL32UTF8'),
            'prefix'        => env('DB3_PREFIX', ''),
            'prefix_schema' => env('DB3_SCHEMA_PREFIX', ''),
            'server_version' => env('DB3_SERVER_VERSION','12c'),
            'edition'       => env('DB3_EDITION', 'ora$base'),
        ],

        'oracle_dwhhis' => [
            'driver'        => 'oracle',
            'tns'           => env('DB4_TNS', ''),
            'host'          => env('DB4_HOST', ''),
            'port'          => env('DB4_PORT', '1521'),
            'database'      => env('DB4_DATABASE', ''),
            'username'      => env('DB4_USERNAME', ''),
            'password'      => env('DB4_PASSWORD', ''),
            'service_name'  => env('DB4_SERVICE_NAME', 'SMART'),
            'charset'       => env('DB4_CHARSET', 'AL32UTF8'),
            'prefix'        => env('DB4_PREFIX', ''),
            'prefix_schema' => env('DB4_SCHEMA_PREFIX', ''),
            'server_version' => env('DB4_SERVER_VERSION','12c'),
            'edition'       => env('DB4_EDITION', 'ora$base'),
        ],

        'oracle_bscs70' => [
            'driver'        => 'oracle',
            'tns'           => env('DB5_TNS', ''),
            'host'          => env('DB5_HOST', ''),
            'port'          => env('DB5_PORT', '1521'),
            'database'      => env('DB5_DATABASE', ''),
            'username'      => env('DB5_USERNAME', ''),
            'password'      => env('DB5_PASSWORD', ''),
            'service_name'  => env('DB5_SERVICE_NAME', 'SMART'),
            'charset'       => env('DB5_CHARSET', 'AL32UTF8'),
            'prefix'        => env('DB5_PREFIX', ''),
            'prefix_schema' => env('DB5_SCHEMA_PREFIX', ''),
            'server_version' => env('DB5_SERVER_VERSION','12c'),
            'edition'       => env('DB5_EDITION', 'ora$base'),
        ],

        'ch-dn02' => [
            'driver' => 'bavix::clickhouse',
            'host' => env('DB6_HOST', 'localhost'),
            'port' => env('DB6_PORT', '1433'),
            'database' => env('DB6_DATABASE', 'default'),
            'username' => env('DB6_USERNAME', 'default'),
            'password' => env('DB6_PASSWORD', ''),
            'options' => [
                'timeout' => 30,
                'protocol' => 'http'
            ]
        ],

        'ch-dn03' => [
            'driver' => 'bavix::clickhouse',
            'host' => env('DB11_HOST', 'localhost'),
            'port' => env('DB11_PORT', '1433'),
            'database' => env('DB11_DATABASE', 'default'),
            'username' => env('DB11_USERNAME', 'default'),
            'password' => env('DB11_PASSWORD', ''),
            'options' => [
                'timeout' => 30,
                'protocol' => 'http'
            ]
        ],

        'ch-dn05' => [
            'driver' => 'bavix::clickhouse',
            'host' => env('DB7_HOST', 'localhost'),
            'port' => env('DB7_PORT', '8123'),
            'database' => env('DB7_DATABASE', 'default'),
            'username' => env('DB7_USERNAME', 'default'),
            'password' => env('DB7_PASSWORD', ''),
            'options' => [
                'timeout' => 30,
                'protocol' => 'http'
            ]
        ],

        'oracle_eirdb' => [
            'driver'        => 'oracle',
            'tns'           => env('DB8_TNS', ''),
            'host'          => env('DB8_HOST', ''),
            'port'          => env('DB8_PORT', '1521'),
            'database'      => env('DB8_DATABASE', ''),
            'username'      => env('DB8_USERNAME', ''),
            'password'      => env('DB8_PASSWORD', ''),
            'service_name'  => env('DB8_SERVICE_NAME'),
            'charset'       => env('DB8_CHARSET', 'AL32UTF8'),
            'prefix'        => env('DB8_PREFIX', ''),
            'prefix_schema' => env('DB8_SCHEMA_PREFIX', ''),
            'server_version' => env('DB8_SERVER_VERSION','12c'),
            'edition'       => env('DB8_EDITION', 'ora$base'),
        ],

        'ch-dn04' => [
            'driver' => 'bavix::clickhouse',
            'host' => env('DB9_HOST', 'localhost'),
            'port' => env('DB9_PORT', '8123'),
            'database' => env('DB9_DATABASE', 'default'),
            'username' => env('DB9_USERNAME', 'default'),
            'password' => env('DB9_PASSWORD', ''),
            'options' => [
                'timeout' => 30,
                'protocol' => 'http'
            ]
        ],

        'ch-dn01' => [
            'driver' => 'bavix::clickhouse',
            'host' => env('DB10_HOST', 'localhost'),
            'port' => env('DB10_PORT', '1433'),
            'database' => env('DB10_DATABASE', 'default'),
            'username' => env('DB10_USERNAME', 'default'),
            'password' => env('DB10_PASSWORD', ''),
            'options' => [
                'timeout' => 30,
                'protocol' => 'http'
            ]
        ],

        'ch-dn09' => [
            'driver' => 'bavix::clickhouse',
            'host' => env('DB13_HOST', 'localhost'),
            'port' => env('DB13_PORT', '1433'),
            'database' => env('DB13_DATABASE', 'default'),
            'username' => env('DB13_USERNAME', 'default'),
            'password' => env('DB13_PASSWORD', ''),
            'options' => [
                'timeout' => 30,
                'protocol' => 'http'
            ]
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB12_HOST', '127.0.0.1'),
            'port' => env('DB12_PORT', '3306'),
            'database' => env('DB12_DATABASE', 'forge'),
            'username' => env('DB12_USERNAME', 'forge'),
            'password' => env('DB12_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
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
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
