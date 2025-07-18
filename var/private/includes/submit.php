<?php
// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set custom error log file
$logFile = __DIR__ . '/../logs/php_errors.log';
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}
ini_set('error_log', $logFile);

// Log the start of the request
error_log("\n=== New Request ===");
error_log("Time: " . date('Y-m-d H:i:s'));
error_log("Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'));
error_log("URI: " . ($_SERVER['REQUEST_URI'] ?? 'UNKNOWN'));
error_log("POST data: " . print_r($_POST, true));
error_log("Input data: " . file_get_contents('php://input'));

// Set default timezone
date_default_timezone_set('UTC');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate a session ID if it doesn't exist
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = bin2hex(random_bytes(16));
}

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
    
    // Log the received input for debugging
    error_log('Received input: ' . print_r($input, true));
    
    // Get form data with fallbacks
    $fullName = $input['fullName'] ?? $input['fullname'] ?? '';
    $email = $input['email'] ?? '';
    $testName = $input['testName'] ?? $input['test_name'] ?? 'Unknown Test';
    $promptNumber = isset($input['promptNumber']) ? intval($input['promptNumber']) : 
                  (isset($input['prompt_number']) ? intval($input['prompt_number']) : 0);
    $userAnswer = $input['userAnswer'] ?? $input['user_answer'] ?? '';
    $correctAnswer = $input['correctAnswer'] ?? $input['correct_answer'] ?? '';
    $responseTime = isset($input['responseTime']) ? intval($input['responseTime']) : 
                   (isset($input['response_time']) ? intval($input['response_time']) : 0);
    $sessionId = $input['sessionId'] ?? $input['session_id'] ?? null;
    
    // Generate a unique user ID if not provided (using email if available)
    $userID = $input['userID'] ?? $input['user_id'] ?? 
             (!empty($email) ? md5(strtolower(trim($email))) : 'user_' . md5(uniqid('', true)));
    
    // Validate required fields with more flexible validation
    $errors = [];
    
    // Only validate name/email if this is a new user registration
    if (empty($input['userID']) && empty($input['user_id'])) {
        if (empty(trim($fullName))) {
            $errors['fullName'] = 'Full name is required';
        }
        
        if (empty(trim($email))) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        }
    }
    
    // Validate test data with more flexible requirements
    if (empty(trim($testName))) {
        $testName = 'Unnamed Test';
    }
    
    if ($promptNumber < 0) {  // Allow 0 for non-numbered prompts
        $errors['promptNumber'] = 'Prompt number cannot be negative';
    }
    
    // Allow empty userAnswer for skipped questions
    if ($userAnswer === '') {
        $userAnswer = 'skipped';
    }
    
    if ($correctAnswer === '') {
        // If correct answer isn't provided, we can't validate the user's answer
        $correctAnswer = 'unknown';
    }
    
    // If there are validation errors
    if (!empty($errors)) {
        $response = [
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
            'input' => $input,
            'debug' => [
                'session_id' => session_id(),
                'session_data' => $_SESSION ?? [],
                'request_method' => $_SERVER['REQUEST_METHOD'],
                'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set'
            ]
        ];
        
        error_log('Validation errors: ' . print_r($response, true));
        
        send_json($response, 400);
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
        $debugInfo['error_get_last'] = error_get_last();
        error_log('Failed to write to log file: ' . $logFile);
        throw new Exception('Failed to write to log file', 0, null, $debugInfo);
    }
    
    // Define database path and ensure directory exists
    $dbDir = __DIR__ . '/../data';
    $dbPath = $dbDir . '/test_results.db';
    
    // Debug output
    error_log("Database directory: $dbDir");
    error_log("Database path: $dbPath");
    error_log("Directory exists: " . (file_exists($dbDir) ? 'yes' : 'no'));
    error_log("Directory writable: " . (is_writable($dbDir) ? 'yes' : 'no'));
    error_log("Database file exists: " . (file_exists($dbPath) ? 'yes' : 'no'));
    error_log("Database file writable: " . (is_writable($dbPath) ? 'yes' : 'no'));
    
    // Ensure the data directory exists and is writable
    if (!file_exists($dbDir)) {
        error_log("Creating database directory: $dbDir");
        if (!mkdir($dbDir, 0777, true)) {
            $error = error_get_last();
            error_log("Failed to create directory: " . ($error['message'] ?? 'Unknown error'));
            throw new Exception('Failed to create database directory: ' . ($error['message'] ?? 'Unknown error'));
        }
        chmod($dbDir, 0777);
        error_log("Directory created with permissions: " . substr(sprintf('%o', fileperms($dbDir)), -4));
    }
    
    // Check if database file exists, if not, create it
    $isNewDb = !file_exists($dbPath);
    
    try {
        // Connect to SQLite database
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // If this is a new database, create the tables
        if ($isNewDb) {
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA synchronous = NORMAL');
            
            $createTableSql = "
                CREATE TABLE IF NOT EXISTS test_results (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    userID TEXT NOT NULL,
                    fullName TEXT,
                    email TEXT,
                    test_name TEXT NOT NULL,
                    prompt_number INTEGER NOT NULL,
                    user_answer TEXT NOT NULL,
                    correct_answer TEXT NOT NULL,
                    response_time INTEGER,
                    session_id TEXT,
                    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(userID, test_name, prompt_number, session_id) ON CONFLICT REPLACE
                )
            ";
            $pdo->exec($createTableSql);
            
            // Create indexes
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_user_session ON test_results (userID, session_id)');
            
            // Set file permissions
            chmod($dbPath, 0666);
            error_log('Created new database at: ' . $dbPath);
        }
        
        // Prepare the SQL query with proper field names
        $sql = "
            INSERT INTO test_results 
            (userID, fullName, email, test_name, prompt_number, user_answer, correct_answer, response_time, session_id)
            VALUES (:userID, :fullName, :email, :testName, :promptNumber, :userAnswer, :correctAnswer, :responseTime, :sessionId)
            ON CONFLICT(userID, test_name, prompt_number, session_id) 
            DO UPDATE SET 
                user_answer = excluded.user_answer,
                correct_answer = excluded.correct_answer,
                response_time = excluded.response_time,
                timestamp = CURRENT_TIMESTAMP
        ";
        
        // Prepare the parameters
        $params = [
            ':userID' => $userID,
            ':fullName' => $fullName,
            ':email' => $email,
            ':testName' => $testName,
            ':promptNumber' => $promptNumber,
            ':userAnswer' => $userAnswer,
            ':correctAnswer' => $correctAnswer,
            ':responseTime' => $responseTime > 0 ? $responseTime : null,
            ':sessionId' => $sessionId ?? ($_SESSION['session_id'] ?? 'default_session')
        ];
        
        // Log the SQL and parameters for debugging
        error_log('Executing SQL: ' . $sql);
        error_log('With parameters: ' . print_r($params, true));
        
        // Prepare and execute the statement
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $resultId = $pdo->lastInsertId();
        
        // Prepare success response
        $response = [
            'success' => true,
            'message' => 'Test result saved successfully!',
            'userID' => $userID,
            'sessionId' => $_SESSION['session_id'] ?? null,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Add additional data if this is the first request in the session
        if (!isset($_SESSION['first_request'])) {
            $response['isNewSession'] = true;
            $response['sessionCreated'] = date('Y-m-d H:i:s');
            $_SESSION['first_request'] = false;
        }
        
        send_json($response);
        
    } catch (PDOException $e) {
        // Database specific errors
        $errorData = [
            'success' => false,
            'message' => 'Database error occurred',
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'errorInfo' => $e->errorInfo ?? null,
            'debug' => [
                'db_path' => $dbPath ?? 'not set',
                'sql' => $sql ?? 'not set',
                'params' => $params ?? 'not set',
                'session_id' => session_id()
            ]
        ];
        
        error_log('Database Error: ' . print_r($errorData, true));
        
        // Don't expose database details in production
        if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') === false) {
            $errorData['error'] = 'A database error occurred';
            unset($errorData['errorInfo']);
        }
        
        send_json($errorData, 500);
        
    } catch (Exception $e) {
        // General errors
        $errorData = [
            'success' => false,
            'message' => 'An error occurred while processing your request',
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'debug' => [
                'request_method' => $_SERVER['REQUEST_METHOD'],
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set',
                'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
                'session_id' => session_id()
            ]
        ];
        
        error_log('Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        error_log('Stack trace: ' . $e->getTraceAsString());
        
        // Don't expose sensitive information in production
        if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') === false) {
            $errorData['error'] = 'An unexpected error occurred';
            unset($errorData['file'], $errorData['line'], $errorData['debug']);
        }
        
        send_json($errorData, 500);
    }
    
} catch (Exception $e) {
    // Log detailed error information
    $logMessage = sprintf(
        "[%s] Database Error: %s\nFile: %s\nLine: %s\nTrace:\n%s\n\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    
    // Log to PHP error log
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
