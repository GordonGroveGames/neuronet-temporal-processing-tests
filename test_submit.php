<?php
// Test data
$data = [
    'fullName' => 'Test User',
    'email' => 'test@example.com',
    'testName' => 'temporal_processing_test',
    'promptNumber' => 1,
    'userAnswer' => 'A',
    'correctAnswer' => 'B',
    'responseTime' => 1500
];

// Initialize cURL
$ch = curl_init('http://localhost:8000/submit.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for errors
if (curl_errno($ch)) {
    echo 'cURL Error: ' . curl_error($ch) . "\n";
} else {
    echo "HTTP Status: $httpCode\n";
    echo "Response: $response\n";
}

// Close cURL
curl_close($ch);
?>
