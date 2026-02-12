<?php
require_once __DIR__ . '/admin_session.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    // Load users to check role
    $usersFile = __DIR__ . '/assets/users.json';
    $users = [];
    if (file_exists($usersFile)) {
        $json = file_get_contents($usersFile);
        $users = json_decode($json, true) ?: [];
    }
    
    if (admin_login($email, $password)) {
        // Check if user should use test taker login instead
        if (isset($users[$email]) && $users[$email]['role'] === 'test_taker') {
            $error = 'Test takers should use the main site login. <a href="login.php">Click here</a>';
        } else {
            header('Location: admin_panel.php');
            exit();
        }
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="assets/css/touch-fixes.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body.login-bg {
            background: url('assets/images/fluencyfactor-bg.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            -webkit-touch-callout: none;
            touch-action: manipulation;
        }
        @media (pointer: coarse) {
            .form-control {
                min-height: 44px;
                font-size: 1rem;
            }
            .btn {
                min-height: 44px;
            }
            #togglePassword {
                min-width: 44px;
                min-height: 44px;
            }
        }
    </style>
</head>
<body class="login-bg">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header text-center">Admin Login</div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <div class="mb-3">
                            <label for="email" class="form-label">Username/Email</label>
                            <input type="text" class="form-control" id="email" name="email" required autofocus 
                                   placeholder="Enter username or email">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword" title="Show password">
                                    <i class="fa fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
        this.title = 'Hide password';
    } else {
        pwd.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
        this.title = 'Show password';
    }
});
</script>
</body>
</html>
