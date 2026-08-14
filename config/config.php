<?php
return [
    'db' => [
        // 'mariadb' to use PostCode, 'sqlite' to use PostCodeSQLite, 'pgsql' to use PostCodePgSQL.
        'DRIVER' => 'mariadb',   // mariadb | sqlite | pgsql
        'sqlite' => [
            'DB_NAME' => 'post_code_lookup',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_USER' => 'db_admin',
            'DB_PASSWORD' => 'db_password',
            'DB_CHARSET' => 'utf8mb4',
            'DB_SQLITE_PATH' => __DIR__ . '/../data/post_code_lookup.sqlite',
        ],
        'mariadb' => [
            'DB_NAME' => 'post_code_lookup',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_USER' => 'admin',
            'DB_PASSWORD' => 'password',
            'DB_CHARSET' => 'utf8mb4',
        ],
        'pgsql' => [
            'DB_NAME' => 'post_code_lookup',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '5432',
            'DB_USER' => 'db_admin',
            'DB_PASSWORD' => 'db_password',
            'DB_CHARSET' => 'UTF8',
        ],
    ],
    'BATCH_SIZE' => 1_000,
];

