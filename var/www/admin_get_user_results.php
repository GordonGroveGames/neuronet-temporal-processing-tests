<?php
require_once __DIR__ . '/admin_session.php';
require_admin_login();

// Check if user can access admin panel
if (!can_access_admin_panel()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    // Load users to check permissions
    $usersFile = __DIR__ . '/assets/users.json';
    $users = [];
    if (file_exists($usersFile)) {
        $json = file_get_contents($usersFile);
        $users = json_decode($json, true) ?: [];
    }
    
    $currentUserRole = get_user_role();
    $currentUserEmail = $_SESSION['admin_user'];
    
    // Check if current user can view this user's results
    $canView = false;
    
    if (in_array($currentUserRole, ['admin', 'site_admin'])) {
        // Admins and site admins can view all results
        $canView = true;
    } elseif ($currentUserRole === 'test_creator') {
        // Test creators can only view results from users they created
        if (isset($users[$email]) && 
            isset($users[$email]['created_by']) && 
            $users[$email]['created_by'] === $currentUserEmail) {
            $canView = true;
        }
    }
    
    if (!$canView) {
        throw new Exception('You do not have permission to view this user\'s results');
    }
    
    // Load test results from database
    $dbFile = dirname(__DIR__) . '/data/test_results.db';
    
    if (!file_exists($dbFile)) {
        throw new Exception('Test results database not found');
    }
    
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all test results for this user
    $query = "
        SELECT 
            test_name,
            session_id,
            timestamp,
            user_answer,
            correct_answer,
            response_time
        FROM test_results 
        WHERE email = ?
        ORDER BY timestamp
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$email]);
    $allResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allResults)) {
        echo json_encode(['success' => true, 'results' => []]);
        exit();
    }
    
    // Group results by session (each session represents one complete test)
    $sessionGroups = [];
    foreach ($allResults as $result) {
        $sessionId = $result['session_id'];
        if (!isset($sessionGroups[$sessionId])) {
            $sessionGroups[$sessionId] = [
                'testName' => $result['test_name'],
                'testStartTime' => $result['timestamp'],
                'testEndTime' => $result['timestamp'],
                'results' => []
            ];
        } else {
            // Update end time as we process more results
            $sessionGroups[$sessionId]['testEndTime'] = $result['timestamp'];
        }
        $sessionGroups[$sessionId]['results'][] = $result;
    }
    
    // Calculate statistics for each test session
    $processedResults = [];
    
    foreach ($sessionGroups as $sessionId => $session) {
        $leftCorrect = 0;
        $leftIncorrect = 0;
        $centerCorrect = 0;
        $centerIncorrect = 0;
        $rightCorrect = 0;
        $rightIncorrect = 0;
        $totalResponseTime = 0;
        $responseCount = 0;
        
        foreach ($session['results'] as $result) {
            $isCorrect = ($result['user_answer'] === $result['correct_answer']);
            $totalResponseTime += $result['response_time'];
            $responseCount++;
            
            switch ($result['correct_answer']) {
                case 'Left':
                    if ($isCorrect) {
                        $leftCorrect++;
                    } else {
                        $leftIncorrect++;
                    }
                    break;
                case 'Center':
                    if ($isCorrect) {
                        $centerCorrect++;
                    } else {
                        $centerIncorrect++;
                    }
                    break;
                case 'Right':
                    if ($isCorrect) {
                        $rightCorrect++;
                    } else {
                        $rightIncorrect++;
                    }
                    break;
            }
        }
        
        // Calculate test duration in milliseconds (sum of all response times)
        // This represents total time from first sound to final click
        $testTimeMs = $totalResponseTime;
        
        // Calculate average response time
        $avgResponseTime = $responseCount > 0 ? round($totalResponseTime / $responseCount) : 0;
        
        // Create individual trial data for score bar visualization
        $trialResults = [];
        foreach ($session['results'] as $result) {
            $trialResults[] = [
                'correct' => ($result['user_answer'] === $result['correct_answer']),
                'userAnswer' => $result['user_answer'],
                'correctAnswer' => $result['correct_answer'],
                'responseTime' => $result['response_time']
            ];
        }
        
        $processedResults[] = [
            'sessionId' => $sessionId,
            'testName' => $session['testName'],
            'testDate' => $session['testStartTime'],
            'leftCorrect' => $leftCorrect,
            'leftIncorrect' => $leftIncorrect,
            'centerCorrect' => $centerCorrect,
            'centerIncorrect' => $centerIncorrect,
            'rightCorrect' => $rightCorrect,
            'rightIncorrect' => $rightIncorrect,
            'testTimeMs' => $testTimeMs,
            'avgResponseTime' => $avgResponseTime,
            'trials' => $trialResults
        ];
    }
    
    // Sort by test date (most recent first)
    usort($processedResults, function($a, $b) {
        return strtotime($b['testDate']) - strtotime($a['testDate']);
    });
    
    echo json_encode(['success' => true, 'results' => $processedResults]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>