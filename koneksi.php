<?php
declare(strict_types=1);

if (!function_exists('db_config')) {
    function db_config(): array
    {
        return [
            'host' => '127.0.0.1',
            'dbname' => 'ypkiv_db',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
        ];
    }
}

if (!function_exists('db_connection')) {
    function db_connection(): PDO
    {
        $config = db_config();
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['dbname'],
            $config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new PDO($dsn, $config['username'], $config['password'], $options);
    }
}

$pdo = db_connection();
