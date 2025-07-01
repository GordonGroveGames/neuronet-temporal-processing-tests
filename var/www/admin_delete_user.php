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
    
    if (!isset($data['email'])) {
        throw new Exception('Email is required');
    }
    
    $email = $data['email'];
    $currentUser = $_SESSION['admin_user'];
    
    // Prevent users from deleting themselves
    if ($email === $currentUser) {
        throw new Exception('Cannot delete your own account');
    }
    
    $usersFile = __DIR__ . '/assets/users.json';
    
    // Load existing users
    $users = [];
    if (file_exists($usersFile)) {
        $json = file_get_contents($usersFile);
        $users = json_decode($json, true) ?: [];
    }
    
    // Check if user exists
    if (!isset($users[$email])) {
        throw new Exception('User not found');
    }
    
    $userToDelete = $users[$email];
    
    // Check permissions
    $currentUserRole = get_user_role();
    $currentUserEmail = $_SESSION['admin_user'];
    
    // Test creators can only delete users they created (excluding admin users)
    if ($currentUserRole === 'test_creator') {
        $canDelete = (isset($userToDelete['created_by']) && $userToDelete['created_by'] === $currentUserEmail && $userToDelete['role'] !== 'admin');
        
        if (!$canDelete) {
            throw new Exception('You can only delete users that you created');
        }
    }
    
    // Site admins cannot delete admin users
    if ($currentUserRole === 'site_admin' && $userToDelete['role'] === 'admin') {
        throw new Exception('You do not have permission to delete this user');
    }
    
    // Remove the user
    unset($users[$email]);
    
    // Save the updated users
    if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Failed to save users file');
    }
    
    $response['success'] = true;
    $response['message'] = 'User deleted successfully';
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Clean any remaining output and send JSON
ob_end_clean();
echo json_encode($response);
exit;
?>