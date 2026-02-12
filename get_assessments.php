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
        
        // Each test in the assessment becomes a test configuration
        foreach ($assessment['tests'] as $index => $test) {
            $testConfig = [
                'id' => $assessmentId . '_test_' . $index,
                'name' => $assessment['name'],
                'description' => 'Audio-visual matching test',
                'type' => 'matching', // New type for admin-created tests
                'entries' => 15, // Default number of entries
                'images' => [
                    'left' => $test['left_image'],
                    'center' => $test['center_image'], 
                    'right' => $test['right_image']
                ],
                'sounds' => [
                    'left' => $test['left_sound'],
                    'center' => $test['center_sound'],
                    'right' => $test['right_sound']
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