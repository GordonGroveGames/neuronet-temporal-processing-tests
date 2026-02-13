<?php
/**
 * One-time migration: extract embedded tests from assessments.json into tests.json.
 * Idempotent — skips assessments that already use test_ids.
 * Include this file once at the top of admin_panel.php (after session check).
 */

$testsFile = __DIR__ . '/assets/tests.json';
$assessmentsFile = __DIR__ . '/assets/assessments.json';

// Ensure tests.json exists
if (!file_exists($testsFile)) {
    file_put_contents($testsFile, json_encode(new stdClass(), JSON_PRETTY_PRINT));
}

// Load both files
$tests = json_decode(file_get_contents($testsFile), true) ?: [];
$assessments = json_decode(file_get_contents($assessmentsFile), true) ?: [];

$migrated = false;

foreach ($assessments as $assessmentId => &$assessment) {
    // Skip if already migrated (has test_ids instead of tests)
    if (isset($assessment['test_ids'])) {
        continue;
    }

    // Skip if no embedded tests array
    if (!isset($assessment['tests']) || !is_array($assessment['tests'])) {
        continue;
    }

    $testIds = [];
    $assessmentName = $assessment['name'] ?? 'Untitled';

    foreach ($assessment['tests'] as $index => $testData) {
        $testId = uniqid('test_', true);
        $testNum = $index + 1;

        $tests[$testId] = [
            'name' => $assessmentName . ' - Test ' . $testNum,
            'left_image' => $testData['left_image'] ?? '',
            'center_image' => $testData['center_image'] ?? '',
            'right_image' => $testData['right_image'] ?? '',
            'left_sound' => $testData['left_sound'] ?? '',
            'center_sound' => $testData['center_sound'] ?? '',
            'right_sound' => $testData['right_sound'] ?? '',
            'correct_image' => $testData['correct_image'] ?? '',
            'incorrect_image' => $testData['incorrect_image'] ?? '',
            'created_by' => $assessment['created_by'] ?? 'admin',
            'updated_by' => $assessment['updated_by'] ?? ($assessment['created_by'] ?? 'admin'),
            'updated_at' => $assessment['updated_at'] ?? date('Y-m-d H:i:s'),
        ];

        $testIds[] = $testId;
    }

    // Replace embedded tests with test_ids
    $assessment['test_ids'] = $testIds;
    unset($assessment['tests']);
    $migrated = true;
}
unset($assessment); // break reference

if ($migrated) {
    file_put_contents($testsFile, json_encode($tests, JSON_PRETTY_PRINT));
    file_put_contents($assessmentsFile, json_encode($assessments, JSON_PRETTY_PRINT));
}
?>
