<?php
// Completely suppress all output until we're ready
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Start output buffering
ob_start();

require_once __DIR__ . '/admin_session.php';
require_admin_login();

// Clear any unwanted output
ob_end_clean();

// Start fresh output buffer
ob_start();

header('Content-Type: application/json');

$uploadDir = __DIR__ . '/assets/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$response = ['success' => false];

try {
    $uploadedFiles = [];
    
    // Handle image files
    if (isset($_FILES['image_files'])) {
        foreach ($_FILES['image_files']['tmp_name'] as $key => $tmpName) {
            if (!empty($tmpName) && is_uploaded_file($tmpName)) {
                $originalName = $_FILES['image_files']['name'][$key];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                
                // Validate image file type
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
                    throw new Exception("Invalid image file type: $originalName");
                }
                
                // Generate safe filename
                $baseName = pathinfo($originalName, PATHINFO_FILENAME);
                $safeName = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $baseName) . '_' . time() . '.' . $ext;
                $targetPath = $uploadDir . $safeName;
                
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedFiles[] = $safeName;
                } else {
                    throw new Exception("Failed to move uploaded file: $originalName");
                }
            }
        }
    }
    
    // Handle audio files
    if (isset($_FILES['audio_files'])) {
        foreach ($_FILES['audio_files']['tmp_name'] as $key => $tmpName) {
            if (!empty($tmpName) && is_uploaded_file($tmpName)) {
                $originalName = $_FILES['audio_files']['name'][$key];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                
                // Validate audio file type
                if (!in_array($ext, ['mp3', 'wav', 'ogg', 'm4a'])) {
                    throw new Exception("Invalid audio file type: $originalName");
                }
                
                // Generate safe filename
                $baseName = pathinfo($originalName, PATHINFO_FILENAME);
                $safeName = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $baseName) . '_' . time() . '.' . $ext;
                $targetPath = $uploadDir . $safeName;
                
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedFiles[] = $safeName;
                } else {
                    throw new Exception("Failed to move uploaded file: $originalName");
                }
            }
        }
    }
    
    if (empty($uploadedFiles)) {
        throw new Exception("No valid files were uploaded");
    }
    
    $response['success'] = true;
    $response['message'] = count($uploadedFiles) . ' file(s) uploaded successfully';
    $response['files'] = $uploadedFiles;
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    error_log("Upload error: " . $e->getMessage());
}

// Clean any remaining output and send JSON
ob_end_clean();
echo json_encode($response);
exit;