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

/**
 * Check if a file path is referenced by any test in tests.json.
 * Returns true if the file is in use, false otherwise.
 */
function isFileInUse($filePath) {
    $testsFile = __DIR__ . '/assets/tests.json';
    if (!file_exists($testsFile)) return false;

    $tests = json_decode(file_get_contents($testsFile), true) ?: [];
    $assetFields = ['left_image', 'center_image', 'right_image', 'left_sound', 'center_sound', 'right_sound', 'correct_image', 'incorrect_image'];

    foreach ($tests as $test) {
        foreach ($assetFields as $field) {
            if (isset($test[$field]) && $test[$field] === $filePath) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Resolve naming conflict for a file:
 * - If file doesn't exist: use original name
 * - If file exists but is NOT in use by any test: overwrite (return original name)
 * - If file exists AND is in use: append _2, _3, etc. until unique
 */
function resolveFilename($targetDir, $originalName, $relativePrefix) {
    $targetPath = $targetDir . $originalName;

    if (!file_exists($targetPath)) {
        return $originalName;
    }

    // File exists — check if it's referenced by any test
    $relativePath = $relativePrefix . $originalName;
    if (!isFileInUse($relativePath)) {
        // Not in use by any test — overwrite is safe
        return $originalName;
    }

    // In use — generate a unique name
    $pathInfo = pathinfo($originalName);
    $baseName = $pathInfo['filename'];
    $ext = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
    $counter = 2;

    while (file_exists($targetDir . $baseName . '_' . $counter . $ext)) {
        $counter++;
    }

    return $baseName . '_' . $counter . $ext;
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

                // Validate filename is safe
                if (strpos($originalName, '..') !== false || strpos($originalName, '/') !== false || strpos($originalName, '\\') !== false) {
                    throw new Exception("Invalid filename: $originalName contains unsafe characters");
                }

                $safeName = resolveFilename($uploadDir, $originalName, 'assets/uploads/');
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

                // Validate filename is safe
                if (strpos($originalName, '..') !== false || strpos($originalName, '/') !== false || strpos($originalName, '\\') !== false) {
                    throw new Exception("Invalid filename: $originalName contains unsafe characters");
                }

                $safeName = resolveFilename($uploadDir, $originalName, 'assets/uploads/');
                $targetPath = $uploadDir . $safeName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedFiles[] = $safeName;
                } else {
                    throw new Exception("Failed to move uploaded file: $originalName");
                }
            }
        }
    }

    // Handle feedback image files (legacy - uploads to feedback/ root)
    if (isset($_FILES['feedback_image_files'])) {
        $feedbackUploadDir = __DIR__ . '/assets/uploads/feedback/';
        if (!is_dir($feedbackUploadDir)) {
            mkdir($feedbackUploadDir, 0777, true);
        }

        foreach ($_FILES['feedback_image_files']['tmp_name'] as $key => $tmpName) {
            if (!empty($tmpName) && is_uploaded_file($tmpName)) {
                $originalName = $_FILES['feedback_image_files']['name'][$key];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'bmp'])) {
                    throw new Exception("Invalid feedback image file type: $originalName");
                }

                if (strpos($originalName, '..') !== false || strpos($originalName, '/') !== false || strpos($originalName, '\\') !== false) {
                    throw new Exception("Invalid filename: $originalName contains unsafe characters");
                }

                $safeName = resolveFilename($feedbackUploadDir, $originalName, 'assets/uploads/feedback/');
                $targetPath = $feedbackUploadDir . $safeName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedFiles[] = $safeName;
                } else {
                    throw new Exception("Failed to move uploaded feedback image file: $originalName");
                }
            }
        }
    }

    // Handle correct feedback image files (uploads to feedback/correct/)
    if (isset($_FILES['correct_image_files'])) {
        $correctUploadDir = __DIR__ . '/assets/uploads/feedback/correct/';
        if (!is_dir($correctUploadDir)) {
            mkdir($correctUploadDir, 0777, true);
        }

        foreach ($_FILES['correct_image_files']['tmp_name'] as $key => $tmpName) {
            if (!empty($tmpName) && is_uploaded_file($tmpName)) {
                $originalName = $_FILES['correct_image_files']['name'][$key];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'bmp'])) {
                    throw new Exception("Invalid correct image file type: $originalName");
                }

                if (strpos($originalName, '..') !== false || strpos($originalName, '/') !== false || strpos($originalName, '\\') !== false) {
                    throw new Exception("Invalid filename: $originalName contains unsafe characters");
                }

                $safeName = resolveFilename($correctUploadDir, $originalName, 'assets/uploads/feedback/correct/');
                $targetPath = $correctUploadDir . $safeName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedFiles[] = $safeName;
                } else {
                    throw new Exception("Failed to move uploaded correct image file: $originalName");
                }
            }
        }
    }

    // Handle incorrect feedback image files (uploads to feedback/incorrect/)
    if (isset($_FILES['incorrect_image_files'])) {
        $incorrectUploadDir = __DIR__ . '/assets/uploads/feedback/incorrect/';
        if (!is_dir($incorrectUploadDir)) {
            mkdir($incorrectUploadDir, 0777, true);
        }

        foreach ($_FILES['incorrect_image_files']['tmp_name'] as $key => $tmpName) {
            if (!empty($tmpName) && is_uploaded_file($tmpName)) {
                $originalName = $_FILES['incorrect_image_files']['name'][$key];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'bmp'])) {
                    throw new Exception("Invalid incorrect image file type: $originalName");
                }

                if (strpos($originalName, '..') !== false || strpos($originalName, '/') !== false || strpos($originalName, '\\') !== false) {
                    throw new Exception("Invalid filename: $originalName contains unsafe characters");
                }

                $safeName = resolveFilename($incorrectUploadDir, $originalName, 'assets/uploads/feedback/incorrect/');
                $targetPath = $incorrectUploadDir . $safeName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedFiles[] = $safeName;
                } else {
                    throw new Exception("Failed to move uploaded incorrect image file: $originalName");
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
