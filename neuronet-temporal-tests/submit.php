<?php
// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set default timezone
date_default_timezone_set('UTC');

// Function to send JSON response
function send_json($data, $status = 200) {
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

// Handle CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    send_json([
        'success' => false,
        'message' => 'Method not allowed. Please use POST.'
    ], 405);
}

try {
    // Get POST data
    $input = [];
    if (!empty($_POST)) {
        $input = $_POST;
    } else {
        $json = file_get_contents('php://input');
        if (!empty($json)) {
            $input = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data');
            }
        }
    }
    
    // Get form data
    $fullName = isset($input['fullName']) ? trim($input['fullName']) : '';
    $email = isset($input['email']) ? trim($input['email']) : '';
    
    // Validate required fields
    $errors = [];
    if (empty($fullName)) {
        $errors['fullName'] = 'Full name is required';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    // If there are validation errors
    if (!empty($errors)) {
        send_json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ], 400);
    }
    
    // Define log directory and file
    $logDir = __DIR__ . '/logs';
    $logFile = $logDir . '/submissions.log';
    
    // Debug information
    $debugInfo = [
        'log_dir' => $logDir,
        'log_file' => $logFile,
        'log_dir_exists' => file_exists($logDir),
        'log_dir_writable' => is_writable($logDir),
        'log_file_exists' => file_exists($logFile),
        'log_file_writable' => file_exists($logFile) ? is_writable($logFile) : is_writable($logDir)
    ];
    
    // Create logs directory if it doesn't exist
    if (!file_exists($logDir)) {
        if (!mkdir($logDir, 0755, true)) {
            $debugInfo['error'] = 'Failed to create logs directory';
            error_log('Failed to create logs directory: ' . $logDir);
            throw new Exception('Failed to create logs directory', 0, null, $debugInfo);
        }
        // Ensure the directory is writable
        chmod($logDir, 0755);
    }
    
    // Prepare log entry
    $logEntry = sprintf(
        "[%s] IP: %s | Name: %s | Email: %s\n",
        date('Y-m-d H:i:s'),
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $fullName,
        $email
    );
    
    // Write to log file
    $result = file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    if ($result === false) {
        $debugInfo['error'] = 'Failed to write to log file';
        $debugInfo['error_get_last'] = error_get_last();
        error_log('Failed to write to log file: ' . $logFile);
        throw new Exception('Failed to write to log file', 0, null, $debugInfo);
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
    // Prepare error data
    $errorData = [
        'success' => false,
        'message' => 'An error occurred while processing your request',
        'error' => $e->getMessage(),
    ];
    
    // Include debug info if available
    if ($e->getPrevious() instanceof Exception) {
        $errorData['debug'] = $e->getPrevious()->getMessage();
    }
    
    // Log the error with additional context
    $logMessage = sprintf(
        "Form processing error: %s\nFile: %s\nLine: %s\nTrace:\n%s\n",
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    error_log($logMessage);
    
    // For development, include more details in the response
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        $errorData['debug'] = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString())
        ];
    }
    
    // Return error response
    send_json($errorData, 500);
}
