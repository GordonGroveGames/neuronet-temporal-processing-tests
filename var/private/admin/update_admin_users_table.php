<?php
// Include database connection
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = getDbConnection();
    
    // Check if the column already exists
    $stmt = $pdo->query("PRAGMA table_info(admin_users)");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
    
    if (!in_array('last_ip', $columns)) {
        // Add the last_ip column
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN last_ip TEXT");
        echo "Successfully added 'last_ip' column to admin_users table.\n";
    } else {
        echo "The 'last_ip' column already exists in the admin_users table.\n";
    }
    
    // Also check and add last_login if it doesn't exist
    if (!in_array('last_login', $columns)) {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "Successfully added 'last_login' column to admin_users table.\n";
    }
    
    echo "Database update completed successfully.\n";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}

// Also update the login function to use the new column
$authFile = __DIR__ . '/includes/auth.php';
$authContent = file_get_contents($authFile);

// Remove the column existence check since we're adding it now
$search = "// First check if last_ip column exists\n            \$stmt = \$pdo->prepare(\"PRAGMA table_info(admin_users)\");\n            \$stmt->execute();\n            \$columns = \$stmt->fetchAll(PDO::FETCH_COLUMN, 1);\n            \$hasLastIpColumn = in_array('last_ip', \$columns);\n            \n            // Prepare the update query\n            if (\$hasLastIpColumn) {\n                \$stmt = \$pdo->prepare(\"UPDATE admin_users SET last_login = CURRENT_TIMESTAMP, last_ip = ? WHERE id = ?\");\n                \$stmt->execute([\$_SERVER['REMOTE_ADDR'], \$user['id']]);\n            } else {\n                \$stmt = \$pdo->prepare(\"UPDATE admin_users SET last_login = CURRENT_TIMESTAMP WHERE id = ?\");\n                \$stmt->execute([\$user['id']]);\n            }";

$replace = "// Update last login and IP\n            \$stmt = \$pdo->prepare(\"UPDATE admin_users SET last_login = CURRENT_TIMESTAMP, last_ip = ? WHERE id = ?\");\n            \$stmt->execute([\$_SERVER['REMOTE_ADDR'], \$user['id']])";

$authContent = str_replace($search, $replace, $authContent);

// Save the updated auth file
file_put_contents($authFile, $authContent);
echo "Auth file updated to use the new columns.\n";

echo "All updates completed successfully!\n";
