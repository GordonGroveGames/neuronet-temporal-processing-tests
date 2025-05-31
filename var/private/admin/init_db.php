<?php
// Include the database connection file
require_once __DIR__ . '/../includes/db.php';

$pdo = getDbConnection();

try {
    // Create tests table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            test_name TEXT NOT NULL UNIQUE,
            left_image TEXT,
            center_image TEXT,
            right_image TEXT,
            left_sound TEXT,
            center_sound TEXT,
            right_sound TEXT,
            correct_image TEXT,
            incorrect_image TEXT,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create test_parameters table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS test_parameters (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            test_id INTEGER NOT NULL,
            parameter_name TEXT NOT NULL,
            parameter_value TEXT,
            parameter_type TEXT DEFAULT 'string',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
            UNIQUE(test_id, parameter_name)
        )
    ");
    
    // Create admin user table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP
        )
    ");
    
    // Create default admin user (username: admin, password: admin123)
    $defaultUsername = 'admin';
    $defaultPassword = 'admin123';
    $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO admin_users (username, password_hash) VALUES (?, ?)");
    $stmt->execute([$defaultUsername, $passwordHash]);
    
    echo "Database initialized successfully!\n";
    echo "Admin user created. Username: admin, Password: admin123\n";
    echo "IMPORTANT: Change the default password after first login!\n";
    
} catch (Exception $e) {
    die("Error initializing database: " . $e->getMessage() . "\n");
}
