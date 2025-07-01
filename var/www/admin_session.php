<?php
// --- Session and Authentication Helper ---

if (session_status() === PHP_SESSION_NONE) {
    session_name('neuro_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Location: admin_login.php');
        exit();
    }
}

function get_user_role() {
    return $_SESSION['admin_role'] ?? null;
}

function require_role($allowedRoles) {
    require_admin_login();
    $userRole = get_user_role();
    
    if (!in_array($userRole, $allowedRoles)) {
        header('HTTP/1.0 403 Forbidden');
        echo '<h1>403 Forbidden</h1><p>You do not have permission to access this resource.</p>';
        exit();
    }
}

function can_access_admin_panel() {
    $userRole = get_user_role();
    return in_array($userRole, ['admin', 'site_admin', 'test_creator']);
}

function can_manage_users() {
    $userRole = get_user_role();
    return in_array($userRole, ['admin', 'site_admin']);
}

function can_manage_assessments() {
    $userRole = get_user_role();
    return in_array($userRole, ['admin', 'site_admin', 'test_creator']);
}

function admin_login($email, $password) {
    // Load users from JSON file
    $usersFile = __DIR__ . '/assets/users.json';
    $users = [];
    
    if (file_exists($usersFile)) {
        $json = file_get_contents($usersFile);
        $users = json_decode($json, true) ?: [];
    }
    
    // If no users file exists, create with default admin user
    if (empty($users)) {
        $users = [
            'admin@example.com' => [
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'admin',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    }
    
    // Check credentials
    if (isset($users[$email]) && password_verify($password, $users[$email]['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $email;
        $_SESSION['admin_role'] = $users[$email]['role'];
        return true;
    }
    
    return false;
}

function admin_logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
