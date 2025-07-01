<?php
// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set content type to JSON
header('Content-Type: application/json');

// Define the base directory
$baseDir = dirname(dirname(__DIR__));

// Include the actual submit handler from the private directory
require_once $baseDir . '/var/private/includes/submit.php';

// The included file should handle the request and send a JSON response
exit;