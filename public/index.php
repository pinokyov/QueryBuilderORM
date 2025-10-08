<?php
/**
 * Front Controller
 *
 * This file serves as the entry point for the application.
 */

// Define the application path
$appPath = dirname(__DIR__);

// Include the bootstrap file
require $appPath . '/bootstrap.php';

// Include the main application file
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Route the request
if ($uri === '/' || $uri === '') {
    // Include the main index file
    require $appPath . '/index.php';
} else if (file_exists($appPath . $uri)) {
    // Serve the requested file if it exists
    return false;
} else {
    // 404 Not Found
    header("HTTP/1.0 404 Not Found");
    echo '404 Not Found';
}
