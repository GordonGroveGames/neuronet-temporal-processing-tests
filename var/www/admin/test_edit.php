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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
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
        .preview-image {
            max-width: 100px;
            max-height: 100px;
            margin-top: 10px;
            display: none;
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
                
                <form method="POST" action="" enctype="multipart/form-data">
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
                                        <label for="left_image" class="form-label">Image</label>
                                        <input type="text" class="form-control" id="left_image" name="left_image" 
                                               value="<?php echo htmlspecialchars($test['left_image'] ?? ''); ?>">
                                        <img src="" id="left_image_preview" class="preview-image" alt="Left Image Preview">
                                    </div>
                                    <div class="mb-3">
                                        <label for="left_sound" class="form-label">Sound</label>
                                        <input type="text" class="form-control" id="left_sound" name="left_sound" 
                                               value="<?php echo htmlspecialchars($test['left_sound'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <h5>Center Section</h5>
                                    <div class="mb-3">
                                        <label for="center_image" class="form-label">Image</label>
                                        <input type="text" class="form-control" id="center_image" name="center_image" 
                                               value="<?php echo htmlspecialchars($test['center_image'] ?? ''); ?>">
                                        <img src="" id="center_image_preview" class="preview-image" alt="Center Image Preview">
                                    </div>
                                    <div class="mb-3">
                                        <label for="center_sound" class="form-label">Sound</label>
                                        <input type="text" class="form-control" id="center_sound" name="center_sound" 
                                               value="<?php echo htmlspecialchars($test['center_sound'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <h5>Right Section</h5>
                                    <div class="mb-3">
                                        <label for="right_image" class="form-label">Image</label>
                                        <input type="text" class="form-control" id="right_image" name="right_image" 
                                               value="<?php echo htmlspecialchars($test['right_image'] ?? ''); ?>">
                                        <img src="" id="right_image_preview" class="preview-image" alt="Right Image Preview">
                                    </div>
                                    <div class="mb-3">
                                        <label for="right_sound" class="form-label">Sound</label>
                                        <input type="text" class="form-control" id="right_sound" name="right_sound" 
                                               value="<?php echo htmlspecialchars($test['right_sound'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Feedback Images</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="correct_image" class="form-label">Correct Answer</label>
                                            <input type="text" class="form-control" id="correct_image" name="correct_image" 
                                                   value="<?php echo htmlspecialchars($test['correct_image'] ?? ''); ?>">
                                            <img src="" id="correct_image_preview" class="preview-image" alt="Correct Image Preview">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="incorrect_image" class="form-label">Incorrect Answer</label>
                                            <input type="text" class="form-control" id="incorrect_image" name="incorrect_image" 
                                                   value="<?php echo htmlspecialchars($test['incorrect_image'] ?? ''); ?>">
                                            <img src="" id="incorrect_image_preview" class="preview-image" alt="Incorrect Image Preview">
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
        document.getElementById('addParameter').addEventListener('click', function() {
            const container = document.getElementById('parametersContainer');
            const newRow = document.createElement('div');
            newRow.className = 'parameter-row';
            newRow.innerHTML = `
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="param_name[]" placeholder="Parameter name" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="param_value[]" placeholder="Value">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="param_type[]">
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
            });
        });
        
        // Add event listeners to existing remove buttons
        document.querySelectorAll('.remove-parameter').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.parameter-row').remove();
            });
        });
        
        // Image preview
        function setupImagePreview(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            
            input.addEventListener('input', function() {
                if (this.value) {
                    preview.src = this.value;
                    preview.style.display = 'block';
                } else {
                    preview.style.display = 'none';
                }
            });
            
            // Trigger input event to show initial preview if value exists
            if (input.value) {
                input.dispatchEvent(new Event('input'));
            }
        }
        
        // Set up all image previews
        setupImagePreview('left_image', 'left_image_preview');
        setupImagePreview('center_image', 'center_image_preview');
        setupImagePreview('right_image', 'right_image_preview');
        setupImagePreview('correct_image', 'correct_image_preview');
        setupImagePreview('incorrect_image', 'incorrect_image_preview');
    </script>
</body>
</html>
