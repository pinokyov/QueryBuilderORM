<?php

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver'    => 'mysql',
            'host'      => getenv('DB_HOST', 'orm_mysql'),
            'port'      => getenv('DB_PORT', '3306'),
            'database'  => getenv('DB_DATABASE', 'query_builder_orm'),
            'username'  => getenv('DB_USERNAME', 'orm_user'),
            'password'  => getenv('DB_PASSWORD', 'orm_password'),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
            'options'   => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
                \PDO::ATTR_EMULATE_PREPARES => true,
            ],
        ],
    ],
];
