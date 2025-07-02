<?php
require_once __DIR__ . '/admin_session.php';
require_role(['admin', 'site_admin', 'test_creator']);

$currentUserRole = get_user_role();
$currentUser = $_SESSION['admin_user'];
$canEditAllAssessments = in_array($currentUserRole, ['admin', 'site_admin']);

// Assessment data file
$assessmentsFile = __DIR__ . '/assets/assessments.json';
$assessments = file_exists($assessmentsFile) ? json_decode(file_get_contents($assessmentsFile), true) : [];
$id = $_GET['id'] ?? null;
$editing = $id !== null && isset($assessments[$id]);

// Check if user can edit this assessment
if ($editing && !$canEditAllAssessments) {
    $assessment = $assessments[$id];
    if (isset($assessment['created_by']) && $assessment['created_by'] !== $currentUser) {
        header('HTTP/1.0 403 Forbidden');
        echo '<h1>403 Forbidden</h1><p>You can only edit assessments that you created.</p>';
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assessmentName = trim($_POST['assessment_name'] ?? '');
    $numTests = max(1, min(10, (int)($_POST['num_tests'] ?? 1)));
    $tests = [];
    for ($i = 0; $i < $numTests; $i++) {
        $tests[] = [
            'left_image' => $_POST['left_image'][$i] ?? '',
            'center_image' => $_POST['center_image'][$i] ?? '',
            'right_image' => $_POST['right_image'][$i] ?? '',
            'left_sound' => $_POST['left_sound'][$i] ?? '',
            'center_sound' => $_POST['center_sound'][$i] ?? '',
            'right_sound' => $_POST['right_sound'][$i] ?? '',
            'correct_image' => $_POST['correct_image'][$i] ?? '',
            'incorrect_image' => $_POST['incorrect_image'][$i] ?? '',
        ];
    }
    $saveId = $editing ? $id : uniqid('assessment_', true);
    $assessments[$saveId] = [
        'name' => $assessmentName,
        'tests' => $tests,
        'created_by' => $editing ? ($assessments[$id]['created_by'] ?? $currentUser) : $currentUser,
        'updated_by' => $currentUser,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if (!$editing) {
        $assessments[$saveId]['created_at'] = date('Y-m-d H:i:s');
    }
    file_put_contents($assessmentsFile, json_encode($assessments, JSON_PRETTY_PRINT));
    header('Location: admin_panel.php');
    exit();
}

// For edit, pre-fill values
$assessment = $editing ? $assessments[$id] : ['name' => '', 'tests' => []];
$numTests = $editing ? count($assessment['tests']) : 1;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $editing ? 'Edit' : 'Create' ?> Assessment - NNTPT Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
    .file-selector {
        max-height: 150px;
        overflow-y: auto;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 0.5rem;
        margin-top: 0.5rem;
    }
    .file-option {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.25rem;
        cursor: pointer;
        border-radius: 0.25rem;
    }
    .file-option .btn {
        flex-shrink: 0;
    }
    .file-option:hover {
        background-color: #f8f9fa;
    }
    .file-option.selected {
        background-color: #e7f3ff;
        border: 1px solid #0d6efd;
    }
    .image-preview {
        width: 40px;
        height: 40px;
        object-fit: cover;
        margin-right: 0.5rem;
        border-radius: 0.25rem;
    }
    .upload-section {
        margin-bottom: 1rem;
        padding: 1rem;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background-color: #f8f9fa;
    }
    </style>
    <script>
    function updateTests() {
        const n = parseInt(document.getElementById('num_tests').value);
        for (let i = 0; i < 10; i++) {
            document.getElementById('test_block_' + i).style.display = i < n ? '' : 'none';
        }
    }
    
    function selectFile(type, position, testIndex, filename) {
        let filePath;
        if (type === 'feedback_image') {
            filePath = 'assets/uploads/feedback/' + filename;
        } else if (filename.startsWith('sounds/')) {
            filePath = 'assets/' + filename;
        } else {
            filePath = 'assets/uploads/' + filename;
        }
        document.getElementById(type + '_' + position + '_' + testIndex).value = filePath;
        // Update selected state
        const container = document.getElementById(type + '_' + position + '_selector_' + testIndex);
        container.querySelectorAll('.file-option').forEach(opt => opt.classList.remove('selected'));
        event.target.closest('.file-option').classList.add('selected');
    }
    
    function showUploadConfirmation(type) {
        const fileInput = document.getElementById('upload_' + type);
        const confirmation = document.getElementById(type + '_upload_confirmation');
        const fileCount = document.getElementById(type + '_file_count');
        
        if (fileInput.files && fileInput.files.length > 0) {
            fileCount.textContent = fileInput.files.length;
            confirmation.style.display = 'block';
        } else {
            confirmation.style.display = 'none';
        }
    }
    
    function cancelUpload(type) {
        const fileInput = document.getElementById('upload_' + type);
        const confirmation = document.getElementById(type + '_upload_confirmation');
        
        fileInput.value = '';
        confirmation.style.display = 'none';
    }
    
    function confirmUpload(type) {
        const fileInput = document.getElementById('upload_' + type);
        
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('No files selected');
            return;
        }
        
        const formData = new FormData();
        for (let i = 0; i < fileInput.files.length; i++) {
            formData.append(type + '_files[]', fileInput.files[i]);
        }
        
        fetch('upload_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            try {
                // Try to extract JSON from response that might have PHP notices
                let jsonText = text;
                const jsonStart = text.indexOf('{');
                if (jsonStart > 0) {
                    jsonText = text.substring(jsonStart);
                }
                
                const data = JSON.parse(jsonText);
                if (data.success) {
                    alert('Files uploaded successfully!');
                    // Reset the upload form
                    const fileInput = document.getElementById('upload_' + type);
                    const confirmation = document.getElementById(type + '_upload_confirmation');
                    fileInput.value = '';
                    confirmation.style.display = 'none';
                    location.reload();
                } else {
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                }
            } catch (e) {
                console.error('Invalid JSON response:', text);
                alert('Upload error: Invalid server response');
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            alert('Upload error: ' + error.message);
        });
    }
    
    function unselectFeedbackFile(type, position, testIndex) {
        // Clear the hidden input value
        document.getElementById(type + '_' + position + '_' + testIndex).value = '';
        
        // Update selected state
        const container = document.getElementById(type + '_' + position + '_selector_' + testIndex);
        container.querySelectorAll('.file-option').forEach(opt => opt.classList.remove('selected'));
        event.target.closest('.file-option').classList.add('selected');
    }
    
    function validateForm() {
        const numTests = parseInt(document.getElementById('num_tests').value);
        const errors = [];
        
        for (let i = 0; i < numTests; i++) {
            const testNum = i + 1;
            
            // Check required image fields
            const leftImage = document.getElementById('image_left_' + i).value;
            const centerImage = document.getElementById('image_center_' + i).value;
            const rightImage = document.getElementById('image_right_' + i).value;
            
            if (!leftImage) errors.push(`Test ${testNum}: Left Image is required`);
            if (!centerImage) errors.push(`Test ${testNum}: Center Image is required`);
            if (!rightImage) errors.push(`Test ${testNum}: Right Image is required`);
            
            // Check required audio fields
            const leftAudio = document.getElementById('audio_left_' + i).value;
            const centerAudio = document.getElementById('audio_center_' + i).value;
            const rightAudio = document.getElementById('audio_right_' + i).value;
            
            if (!leftAudio) errors.push(`Test ${testNum}: Left Audio is required`);
            if (!centerAudio) errors.push(`Test ${testNum}: Center Audio is required`);
            if (!rightAudio) errors.push(`Test ${testNum}: Right Audio is required`);
            
            // Feedback images are optional - no validation needed
        }
        
        if (errors.length > 0) {
            alert('Please fix the following errors:\n\n' + errors.join('\n'));
            return false;
        }
        
        return true;
    }
    
    function playAudio(filePath) {
        // Stop any currently playing audio
        const existingAudio = document.getElementById('preview_audio');
        if (existingAudio) {
            existingAudio.pause();
            existingAudio.remove();
        }
        
        // Create new audio element
        const audio = document.createElement('audio');
        audio.id = 'preview_audio';
        audio.src = filePath;
        audio.volume = 0.5; // Set to 50% volume for preview
        
        // Play the audio
        audio.play().catch(error => {
            console.error('Error playing audio:', error);
            alert('Could not play audio file. Please check if the file exists and is a valid audio format.');
        });
        
        // Clean up after audio ends
        audio.addEventListener('ended', function() {
            audio.remove();
        });
        
        // Append to body (hidden)
        document.body.appendChild(audio);
    }
    </script>
