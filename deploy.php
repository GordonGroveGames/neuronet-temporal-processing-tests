<?php
/**
 * GitHub Webhook Auto-Deploy Script
 * Pulls latest changes from main or dev when GitHub sends a push notification.
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

$data = json_decode($payload, true);
$ref = $data['ref'] ?? '';
$allowedBranches = ['refs/heads/main', 'refs/heads/dev'];

if (!in_array($ref, $allowedBranches, true)) {
    exit('Branch not configured for deploy, skipping.');
}

$branch = substr($ref, strlen('refs/heads/'));

// Pull latest changes
$repoDir = __DIR__;
$output = [];
$returnCode = 0;
exec("cd " . escapeshellarg($repoDir) . " && /usr/local/cpanel/3rdparty/bin/git pull origin " . escapeshellarg($branch) . " 2>&1", $output, $returnCode);

// Log the result
$logEntry = date('Y-m-d H:i:s') . " | Branch: $branch | Return code: $returnCode\n" . implode("\n", $output) . "\n---\n";
file_put_contents(__DIR__ . '/deploy.log', $logEntry, FILE_APPEND | LOCK_EX);

if ($returnCode === 0) {
    echo 'Deploy successful';
} else {
    http_response_code(500);
    echo 'Deploy failed: ' . implode("\n", $output);
}
