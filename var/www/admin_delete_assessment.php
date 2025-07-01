<?php
// Suppress notices and warnings for clean JSON output
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Start output buffering
ob_start();

require_once __DIR__ . '/admin_session.php';
require_role(['admin', 'site_admin', 'test_creator']);

$currentUserRole = get_user_role();
$currentUser = $_SESSION['admin_user'];

// Clear any unwanted output
ob_end_clean();

// Start fresh output buffer
ob_start();

header('Content-Type: application/json');

$response = ['success' => false];

try {
    // Get the request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['assessment_id'])) {
        throw new Exception('Assessment ID is required');
    }
    
    $assessmentId = $data['assessment_id'];
    $assessmentsFile = __DIR__ . '/assets/assessments.json';
    
    // Load existing assessments
    $assessments = [];
    if (file_exists($assessmentsFile)) {
        $json = file_get_contents($assessmentsFile);
        $assessments = json_decode($json, true) ?: [];
    }
    
    // Check if assessment exists
    if (!isset($assessments[$assessmentId])) {
        throw new Exception('Assessment not found');
    }
    
    // Check permissions
    $assessment = $assessments[$assessmentId];
    $createdBy = $assessment['created_by'] ?? 'admin';
    $canDelete = in_array($currentUserRole, ['admin', 'site_admin']) || 
                 ($currentUserRole === 'test_creator' && $createdBy === $currentUser);
    
    if (!$canDelete) {
        throw new Exception('You do not have permission to delete this assessment');
    }
    
    // Remove the assessment
    unset($assessments[$assessmentId]);
    
    // Save the updated assessments
    if (file_put_contents($assessmentsFile, json_encode($assessments, JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Failed to save assessments file');
    }
    
    $response['success'] = true;
    $response['message'] = 'Assessment deleted successfully';
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Clean any remaining output and send JSON
ob_end_clean();
echo json_encode($response);
exit;
?>