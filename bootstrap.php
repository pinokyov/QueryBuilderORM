<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables from .env file
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Database configuration from environment variables
define('DB_HOST', getenv('DB_HOST') ?: 'orm_mysql');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_DATABASE', getenv('DB_DATABASE') ?: 'query_builder_orm');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'orm_user');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'orm_password');

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include helper functions
require_once __DIR__ . '/src/Orm/helpers.php';

// Register autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Set default timezone
date_default_timezone_set('UTC');
