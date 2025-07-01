<?php
// Suppress notices and warnings for clean JSON output
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Start output buffering
ob_start();

require_once __DIR__ . '/admin_session.php';
require_admin_login();

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
    
    if (!isset($data['current_password']) || !isset($data['new_password'])) {
        throw new Exception('Current password and new password are required');
    }
    
    $currentPassword = $data['current_password'];
    $newPassword = $data['new_password'];
    $currentUser = $_SESSION['admin_user'];
    
    // Validate new password
    if (strlen($newPassword) < 6) {
        throw new Exception('New password must be at least 6 characters long');
    }
    
    $usersFile = __DIR__ . '/assets/users.json';
    
    // Load existing users
    $users = [];
    if (file_exists($usersFile)) {
        $json = file_get_contents($usersFile);
        $users = json_decode($json, true) ?: [];
    }
    
    // Check if user exists
    if (!isset($users[$currentUser])) {
        throw new Exception('User not found');
    }
    
    // Verify current password
    if (!password_verify($currentPassword, $users[$currentUser]['password'])) {
        throw new Exception('Current password is incorrect');
    }
    
    // Update password
    $users[$currentUser]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
    $users[$currentUser]['updated_at'] = date('Y-m-d H:i:s');
    
    // Save the updated users
    if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Failed to save users file');
    }
    
    $response['success'] = true;
    $response['message'] = 'Password changed successfully';
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Clean any remaining output and send JSON
ob_end_clean();
echo json_encode($response);
exit;
?>