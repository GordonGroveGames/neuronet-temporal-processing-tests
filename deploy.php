<?php
/**
 * GitHub Webhook Auto-Deploy Script
 * Pulls latest changes from main when GitHub sends a push notification.
 */

// Secret token — must match the one configured in GitHub webhook settings
$secret = 'd74d371351abb9f3aa8dbab1b77f0741e9279e92';

// Verify the request is from GitHub
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload = file_get_contents('php://input');

if (!$signature || !$payload) {
    http_response_code(403);
    exit('Forbidden');
}

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    exit('Invalid signature');
}

// Only deploy on pushes to main
$data = json_decode($payload, true);
if (($data['ref'] ?? '') !== 'refs/heads/main') {
    exit('Not main branch, skipping.');
}

// Pull latest changes
$repoDir = __DIR__;
$output = [];
$returnCode = 0;
exec("cd " . escapeshellarg($repoDir) . " && /usr/local/cpanel/3rdparty/bin/git pull origin main 2>&1", $output, $returnCode);

// Log the result
$logEntry = date('Y-m-d H:i:s') . " | Return code: $returnCode\n" . implode("\n", $output) . "\n---\n";
file_put_contents(__DIR__ . '/deploy.log', $logEntry, FILE_APPEND | LOCK_EX);

if ($returnCode === 0) {
    echo 'Deploy successful';
} else {
    http_response_code(500);
    echo 'Deploy failed: ' . implode("\n", $output);
}
