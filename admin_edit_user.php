<?php
require_once __DIR__ . '/admin_session.php';
require_role(['admin', 'site_admin']);

header('Content-Type: application/json');

// Suppress any notices or warnings that might contaminate JSON output
ini_set('display_errors', 0);
error_reporting(0);
ob_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    $originalEmail = trim($data['original_email'] ?? '');
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $role = trim($data['role'] ?? '');
    $createdBy = isset($data['created_by']) ? $data['created_by'] : 'no_change';
    
    // Validate required fields
    if (empty($originalEmail) || empty($name) || empty($email) || empty($role)) {
        throw new Exception('All fields except password are required');
    }
    
    // Validate name format
    if (!preg_match('/^[a-zA-Z\s\'\-\.]+$/', $name)) {
        throw new Exception('Name can only contain letters, spaces, apostrophes, hyphens, and periods');
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Validate role
    $validRoles = ['test_taker', 'test_creator', 'site_admin'];
    if (!in_array($role, $validRoles)) {
        throw new Exception('Invalid role');
    }
    
    // Load users
    $usersFile = __DIR__ . '/assets/users.json';
    if (!file_exists($usersFile)) {
        throw new Exception('Users file not found');
    }
    
    $users = json_decode(file_get_contents($usersFile), true);
    if (!$users) {
        throw new Exception('Failed to load users');
    }
    
    // Check if original user exists
    if (!isset($users[$originalEmail])) {
        throw new Exception('User not found');
    }
    
    $currentUser = $users[$originalEmail];
    
    // Check permissions
    $currentUserRole = get_user_role();
    $currentUserEmail = $_SESSION['admin_user'];
    
    if ($currentUserRole === 'site_admin' && $currentUser['role'] === 'admin') {
        throw new Exception('You do not have permission to edit this user');
    }
    
    // Test creators can only edit users they created (excluding admin users) or themselves
    if ($currentUserRole === 'test_creator') {
        $canEdit = ($originalEmail === $currentUserEmail) || 
                   (isset($currentUser['created_by']) && $currentUser['created_by'] === $currentUserEmail && $currentUser['role'] !== 'admin');
        
        if (!$canEdit) {
            throw new Exception('You can only edit users that you created');
        }
    }
    
    // Check if new email already exists (if email changed)
    if ($email !== $originalEmail && isset($users[$email])) {
        throw new Exception('Email already exists');
    }
    
    // Update user data
    $updatedUser = $currentUser;
    $updatedUser['name'] = $name;
    $updatedUser['email'] = $email;
    $updatedUser['role'] = $role;
    
    // Update password if provided
    if (!empty($password)) {
        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters long');
        }
        $updatedUser['password'] = password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Handle created_by based on role
    if ($role === 'test_taker') {
        // Only test_takers can have created_by values
        if ($createdBy !== 'no_change' && in_array($currentUserRole, ['admin', 'site_admin'])) {
            if ($createdBy === null || empty($createdBy)) {
                // Remove created_by assignment (System/admin equivalent)
                unset($updatedUser['created_by']);
            } else {
                // Validate that the creator exists and has appropriate role
                if (isset($users[$createdBy])) {
                    $creatorRole = $users[$createdBy]['role'];
                    if (in_array($creatorRole, ['admin', 'site_admin', 'test_creator'])) {
                        $updatedUser['created_by'] = $createdBy;
                    } else {
                        throw new Exception('Invalid creator: User must be admin, site admin, or test creator');
                    }
                } else {
                    throw new Exception('Invalid creator: User does not exist');
                }
            }
        }
        // If no created_by change requested but user already has one, keep it
    } else {
        // Non-test_taker roles should never have created_by values
        unset($updatedUser['created_by']);
    }
    
    // If email changed, remove old entry and add new one
    if ($email !== $originalEmail) {
        unset($users[$originalEmail]);
        $users[$email] = $updatedUser;
        
        // Update session if user is editing themselves
        if ($_SESSION['admin_user'] === $originalEmail) {
            $_SESSION['admin_user'] = $email;
        }
    } else {
        // Just update the existing user
        $users[$email] = $updatedUser;
    }
    
    // Save users file
    if (!file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
        throw new Exception('Failed to save users file');
    }
    
    // Clean any output buffer
    ob_end_clean();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // Clean any output buffer
    ob_end_clean();
    
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>