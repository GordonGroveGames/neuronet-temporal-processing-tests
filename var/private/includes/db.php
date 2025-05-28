<?php
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dbDir = __DIR__ . '/../../data';
        $dbPath = $dbDir . '/test_results.db';
        
        // Ensure the data directory exists
        if (!file_exists($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        try {
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA foreign_keys = ON');
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw $e;
        }
    }
    
    return $pdo;
}

// Helper function to execute a query with parameters
function executeQuery($query, $params = []) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

// Helper function to fetch a single row
function fetchOne($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper function to fetch all rows
function fetchAll($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
