<?php
// Set content type to JSON
header('Content-Type: application/json');

// Include the actual submit handler from the private directory
require_once __DIR__ . '/../private/includes/submit.php';

// The included file should handle the request and send a JSON response
exit;