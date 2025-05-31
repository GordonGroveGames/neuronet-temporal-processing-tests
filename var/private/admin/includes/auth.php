<?php
// Set session configuration before starting the session
$sessionName = 'neurotest_admin';
$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
$isLocalhost = in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', 'localhost:8000', '127.0.0.1:8000']);

// Configure session parameters
$cookieParams = [
    'lifetime' => 86400, // 24 hours
    'path' => '/',
    'domain' => '', // Empty domain for better compatibility
    'secure' => $isHttps && !$isLocalhost, // Only force HTTPS in production
    'httponly' => true,
    'samesite' => $isLocalhost ? 'Lax' : 'None' // More permissive for localhost
];

// Set session name and parameters
session_name($sessionName);

// Set custom session save path
$sessionPath = __DIR__ . '/../../../tmp/sessions';
if (!file_exists($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}

// Set session configuration
ini_set('session.save_path', $sessionPath);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $cookieParams['secure'] ? 1 : 0);
ini_set('session.cookie_samesite', $cookieParams['samesite']);
ini_set('session.gc_maxlifetime', 86400); // 24 hours
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

// Start the session with error suppression to avoid headers already sent warnings
if (session_status() === PHP_SESSION_NONE) {
    error_log('Starting new session. Save path: ' . $sessionPath);
    
    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => $cookieParams['secure'],
        'httponly' => $cookieParams['httponly'],
        'samesite' => $cookieParams['samesite']
    ]);
    
    @session_start();
    error_log('Session started. ID: ' . session_id());
    
    // Set a test cookie to verify cookies are working
    setcookie('test_cookie', 'test_value', [
        'expires' => time() + 86400,
        'path' => '/',
        'domain' => $cookieParams['domain'],
        'secure' => $cookieParams['secure'],
        'httponly' => true,
        'samesite' => $cookieParams['samesite']
    ]);
}

error_log('Session configuration - Name: ' . session_name() . ', ID: ' . session_id());
error_log('Session cookie params: ' . print_r(session_get_cookie_params(), true));
error_log('Current cookies: ' . print_r($_COOKIE, true));

// Regenerate session ID periodically for security
$regenerate = false;
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
    $regenerate = true;
} elseif (time() - $_SESSION['created'] > 1800) { // 30 minutes
    $regenerate = true;
}

if ($regenerate) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Include the database connection files
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/admin_db.php';

function isAuthenticated() {
    // Make sure session is started
    if (session_status() === PHP_SESSION_NONE) {
        error_log('Starting new session in isAuthenticated()');
        @session_start();
        error_log('New session started in isAuthenticated(). ID: ' . session_id());
    } else {
        error_log('Session already active in isAuthenticated(). ID: ' . session_id());
    }
    
    error_log('=== isAuthenticated() check ===');
    error_log('Session ID: ' . session_id());
    error_log('Session name: ' . session_name());
    error_log('Session status: ' . session_status());
    error_log('Session cookie params: ' . print_r(session_get_cookie_params(), true));
    error_log('Current cookies: ' . print_r($_COOKIE, true));
    error_log('Session data: ' . print_r($_SESSION, true));
    
    // Check if user is marked as authenticated
    if (empty($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
        error_log('Authentication failed: admin_authenticated flag not set or not true');
        error_log('Session data: ' . print_r($_SESSION, true));
        return false;
    }
    
    // Check for required session variables
    $requiredVars = ['admin_user_id', 'admin_username', 'last_activity', 'ip_address', 'user_agent'];
    $missingVars = [];
    foreach ($requiredVars as $var) {
        if (!isset($_SESSION[$var])) {
            $missingVars[] = $var;
        }
    }
    
    if (!empty($missingVars)) {
        error_log('Authentication failed: Missing required session variables: ' . implode(', ', $missingVars));
        error_log('Current session data: ' . print_r($_SESSION, true));
        return false;
    }
    
    // Check session timeout (30 minutes)
    $timeout = 1800;
    $timeSinceLastActivity = time() - $_SESSION['last_activity'];
    
    if ($timeSinceLastActivity > $timeout) {
        error_log(sprintf(
            'Authentication failed: Session expired. Last activity: %d, Current time: %d, Timeout: %d seconds',
            $_SESSION['last_activity'],
            time(),
            $timeout
        ));
        session_unset();
        session_destroy();
        return false;
    }
    
    // Verify IP address (commented out for local development)
    /*
    if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        error_log(sprintf(
            'Authentication failed: IP address changed. Session IP: %s, Current IP: %s',
            $_SESSION['ip_address'],
            $_SERVER['REMOTE_ADDR']
        ));
        return false;
    }
    */
    
    // Verify user agent
    if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        error_log(sprintf(
            'Authentication failed: User agent changed. Session UA: %s, Current UA: %s',
            $_SESSION['user_agent'],
            $_SERVER['HTTP_USER_AGENT']
        ));
        return false;
    }
    
    // Update last activity time
    $oldActivity = $_SESSION['last_activity'];
    $_SESSION['last_activity'] = time();
    
    error_log(sprintf(
        'Authentication successful for user: %s (ID: %s). Session active for %d seconds',
        $_SESSION['admin_username'],
        $_SESSION['admin_user_id'],
        time() - $oldActivity
    ));
    
    return true;
}

