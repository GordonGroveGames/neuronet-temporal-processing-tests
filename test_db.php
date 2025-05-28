<?php
// Test database connection and permissions
$dbDir = __DIR__ . '/var/data';
$dbPath = $dbDir . '/test_results.db';

// Check if directory exists and is writable
if (!is_dir($dbDir)) {
    echo "Creating directory: $dbDir\n";
    if (!mkdir($dbDir, 0777, true)) {
        die("Failed to create directory: $dbDir\n");
    }
    echo "Directory created successfully\n";
}

echo "Directory permissions: " . substr(sprintf('%o', fileperms($dbDir)), -4) . "\n";

// Try to create the database file if it doesn't exist
if (!file_exists($dbPath)) {
    echo "Creating database file: $dbPath\n";
    touch($dbPath);
    if (!file_exists($dbPath)) {
        die("Failed to create database file\n");
    }
    chmod($dbPath, 0666);
}

echo "Database file permissions: " . substr(sprintf('%o', fileperms($dbPath)), -4) . "\n";

// Test database connection
try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test creating a table
    $pdo->exec('CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY, name TEXT)');
    
    // Test inserting data
    $stmt = $pdo->prepare('INSERT INTO test (name) VALUES (:name)');
    $stmt->execute([':name' => 'test']);
    
    // Test reading data
    $result = $pdo->query('SELECT * FROM test')->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Database test successful!\n";
    echo "Test data: " . print_r($result, true) . "\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    if (isset($e->errorInfo)) {
        echo "Error info: " . print_r($e->errorInfo, true) . "\n";
    }
}
