<?php
/**
 * Admin Database Connection
 * 
 * This file contains the database connection function for the admin panel.
 */

function getAdminDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dbPath = __DIR__ . '/../../../private/data/test_db.sqlite';
        
        // Ensure the data directory exists
        $dbDir = dirname($dbPath);
        if (!file_exists($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        try {
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Admin database connection failed: ' . $e->getMessage());
            throw $e;
        }
    }
    return $pdo;
}
