<?php
// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set default timezone
date_default_timezone_set('UTC');

// Database path
$dbPath = __DIR__ . '/../data/test_results.db';

// Create data directory if it doesn't exist
$dataDir = dirname($dbPath);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

try {
    // Connect to SQLite database (this will create the file if it doesn't exist)
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Enable foreign key constraints
    $pdo->exec('PRAGMA foreign_keys = ON');
    
    // Drop existing table if it exists (for testing)
    // $pdo->exec('DROP TABLE IF EXISTS test_results');
    
    // Create test_results table if it doesn't exist
    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS test_results (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        userID TEXT NOT NULL,
        fullName TEXT,
        email TEXT,
        test_name TEXT NOT NULL,
        prompt_number INTEGER NOT NULL,
        user_answer TEXT NOT NULL,
        correct_answer TEXT NOT NULL,
        response_time INTEGER,
        session_id TEXT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(userID, test_name, prompt_number, session_id) ON CONFLICT REPLACE
    )
SQL;
    
    $pdo->exec($sql);
    
    // Create an index on userID and session_id for faster lookups
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_user_session ON test_results (userID, session_id)');
    
    // Verify the table was created
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='test_results'");
    $tableExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tableExists) {
        echo "Database initialized successfully!\n";
        echo "Table 'test_results' is ready for use.\n";
    } else {
        throw new Exception("Failed to create 'test_results' table");
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

// Test the connection
if (file_exists($dbPath)) {
    echo "Database file created at: " . realpath($dbPath) . "\n";
    echo "File permissions: " . substr(sprintf('%o', fileperms($dbPath)), -4) . "\n";
} else {
    echo "Warning: Database file was not created!\n";
}
