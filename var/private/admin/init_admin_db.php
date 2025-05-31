<?php
// Initialize the admin database
$dbPath = __DIR__ . '/../../private/data/test_db.sqlite';

// Ensure the data directory exists
$dbDir = dirname($dbPath);
if (!file_exists($dbDir)) {
    mkdir($dbDir, 0755, true);
}

// Remove existing database if it exists
if (file_exists($dbPath)) {
    unlink($dbPath);
}

// Create a new database
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create admin_users table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        last_login TIMESTAMP,
        last_ip TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Create default admin user (username: admin, password: admin123)
$username = 'admin';
$password = 'admin123';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
$stmt->execute([$username, $passwordHash]);

echo "Admin database initialized successfully.\n";
echo "Default admin credentials:\n";
echo "Username: admin\n";
echo "Password: admin123\n";

// Set proper permissions
chmod($dbPath, 0666);
chmod(dirname($dbPath), 0777);
