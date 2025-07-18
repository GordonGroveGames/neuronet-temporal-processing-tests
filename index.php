<?php
require_once __DIR__ . '/check_test_session.php';
require_test_user_login();

$userInfo = get_test_user_info();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeuroNet Temporal Processing Tests</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f5f7fa;
        }
        
        h1 {
            color: #1a365d; /* Dark blue color */
            margin-bottom: 2rem;
            text-align: center;
            padding: 0 1rem;
        }
        
        .welcome-message {
            text-align: center;
            max-width: 500px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .welcome-message p {
            color: #2d3748;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .welcome-message p:first-child {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a365d;
        }
        
        .continue-btn {
            background-color: #2c5282; /* Dark blue color */
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 1.1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .continue-btn:hover {
            background-color: #2b6cb0; /* Slightly lighter blue on hover */
        }
    </style>
</head>
<body>
    <div style="position: absolute; top: 20px; right: 20px;">
        <span class="text-muted">Logged in as: <?= htmlspecialchars($userInfo['username']) ?></span>
        <a href="logout.php" style="margin-left: 10px; color: #e53e3e; text-decoration: none;">Logout</a>
    </div>
    
    <h1>NeuroNet Temporal Processing Tests</h1>
    
    <div class="welcome-message">
        <p>Welcome, <?= htmlspecialchars($userInfo['username']) ?>!</p>
        <p>You are ready to begin the NeuroNet Temporal Processing Tests.</p>
        <a href="test.php" class="continue-btn">Start Test</a>
    </div>

</body>
</html>
