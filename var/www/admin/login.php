<?php
// Start output buffering to prevent headers already sent issues
ob_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set error log path
$logDir = __DIR__ . '/../../../logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}
$errorLog = $logDir . '/php_errors.log';
ini_set('error_log', $errorLog);

// Log the start of the script
error_log("\n=== Login page accessed at " . date('Y-m-d H:i:s') . " ===");
error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Request URI: ' . $_SERVER['REQUEST_URI']);

// Include required files
require_once __DIR__ . '/../private/admin/includes/admin_db.php';
require_once __DIR__ . '/../private/admin/includes/auth.php';

// Log session status before any output
error_log('\n======= Login Page Loaded =======');
error_log('Session ID: ' . (session_id() ?: 'Not started'));
error_log('Session status: ' . session_status());
if (isset($_COOKIE[session_name()])) {
    error_log('Session cookie found: ' . $_COOKIE[session_name()]);
} else {
    error_log('No session cookie found');
}
error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
error_log('POST data: ' . print_r($_POST, true));
error_log('GET data: ' . print_r($_GET, true));

// Initialize variables
$error = '';
$username = '';

// Check if already logged in
error_log("\n=== Checking if already authenticated ===");
$isAuth = isAuthenticated();
error_log('isAuthenticated() returned: ' . ($isAuth ? 'true' : 'false'));
if ($isAuth) {
    error_log('Already authenticated, redirecting to index.php');
    error_log('Session data: ' . print_r($_SESSION, true));
    
    // Ensure no output before header
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header('Location: index.php');
    exit();
} else {
    error_log('Not authenticated, showing login form');
    error_log('Session data: ' . print_r($_SESSION, true));
}

// Check if there are any PHP errors in the error log
$errorLog = ini_get('error_log');
if (file_exists($errorLog)) {
    $debugInfo['error_log'] = 'Error log exists at: ' . $errorLog;
    $debugInfo['last_modified'] = 'Last modified: ' . date('Y-m-d H:i:s', filemtime($errorLog));
} else {
    $debugInfo['error_log'] = 'Error log not found at: ' . $errorLog;
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("\n=== Processing login form ===");
    error_log('Session ID: ' . session_id());
    error_log('Session status: ' . session_status());
    error_log('Session data: ' . print_r($_SESSION, true));
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    error_log('Login attempt - Username: ' . $username);
    
    error_log('Login attempt for username: ' . $username);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
        error_log('Validation failed: ' . $error);
    } else {
        error_log('Calling login() function...');
        $loginResult = login($username, $password);
        error_log('login() returned: ' . ($loginResult ? 'true' : 'false'));
        
        if ($loginResult) {
            error_log('Login successful for user: ' . $username);
            error_log('Session after login: ' . print_r($_SESSION, true));
            
            // Handle redirect
            $redirect = 'index.php';
            if (!empty($_GET['redirect'])) {
                $requested = filter_var($_GET['redirect'], FILTER_SANITIZE_URL);
                error_log('Original redirect: ' . $requested);
                // Basic security check for redirect
                if (strpos($requested, '..') === false && strpos($requested, '//') === false) {
                    $redirect = $requested;
                }
            }
            
            error_log('Final redirect URL: ' . $redirect);
            error_log('Session ID before redirect: ' . session_id());
            
            // Ensure no output before header
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            error_log('Sending redirect to: ' . $redirect);
            error_log('Headers already sent: ' . (headers_sent() ? 'Yes' : 'No'));
            error_log('Current headers: ' . print_r(headers_list(), true));
            error_log('Session data before redirect: ' . print_r($_SESSION, true));
            
            // Set a debug cookie to verify client-side cookies are working
            setcookie('debug_cookie', 'test_value', [
                'expires' => time() + 3600,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            // Ensure session is written
            session_write_close();
            
            // Perform the redirect
            header('Location: ' . $redirect);
            error_log('Redirect header sent to: ' . $redirect);
            exit();
        } else {
            error_log('Login failed for user: ' . $username);
            $error = 'Invalid username or password';
            error_log('Login failed: ' . $error);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - NeuroNet Tests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #3498db;
            color: white;
            text-align: center;
            padding: 20px;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-primary {
            background-color: #3498db;
            border: none;
            padding: 10px 20px;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <h3>NeuroNet Tests Admin</h3>
            </div>
            <div class="card-body p-4">
                <?php 
                // Debug information
                $debugOutput = [];
                
                // Session info
                $debugOutput[] = "Session Status: " . session_status();
                $debugOutput[] = "Session ID: " . session_id();
                $debugOutput[] = "Session Data: " . print_r($_SESSION, true);
                
                // Check if the user exists in the database
                if (!empty($username)) {
                    try {
                        $pdo = getAdminDbConnection();
                        $debugOutput[] = "Database connection successful";
                        
                        // Check if table exists
                        $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='admin_users'")->fetchColumn();
                        $debugOutput[] = "Table 'admin_users' exists: " . ($tableExists ? 'Yes' : 'No');
                        
                        if ($tableExists) {
                            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ?");
                            $stmt->execute([$username]);
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                            $debugOutput[] = "User in database: " . ($user ? 'Found' : 'Not found');
                            
                            if ($user) {
                                $debugOutput[] = "User ID: " . $user['id'];
                                $debugOutput[] = "Password hash: " . substr($user['password_hash'], 0, 20) . "...";
                                
                                // Test password verification
                                $passwordMatch = isset($user['password_hash']) && 
                                              password_verify($_POST['password'] ?? '', $user['password_hash']);
                                $debugOutput[] = "Password verification: " . ($passwordMatch ? 'Success' : 'Failed');
                                
                                if (!$passwordMatch) {
                                    $debugOutput[] = "Password provided: " . ($_POST['password'] ?? 'Not provided');
                                    $debugOutput[] = "Hash of provided password: " . 
                                        password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
                                }
                            } else {
                                // List all users in the database
                                $allUsers = $pdo->query("SELECT username FROM admin_users")->fetchAll(PDO::FETCH_COLUMN);
                                $debugOutput[] = "All users in database: " . implode(', ', $allUsers);
                            }
                        }
                    } catch (Exception $e) {
                        $debugOutput[] = "Database error: " . $e->getMessage();
                    }
                }
                
                // Display error message if any
                if ($error): ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                        
                        <hr>
                        <h6>Debug Information:</h6>
                        <pre class="bg-light p-2 small"><?php 
                            echo implode("\n", array_map('htmlspecialchars', $debugOutput));
                        ?></pre>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
                
                <!-- Login form footer removed -->
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
