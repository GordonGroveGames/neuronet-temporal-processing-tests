<?php
require_once __DIR__ . '/admin_session.php';
require_admin_login();

// Only admins and site admins can delete test results
if (!in_array(get_user_role(), ['admin', 'site_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid request data');
    }
    
    $action = $input['action'] ?? '';
    
    // Load test results from database
    $dbFile = __DIR__ . '/var/data/test_results.db';
    
    if (!file_exists($dbFile)) {
        throw new Exception('Test results database not found');
    }
    
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $deletedCount = 0;
    
    switch ($action) {
        case 'delete_user_results':
            $email = $input['email'] ?? '';
            if (empty($email)) {
                throw new Exception('Email is required');
            }
            
            // Delete all test results for this user
            $stmt = $pdo->prepare("DELETE FROM test_results WHERE email = ?");
            $stmt->execute([$email]);
            $deletedCount = $stmt->rowCount();
            
            $message = "Deleted $deletedCount test result(s) for user: $email";
            break;
            
        case 'delete_multiple_users':
            $emails = $input['emails'] ?? [];
            if (empty($emails) || !is_array($emails)) {
                throw new Exception('Email list is required');
            }
            
            // Delete test results for multiple users
            $placeholders = str_repeat('?,', count($emails) - 1) . '?';
            $stmt = $pdo->prepare("DELETE FROM test_results WHERE email IN ($placeholders)");
            $stmt->execute($emails);
            $deletedCount = $stmt->rowCount();
            
            $userCount = count($emails);
            $message = "Deleted $deletedCount test result(s) for $userCount user(s)";
            break;
            
        case 'delete_test_session':
            $email = $input['email'] ?? '';
            $sessionId = $input['session_id'] ?? '';
            
            if (empty($email)) {
                throw new Exception('Email is required');
            }
            if (empty($sessionId)) {
                throw new Exception('Session ID is required');
            }
            
            // Delete all test results for this specific test session
            $stmt = $pdo->prepare("DELETE FROM test_results WHERE email = ? AND session_id = ?");
            $stmt->execute([$email, $sessionId]);
            $deletedCount = $stmt->rowCount();
            
            $message = "Deleted test session ($deletedCount result(s)) for user: $email";
            break;
            
        case 'delete_date_range':
            $dateFrom = $input['date_from'] ?? '';
            $dateTo = $input['date_to'] ?? '';
            
            if (empty($dateFrom) && empty($dateTo)) {
                throw new Exception('At least one date is required');
            }
            
            // Build date range query
            $whereClause = [];
            $params = [];
            
            if ($dateFrom) {
                $whereClause[] = "date(timestamp) >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $whereClause[] = "date(timestamp) <= ?";
                $params[] = $dateTo;
            }
            
            $sql = "DELETE FROM test_results WHERE " . implode(' AND ', $whereClause);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $deletedCount = $stmt->rowCount();
            
            $message = "Deleted $deletedCount test result(s) from date range";
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
    // Log the deletion for audit purposes
    error_log("ADMIN DELETE: User " . $_SESSION['admin_user'] . " deleted $deletedCount test results. Action: $action");
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'deleted_count' => $deletedCount
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>