<?php
// Suppress notices and warnings for clean JSON output
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Start output buffering
ob_start();

require_once __DIR__ . '/admin_session.php';
require_role(['admin', 'site_admin']);

// Clear any unwanted output
ob_end_clean();

// Start fresh output buffer
ob_start();

header('Content-Type: application/json');

$response = ['success' => false];

try {
    // Get the request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['name']) || !isset($data['email']) || !isset($data['password']) || !isset($data['role'])) {
        throw new Exception('Name, email, password, and role are required');
    }
    
    $name = trim($data['name']);
    $email = trim($data['email']);
    $password = $data['password'];
    $role = $data['role'];
    
    // Validate input
    if (empty($name)) {
        throw new Exception('Name cannot be empty');
    }
    
    if (empty($email)) {
        throw new Exception('Email cannot be empty');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters long');
    }
    
    if (!in_array($role, ['test_taker', 'test_creator', 'site_admin'])) {
        throw new Exception('Invalid role specified');
    }
    
    // Validate name format (allow spaces, letters, and common punctuation)
    if (!preg_match('/^[a-zA-Z\s\'\-\.]+$/', $name)) {
        throw new Exception('Name can only contain letters, spaces, apostrophes, hyphens, and periods');
    }
    
    $usersFile = __DIR__ . '/assets/users.json';
    
    // Load existing users
    $users = [];
    if (file_exists($usersFile)) {
        $json = file_get_contents($usersFile);
        $users = json_decode($json, true) ?: [];
    }
    
    // Check if email already exists (email is now the key)
    if (isset($users[$email])) {
        throw new Exception('Email address already exists');
    }
    
    // Create new user (using email as key)
    $newUser = [
        'name' => $name,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Only set created_by for test_taker role
    if ($role === 'test_taker') {
        $newUser['created_by'] = $_SESSION['admin_user'];
    }
    
    $users[$email] = $newUser;
    
    // Save the updated users
    if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Failed to save users file');
    }
    
    $response['success'] = true;
    $response['message'] = 'User created successfully';
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Clean any remaining output and send JSON
ob_end_clean();
echo json_encode($response);
exit;
?>