<?php
// Test script to check file system permissions
header('Content-Type: text/plain');

echo "=== PHP File System Permissions Test ===\n\n";

// Check if we can write to the current directory
$currentDir = __DIR__;
echo "Current directory: $currentDir\n";
echo "Is writable: " . (is_writable($currentDir) ? 'Yes' : 'No') . "\n\n";

// Test creating and writing to a file
$testFile = __DIR__ . '/test_write.txt';
$testContent = "Test content at " . date('Y-m-d H:i:s') . "\n";

try {
    $bytes = file_put_contents($testFile, $testContent, FILE_APPEND);
    
    if ($bytes === false) {
        echo "Failed to write to test file\n";
    } else {
        echo "Successfully wrote $bytes bytes to test file\n";
        echo "Test file content:\n" . file_get_contents($testFile) . "\n";
        
        // Clean up
        if (unlink($testFile)) {
            echo "Test file deleted successfully\n";
        } else {
            echo "Failed to delete test file\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test creating and writing to logs directory
$logsDir = __DIR__ . '/logs';
$logFile = $logsDir . '/test_log.log';

echo "\n=== Logs Directory Test ===\n";
echo "Logs directory: $logsDir\n";

// Check if logs directory exists
if (!file_exists($logsDir)) {
    echo "Logs directory does not exist, attempting to create...\n";
    
    if (mkdir($logsDir, 0755, true)) {
        echo "Successfully created logs directory\n";
    } else {
        echo "Failed to create logs directory\n";
        exit;
    }
}

echo "Logs directory permissions: " . substr(sprintf('%o', fileperms($logsDir)), -4) . "\n";
echo "Is writable: " . (is_writable($logsDir) ? 'Yes' : 'No') . "\n";

// Test writing to log file
try {
    $logContent = "Test log entry at " . date('Y-m-d H:i:s') . "\n";
    $bytes = file_put_contents($logFile, $logContent, FILE_APPEND);
    
    if ($bytes === false) {
        echo "Failed to write to log file\n";
    } else {
        echo "Successfully wrote $bytes bytes to log file\n";
        echo "Log file content:\n" . file_get_contents($logFile) . "\n";
    }
} catch (Exception $e) {
    echo "Error writing to log file: " . $e->getMessage() . "\n";
}

// Show PHP user and group
echo "\n=== PHP Process User ===\n";
if (function_exists('posix_getpwuid')) {
    $processUser = posix_getpwuid(posix_geteuid());
    echo "Running as user: " . $processUser['name'] . " (UID: " . $processUser['uid'] . ")\n";
} else {
    echo "posix functions not available\n";
}

echo "\n=== Directory Listing ===\n";
echo "Current directory contents:\n";
$files = scandir(__DIR__);
foreach ($files as $file) {
    echo "- $file\n";
}
