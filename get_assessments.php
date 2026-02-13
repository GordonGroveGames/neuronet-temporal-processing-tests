<?php
// Suppress any notices or warnings that might contaminate JSON output
ini_set('display_errors', 0);
error_reporting(0);
ob_start();

header('Content-Type: application/json');

try {
    // Load assessments from JSON file
    $assessmentsFile = __DIR__ . '/assets/assessments.json';

    if (!file_exists($assessmentsFile)) {
        throw new Exception('No assessments found');
    }

    $json = file_get_contents($assessmentsFile);
    $assessments = json_decode($json, true);

    if (!$assessments) {
        throw new Exception('Failed to load assessments');
    }

    // Load standalone tests (new format)
    $testsFile = __DIR__ . '/assets/tests.json';
    $allTests = [];
    if (file_exists($testsFile)) {
        $allTests = json_decode(file_get_contents($testsFile), true) ?: [];
    }

    // Support single test_id param: wrap a standalone test as a pseudo-assessment
    if (isset($_GET['test_id']) && !empty($_GET['test_id'])) {
        $singleTestId = $_GET['test_id'];
        if (isset($allTests[$singleTestId])) {
            $singleTest = $allTests[$singleTestId];
            $testConfig = [
                'id' => $singleTestId,
                'name' => $singleTest['name'] ?? 'Test',
                'description' => 'Audio-visual matching test',
                'type' => 'matching',
                'entries' => 15,
                'images' => [
                    'left' => $singleTest['left_image'] ?? '',
                    'center' => $singleTest['center_image'] ?? '',
                    'right' => $singleTest['right_image'] ?? ''
                ],
                'sounds' => [
                    'left' => $singleTest['left_sound'] ?? '',
                    'center' => $singleTest['center_sound'] ?? '',
                    'right' => $singleTest['right_sound'] ?? ''
                ],
                'feedback_images' => [
                    'correct' => $singleTest['correct_image'] ?? '',
                    'incorrect' => $singleTest['incorrect_image'] ?? ''
                ],
                'zones' => ['Left', 'Center', 'Right']
            ];

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'assessments' => [[
                    'id' => 'single_test_' . $singleTestId,
                    'name' => $singleTest['name'] ?? 'Test',
                    'tests' => [$testConfig]
                ]]
            ]);
            exit;
        }
    }

    // Filter by selected IDs if provided
    $selectedIds = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
    if (!empty($selectedIds)) {
        $assessments = array_intersect_key($assessments, array_flip($selectedIds));
    }

    // Transform the data structure for the test interface
    $transformedAssessments = [];

    foreach ($assessments as $assessmentId => $assessment) {
        $transformedAssessment = [
            'id' => $assessmentId,
            'name' => $assessment['name'],
            'tests' => []
        ];

        // Resolve tests: support both new (test_ids) and old (embedded tests) formats
        $testList = [];
        if (isset($assessment['test_ids']) && is_array($assessment['test_ids'])) {
            // New format: look up each test from tests.json
            foreach ($assessment['test_ids'] as $testId) {
                if (isset($allTests[$testId])) {
                    $testList[] = $allTests[$testId];
                }
            }
        } elseif (isset($assessment['tests']) && is_array($assessment['tests'])) {
            // Old format: embedded test objects
            $testList = $assessment['tests'];
        }

        // Each test in the assessment becomes a test configuration
        foreach ($testList as $index => $test) {
            $testConfig = [
                'id' => $assessmentId . '_test_' . $index,
                'name' => $assessment['name'],
                'description' => 'Audio-visual matching test',
                'type' => 'matching',
                'entries' => 15,
                'images' => [
                    'left' => $test['left_image'] ?? '',
                    'center' => $test['center_image'] ?? '',
                    'right' => $test['right_image'] ?? ''
                ],
                'sounds' => [
                    'left' => $test['left_sound'] ?? '',
                    'center' => $test['center_sound'] ?? '',
                    'right' => $test['right_sound'] ?? ''
                ],
                'feedback_images' => [
                    'correct' => $test['correct_image'] ?? '',
                    'incorrect' => $test['incorrect_image'] ?? ''
                ],
                'zones' => ['Left', 'Center', 'Right']
            ];

            $transformedAssessment['tests'][] = $testConfig;
        }

        $transformedAssessments[] = $transformedAssessment;
    }

    // Clean any output buffer
    ob_end_clean();

    echo json_encode([
        'success' => true,
        'assessments' => $transformedAssessments
    ]);

} catch (Exception $e) {
    // Clean any output buffer
    ob_end_clean();

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
