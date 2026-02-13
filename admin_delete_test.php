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

    if (!isset($data['test_id'])) {
        throw new Exception('Test ID is required');
    }

    $testId = $data['test_id'];
    $testsFile = __DIR__ . '/assets/tests.json';
    $assessmentsFile = __DIR__ . '/assets/assessments.json';

    // Load existing tests
    $tests = [];
    if (file_exists($testsFile)) {
        $json = file_get_contents($testsFile);
        $tests = json_decode($json, true) ?: [];
    }

    // Check if test exists
    if (!isset($tests[$testId])) {
        throw new Exception('Test not found');
    }

    // Check permissions
    $testData = $tests[$testId];
    $createdBy = $testData['created_by'] ?? 'admin';
    $canDelete = in_array($currentUserRole, ['admin', 'site_admin']) ||
                 ($currentUserRole === 'test_creator' && $createdBy === $currentUser);

    if (!$canDelete) {
        throw new Exception('You do not have permission to delete this test');
    }

    // Referential integrity check: is this test used by any assessment?
    $assessments = [];
    if (file_exists($assessmentsFile)) {
        $assessments = json_decode(file_get_contents($assessmentsFile), true) ?: [];
    }

    foreach ($assessments as $assessmentId => $assessment) {
        if (isset($assessment['test_ids']) && is_array($assessment['test_ids'])) {
            if (in_array($testId, $assessment['test_ids'])) {
                $assessmentName = $assessment['name'] ?? $assessmentId;
                throw new Exception("Cannot delete: this test is used by assessment \"$assessmentName\". Remove it from the assessment first.");
            }
        }
    }

    // Remove the test
    unset($tests[$testId]);

    // Save the updated tests
    if (file_put_contents($testsFile, json_encode($tests, JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Failed to save tests file');
    }

    $response['success'] = true;
    $response['message'] = 'Test deleted successfully';

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Clean any remaining output and send JSON
ob_end_clean();
echo json_encode($response);
exit;
?>
