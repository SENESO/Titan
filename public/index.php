<?php

/**
 * Titan Framework - A powerful, secure, and developer-friendly PHP framework
 *
 * This file is the entry point for a Titan application.
 * It bootstraps the application and handles the HTTP request.
 */

// Define the application start time
define('TITAN_START', microtime(true));

// Load the composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Get the application instance
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Run the application
$response = $app->run();

// Send the response
$response->send();

// End of file
