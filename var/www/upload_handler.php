<?php
// Completely disable all PHP error output
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '0');
error_reporting(0);

// Start output buffering to catch any unwanted output
ob_start();

// Set JSON header
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Function to send clean JSON and exit
function sendJSON($data) {
    // Clear any buffered output
    while (ob_get_level()) {
        ob_end_clean();
    }
    echo json_encode($data);
    exit;
}

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
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'bmp'])) {
                    throw new Exception("Invalid image file type: $originalName");
                }
                
                // Validate filename is safe (no path traversal, special chars that could cause issues)
                if (strpos($originalName, '..') !== false || strpos($originalName, '/') !== false || strpos($originalName, '\\') !== false) {
                    throw new Exception("Invalid filename: $originalName contains unsafe characters");
                }
                
                // Use original filename, check for duplicates
                $safeName = $originalName;
                $targetPath = $uploadDir . $safeName;
                
                // Check if file already exists
                if (file_exists($targetPath)) {
                    throw new Exception("File '$originalName' already exists. Please rename the file or delete the existing one.");
                }
                
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
                
                // Validate filename is safe (no path traversal, special chars that could cause issues)
                if (strpos($originalName, '..') !== false || strpos($originalName, '/') !== false || strpos($originalName, '\\') !== false) {
                    throw new Exception("Invalid filename: $originalName contains unsafe characters");
                }
                
                // Use original filename, check for duplicates
                $safeName = $originalName;
                $targetPath = $uploadDir . $safeName;
                
                // Check if file already exists
                if (file_exists($targetPath)) {
                    throw new Exception("File '$originalName' already exists. Please rename the file or delete the existing one.");
                }
                
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedFiles[] = $safeName;
                } else {
                    throw new Exception("Failed to move uploaded file: $originalName");
                }
            }
        }
    }
    
    // Handle feedback image files
    if (isset($_FILES['feedback_image_files'])) {
        $feedbackUploadDir = __DIR__ . '/assets/uploads/feedback/';
        if (!is_dir($feedbackUploadDir)) {
            mkdir($feedbackUploadDir, 0777, true);
        }
        
        foreach ($_FILES['feedback_image_files']['tmp_name'] as $key => $tmpName) {
            if (!empty($tmpName) && is_uploaded_file($tmpName)) {
                $originalName = $_FILES['feedback_image_files']['name'][$key];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                
                // Validate image file type
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'bmp'])) {
                    throw new Exception("Invalid feedback image file type: $originalName");
                }
                
                // Validate filename is safe (no path traversal, special chars that could cause issues)
                if (strpos($originalName, '..') !== false || strpos($originalName, '/') !== false || strpos($originalName, '\\') !== false) {
                    throw new Exception("Invalid filename: $originalName contains unsafe characters");
                }
                
                // Use original filename, check for duplicates
                $safeName = $originalName;
                $targetPath = $feedbackUploadDir . $safeName;
                
                // Check if file already exists
                if (file_exists($targetPath)) {
                    throw new Exception("Feedback image file '$originalName' already exists. Please rename the file or delete the existing one.");
                }
                
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedFiles[] = $safeName;
                } else {
                    throw new Exception("Failed to move uploaded feedback image file: $originalName");
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
    
    sendJSON($response);
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    sendJSON($response);
}
?>