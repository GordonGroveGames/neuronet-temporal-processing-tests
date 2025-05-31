<?php
require_once __DIR__ . '/../private/admin/includes/auth.php';
require_once __DIR__ . '/../private/includes/db.php';

// Require authentication
requireAuth();

$pdo = getDbConnection();
$testId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isCopy = isset($_GET['copy']);
$test = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testData = [
        'test_name' => trim($_POST['test_name']),
        'left_image' => trim($_POST['left_image']),
        'center_image' => trim($_POST['center_image']),
        'right_image' => trim($_POST['right_image']),
        'left_sound' => trim($_POST['left_sound']),
        'center_sound' => trim($_POST['center_sound']),
        'right_sound' => trim($_POST['right_sound']),
        'correct_image' => trim($_POST['correct_image']),
        'incorrect_image' => trim($_POST['incorrect_image']),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
    
    // Validate required fields
    $errors = [];
    if (empty($testData['test_name'])) {
        $errors[] = 'Test name is required';
    }
    
    if (empty($errors)) {
        $pdo->beginTransaction();
        
        try {
            if ($testId > 0 && !$isCopy) {
                // Update existing test
                $sql = "UPDATE tests SET 
                        test_name = :test_name,
                        left_image = :left_image,
                        center_image = :center_image,
                        right_image = :right_image,
                        left_sound = :left_sound,
                        center_sound = :center_sound,
                        right_sound = :right_sound,
                        correct_image = :correct_image,
                        incorrect_image = :incorrect_image,
                        is_active = :is_active,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE id = :id";
                
                $stmt = $pdo->prepare($sql);
                $testData['id'] = $testId;
                $stmt->execute($testData);
                $message = 'Test updated successfully';
            } else {
                // Insert new test
                $sql = "INSERT INTO tests (
                        test_name, left_image, center_image, right_image, 
                        left_sound, center_sound, right_sound,
                        correct_image, incorrect_image, is_active
                    ) VALUES (
                        :test_name, :left_image, :center_image, :right_image, 
                        :left_sound, :center_sound, :right_sound,
                        :correct_image, :incorrect_image, :is_active
                    )";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($testData);
                $testId = $pdo->lastInsertId();
                $message = 'Test created successfully';
            }
            
            // Handle test parameters
            if (isset($_POST['param_name']) && is_array($_POST['param_name'])) {
                $paramStmt = $pdo->prepare("
                    INSERT OR REPLACE INTO test_parameters 
                    (test_id, parameter_name, parameter_value, parameter_type)
                    VALUES (?, ?, ?, ?)
                ");
                
                foreach ($_POST['param_name'] as $index => $paramName) {
                    if (!empty($paramName)) {
                        $paramValue = $_POST['param_value'][$index] ?? '';
                        $paramType = $_POST['param_type'][$index] ?? 'string';
                        
                        $paramStmt->execute([
                            $testId,
                            $paramName,
                            $paramValue,
                            $paramType
                        ]);
                    }
                }
            }
            
            $pdo->commit();
            $_SESSION['success_message'] = $message;
            header('Location: index.php');
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Error saving test: ' . $e->getMessage();
        }
    }
} elseif ($testId > 0) {
    // Load existing test
    $stmt = $pdo->prepare("SELECT * FROM tests WHERE id = ?");
    $stmt->execute([$testId]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($isCopy) {
        $test['test_name'] .= ' (Copy)';
        $test['is_active'] = 0;
    }
}

// Load test parameters if editing
$parameters = [];
if ($testId > 0 && !$isCopy) {
    $stmt = $pdo->prepare("SELECT * FROM test_parameters WHERE test_id = ?");
    $stmt->execute([$testId]);
    $parameters = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add a default parameter if none exist
if (empty($parameters)) {
    $parameters = [
        ['parameter_name' => 'trial_duration', 'parameter_value' => '5000', 'parameter_type' => 'number'],
        ['parameter_name' => 'feedback_duration', 'parameter_value' => '1000', 'parameter_type' => 'number'],
        ['parameter_name' => 'trials_per_test', 'parameter_value' => '15', 'parameter_type' => 'number']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $testId ? 'Edit' : 'Add'; ?> Test - NeuroNet Tests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css">
    <style>
        .preview-container {
            margin-top: 10px;
        }
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
            display: block;
            margin: 10px 0;
        }
        .preview-audio {
            width: 100%;
            margin: 10px 0;
        }
        .remove-asset {
            margin-top: 5px;
        }
        .upload-button {
            margin-bottom: 10px;
        }
        .file-input {
            display: none;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 20px;
            margin: 5px 0;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #495057;
            color: white;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #3498db;
            color: white;
            font-weight: 600;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-primary {
            background-color: #3498db;
            border: none;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .parameter-row {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-center">NeuroNet Admin</h4>
                    <hr>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="test_edit.php" class="nav-link active">
                            <i class="bi bi-plus-circle"></i> Add New Test
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="users.php" class="nav-link">
                            <i class="bi bi-people"></i> Users
                        </a>
                    </li>
                    <li class="nav-item mt-auto">
                        <a href="logout.php" class="nav-link">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo $testId ? 'Edit' : 'Add New'; ?> Test</h2>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Tests
                    </a>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data" id="testForm">
                    <div class="card mb-4">
                        <div class="card-header">
                            Test Information
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="test_name" class="form-label">Test Name *</label>
                                    <input type="text" class="form-control" id="test_name" name="test_name" 
                                           value="<?php echo htmlspecialchars($test['test_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                               <?php echo ($test['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            Test Assets
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <h5>Left Section</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Image</label>
                                        <div>
                                            <button type="button" class="btn btn-primary btn-sm upload-button" onclick="document.getElementById('left_image_file').click()">
                                                <i class="bi bi-upload"></i> Upload Image
                                            </button>
                                            <input type="file" id="left_image_file" class="file-input" accept="image/*" onchange="handleFileSelect(this, 'left-image', 'image')">
                                            <input type="hidden" name="left_image" value="<?php echo htmlspecialchars($test['left_image'] ?? ''); ?>" id="left_image">
                                        </div>
                                        <div class="preview-container" id="left-image-preview" style="display: none;">
                                            <img src="" class="preview-image" id="left-image-preview-img">
                                            <div class="remove-asset" onclick="removeAsset('left-image')">
                                                <i class="bi bi-x"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Sound</label>
                                        <div>
                                            <button type="button" class="btn btn-primary btn-sm upload-button" onclick="document.getElementById('left_sound_file').click()">
                                                <i class="bi bi-upload"></i> Upload Audio
                                            </button>
                                            <input type="file" id="left_sound_file" class="file-input" accept="audio/*" onchange="handleFileSelect(this, 'left-sound', 'audio')">
                                            <input type="hidden" name="left_sound" value="<?php echo htmlspecialchars($test['left_sound'] ?? ''); ?>" id="left_sound">
                                        </div>
                                        <div class="preview-container" id="left-sound-preview" style="display: none;">
                                            <audio controls class="preview-audio" id="left-sound-preview-audio"></audio>
                                            <div class="remove-asset" onclick="removeAsset('left-sound')">
                                                <i class="bi bi-x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <h5>Center Section</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Image</label>
                                        <div>
                                            <button type="button" class="btn btn-primary btn-sm upload-button" onclick="document.getElementById('center_image_file').click()">
                                                <i class="bi bi-upload"></i> Upload Image
                                            </button>
                                            <input type="file" id="center_image_file" class="file-input" accept="image/*" onchange="handleFileSelect(this, 'center-image', 'image')">
                                            <input type="hidden" name="center_image" value="<?php echo htmlspecialchars($test['center_image'] ?? ''); ?>" id="center_image">
                                        </div>
                                        <div class="preview-container" id="center-image-preview" style="display: none;">
                                            <img src="" class="preview-image" id="center-image-preview-img">
                                            <div class="remove-asset" onclick="removeAsset('center-image')">
                                                <i class="bi bi-x"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Sound</label>
                                        <div>
                                            <button type="button" class="btn btn-primary btn-sm upload-button" onclick="document.getElementById('center_sound_file').click()">
                                                <i class="bi bi-upload"></i> Upload Audio
                                            </button>
                                            <input type="file" id="center_sound_file" class="file-input" accept="audio/*" onchange="handleFileSelect(this, 'center-sound', 'audio')">
                                            <input type="hidden" name="center_sound" value="<?php echo htmlspecialchars($test['center_sound'] ?? ''); ?>" id="center_sound">
                                        </div>
                                        <div class="preview-container" id="center-sound-preview" style="display: none;">
                                            <audio controls class="preview-audio" id="center-sound-preview-audio"></audio>
                                            <div class="remove-asset" onclick="removeAsset('center-sound')">
                                                <i class="bi bi-x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <h5>Right Section</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Image</label>
                                        <div>
                                            <button type="button" class="btn btn-primary btn-sm upload-button" onclick="document.getElementById('right_image_file').click()">
                                                <i class="bi bi-upload"></i> Upload Image
                                            </button>
                                            <input type="file" id="right_image_file" class="file-input" accept="image/*" onchange="handleFileSelect(this, 'right-image', 'image')">
                                            <input type="hidden" name="right_image" value="<?php echo htmlspecialchars($test['right_image'] ?? ''); ?>" id="right_image">
                                        </div>
                                        <div class="preview-container" id="right-image-preview" style="display: none;">
                                            <img src="" class="preview-image" id="right-image-preview-img">
                                            <div class="remove-asset" onclick="removeAsset('right-image')">
                                                <i class="bi bi-x"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Sound</label>
                                        <div>
                                            <button type="button" class="btn btn-primary btn-sm upload-button" onclick="document.getElementById('right_sound_file').click()">
                                                <i class="bi bi-upload"></i> Upload Audio
                                            </button>
                                            <input type="file" id="right_sound_file" class="file-input" accept="audio/*" onchange="handleFileSelect(this, 'right-sound', 'audio')">
                                            <input type="hidden" name="right_sound" value="<?php echo htmlspecialchars($test['right_sound'] ?? ''); ?>" id="right_sound">
                                        </div>
                                        <div class="preview-container" id="right-sound-preview" style="display: none;">
                                            <audio controls class="preview-audio" id="right-sound-preview-audio"></audio>
                                            <div class="remove-asset" onclick="removeAsset('right-sound')">
                                                <i class="bi bi-x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Feedback Images</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Correct Answer</label>
                                            <div>
                                                <button type="button" class="btn btn-primary btn-sm upload-button" onclick="document.getElementById('correct_image_file').click()">
                                                    <i class="bi bi-upload"></i> Upload Correct Image
                                                </button>
                                                <input type="file" id="correct_image_file" class="file-input" accept="image/*" onchange="handleFileSelect(this, 'correct-image', 'image')">
                                                <input type="hidden" name="correct_image" value="<?php echo htmlspecialchars($test['correct_image'] ?? ''); ?>" id="correct_image">
                                            </div>
                                            <div class="preview-container" id="correct-image-preview" style="display: none;">
                                                <img src="" class="preview-image" id="correct-image-preview-img">
                                                <div class="remove-asset" onclick="removeAsset('correct-image')">
                                                    <i class="bi bi-x"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Incorrect Answer</label>
                                            <div>
                                                <button type="button" class="btn btn-primary btn-sm upload-button" onclick="document.getElementById('incorrect_image_file').click()">
                                                    <i class="bi bi-upload"></i> Upload Incorrect Image
                                                </button>
                                                <input type="file" id="incorrect_image_file" class="file-input" accept="image/*" onchange="handleFileSelect(this, 'incorrect-image', 'image')">
                                                <input type="hidden" name="incorrect_image" value="<?php echo htmlspecialchars($test['incorrect_image'] ?? ''); ?>" id="incorrect_image">
                                            </div>
                                            <div class="preview-container" id="incorrect-image-preview" style="display: none;">
                                                <img src="" class="preview-image" id="incorrect-image-preview-img">
                                                <div class="remove-asset" onclick="removeAsset('incorrect-image')">
                                                    <i class="bi bi-x"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Test Parameters</span>
                            <button type="button" class="btn btn-sm btn-primary" id="addParameter">
                                <i class="bi bi-plus"></i> Add Parameter
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="parametersContainer">
                                <?php foreach ($parameters as $index => $param): ?>
                                    <div class="parameter-row">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <input type="text" class="form-control" name="param_name[]" 
                                                       placeholder="Parameter name" value="<?php echo htmlspecialchars($param['parameter_name']); ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="text" class="form-control" name="param_value[]" 
                                                       placeholder="Value" value="<?php echo htmlspecialchars($param['parameter_value']); ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <select class="form-select" name="param_type[]">
                                                    <option value="string" <?php echo $param['parameter_type'] === 'string' ? 'selected' : ''; ?>>String</option>
                                                    <option value="number" <?php echo $param['parameter_type'] === 'number' ? 'selected' : ''; ?>>Number</option>
                                                    <option value="boolean" <?php echo $param['parameter_type'] === 'boolean' ? 'selected' : ''; ?>>Boolean</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger btn-sm remove-parameter">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Test
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add parameter row
        document.addEventListener('DOMContentLoaded', function() {
            // Add parameter row function
            window.addParameterRow = function() {
                const container = document.getElementById('parametersContainer');
                const rowCount = container.querySelectorAll('.parameter-row').length;
                const newRow = document.createElement('div');
                newRow.className = 'parameter-row mb-3';
                newRow.innerHTML = `
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="parameters[${rowCount}][name]" placeholder="Parameter name" required>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="parameters[${rowCount}][value]" placeholder="Value" required>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="parameters[${rowCount}][type]">
                                <option value="string">String</option>
                                <option value="number">Number</option>
                                <option value="boolean">Boolean</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm remove-parameter">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(newRow);
                
                // Add event listener to the new remove button
                newRow.querySelector('.remove-parameter').addEventListener('click', function() {
                    container.removeChild(newRow);
                    // Rename remaining parameters to maintain array indices
                    const rows = container.querySelectorAll('.parameter-row');
                    rows.forEach((row, index) => {
                        const inputs = row.querySelectorAll('input, select');
                        inputs.forEach(input => {
                            input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
                        });
                    });
                });
            };
            
            // Add initial parameter row if none exists
            const container = document.getElementById('parametersContainer');
            if (container.querySelectorAll('.parameter-row').length === 0) {
                addParameterRow();
            }
            
            // Add event listener to the add parameter button
            document.getElementById('addParameter').addEventListener('click', addParameterRow);
            
            // Load existing previews if editing
            <?php if ($test): ?>
                <?php if (!empty($test['left_image'])): ?>updatePreview('left-image', '<?php echo $test['left_image']; ?>', 'image');<?php endif; ?>
                <?php if (!empty($test['center_image'])): ?>updatePreview('center-image', '<?php echo $test['center_image']; ?>', 'image');<?php endif; ?>
                <?php if (!empty($test['right_image'])): ?>updatePreview('right-image', '<?php echo $test['right_image']; ?>', 'image');<?php endif; ?>
                <?php if (!empty($test['correct_image'])): ?>updatePreview('correct-image', '<?php echo $test['correct_image']; ?>', 'image');<?php endif; ?>
                <?php if (!empty($test['incorrect_image'])): ?>updatePreview('incorrect-image', '<?php echo $test['incorrect_image']; ?>', 'image');<?php endif; ?>
                <?php if (!empty($test['left_sound'])): ?>updatePreview('left-sound', '<?php echo $test['left_sound']; ?>', 'audio');<?php endif; ?>
                <?php if (!empty($test['center_sound'])): ?>updatePreview('center-sound', '<?php echo $test['center_sound']; ?>', 'audio');<?php endif; ?>
                <?php if (!empty($test['right_sound'])): ?>updatePreview('right-sound', '<?php echo $test['right_sound']; ?>', 'audio');<?php endif; ?>
            <?php endif; ?>
            
            // Add initial parameter row for new tests
            <?php if ($test && empty($test['id'])): ?>
                addParameterRow();
            <?php endif; ?>
        });

        // Remove asset
        function removeAsset(elementId) {
            const previewContainer = document.getElementById(`${elementId}-preview`);
            const hiddenInput = document.getElementById(elementId);
            const fileInput = document.getElementById(`${elementId}_file`);
            const previewElement = previewContainer ? previewContainer.querySelector('img, audio') : null;
            
            // Reset file input
            if (fileInput) fileInput.value = '';
            
            // Clear hidden input
            if (hiddenInput) hiddenInput.value = '';
            
            // Hide preview and clear source
            if (previewElement) {
                previewElement.src = '';
                if (previewContainer) {
                    previewContainer.style.display = 'none';
                }
            }
        }

        // Handle file selection
        function handleFileSelect(input, elementId, type) {
            if (input.files && input.files[0]) {
                handleFiles(input, elementId, type);
            }
        }
        
        // Handle file upload
        function handleFiles(input, elementId, type) {
            const files = input.files;
            if (!files || files.length === 0) return;
            
            const file = files[0];
            const formData = new FormData();
            formData.append('file', file);
            
            // Find the closest button to show loading state
            const button = input.closest('div').querySelector('button');
            let originalHTML = '';
            if (button) {
                originalHTML = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...';
                button.disabled = true;
            }
            
            // Show success message function
            const showSuccess = (message) => {
                console.log('Success:', message);
                
                // Create or update success message element
                let successElement = document.getElementById(`${elementId}-success`);
                if (!successElement) {
                    successElement = document.createElement('div');
                    successElement.id = `${elementId}-success`;
                    successElement.className = 'text-success mt-1 small';
                    input.parentNode.insertBefore(successElement, input.nextSibling);
                }
                successElement.textContent = message;
                
                // Auto-hide after 3 seconds
                setTimeout(() => {
                    if (successElement) {
                        successElement.remove();
                    }
                }, 3000);
                
                // Clear any existing error
                const errorElement = document.getElementById(`${elementId}-error`);
                if (errorElement) {
                    errorElement.remove();
                }
            };
            
            // Show error message function
            const showError = (message, responseText) => {
                console.error('Upload error:', message);
                console.log('Server response:', responseText);
                
                // Create or update error message element
                let errorElement = document.getElementById(`${elementId}-error`);
                if (!errorElement) {
                    errorElement = document.createElement('div');
                    errorElement.id = `${elementId}-error`;
                    errorElement.className = 'text-danger mt-1 small';
                    input.parentNode.insertBefore(errorElement, input.nextSibling);
                }
                errorElement.textContent = message;
                
                // Clear any existing success message
                const successElement = document.getElementById(`${elementId}-success`);
                if (successElement) {
                    successElement.remove();
                }
                
                // Show alert with more details in development
                const isDev = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
                if (isDev || message.includes('Unauthorized') || message.includes('Failed to create')) {
                    alert('Upload Error: ' + message + (responseText ? '\n\nServer response: ' + responseText : ''));
                }
            };
            
            // Clear any previous error
            const prevError = document.getElementById(`${elementId}-error`);
            if (prevError) {
                prevError.remove();
            }
            
            // Upload file
            fetch('/admin/handlers/upload.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin', // Include cookies for authentication
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' // Indicate this is an AJAX request
                }
            })
            .then(async response => {
                // First, read the response as text
                const responseText = await response.text();
                let data;
                
                try {
                    // Try to parse the response as JSON
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('Failed to parse response:', responseText);
                    // If parsing as JSON fails, check if it's an HTML response (redirect to login)
                    if (responseText.trim().startsWith('<!DOCTYPE html>') || 
                        responseText.includes('<html') || 
                        responseText.includes('<!DOCTYPE')) {
                        window.location.href = '/admin/login.php?redirect=' + encodeURIComponent(window.location.pathname);
                        return;
                    }
                    throw new Error('Invalid server response');
                }
                
                // Check for redirect in the response (for session expiration)
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                
                // Handle non-OK responses
                if (!response.ok) {
                    // If unauthorized, redirect to login
                    if (response.status === 401) {
                        window.location.href = '/admin/login.php?redirect=' + encodeURIComponent(window.location.pathname);
                        return;
                    }
                    throw new Error(data?.error || `Server returned ${response.status} status`);
                }
                
                // If we got here, the request was successful
                if (!data || data.success === false) {
                    throw new Error(data?.error || 'Upload failed');
                }
                
                // Update hidden input with file path
                const hiddenInput = document.getElementById(elementId);
                if (hiddenInput && data.filePath) {
                    hiddenInput.value = data.filePath;
                }
                
                // Update preview
                updatePreview(elementId, data.filePath, type);
                
                // Clear the file input to allow re-uploading the same file
                input.value = '';
                
                // Show success message
                showSuccess('File uploaded successfully');
                
            })
            .catch(error => {
                // Don't show error if we're already redirecting
                if (!window.location.href.includes('login.php')) {
                    showError(error.message || 'Error uploading file', error.responseText || '');
                }
            })
            .finally(() => {
                // Always restore button state
                if (button) {
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                }
            });
        }
        
        // Update preview for an element
        function updatePreview(elementId, filePath, type = 'image') {
            const previewContainer = document.getElementById(`${elementId}-preview`);
            const previewElement = type === 'image' 
                ? document.getElementById(`${elementId}-preview-img`)
                : document.getElementById(`${elementId}-preview-audio`);
            
            if (previewElement) {
                previewElement.src = filePath;
                previewElement.style.display = 'block';
            }
            
            if (previewContainer) {
                previewContainer.style.display = 'block';
            }
        }
    </script>
</body>
</html>
