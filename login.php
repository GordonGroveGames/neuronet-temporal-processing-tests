<?php
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        // Load users from JSON file
        $usersFile = __DIR__ . '/assets/users.json';
        $users = [];
        
        if (file_exists($usersFile)) {
            $json = file_get_contents($usersFile);
            $users = json_decode($json, true) ?: [];
        }
        
        // Check credentials
        if (isset($users[$email]) && password_verify($password, $users[$email]['password'])) {
            $user = $users[$email];
            
            // Only allow test_takers to access the main site
            if ($user['role'] === 'test_taker') {
                $_SESSION['test_user_logged_in'] = true;
                $_SESSION['test_user'] = $user['name'] ?? $email;
                $_SESSION['test_user_email'] = $email;
                header('Location: index.php');
                exit();
            } else {
                $error = 'This login is only for test takers. Please use the admin login for other roles.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please enter both email and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - The Fluency Factor</title>
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
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card mt-5">
                <div class="card-header">
                    <h4 class="text-center">Test Taker Login</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword" title="Show password">
                                    <i class="fa fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            Admin users: <a href="admin_login.php">Login here</a>
                        </small>
                    </div>
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