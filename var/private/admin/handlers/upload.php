<?php
// Include required files
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

// Set content type first to ensure proper JSON response
header('Content-Type: application/json');

// Check authentication before anything else
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Session expired. Please refresh the page and log in again.',
        'redirect' => '/admin/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])
    ]);
    exit;
}

// Verify this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Verify file was uploaded without errors
if (!isset($_FILES['file']) || !isset($_FILES['file']['error']) || is_array($_FILES['file']['error'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid file upload']);
    exit;
}

// Initialize response
$response = [
    'success' => false,
    'error' => 'An unknown error occurred',
    'filePath' => null
];

try {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Include required files
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/db.php';

    // Only allow authenticated users
    if (!isAuthenticated()) {
        http_response_code(401);
        throw new Exception('Unauthorized');
    }

    // Check if file was uploaded without errors
    if (!isset($_FILES['file']) || !isset($_FILES['file']['error'])) {
        throw new Exception('No file was uploaded');
    }

    // Check for upload errors
    switch ($_FILES['file']['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new Exception('File is too large');
        case UPLOAD_ERR_PARTIAL:
            throw new Exception('File was only partially uploaded');
        case UPLOAD_ERR_NO_FILE:
            throw new Exception('No file was uploaded');
        default:
            throw new Exception('Error uploading file');
    }

    // Set upload directory
    $uploadDir = __DIR__ . '/../../../data/uploads/';

    // Create upload directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }

    // Verify upload directory is writable
    if (!is_writable($uploadDir)) {
        throw new Exception('Upload directory is not writable');
    }

    $file = $_FILES['file'];
    $fileTmpPath = $file['tmp_name'];
    $fileName = $file['name'];
    $fileSize = $file['size'];
    
    // Get file extension
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Validate file has an extension
    if (empty($fileExt)) {
        throw new Exception('File has no extension');
    }
    
    // Allowed file types
    $allowedImageTypes = ['jpeg', 'jpg', 'png', 'gif'];
    $allowedAudioTypes = ['mp3', 'wav', 'ogg'];
    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'audio/mpeg' => 'mp3',
        'audio/wav' => 'wav',
        'audio/ogg' => 'ogg'
    ];
    
    // Get MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $fileMimeType = finfo_file($finfo, $fileTmpPath);
    finfo_close($finfo);
    
    // Validate MIME type
    if (!array_key_exists($fileMimeType, $allowedMimeTypes)) {
        throw new Exception('Invalid file type. Only images (JPEG, PNG, GIF) and audio (MP3, WAV, OGG) are allowed.');
    }
    
    // Validate extension matches MIME type
    $expectedExt = $allowedMimeTypes[$fileMimeType];
    if ($fileExt !== $expectedExt) {
        // If extension doesn't match MIME type, use the correct one
        $fileExt = $expectedExt;
    }
    
    // Maximum file size (5MB)
    $maxFileSize = 5 * 1024 * 1024;
    
    // Validate file size
    if ($fileSize > $maxFileSize) {
        throw new Exception('File is too large. Maximum size is 5MB.');
    }
    
    // Generate a unique filename with extension based on MIME type
    $newFileName = uniqid() . '.' . $fileExt;
    $targetPath = $uploadDir . $newFileName;
    
    // Move the uploaded file
    if (!move_uploaded_file($fileTmpPath, $targetPath)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    // Set success response
    $response = [
        'success' => true,
        'error' => null,
        'filePath' => '/data/uploads/' . $newFileName
    ];
    
} catch (Exception $e) {
    http_response_code(400);
    $response['error'] = $e->getMessage();
}

// Ensure we output valid JSON
$jsonResponse = json_encode($response);
if ($jsonResponse === false) {
    // Fallback error if json_encode fails
    $jsonResponse = json_encode([
        'success' => false,
        'error' => 'Failed to encode response',
        'filePath' => null
    ]);
}

echo $jsonResponse;
