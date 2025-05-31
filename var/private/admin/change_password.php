<?php
require_once __DIR__ . '/../../includes/db.php';

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

// Get username and new password from command line arguments
$options = getopt("u:p:", ["username:", "password:"]);
$username = $options['u'] ?? $options['username'] ?? null;
$password = $options['p'] ?? $options['password'] ?? null;

if (!$username || !$password) {
    echo "Usage: php change_password.php -u <username> -p <new_password>\n";
    echo "   or: php change_password.php --username=<username> --password=<new_password>\n";
    exit(1);
}

try {
    $pdo = getDbConnection();
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("Error: User '$username' not found.\n");
    }
    
    // Update password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE username = ?");
    $stmt->execute([$passwordHash, $username]);
    
    echo "Password for user '$username' has been updated successfully.\n";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
