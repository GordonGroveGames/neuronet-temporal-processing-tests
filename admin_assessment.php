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

// Handle form submission (same as before)
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
    <title><?= $editing ? 'Edit' : 'Create' ?> Assessment — The Fluency Factor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/touch-fixes.css">
    <link rel="stylesheet" href="assets/css/admin-styles.css">
    <style>
        .card-modern:hover { transform: none; } /* disable lift on wizard cards */
    </style>
</head>
<body>
    <!-- Header -->
    <div class="container mt-3 mb-2">
        <a href="admin_panel.php" class="btn-ghost" style="text-decoration:none;">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Admin Panel
        </a>
        <h2 class="mt-2" style="font-weight:700;">
            <?= $editing ? 'Edit' : 'Create New' ?> Assessment
        </h2>
    </div>

    <!-- Progress Stepper -->
    <div class="container mb-3">
        <div class="card-modern" style="padding:1rem 1.25rem;">
            <div class="wizard-stepper" id="wizardStepper">
                <div class="wizard-step active" data-step="1">
                    <div class="wizard-step-number">1</div>
                    <div class="wizard-step-label">Name & Setup</div>
                </div>
                <div class="wizard-step-connector" data-after="1"></div>
                <div class="wizard-step" data-step="2">
                    <div class="wizard-step-number">2</div>
                    <div class="wizard-step-label">Upload Assets</div>
                </div>
                <div class="wizard-step-connector" data-after="2"></div>
                <div class="wizard-step" data-step="3">
                    <div class="wizard-step-number">3</div>
                    <div class="wizard-step-label">Feedback</div>
                </div>
                <div class="wizard-step-connector" data-after="3"></div>
                <div class="wizard-step" data-step="4">
                    <div class="wizard-step-number">4</div>
                    <div class="wizard-step-label">Review & Save</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wizard Panels Container -->
    <div class="container" id="wizardContainer">

        <!-- ========== STEP 1: Name & Setup ========== -->
        <div class="wizard-panel" data-step="1">
            <div class="card-modern">
                <div class="card-header">
                    <strong>Step 1 of 4</strong> &mdash; Name & Setup
                </div>
                <div class="card-body" style="padding:1.5rem;">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Assessment Name</label>
                        <input type="text" class="form-control form-control-lg" id="wizardName"
                               placeholder="e.g. Cat Dog Cow"
                               value="<?= htmlspecialchars($assessment['name']) ?>">
                        <div class="form-text">Give your assessment a descriptive name.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Number of Tests</label>
                        <div class="d-flex gap-2 flex-wrap" id="numTestsSelector">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                            <button type="button"
                                    class="btn-num-test<?= $i == $numTests ? ' active' : '' ?>"
                                    data-value="<?= $i ?>"
                                    onclick="setNumTests(<?= $i ?>)">
                                <?= $i ?>
                            </button>
                            <?php endfor; ?>
                        </div>
                        <div class="form-text">Each test has 3 images and 3 sounds (Left / Center / Right).</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== STEP 2: Upload Assets ========== -->
        <div class="wizard-panel" data-step="2" style="display:none;">
            <div id="testCardsContainer">
                <!-- JS renders test cards here -->
            </div>
        </div>

        <!-- ========== STEP 3: Feedback Images ========== -->
        <div class="wizard-panel" data-step="3" style="display:none;">
            <div class="card-modern">
                <div class="card-header">
                    <strong>Step 3 of 4</strong> &mdash; Feedback Images (Optional)
                </div>
                <div class="card-body" style="padding:1.5rem;">
                    <p class="text-secondary mb-4">
                        These images are shown after each response. They apply to all tests. Leave blank to skip.
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
                                     data-type="correct_image"
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
                                     data-type="incorrect_image"
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
        </div>

        <!-- ========== STEP 4: Review & Save ========== -->
        <div class="wizard-panel" data-step="4" style="display:none;">
            <div class="card-modern">
                <div class="card-header">
                    <strong>Step 4 of 4</strong> &mdash; Review & Save
                </div>
                <div class="card-body" style="padding:1.5rem;">
                    <div id="reviewContent">
                        <!-- JS populates review here -->
                    </div>
                </div>
            </div>

            <!-- Hidden form for submission -->
            <form method="post" id="wizardForm" style="display:none;">
                <input type="hidden" name="assessment_name" id="formAssessmentName">
                <input type="hidden" name="num_tests" id="formNumTests">
                <div id="formTestInputs"></div>
            </form>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="wizard-nav" id="wizardNav">
        <div class="container d-flex justify-content-between align-items-center">
            <button type="button" class="btn-ghost" id="wizardBackBtn" onclick="wizardBack()" style="text-decoration:none;display:none;">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </button>
            <span class="wizard-nav-label" id="wizardNavLabel">Step 1 of 4</span>
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
        totalSteps: 4,
        editing: <?= json_encode($editing) ?>,
        assessmentName: <?= json_encode($assessment['name']) ?>,
        numTests: <?= json_encode($numTests) ?>,
        tests: [],
        correctImage: <?= json_encode(
            $editing
                ? ($assessment['tests'][0]['correct_image'] ?? '')
                : $defaultCorrectImage
        ) ?>,
        incorrectImage: <?= json_encode(
            $editing
                ? ($assessment['tests'][0]['incorrect_image'] ?? '')
                : $defaultIncorrectImage
        ) ?>
    };

    // Initialize tests array
    <?php if ($editing): ?>
    wizardState.tests = <?= json_encode($assessment['tests']) ?>;
    <?php else: ?>
    for (let i = 0; i < wizardState.numTests; i++) {
        wizardState.tests.push({
            left_image: '', center_image: '', right_image: '',
            left_sound: '', center_sound: '', right_sound: ''
        });
    }
    <?php endif; ?>

    // Current audio element for playback
    let currentAudio = null;

    // ============================================
    // Step 1: Num Tests
    // ============================================
    function setNumTests(n) {
        wizardState.numTests = n;
        // Update pill buttons
        document.querySelectorAll('.btn-num-test').forEach(btn => {
            btn.classList.toggle('active', parseInt(btn.dataset.value) === n);
        });
        // Extend or trim tests array
        while (wizardState.tests.length < n) {
            wizardState.tests.push({
                left_image: '', center_image: '', right_image: '',
                left_sound: '', center_sound: '', right_sound: ''
            });
        }
        // Don't delete data if user decreases — just hide extra cards in step 2
    }

    // ============================================
    // Navigation
    // ============================================
    function wizardNext() {
        // Validate current step
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
            nextBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Save Assessment';
        } else {
            nextBtn.innerHTML = 'Next <i class="fa-solid fa-arrow-right ms-1"></i>';
        }

        // Step-specific rendering
        if (step === 2) renderTestCards();
        if (step === 3) renderFeedbackStep();
        if (step === 4) renderReview();

        // Scroll to top
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
                alert('Please enter an assessment name.');
                return false;
            }
            wizardState.assessmentName = name;
            document.getElementById('wizardName').style.borderColor = '';
            return true;
        }

        if (step === 2) {
            const errors = [];
            for (let i = 0; i < wizardState.numTests; i++) {
                const t = wizardState.tests[i];
                const missing = [];
                if (!t.left_image) missing.push('Left Image');
                if (!t.center_image) missing.push('Center Image');
                if (!t.right_image) missing.push('Right Image');
                if (!t.left_sound) missing.push('Left Sound');
                if (!t.center_sound) missing.push('Center Sound');
                if (!t.right_sound) missing.push('Right Sound');
                if (missing.length > 0) {
                    errors.push('Test ' + (i + 1) + ' is missing: ' + missing.join(', '));
                }
            }
            if (errors.length > 0) {
                // Highlight empty slots
                document.querySelectorAll('.upload-slot[data-test]').forEach(slot => {
                    const ti = parseInt(slot.dataset.test);
                    if (ti >= wizardState.numTests) return;
                    const field = slot.dataset.position + '_' + slot.dataset.type;
                    // Map to state field name
                    const stateField = slot.dataset.position + '_' + (slot.dataset.type === 'audio' ? 'sound' : 'image');
                    if (!wizardState.tests[ti][stateField]) {
                        slot.classList.add('has-error');
                        setTimeout(() => slot.classList.remove('has-error'), 2000);
                    }
                });
                // Scroll to first error
                const firstError = document.querySelector('.upload-slot.has-error');
                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                alert(errors.join('\n'));
                return false;
            }
            return true;
        }

        // Steps 3 and 4 need no validation
        return true;
    }

    // ============================================
    // Step 2: Test Card Rendering
    // ============================================
    function renderTestCards() {
        const container = document.getElementById('testCardsContainer');
        let html = '';

        for (let i = 0; i < wizardState.numTests; i++) {
            const t = wizardState.tests[i] || {};
            const filled = [t.left_image, t.center_image, t.right_image, t.left_sound, t.center_sound, t.right_sound].filter(Boolean).length;
            const statusClass = filled === 6 ? 'complete' : 'incomplete';
            const statusText = filled === 6 ? '<i class="fa-solid fa-check me-1"></i>Complete' : filled + ' of 6';

            html += '<div class="card-modern mb-3">';
            html += '<div class="card-header d-flex align-items-center justify-content-between">';
            html += '<strong>Test ' + (i + 1) + '</strong>';
            html += '<span class="test-status-badge ' + statusClass + '" id="testStatus' + i + '">' + statusText + '</span>';
            html += '</div>';
            html += '<div class="card-body"><div class="row g-3">';

            // Three columns: Left, Center, Right
            const channels = [
                { pos: 'left', label: 'Left', cls: 'channel-left' },
                { pos: 'center', label: 'Center', cls: 'channel-center' },
                { pos: 'right', label: 'Right', cls: 'channel-right' }
            ];

            channels.forEach(ch => {
                const imgPath = t[ch.pos + '_image'] || '';
                const audioPath = t[ch.pos + '_sound'] || '';

                html += '<div class="col-12 col-md-4">';
                html += '<div class="channel-card ' + ch.cls + '">';
                html += '<div class="channel-label">' + ch.label + '</div>';

                // Image upload slot
                html += buildSlot(i, 'image', ch.pos, imgPath, 'image/*', 'fa-image', 'Add image');

                // Audio upload slot
                html += '<div style="margin-top:0.5rem;">';
                html += buildSlot(i, 'audio', ch.pos, audioPath, 'audio/*', 'fa-volume-high', 'Add audio');
                html += '</div>';

                html += '</div></div>';
            });

            html += '</div></div></div>';
        }

        container.innerHTML = html;
    }

    function buildSlot(testIndex, type, position, currentPath, accept, icon, label) {
        const hasFile = !!currentPath;
        const filename = currentPath ? currentPath.split('/').pop() : '';
        const isImage = type === 'image';

        let html = '<div class="upload-slot' + (hasFile ? ' has-file' : '') + '"';
        html += ' data-test="' + testIndex + '" data-type="' + type + '" data-position="' + position + '"';
        html += ' onclick="triggerUpload(this)">';
        html += '<input type="file" accept="' + accept + '" style="display:none;" onchange="handleSlotUpload(this)">';

        // Empty state
        html += '<div class="upload-slot-empty"' + (hasFile ? ' style="display:none;"' : '') + '>';
        html += '<i class="fa-solid ' + icon + '"></i>';
        html += '<span>' + label + '</span>';
        html += '</div>';

        // Filled state
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

    function updateTestStatus(testIndex) {
        const t = wizardState.tests[testIndex];
        if (!t) return;
        const filled = [t.left_image, t.center_image, t.right_image, t.left_sound, t.center_sound, t.right_sound].filter(Boolean).length;
        const badge = document.getElementById('testStatus' + testIndex);
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

    function handleSlotUpload(fileInput) {
        const file = fileInput.files[0];
        if (!file) return;

        const slot = fileInput.closest('.upload-slot');
        const testIndex = parseInt(slot.dataset.test);
        const type = slot.dataset.type; // 'image' or 'audio'
        const position = slot.dataset.position; // 'left', 'center', 'right'

        // Determine FormData key for upload_handler.php
        const formKey = type === 'image' ? 'image_files[]' : 'audio_files[]';

        // Show uploading state
        slot.classList.add('uploading');
        slot.classList.remove('has-file', 'has-error');

        const formData = new FormData();
        formData.append(formKey, file);

        fetch('upload_handler.php', { method: 'POST', body: formData })
            .then(resp => resp.text())
            .then(text => {
                // Handle potential PHP notices before JSON
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

                    // Update state
                    const stateField = position + '_' + (type === 'audio' ? 'sound' : 'image');
                    wizardState.tests[testIndex][stateField] = fullPath;

                    // Update slot DOM
                    slot.classList.add('has-file');
                    slot.querySelector('.upload-slot-empty').style.display = 'none';
                    const filled = slot.querySelector('.upload-slot-filled');
                    filled.style.display = 'flex';
                    filled.querySelector('.upload-slot-filename').textContent = filename;

                    if (type === 'image') {
                        filled.querySelector('.upload-slot-thumb').src = fullPath;
                    }

                    updateTestStatus(testIndex);
                } else {
                    slot.classList.add('has-error');
                    setTimeout(() => slot.classList.remove('has-error'), 3000);
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                }
                // Reset file input so same file can be re-selected
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

    // ============================================
    // Step 3: Feedback Images
    // ============================================
    function renderFeedbackStep() {
        // Populate correct feedback slot
        populateFeedbackSlot('feedbackCorrectSlot', wizardState.correctImage);
        // Populate incorrect feedback slot
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
        const testIndex = parseInt(slot.dataset.test);
        const position = slot.dataset.position;
        const path = wizardState.tests[testIndex][position + '_sound'];
        if (!path) return;

        if (currentAudio) {
            currentAudio.pause();
            currentAudio = null;
        }

        const audio = new Audio(path);
        audio.volume = 0.5;
        currentAudio = audio;
        audio.play().catch(err => {
            console.error('Audio playback failed:', err);
            alert('Could not play audio file.');
        });
        audio.onended = function() { currentAudio = null; };
    }

    // ============================================
    // Step 4: Review & Save
    // ============================================
    function renderReview() {
        let html = '';

        // Assessment info
        html += '<div class="mb-3">';
        html += '<span class="text-secondary">Assessment Name:</span> ';
        html += '<strong>' + escapeHtml(wizardState.assessmentName) + '</strong>';
        html += '</div>';
        html += '<div class="mb-3">';
        html += '<span class="text-secondary">Number of Tests:</span> ';
        html += '<strong>' + wizardState.numTests + '</strong>';
        html += '</div>';

        // Per-test review
        for (let i = 0; i < wizardState.numTests; i++) {
            const t = wizardState.tests[i];
            html += '<div class="review-test-card">';
            html += '<strong class="d-block mb-2">Test ' + (i + 1) + '</strong>';
            html += '<div class="row g-2">';

            ['left', 'center', 'right'].forEach(pos => {
                const imgPath = t[pos + '_image'];
                const audioPath = t[pos + '_sound'];
                const posLabel = pos.charAt(0).toUpperCase() + pos.slice(1);
                const channelColor = pos === 'left' ? 'var(--channel-left)' : pos === 'center' ? 'var(--channel-center)' : 'var(--channel-right)';

                html += '<div class="col-4">';
                html += '<div style="font-size:0.7rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:' + channelColor + ';margin-bottom:0.25rem;">' + posLabel + '</div>';

                // Image
                if (imgPath) {
                    html += '<div class="review-slot">';
                    html += '<img class="review-slot-thumb" src="' + imgPath + '" alt="">';
                    html += '<span class="review-slot-name">' + escapeHtml(imgPath.split('/').pop()) + '</span>';
                    html += '</div>';
                }
                // Audio
                if (audioPath) {
                    html += '<div class="review-slot">';
                    html += '<i class="fa-solid fa-volume-high" style="color:var(--text-muted);width:32px;text-align:center;flex-shrink:0;"></i>';
                    html += '<span class="review-slot-name">' + escapeHtml(audioPath.split('/').pop()) + '</span>';
                    html += '</div>';
                }
                html += '</div>';
            });

            html += '</div></div>';
        }

        // Feedback images
        if (wizardState.correctImage || wizardState.incorrectImage) {
            html += '<div class="review-test-card">';
            html += '<strong class="d-block mb-2">Feedback Images</strong>';
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
        document.getElementById('formAssessmentName').value = wizardState.assessmentName;
        document.getElementById('formNumTests').value = wizardState.numTests;

        let formHtml = '';
        for (let i = 0; i < wizardState.numTests; i++) {
            const t = wizardState.tests[i];
            formHtml += '<input type="hidden" name="left_image[]" value="' + escapeAttr(t.left_image) + '">';
            formHtml += '<input type="hidden" name="center_image[]" value="' + escapeAttr(t.center_image) + '">';
            formHtml += '<input type="hidden" name="right_image[]" value="' + escapeAttr(t.right_image) + '">';
            formHtml += '<input type="hidden" name="left_sound[]" value="' + escapeAttr(t.left_sound) + '">';
            formHtml += '<input type="hidden" name="center_sound[]" value="' + escapeAttr(t.center_sound) + '">';
            formHtml += '<input type="hidden" name="right_sound[]" value="' + escapeAttr(t.right_sound) + '">';
            formHtml += '<input type="hidden" name="correct_image[]" value="' + escapeAttr(wizardState.correctImage) + '">';
            formHtml += '<input type="hidden" name="incorrect_image[]" value="' + escapeAttr(wizardState.incorrectImage) + '">';
        }
        document.getElementById('formTestInputs').innerHTML = formHtml;
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
        // Name field sync
        document.getElementById('wizardName').addEventListener('input', function() {
            wizardState.assessmentName = this.value.trim();
        });

        // Initial render
        renderStep();
    });
    </script>
</body>
</html>
