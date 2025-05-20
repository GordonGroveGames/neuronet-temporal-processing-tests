<?php
// Start output buffering to catch any unexpected output
ob_start();

// Disable display errors to prevent HTML output
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Function to send JSON response
function send_json($data, $status = 200) {
    // Clear any previous output
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Set headers
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Set status code
    http_response_code($status);
    
    // Output JSON
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Handle errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    send_json([
        'success' => false,
        'message' => 'Server Error',
        'error' => [
            'code' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ]
    ], 500);
}, E_ALL);

// Handle exceptions
set_exception_handler(function($e) {
    error_log("Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    send_json([
        'success' => false,
        'message' => 'An error occurred while processing your request',
        'error' => $e->getMessage()
    ], 500);
});

// Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    send_json([
        'success' => false,
        'message' => 'Method not allowed. Please use POST.',
        'allowed_methods' => ['POST']
    ], 405);
    exit;
}

// Ensure we have POST data
if (empty($_POST)) {
    // Try to get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $_POST = $input;
    } else {
        send_json([
            'success' => false,
            'message' => 'No data received. Please make sure to send form data.'
        ], 400);
    }
}

// Get POST data
$fullName = isset($_POST['fullName']) ? trim($_POST['fullName']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Simple validation
if (empty($fullName) || empty($email)) {
    send_json([
        'success' => false,
        'message' => 'Please fill in all required fields',
        'missing_fields' => array_filter([
            empty($fullName) ? 'fullName' : null,
            empty($email) ? 'email' : null
        ])
    ], 400);
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_json([
        'success' => false,
        'message' => 'Please enter a valid email address',
        'field' => 'email'
    ], 400);
}

try {
    // Create logs directory if it doesn't exist
    $logDir = __DIR__ . '/logs';
    if (!file_exists($logDir)) {
        if (!mkdir($logDir, 0755, true)) {
            throw new Exception('Failed to create logs directory');
        }
    }

    // Prepare log entry
    $logEntry = sprintf(
        "[%s] IP: %s | Name: %s | Email: %s\n",
        date('Y-m-d H:i:s'),
        $_SERVER['REMOTE_ADDR'],
        $fullName,
        $email
    );

    // Write to log file
    $logFile = $logDir . '/submissions.log';
    $logResult = file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    if ($logResult === false) {
        throw new Exception('Failed to write to log file');
    }

    // Return success response
    send_json([
        'success' => true,
        'message' => 'Form submitted successfully!',
        'data' => [
            'name' => $fullName,
            'email' => $email
        ]
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log('Form submission error: ' . $e->getMessage());
    
    // Return error response
    send_json([
        'success' => false,
        'message' => 'An error occurred while processing your request',
        'error' => $e->getMessage()
    ], 500);
}
?>