</head>
<body class="bg-light">
<div class="container mt-4">
    <a href="admin_panel.php" class="btn btn-link">&larr; Back to Admin Panel</a>
    <h2><?= $editing ? 'Edit' : 'Create New' ?> Assessment</h2>
    <form method="post">
        <!-- Assessment Configuration Section -->
        <div class="upload-section mb-4">
            <h5 class="text-primary">⚙️ Assessment Configuration</h5>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Assessment Name</label>
                    <input type="text" class="form-control" name="assessment_name" required value="<?= htmlspecialchars($assessment['name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Number of Tests</label>
                    <select name="num_tests" id="num_tests" class="form-select" onchange="updateTests()">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>"<?= $i == $numTests ? ' selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </div>
        <?php for ($i = 0; $i < 10; $i++):
            $test = $assessment['tests'][$i] ?? ['left_image'=>'','center_image'=>'','right_image'=>'','left_sound'=>'','center_sound'=>'','right_sound'=>'']; ?>
        <div id="test_block_<?= $i ?>" class="card mb-3" style="<?= $i >= $numTests ? 'display:none;' : '' ?>">
            <div class="card-header">Test <?= $i+1 ?></div>
            <div class="card-body">
                <?php if ($i == 0): ?>
                <h5 class="text-success">Test Configuration</h5>
                <?php endif; ?>
                
                <!-- Image Selection Section -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="text-primary mb-0">📷 Image Selection for Test <?= $i+1 ?></h6>
                        <?php if ($i == 0): ?>
                        <div>
                            <input type="file" id="upload_image" class="form-control" multiple accept="image/*" style="display: none;" onchange="showUploadConfirmation('image')">
                            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('upload_image').click()">Upload Images</button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($i == 0): ?>
                    <div id="image_upload_confirmation" style="display: none;" class="mt-3">
                        <div class="alert alert-info">
                            <strong id="image_file_count">0</strong> image(s) selected
                            <div class="mt-2">
                                <button type="button" class="btn btn-success btn-sm" onclick="confirmUpload('image')">Confirm Upload</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="cancelUpload('image')">Cancel</button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Left Image</label>
                            <input type="hidden" id="image_left_<?= $i ?>" name="left_image[]" value="<?= htmlspecialchars($test['left_image']) ?>">
                            <div class="file-selector" id="image_left_selector_<?= $i ?>">
                            <?php
                            $uploadDir = __DIR__ . '/assets/uploads/';
                            $imageFiles = [];
                            if (is_dir($uploadDir)) {
                                $files = scandir($uploadDir);
                                foreach ($files as $file) {
                                    if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'svg', 'bmp'])) {
                                        $imageFiles[] = $file;
                                    }
                                }
                            }
                            foreach ($imageFiles as $file): 
                                $isSelected = ($test['left_image'] === 'assets/uploads/' . $file);
                            ?>
                            <div class="file-option<?= $isSelected ? ' selected' : '' ?>" onclick="selectFile('image', 'left', <?= $i ?>, '<?= $file ?>')">
                                <img src="assets/uploads/<?= $file ?>" class="image-preview" alt="<?= $file ?>">
                                <span><?= $file ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label>Center Image</label>
                        <input type="hidden" id="image_center_<?= $i ?>" name="center_image[]" value="<?= htmlspecialchars($test['center_image']) ?>">
                        <div class="file-selector" id="image_center_selector_<?= $i ?>">
                            <?php foreach ($imageFiles as $file): 
                                $isSelected = ($test['center_image'] === 'assets/uploads/' . $file);
                            ?>
                            <div class="file-option<?= $isSelected ? ' selected' : '' ?>" onclick="selectFile('image', 'center', <?= $i ?>, '<?= $file ?>')">
                                <img src="assets/uploads/<?= $file ?>" class="image-preview" alt="<?= $file ?>">
                                <span><?= $file ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label>Right Image</label>
                        <input type="hidden" id="image_right_<?= $i ?>" name="right_image[]" value="<?= htmlspecialchars($test['right_image']) ?>">
                        <div class="file-selector" id="image_right_selector_<?= $i ?>">
                            <?php foreach ($imageFiles as $file): 
                                $isSelected = ($test['right_image'] === 'assets/uploads/' . $file);
                            ?>
                            <div class="file-option<?= $isSelected ? ' selected' : '' ?>" onclick="selectFile('image', 'right', <?= $i ?>, '<?= $file ?>')">
                                <img src="assets/uploads/<?= $file ?>" class="image-preview" alt="<?= $file ?>">
                                <span><?= $file ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    </div>
                </div>
                
                <!-- Audio Selection Section -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="text-primary mb-0">🎵 Audio Selection for Test <?= $i+1 ?></h6>
                        <?php if ($i == 0): ?>
                        <div>
                            <input type="file" id="upload_audio" class="form-control" multiple accept="audio/*" style="display: none;" onchange="showUploadConfirmation('audio')">
                            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('upload_audio').click()">Upload Audio</button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($i == 0): ?>
                    <div id="audio_upload_confirmation" style="display: none;" class="mt-3">
                        <div class="alert alert-info">
                            <strong id="audio_file_count">0</strong> audio file(s) selected
                            <div class="mt-2">
                                <button type="button" class="btn btn-success btn-sm" onclick="confirmUpload('audio')">Confirm Upload</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="cancelUpload('audio')">Cancel</button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Left Sound</label>
                            <input type="hidden" id="audio_left_<?= $i ?>" name="left_sound[]" value="<?= htmlspecialchars($test['left_sound']) ?>">
                            <div class="file-selector" id="audio_left_selector_<?= $i ?>">
                            <?php
                            $audioFiles = [];
                            if (is_dir($uploadDir)) {
                                $files = scandir($uploadDir);
                                foreach ($files as $file) {
                                    if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['mp3', 'wav', 'ogg', 'm4a'])) {
                                        $audioFiles[] = $file;
                                    }
                                }
                            }
                            // Also include files from assets/sounds
                            $soundsDir = __DIR__ . '/assets/sounds/';
                            if (is_dir($soundsDir)) {
                                $soundFiles = scandir($soundsDir);
                                foreach ($soundFiles as $file) {
                                    if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['mp3', 'wav', 'ogg', 'm4a'])) {
                                        $audioFiles[] = 'sounds/' . $file;
                                    }
                                }
                            }
                            foreach ($audioFiles as $file): 
                                $filePath = strpos($file, 'sounds/') === 0 ? 'assets/' . $file : 'assets/uploads/' . $file;
                                $fileName = basename($file);
                                $isSelected = ($test['left_sound'] === $filePath);
                            ?>
                            <div class="file-option<?= $isSelected ? ' selected' : '' ?>" onclick="selectFile('audio', 'left', <?= $i ?>, '<?= $file ?>')">
                                <span>🎵 <?= $fileName ?></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="event.stopPropagation(); playAudio('<?= $filePath ?>')">▶️</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label>Center Sound</label>
                        <input type="hidden" id="audio_center_<?= $i ?>" name="center_sound[]" value="<?= htmlspecialchars($test['center_sound']) ?>">
                        <div class="file-selector" id="audio_center_selector_<?= $i ?>">
                            <?php foreach ($audioFiles as $file): 
                                $filePath = strpos($file, 'sounds/') === 0 ? 'assets/' . $file : 'assets/uploads/' . $file;
                                $fileName = basename($file);
                                $isSelected = ($test['center_sound'] === $filePath);
                            ?>
                            <div class="file-option<?= $isSelected ? ' selected' : '' ?>" onclick="selectFile('audio', 'center', <?= $i ?>, '<?= $file ?>')">
                                <span>🎵 <?= $fileName ?></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="event.stopPropagation(); playAudio('<?= $filePath ?>')">▶️</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label>Right Sound</label>
                        <input type="hidden" id="audio_right_<?= $i ?>" name="right_sound[]" value="<?= htmlspecialchars($test['right_sound']) ?>">
                        <div class="file-selector" id="audio_right_selector_<?= $i ?>">
                            <?php foreach ($audioFiles as $file): 
                                $filePath = strpos($file, 'sounds/') === 0 ? 'assets/' . $file : 'assets/uploads/' . $file;
                                $fileName = basename($file);
                                $isSelected = ($test['right_sound'] === $filePath);
                            ?>
                            <div class="file-option<?= $isSelected ? ' selected' : '' ?>" onclick="selectFile('audio', 'right', <?= $i ?>, '<?= $file ?>')">
                                <span>🎵 <?= $fileName ?></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="event.stopPropagation(); playAudio('<?= $filePath ?>')">▶️</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    </div>
                </div>
                
                <!-- Correct and Incorrect Image Selection Section -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="text-warning mb-0">✅❌ Correct and Incorrect Image Selection</h6>
                        <?php if ($i == 0): ?>
                        <div>
                            <input type="file" id="upload_feedback_image" class="form-control" multiple accept="image/*" style="display: none;" onchange="showUploadConfirmation('feedback_image')">
                            <button type="button" class="btn btn-warning btn-sm" onclick="document.getElementById('upload_feedback_image').click()">Upload Images</button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($i == 0): ?>
                    <div id="feedback_image_upload_confirmation" style="display: none;" class="mt-3">
                        <div class="alert alert-info">
                            <strong id="feedback_image_file_count">0</strong> image(s) selected
                            <div class="mt-2">
                                <button type="button" class="btn btn-success btn-sm" onclick="confirmUpload('feedback_image')">Confirm Upload</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="cancelUpload('feedback_image')">Cancel</button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Correct Image</label>
                            <input type="hidden" id="feedback_image_correct_<?= $i ?>" name="correct_image[]" value="<?= htmlspecialchars($test['correct_image'] ?? '') ?>">
                            <div class="file-selector" id="feedback_image_correct_selector_<?= $i ?>">
                            <?php
                            $feedbackUploadDir = __DIR__ . '/assets/uploads/feedback/';
                            $feedbackImageFiles = [];
                            if (is_dir($feedbackUploadDir)) {
                                $files = scandir($feedbackUploadDir);
                                foreach ($files as $file) {
                                    if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'svg', 'bmp'])) {
                                        $feedbackImageFiles[] = $file;
                                    }
                                }
                            }
                            
                            // Add "None/Clear" option
                            $isNoneSelected = empty($test['correct_image'] ?? '');
                            ?>
                            <div class="file-option<?= $isNoneSelected ? ' selected' : '' ?>" onclick="unselectFeedbackFile('feedback_image', 'correct', <?= $i ?>)">
                                <span style="color: #6c757d; font-style: italic;">❌ None / Clear Selection</span>
                            </div>
                            <?php
                            foreach ($feedbackImageFiles as $file): 
                                $isSelected = ($test['correct_image'] ?? '') === 'assets/uploads/feedback/' . $file;
                            ?>
                            <div class="file-option<?= $isSelected ? ' selected' : '' ?>" onclick="selectFile('feedback_image', 'correct', <?= $i ?>, '<?= $file ?>')">
                                <img src="assets/uploads/feedback/<?= $file ?>" class="image-preview" alt="<?= $file ?>">
                                <span><?= $file ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label>Incorrect Image</label>
                        <input type="hidden" id="feedback_image_incorrect_<?= $i ?>" name="incorrect_image[]" value="<?= htmlspecialchars($test['incorrect_image'] ?? '') ?>">
                        <div class="file-selector" id="feedback_image_incorrect_selector_<?= $i ?>">
                            <?php
                            // Add "None/Clear" option
                            $isNoneSelected = empty($test['incorrect_image'] ?? '');
                            ?>
                            <div class="file-option<?= $isNoneSelected ? ' selected' : '' ?>" onclick="unselectFeedbackFile('feedback_image', 'incorrect', <?= $i ?>)">
                                <span style="color: #6c757d; font-style: italic;">❌ None / Clear Selection</span>
                            </div>
                            <?php foreach ($feedbackImageFiles as $file): 
                                $isSelected = ($test['incorrect_image'] ?? '') === 'assets/uploads/feedback/' . $file;
                            ?>
                            <div class="file-option<?= $isSelected ? ' selected' : '' ?>" onclick="selectFile('feedback_image', 'incorrect', <?= $i ?>, '<?= $file ?>')">
                                <img src="assets/uploads/feedback/<?= $file ?>" class="image-preview" alt="<?= $file ?>">
                                <span><?= $file ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endfor; ?>
        
        <button type="submit" class="btn btn-success" onclick="return validateForm()">Save Assessment</button>
    </form>
</div>
<script>updateTests();</script>
</body>
</html>
