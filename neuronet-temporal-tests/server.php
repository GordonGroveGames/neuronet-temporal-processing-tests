<?php
// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set default timezone
date_default_timezone_set('UTC');

// Simple router
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$script_name = dirname($_SERVER['SCRIPT_NAME']);

// Remove script name from request URI
if (strpos($request_uri, $script_name) === 0) {
    $request_uri = substr($request_uri, strlen($script_name));
}

// Route requests
switch ($request_uri) {
    case '/':
    case '/index.html':
        include __DIR__ . '/index.html';
        break;
        
    case '/process.php':
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Process the form
            process_form();
        } else {
            // Method not allowed
            header('Allow: POST', true, 405);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed. Please use POST.'
            ]);
        }
        break;
        
    default:
        // Not found
        header('HTTP/1.0 404 Not Found');
        echo '404 Not Found';
        break;
}

/**
 * Process the form submission
 */
function process_form() {
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
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $fullName,
            $email
        );
        
        // Write to log file
        $logFile = $logDir . '/submissions.log';
        if (file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX) === false) {
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
        error_log('Form processing error: ' . $e->getMessage());
        
        // Return error response
        send_json([
            'success' => false,
            'message' => 'An error occurred while processing your request',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Send JSON response
 */
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
