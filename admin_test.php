<?php
require_once __DIR__ . '/admin_session.php';
require_role(['admin', 'site_admin', 'test_creator']);

$currentUserRole = get_user_role();
$currentUser = $_SESSION['admin_user'];
$canEditAll = in_array($currentUserRole, ['admin', 'site_admin']);

// Tests data file
$testsFile = __DIR__ . '/assets/tests.json';
$allTests = file_exists($testsFile) ? json_decode(file_get_contents($testsFile), true) : [];
$id = $_GET['id'] ?? null;
$editing = $id !== null && isset($allTests[$id]);

// Check permissions for editing
if ($editing && !$canEditAll) {
    $testData = $allTests[$id];
    if (isset($testData['created_by']) && $testData['created_by'] !== $currentUser) {
        header('HTTP/1.0 403 Forbidden');
        echo '<h1>403 Forbidden</h1><p>You can only edit tests that you created.</p>';
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testName = trim($_POST['test_name'] ?? '');
    $testData = [
        'name' => $testName,
        'left_image' => $_POST['left_image'] ?? '',
        'center_image' => $_POST['center_image'] ?? '',
        'right_image' => $_POST['right_image'] ?? '',
        'left_sound' => $_POST['left_sound'] ?? '',
        'center_sound' => $_POST['center_sound'] ?? '',
        'right_sound' => $_POST['right_sound'] ?? '',
        'correct_image' => $_POST['correct_image'] ?? '',
        'incorrect_image' => $_POST['incorrect_image'] ?? '',
        'created_by' => $editing ? ($allTests[$id]['created_by'] ?? $currentUser) : $currentUser,
        'updated_by' => $currentUser,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if (!$editing) {
        $testData['created_at'] = date('Y-m-d H:i:s');
    }

    $saveId = $editing ? $id : uniqid('test_', true);
    $allTests[$saveId] = $testData;

    file_put_contents($testsFile, json_encode($allTests, JSON_PRETTY_PRINT));
    header('Location: admin_panel.php');
    exit();
}

// For edit, pre-fill values
$test = $editing ? $allTests[$id] : [
    'name' => '', 'left_image' => '', 'center_image' => '', 'right_image' => '',
    'left_sound' => '', 'center_sound' => '', 'right_sound' => '',
    'correct_image' => '', 'incorrect_image' => ''
];

// Determine default feedback images
$defaultCorrectImage = '';
$defaultIncorrectImage = '';
$correctDir = __DIR__ . '/assets/uploads/feedback/correct/';
$incorrectDir = __DIR__ . '/assets/uploads/feedback/incorrect/';
if (file_exists($correctDir . 'Full Milk Bottle.bmp')) {
    $defaultCorrectImage = 'assets/uploads/feedback/correct/Full Milk Bottle.bmp';
}
if (file_exists($incorrectDir . 'Empty Milk Bottle.bmp')) {
    $defaultIncorrectImage = 'assets/uploads/feedback/incorrect/Empty Milk Bottle.bmp';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editing ? 'Edit' : 'Create' ?> Test — The Fluency Factor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/touch-fixes.css">
    <link rel="stylesheet" href="assets/css/admin-styles.css">
    <style>
        .card-modern:hover { transform: none; }
        .upload-slot.drag-over {
            border-color: var(--primary) !important;
            background: var(--primary-light) !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }
    </style>
</head>
<body>
    <!-- Gradient Navbar -->
    <nav class="navbar-admin d-flex justify-content-between align-items-center">
        <a class="navbar-brand" href="admin_panel.php">The Fluency Factor</a>
        <div class="d-flex align-items-center gap-3">
            <a href="admin_panel.php" class="nav-link-light"><i class="fa-solid fa-arrow-left me-1"></i> Admin Panel</a>
            <a href="admin_logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket me-1"></i> Logout</a>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="container mt-4 mb-2">
        <h2 style="font-weight:700;">
            <i class="fa-solid fa-vial me-2" style="color:var(--primary);"></i>
            <?= $editing ? 'Edit' : 'Create New' ?> Test
        </h2>
    </div>

    <!-- Progress Stepper -->
    <div class="container mb-3">
        <div class="card-modern" style="padding:1rem 1.25rem;">
            <div class="wizard-stepper" id="wizardStepper">
                <div class="wizard-step active" data-step="1">
                    <div class="wizard-step-number">1</div>
                    <div class="wizard-step-label">Name</div>
                </div>
                <div class="wizard-step-connector" data-after="1"></div>
                <div class="wizard-step" data-step="2">
                    <div class="wizard-step-number">2</div>
                    <div class="wizard-step-label">Upload Assets</div>
                </div>
                <div class="wizard-step-connector" data-after="2"></div>
                <div class="wizard-step" data-step="3">
                    <div class="wizard-step-number">3</div>
                    <div class="wizard-step-label">Feedback & Save</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wizard Panels Container -->
    <div class="container" id="wizardContainer">

        <!-- ========== STEP 1: Name ========== -->
        <div class="wizard-panel" data-step="1">
            <div class="card-modern">
                <div class="card-header">
                    <strong>Step 1 of 3</strong> &mdash; Test Name
                </div>
                <div class="card-body" style="padding:1.5rem;">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Test Name</label>
                        <input type="text" class="form-control form-control-lg" id="wizardName"
                               placeholder="e.g. Cat Dog Cow"
                               value="<?= htmlspecialchars($test['name']) ?>">
                        <div class="form-text">Give your test a descriptive name. A test has 3 images and 3 sounds (Left / Center / Right).</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== STEP 2: Upload Assets ========== -->
        <div class="wizard-panel" data-step="2" style="display:none;">
            <div class="card-modern">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <strong>Step 2 of 3 &mdash; Upload Assets</strong>
                    <span class="test-status-badge incomplete" id="testStatus">0 of 6</span>
                </div>
                <div class="card-body">
                    <div class="row g-3" id="uploadSlotsContainer">
                        <!-- JS renders upload slots here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== STEP 3: Feedback & Save ========== -->
        <div class="wizard-panel" data-step="3" style="display:none;">
            <div class="card-modern">
                <div class="card-header">
                    <strong>Step 3 of 3</strong> &mdash; Feedback Images (Optional)
                </div>
                <div class="card-body" style="padding:1.5rem;">
                    <p class="text-secondary mb-4">
                        These images are shown after each response. Leave blank to skip.
                    </p>
                    <div class="row g-4">
                        <!-- Correct feedback -->
                        <div class="col-12 col-sm-6">
                            <div class="feedback-upload-card">
                                <div class="feedback-label">
                                    <i class="fa-solid fa-circle-check" style="color:var(--success);"></i>
                                    Correct Response
                                </div>
                                <div class="upload-slot" id="feedbackCorrectSlot"
                                     onclick="triggerUpload(this)">
                                    <input type="file" accept="image/*" style="display:none;"
                                           onchange="handleFeedbackUpload(this, 'correct')">
                                    <div class="upload-slot-empty">
                                        <i class="fa-solid fa-image"></i>
                                        <span>Tap to add image</span>
                                    </div>
                                    <div class="upload-slot-filled" style="display:none;">
                                        <img class="upload-slot-thumb" src="" alt="">
                                        <span class="upload-slot-filename"></span>
                                        <div class="upload-slot-actions">
                                            <button type="button" class="upload-slot-btn"
                                                    onclick="event.stopPropagation(); triggerUpload(this.closest('.upload-slot'))">
                                                <i class="fa-solid fa-arrows-rotate"></i>
                                            </button>
                                            <button type="button" class="upload-slot-btn btn-clear"
                                                    onclick="event.stopPropagation(); clearFeedback('correct')">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Incorrect feedback -->
                        <div class="col-12 col-sm-6">
                            <div class="feedback-upload-card">
                                <div class="feedback-label">
                                    <i class="fa-solid fa-circle-xmark" style="color:var(--danger);"></i>
                                    Incorrect Response
                                </div>
                                <div class="upload-slot" id="feedbackIncorrectSlot"
                                     onclick="triggerUpload(this)">
                                    <input type="file" accept="image/*" style="display:none;"
                                           onchange="handleFeedbackUpload(this, 'incorrect')">
                                    <div class="upload-slot-empty">
                                        <i class="fa-solid fa-image"></i>
                                        <span>Tap to add image</span>
                                    </div>
                                    <div class="upload-slot-filled" style="display:none;">
                                        <img class="upload-slot-thumb" src="" alt="">
                                        <span class="upload-slot-filename"></span>
                                        <div class="upload-slot-actions">
                                            <button type="button" class="upload-slot-btn"
                                                    onclick="event.stopPropagation(); triggerUpload(this.closest('.upload-slot'))">
                                                <i class="fa-solid fa-arrows-rotate"></i>
                                            </button>
                                            <button type="button" class="upload-slot-btn btn-clear"
                                                    onclick="event.stopPropagation(); clearFeedback('incorrect')">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Review Summary -->
            <div class="card-modern mt-3">
                <div class="card-header">
                    <strong>Review</strong>
                </div>
                <div class="card-body" style="padding:1.5rem;">
                    <div id="reviewContent">
                        <!-- JS populates review here -->
                    </div>
                </div>
            </div>

            <!-- Hidden form for submission -->
            <form method="post" id="wizardForm" style="display:none;">
                <input type="hidden" name="test_name" id="formTestName">
                <input type="hidden" name="left_image" id="formLeftImage">
                <input type="hidden" name="center_image" id="formCenterImage">
                <input type="hidden" name="right_image" id="formRightImage">
                <input type="hidden" name="left_sound" id="formLeftSound">
                <input type="hidden" name="center_sound" id="formCenterSound">
                <input type="hidden" name="right_sound" id="formRightSound">
                <input type="hidden" name="correct_image" id="formCorrectImage">
                <input type="hidden" name="incorrect_image" id="formIncorrectImage">
            </form>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="wizard-nav" id="wizardNav">
        <div class="container d-flex justify-content-between align-items-center">
            <button type="button" class="btn-ghost" id="wizardBackBtn" onclick="wizardBack()" style="text-decoration:none;display:none;">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </button>
            <span class="wizard-nav-label" id="wizardNavLabel">Step 1 of 3</span>
            <button type="button" class="btn-modern-primary" id="wizardNextBtn" onclick="wizardNext()">
                Next <i class="fa-solid fa-arrow-right ms-1"></i>
            </button>
        </div>
    </div>
    <div style="height:80px;"></div>

    <script>
    // ============================================
    // Wizard State
    // ============================================
    const wizardState = {
        currentStep: 1,
        totalSteps: 3,
        editing: <?= json_encode($editing) ?>,
        testName: <?= json_encode($test['name']) ?>,
        test: {
            left_image: <?= json_encode($test['left_image']) ?>,
            center_image: <?= json_encode($test['center_image']) ?>,
            right_image: <?= json_encode($test['right_image']) ?>,
            left_sound: <?= json_encode($test['left_sound']) ?>,
            center_sound: <?= json_encode($test['center_sound']) ?>,
            right_sound: <?= json_encode($test['right_sound']) ?>
        },
        correctImage: <?= json_encode(
            $editing
                ? ($test['correct_image'] ?? '')
                : $defaultCorrectImage
        ) ?>,
        incorrectImage: <?= json_encode(
            $editing
                ? ($test['incorrect_image'] ?? '')
                : $defaultIncorrectImage
        ) ?>
    };

    let currentAudio = null;

    // ============================================
    // Navigation
    // ============================================
    function wizardNext() {
        if (!validateStep(wizardState.currentStep)) return;

        if (wizardState.currentStep === wizardState.totalSteps) {
            submitWizard();
            return;
        }

        wizardState.currentStep++;
        renderStep();
    }

    function wizardBack() {
        if (wizardState.currentStep <= 1) return;
        wizardState.currentStep--;
        renderStep();
    }

    function renderStep() {
        const step = wizardState.currentStep;

        // Show/hide panels
        document.querySelectorAll('.wizard-panel').forEach(p => {
            p.style.display = parseInt(p.dataset.step) === step ? '' : 'none';
        });

        // Update stepper
        document.querySelectorAll('.wizard-step').forEach(s => {
            const sStep = parseInt(s.dataset.step);
            s.classList.remove('active', 'completed');
            if (sStep === step) s.classList.add('active');
            else if (sStep < step) s.classList.add('completed');
        });
        document.querySelectorAll('.wizard-step-connector').forEach(c => {
            const afterStep = parseInt(c.dataset.after);
            c.classList.toggle('completed', afterStep < step);
        });

        // Update nav buttons
        const backBtn = document.getElementById('wizardBackBtn');
        const nextBtn = document.getElementById('wizardNextBtn');
        const navLabel = document.getElementById('wizardNavLabel');

        backBtn.style.display = step === 1 ? 'none' : '';
        navLabel.textContent = 'Step ' + step + ' of ' + wizardState.totalSteps;

        if (step === wizardState.totalSteps) {
            nextBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Save Test';
        } else {
            nextBtn.innerHTML = 'Next <i class="fa-solid fa-arrow-right ms-1"></i>';
        }

        // Step-specific rendering
        if (step === 2) renderUploadSlots();
        if (step === 3) {
            renderFeedbackStep();
            renderReview();
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ============================================
    // Validation
    // ============================================
    function validateStep(step) {
        if (step === 1) {
            const name = document.getElementById('wizardName').value.trim();
            if (!name) {
                document.getElementById('wizardName').focus();
                document.getElementById('wizardName').style.borderColor = 'var(--danger)';
                alert('Please enter a test name.');
                return false;
            }
            wizardState.testName = name;
            document.getElementById('wizardName').style.borderColor = '';
            return true;
        }

        if (step === 2) {
            const t = wizardState.test;
            const missing = [];
            if (!t.left_image) missing.push('Left Image');
            if (!t.center_image) missing.push('Center Image');
            if (!t.right_image) missing.push('Right Image');
            if (!t.left_sound) missing.push('Left Sound');
            if (!t.center_sound) missing.push('Center Sound');
            if (!t.right_sound) missing.push('Right Sound');

            if (missing.length > 0) {
                document.querySelectorAll('.upload-slot[data-position]').forEach(slot => {
                    const stateField = slot.dataset.position + '_' + (slot.dataset.type === 'audio' ? 'sound' : 'image');
                    if (!wizardState.test[stateField]) {
                        slot.classList.add('has-error');
                        setTimeout(() => slot.classList.remove('has-error'), 2000);
                    }
                });
                const firstError = document.querySelector('.upload-slot.has-error');
                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                alert('Missing: ' + missing.join(', '));
                return false;
            }
            return true;
        }

        return true;
    }

    // ============================================
    // Step 2: Upload Slots
    // ============================================
    function renderUploadSlots() {
        const container = document.getElementById('uploadSlotsContainer');
        const t = wizardState.test;

        const channels = [
            { pos: 'left', label: 'Left', cls: 'channel-left' },
            { pos: 'center', label: 'Center', cls: 'channel-center' },
            { pos: 'right', label: 'Right', cls: 'channel-right' }
        ];

        let html = '';
        channels.forEach(ch => {
            const imgPath = t[ch.pos + '_image'] || '';
            const audioPath = t[ch.pos + '_sound'] || '';

            html += '<div class="col-12 col-md-4">';
            html += '<div class="channel-card ' + ch.cls + '">';
            html += '<div class="channel-label">' + ch.label + '</div>';
            html += buildSlot('image', ch.pos, imgPath, 'image/*', 'fa-image', 'Add image');
            html += '<div style="margin-top:0.5rem;">';
            html += buildSlot('audio', ch.pos, audioPath, 'audio/*', 'fa-volume-high', 'Add audio');
            html += '</div>';
            html += '</div></div>';
        });

        container.innerHTML = html;
        updateTestStatus();
    }

    function buildSlot(type, position, currentPath, accept, icon, label) {
        const hasFile = !!currentPath;
        const filename = currentPath ? currentPath.split('/').pop() : '';
        const isImage = type === 'image';

        let html = '<div class="upload-slot' + (hasFile ? ' has-file' : '') + '"';
        html += ' data-type="' + type + '" data-position="' + position + '"';
        html += ' onclick="triggerUpload(this)"';
        html += ' ondragover="onSlotDragOver(event)" ondragleave="onSlotDragLeave(event)" ondrop="onSlotDrop(event)">';
        html += '<input type="file" accept="' + accept + '" style="display:none;" onchange="handleSlotUpload(this)">';

        html += '<div class="upload-slot-empty"' + (hasFile ? ' style="display:none;"' : '') + '>';
        html += '<i class="fa-solid ' + icon + '"></i>';
        html += '<span>' + label + ' or drag here</span>';
        html += '</div>';

        html += '<div class="upload-slot-filled"' + (!hasFile ? ' style="display:none;"' : '') + '>';
        if (isImage) {
            html += '<img class="upload-slot-thumb" src="' + (currentPath || '') + '" alt="">';
        } else {
            html += '<i class="fa-solid fa-volume-high" style="color:var(--text-secondary);font-size:1.1rem;flex-shrink:0;"></i>';
        }
        html += '<span class="upload-slot-filename">' + escapeHtml(filename) + '</span>';
        html += '<div class="upload-slot-actions">';
        if (!isImage) {
            html += '<button type="button" class="upload-slot-btn" onclick="event.stopPropagation(); playSlotAudio(this)" title="Play">';
            html += '<i class="fa-solid fa-play"></i></button>';
        }
        html += '<button type="button" class="upload-slot-btn" onclick="event.stopPropagation(); triggerUpload(this.closest(\'.upload-slot\'))" title="Replace">';
        html += '<i class="fa-solid fa-arrows-rotate"></i></button>';
        html += '</div>';
        html += '</div>';

        html += '</div>';
        return html;
    }

    function updateTestStatus() {
        const t = wizardState.test;
        const filled = [t.left_image, t.center_image, t.right_image, t.left_sound, t.center_sound, t.right_sound].filter(Boolean).length;
        const badge = document.getElementById('testStatus');
        if (!badge) return;
        if (filled === 6) {
            badge.className = 'test-status-badge complete';
            badge.innerHTML = '<i class="fa-solid fa-check me-1"></i>Complete';
        } else {
            badge.className = 'test-status-badge incomplete';
            badge.textContent = filled + ' of 6';
        }
    }

    // ============================================
    // Upload Handling
    // ============================================
    function triggerUpload(slotEl) {
        const fileInput = slotEl.querySelector('input[type="file"]');
        if (fileInput) fileInput.click();
    }

    // Drag-and-drop handlers for upload slots
    function onSlotDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        e.dataTransfer.dropEffect = 'copy';
        const slot = e.target.closest('.upload-slot');
        if (slot) slot.classList.add('drag-over');
    }

    function onSlotDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        const slot = e.target.closest('.upload-slot');
        if (slot) slot.classList.remove('drag-over');
    }

    function onSlotDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        const slot = e.target.closest('.upload-slot');
        if (!slot) return;
        slot.classList.remove('drag-over');

        const files = e.dataTransfer.files;
        if (!files || files.length === 0) return;

        const file = files[0];
        const type = slot.dataset.type; // 'image' or 'audio'

        // Validate file type
        if (type === 'image' && !file.type.startsWith('image/')) {
            alert('Please drop an image file here.');
            return;
        }
        if (type === 'audio' && !file.type.startsWith('audio/')) {
            alert('Please drop an audio file here.');
            return;
        }

        // Upload the dropped file using the same handler
        uploadFileToSlot(slot, file);
    }

    function uploadFileToSlot(slot, file) {
        const type = slot.dataset.type;
        const position = slot.dataset.position;
        const formKey = type === 'image' ? 'image_files[]' : 'audio_files[]';

        slot.classList.add('uploading');
        slot.classList.remove('has-file', 'has-error');

        const formData = new FormData();
        formData.append(formKey, file);

        fetch('upload_handler.php', { method: 'POST', body: formData })
            .then(resp => resp.text())
            .then(text => {
                let jsonText = text;
                const jsonStart = text.indexOf('{');
                if (jsonStart > 0) jsonText = text.substring(jsonStart);
                return JSON.parse(jsonText);
            })
            .then(data => {
                slot.classList.remove('uploading');
                if (data.success && data.files && data.files.length > 0) {
                    const filename = data.files[0];
                    const fullPath = 'assets/uploads/' + filename;
                    const stateField = position + '_' + (type === 'audio' ? 'sound' : 'image');
                    wizardState.test[stateField] = fullPath;

                    slot.classList.add('has-file');
                    slot.querySelector('.upload-slot-empty').style.display = 'none';
                    const filled = slot.querySelector('.upload-slot-filled');
                    filled.style.display = 'flex';
                    filled.querySelector('.upload-slot-filename').textContent = filename;

                    if (type === 'image') {
                        filled.querySelector('.upload-slot-thumb').src = fullPath;
                    }

                    updateTestStatus();
                } else {
                    slot.classList.add('has-error');
                    setTimeout(() => slot.classList.remove('has-error'), 3000);
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => {
                slot.classList.remove('uploading');
                slot.classList.add('has-error');
                setTimeout(() => slot.classList.remove('has-error'), 3000);
                alert('Upload error: ' + err.message);
            });
    }

    function handleSlotUpload(fileInput) {
        const file = fileInput.files[0];
        if (!file) return;
        const slot = fileInput.closest('.upload-slot');
        uploadFileToSlot(slot, file);
        fileInput.value = '';
    }

    // ============================================
    // Feedback Images
    // ============================================
    function renderFeedbackStep() {
        populateFeedbackSlot('feedbackCorrectSlot', wizardState.correctImage);
        populateFeedbackSlot('feedbackIncorrectSlot', wizardState.incorrectImage);
    }

    function populateFeedbackSlot(slotId, path) {
        const slot = document.getElementById(slotId);
        if (!slot) return;
        if (path) {
            slot.classList.add('has-file');
            slot.querySelector('.upload-slot-empty').style.display = 'none';
            const filled = slot.querySelector('.upload-slot-filled');
            filled.style.display = 'flex';
            filled.querySelector('.upload-slot-thumb').src = path;
            filled.querySelector('.upload-slot-filename').textContent = path.split('/').pop();
        } else {
            slot.classList.remove('has-file');
            slot.querySelector('.upload-slot-empty').style.display = '';
            slot.querySelector('.upload-slot-filled').style.display = 'none';
        }
    }

    function handleFeedbackUpload(fileInput, kind) {
        const file = fileInput.files[0];
        if (!file) return;

        const slot = fileInput.closest('.upload-slot');
        const formKey = kind === 'correct' ? 'correct_image_files[]' : 'incorrect_image_files[]';
        const pathPrefix = kind === 'correct'
            ? 'assets/uploads/feedback/correct/'
            : 'assets/uploads/feedback/incorrect/';

        slot.classList.add('uploading');
        slot.classList.remove('has-file', 'has-error');

        const formData = new FormData();
        formData.append(formKey, file);

        fetch('upload_handler.php', { method: 'POST', body: formData })
            .then(resp => resp.text())
            .then(text => {
                let jsonText = text;
                const jsonStart = text.indexOf('{');
                if (jsonStart > 0) jsonText = text.substring(jsonStart);
                return JSON.parse(jsonText);
            })
            .then(data => {
                slot.classList.remove('uploading');
                if (data.success && data.files && data.files.length > 0) {
                    const fullPath = pathPrefix + data.files[0];
                    if (kind === 'correct') wizardState.correctImage = fullPath;
                    else wizardState.incorrectImage = fullPath;

                    slot.classList.add('has-file');
                    slot.querySelector('.upload-slot-empty').style.display = 'none';
                    const filled = slot.querySelector('.upload-slot-filled');
                    filled.style.display = 'flex';
                    filled.querySelector('.upload-slot-thumb').src = fullPath;
                    filled.querySelector('.upload-slot-filename').textContent = data.files[0];
                } else {
                    slot.classList.add('has-error');
                    setTimeout(() => slot.classList.remove('has-error'), 3000);
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                }
                fileInput.value = '';
            })
            .catch(err => {
                slot.classList.remove('uploading');
                slot.classList.add('has-error');
                setTimeout(() => slot.classList.remove('has-error'), 3000);
                alert('Upload error: ' + err.message);
                fileInput.value = '';
            });
    }

    function clearFeedback(kind) {
        if (kind === 'correct') {
            wizardState.correctImage = '';
            populateFeedbackSlot('feedbackCorrectSlot', '');
        } else {
            wizardState.incorrectImage = '';
            populateFeedbackSlot('feedbackIncorrectSlot', '');
        }
    }

    // ============================================
    // Audio Playback
    // ============================================
    function playSlotAudio(btn) {
        const slot = btn.closest('.upload-slot');
        const position = slot.dataset.position;
        const path = wizardState.test[position + '_sound'];
        if (!path) return;

        if (currentAudio) { currentAudio.pause(); currentAudio = null; }
        const audio = new Audio(path);
        audio.volume = 0.5;
        currentAudio = audio;
        audio.play().catch(err => { alert('Could not play audio file.'); });
        audio.onended = function() { currentAudio = null; };
    }

    // ============================================
    // Review & Save
    // ============================================
    function renderReview() {
        const t = wizardState.test;
        let html = '';

        html += '<div class="mb-2"><span class="text-secondary">Test Name:</span> <strong>' + escapeHtml(wizardState.testName) + '</strong></div>';

        html += '<div class="review-test-card">';
        html += '<div class="row g-2">';

        ['left', 'center', 'right'].forEach(pos => {
            const imgPath = t[pos + '_image'];
            const audioPath = t[pos + '_sound'];
            const posLabel = pos.charAt(0).toUpperCase() + pos.slice(1);
            const channelColor = pos === 'left' ? 'var(--channel-left)' : pos === 'center' ? 'var(--channel-center)' : 'var(--channel-right)';

            html += '<div class="col-4">';
            html += '<div style="font-size:0.7rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:' + channelColor + ';margin-bottom:0.25rem;">' + posLabel + '</div>';

            if (imgPath) {
                html += '<div class="review-slot">';
                html += '<img class="review-slot-thumb" src="' + imgPath + '" alt="">';
                html += '<span class="review-slot-name">' + escapeHtml(imgPath.split('/').pop()) + '</span>';
                html += '</div>';
            }
            if (audioPath) {
                html += '<div class="review-slot">';
                html += '<i class="fa-solid fa-volume-high" style="color:var(--text-muted);width:32px;text-align:center;flex-shrink:0;"></i>';
                html += '<span class="review-slot-name">' + escapeHtml(audioPath.split('/').pop()) + '</span>';
                html += '</div>';
            }
            html += '</div>';
        });

        html += '</div></div>';

        // Feedback
        if (wizardState.correctImage || wizardState.incorrectImage) {
            html += '<div class="review-test-card mt-2">';
            html += '<strong class="d-block mb-2" style="font-size:0.85rem;">Feedback Images</strong>';
            html += '<div class="row g-2">';
            if (wizardState.correctImage) {
                html += '<div class="col-6"><div class="review-slot">';
                html += '<img class="review-slot-thumb" src="' + wizardState.correctImage + '" alt="">';
                html += '<span class="review-slot-name"><i class="fa-solid fa-check" style="color:var(--success);margin-right:4px;"></i>' + escapeHtml(wizardState.correctImage.split('/').pop()) + '</span>';
                html += '</div></div>';
            }
            if (wizardState.incorrectImage) {
                html += '<div class="col-6"><div class="review-slot">';
                html += '<img class="review-slot-thumb" src="' + wizardState.incorrectImage + '" alt="">';
                html += '<span class="review-slot-name"><i class="fa-solid fa-xmark" style="color:var(--danger);margin-right:4px;"></i>' + escapeHtml(wizardState.incorrectImage.split('/').pop()) + '</span>';
                html += '</div></div>';
            }
            html += '</div></div>';
        }

        document.getElementById('reviewContent').innerHTML = html;

        // Populate hidden form
        document.getElementById('formTestName').value = wizardState.testName;
        document.getElementById('formLeftImage').value = t.left_image;
        document.getElementById('formCenterImage').value = t.center_image;
        document.getElementById('formRightImage').value = t.right_image;
        document.getElementById('formLeftSound').value = t.left_sound;
        document.getElementById('formCenterSound').value = t.center_sound;
        document.getElementById('formRightSound').value = t.right_sound;
        document.getElementById('formCorrectImage').value = wizardState.correctImage;
        document.getElementById('formIncorrectImage').value = wizardState.incorrectImage;
    }

    function submitWizard() {
        document.getElementById('wizardForm').submit();
    }

    // ============================================
    // Utilities
    // ============================================
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function escapeAttr(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // ============================================
    // Init
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('wizardName').addEventListener('input', function() {
            wizardState.testName = this.value.trim();
        });
        renderStep();
    });
    </script>
</body>
</html>