function requireAuth() {
    error_log('\n=== requireAuth() called ===');
    error_log('Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
    error_log('Request method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));
    error_log('Session status: ' . session_status());
    error_log('Session ID: ' . (session_id() ?: 'Not started'));
    error_log('Session name: ' . session_name());
    error_log('Headers already sent: ' . (headers_sent() ? 'Yes' : 'No'));
    
    // Log all request headers for debugging
    error_log('Request headers: ' . print_r(getallheaders(), true));
    
    // Check if user is authenticated
    $isAuth = isAuthenticated();
    error_log('isAuthenticated() returned: ' . ($isAuth ? 'true' : 'false'));
    
    if (!$isAuth) {
        error_log('User not authenticated, preparing redirect to login');
        
        // Check if this is an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($isAjax) {
            error_log('AJAX request detected, sending JSON response');
            // It's an AJAX request, return a JSON response
            if (!headers_sent()) {
                header('Content-Type: application/json');
                http_response_code(401);
            }
            
            $response = [
                'success' => false,
                'error' => 'Session expired. Please refresh the page and log in again.',
                'redirect' => 'login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']),
                'session_status' => session_status(),
                'session_id' => session_id()
            ];
            
            error_log('Sending JSON response: ' . json_encode($response));
            echo json_encode($response);
        } else {
            // Regular page request, redirect to login
            $redirect = '';
            if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] !== '/admin/login.php') {
                $redirect = '?redirect=' . urlencode($_SERVER['REQUEST_URI']);
            }
            
            $loginUrl = 'login.php' . $redirect;
            error_log('Redirecting to: ' . $loginUrl);
            
            // Ensure no output before header
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // Write and close session to avoid session lock
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            
            // Perform the redirect
            header('Location: ' . $loginUrl);
        }
        exit();
    }
    
    error_log('User is authenticated, allowing access');
}

// Database connection is now in admin_db.php

function login($username, $password) {
    try {
        error_log("\n=== Login attempt start ===");
        error_log("Username: $username");
        error_log('Session ID: ' . session_id());
        error_log('Session status: ' . session_status());
        error_log('Session name: ' . session_name());
        error_log('Cookie params: ' . print_r(session_get_cookie_params(), true));
        
        // Verify session is active
        if (session_status() !== PHP_SESSION_ACTIVE) {
            error_log('Session is not active, starting new session');
            @session_start();
            error_log('New session started. Session ID: ' . session_id());
        } else {
            error_log('Session is already active');
        }
        
        // Get database connection
        $pdo = getAdminDbConnection();
        
        // Prepare and execute query
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log('User not found');
            return false;
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            error_log('Invalid password');
            return false;
        }
        
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        // Set session data
        $_SESSION = []; // Clear existing session data
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        
        error_log('Session data set: ' . print_r($_SESSION, true));
        error_log('Session ID after setting data: ' . session_id());
        
        // Force session write
        session_write_close();
        error_log('Session written and closed. Session ID: ' . session_id());
        
        // Start session again to continue
        @session_start();
        
        // Update last login and IP
        try {
            $stmt = $pdo->prepare("UPDATE admin_users SET last_login = CURRENT_TIMESTAMP, last_ip = ? WHERE id = ?");
            $stmt->execute([$_SERVER['REMOTE_ADDR'], $user['id']]);
            
            // Get current session cookie params
            $cookieParams = session_get_cookie_params();
            
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Update session with user data
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['last_activity'] = time();
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            
            // Write session data and close it
            session_write_close();
            
            // Set the session cookie with updated parameters
            setcookie(
                session_name(),
                session_id(),
                [
                    'expires' => time() + 86400, // 24 hours
                    'path' => $cookieParams['path'],
                    'domain' => $cookieParams['domain'],
                    'secure' => $cookieParams['secure'],
                    'httponly' => true,
                    'samesite' => $cookieParams['samesite']
                ]
            );
            
            // Start the session again for the rest of the script
            @session_start();
            
        } catch (PDOException $e) {
            // Log the error but don't prevent login
            error_log('Error updating last login info: ' . $e->getMessage());
        }
        
        error_log('Login successful for user: ' . $user['username']);
        error_log('Final session data: ' . print_r($_SESSION, true));
        error_log('Final session ID: ' . session_id());
        
        // Verify session is still active
        if (session_status() !== PHP_SESSION_ACTIVE) {
            error_log('WARNING: Session is not active at end of login function');
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log('Login error: ' . $e->getMessage());
        return false;
    }
}

function logout() {
    session_unset();
    session_destroy();
    session_start(); // Start a new session for flash messages
}
