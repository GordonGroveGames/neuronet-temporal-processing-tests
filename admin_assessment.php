<?php
require_once __DIR__ . '/admin_session.php';
require_role(['admin', 'site_admin', 'test_creator']);

$currentUserRole = get_user_role();
$currentUser = $_SESSION['admin_user'];
$canEditAll = in_array($currentUserRole, ['admin', 'site_admin']);

// Load data files
$assessmentsFile = __DIR__ . '/assets/assessments.json';
$testsFile = __DIR__ . '/assets/tests.json';
$assessments = file_exists($assessmentsFile) ? json_decode(file_get_contents($assessmentsFile), true) : [];
$allTests = file_exists($testsFile) ? json_decode(file_get_contents($testsFile), true) : [];

$id = $_GET['id'] ?? null;
$editing = $id !== null && isset($assessments[$id]);

// Check permissions for editing
if ($editing && !$canEditAll) {
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
    $testIdsRaw = $_POST['test_ids'] ?? '';
    $testIds = array_filter(explode(',', $testIdsRaw));

    $saveId = $editing ? $id : uniqid('assessment_', true);
    $assessments[$saveId] = [
        'name' => $assessmentName,
        'test_ids' => $testIds,
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
$assessment = $editing ? $assessments[$id] : ['name' => '', 'test_ids' => []];
$selectedTestIds = $assessment['test_ids'] ?? [];
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

    <div class="container mt-4">
        <!-- Page Header -->
        <h2 style="font-weight:700;" class="mb-4">
            <i class="fa-solid fa-clipboard-list me-2" style="color:var(--primary);"></i>
            <?= $editing ? 'Edit' : 'Create New' ?> Assessment
        </h2>

        <!-- Section A: Assessment Name -->
        <div class="card-modern mb-4">
            <div class="card-header">
                <strong>Assessment Name</strong>
            </div>
            <div class="card-body" style="padding:1.5rem;">
                <input type="text" class="form-control form-control-lg" id="assessmentName"
                       placeholder="e.g. Session 1 — Animals"
                       value="<?= htmlspecialchars($assessment['name']) ?>">
                <div class="form-text">Give your assessment a descriptive name.</div>
            </div>
        </div>

        <!-- Section B: Test Picker -->
        <div class="card-modern mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Select Tests</strong>
                <a href="admin_test.php" class="btn-ghost" style="font-size:0.85rem;">
                    <i class="fa-solid fa-plus me-1"></i> Create New Test
                </a>
            </div>
            <div class="card-body" style="padding:1.5rem;">
                <?php if (empty($allTests)): ?>
                    <div class="test-picker-empty">
                        <i class="fa-solid fa-vial"></i>
                        <p>No tests available. Create a test first.</p>
                        <a href="admin_test.php" class="btn btn-modern-primary btn-sm">
                            <i class="fa-solid fa-plus me-1"></i> Create Test
                        </a>
                    </div>
                <?php else: ?>
                    <p class="text-secondary mb-3" style="font-size:0.9rem;">
                        Tap tests to add them. Drag to reorder.
                    </p>
                    <div class="test-picker-grid" id="testPickerGrid">
                        <?php foreach ($allTests as $testId => $testData): ?>
                        <div class="test-picker-card<?= in_array($testId, $selectedTestIds) ? ' selected' : '' ?>"
                             data-test-id="<?= htmlspecialchars($testId) ?>"
                             onclick="toggleTestSelection('<?= htmlspecialchars($testId) ?>')">
                            <div class="test-picker-thumbs">
                                <?php
                                $imgs = [
                                    'L' => $testData['left_image'] ?? '',
                                    'C' => $testData['center_image'] ?? '',
                                    'R' => $testData['right_image'] ?? ''
                                ];
                                foreach ($imgs as $label => $imgPath):
                                    if ($imgPath): ?>
                                        <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= $label ?>" draggable="false">
                                    <?php else: ?>
                                        <div class="thumb-placeholder-sm"><?= $label ?></div>
                                    <?php endif;
                                endforeach; ?>
                            </div>
                            <div class="test-picker-name"><?= htmlspecialchars($testData['name'] ?? $testId) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section C: Selected Tests (ordered) -->
        <div class="card-modern mb-4" id="selectedTestsCard" style="<?= empty($selectedTestIds) ? 'display:none;' : '' ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Test Order</strong>
                <span class="test-count-badge" id="testCountBadge"><?= count($selectedTestIds) ?> test<?= count($selectedTestIds) !== 1 ? 's' : '' ?></span>
            </div>
            <div class="card-body" style="padding:1rem;">
                <ul class="selected-tests-list" id="selectedTestsList">
                    <!-- JS renders selected tests here -->
                </ul>
            </div>
        </div>

        <!-- Save Button -->
        <div class="d-flex justify-content-end mb-5">
            <a href="admin_panel.php" class="btn-ghost me-3" style="text-decoration:none;align-self:center;">Cancel</a>
            <button type="button" class="btn btn-modern-primary btn-lg" id="saveBtn" onclick="saveAssessment()">
                <i class="fa-solid fa-check me-1"></i> Save Assessment
            </button>
        </div>

        <!-- Hidden form -->
        <form method="post" id="assessmentForm" style="display:none;">
            <input type="hidden" name="assessment_name" id="formAssessmentName">
            <input type="hidden" name="test_ids" id="formTestIds">
        </form>
    </div>

    <script>
    // ============================================
    // State
    // ============================================
    const allTests = <?= json_encode($allTests) ?>;
    let selectedTestIds = <?= json_encode(array_values($selectedTestIds)) ?>;

    // Drag state
    let draggedItem = null;
    let draggedIndex = -1;

    // ============================================
    // Test Selection
    // ============================================
    function toggleTestSelection(testId) {
        const index = selectedTestIds.indexOf(testId);
        if (index >= 0) {
            selectedTestIds.splice(index, 1);
        } else {
            selectedTestIds.push(testId);
        }
        renderAll();
    }

    function removeTest(testId) {
        const index = selectedTestIds.indexOf(testId);
        if (index >= 0) {
            selectedTestIds.splice(index, 1);
        }
        renderAll();
    }

    function renderAll() {
        // Update picker card states
        document.querySelectorAll('.test-picker-card').forEach(card => {
            const id = card.dataset.testId;
            card.classList.toggle('selected', selectedTestIds.indexOf(id) >= 0);
        });

        // Show/hide selected tests card
        const selectedCard = document.getElementById('selectedTestsCard');
        selectedCard.style.display = selectedTestIds.length > 0 ? '' : 'none';

        // Update count badge
        const badge = document.getElementById('testCountBadge');
        badge.textContent = selectedTestIds.length + ' test' + (selectedTestIds.length !== 1 ? 's' : '');

        // Render ordered list
        renderSelectedList();
    }

    function renderSelectedList() {
        const list = document.getElementById('selectedTestsList');
        let html = '';

        selectedTestIds.forEach((testId, index) => {
            const t = allTests[testId];
            if (!t) return;

            const leftImg = t.left_image || '';
            const centerImg = t.center_image || '';
            const rightImg = t.right_image || '';

            html += '<li class="selected-test-item" draggable="true" data-test-id="' + escapeAttr(testId) + '" data-index="' + index + '"';
            html += ' ondragstart="onDragStart(event)" ondragover="onDragOver(event)" ondrop="onDrop(event)" ondragend="onDragEnd(event)">';

            // Drag handle
            html += '<span class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></span>';

            // Order number
            html += '<span class="selected-test-order">' + (index + 1) + '</span>';

            // Thumbnails
            html += '<span class="selected-test-thumbs">';
            if (leftImg) html += '<img src="' + escapeAttr(leftImg) + '" alt="L" draggable="false">';
            if (centerImg) html += '<img src="' + escapeAttr(centerImg) + '" alt="C" draggable="false">';
            if (rightImg) html += '<img src="' + escapeAttr(rightImg) + '" alt="R" draggable="false">';
            html += '</span>';

            // Name
            html += '<span class="selected-test-name">' + escapeHtml(t.name || testId) + '</span>';

            // Remove button
            html += '<button type="button" class="selected-test-remove" onclick="removeTest(\'' + escapeAttr(testId) + '\')" title="Remove">';
            html += '<i class="fa-solid fa-xmark"></i>';
            html += '</button>';

            html += '</li>';
        });

        list.innerHTML = html;

        // Add touch drag support
        addTouchDragSupport();
    }

    // ============================================
    // Drag and Drop (reordering)
    // ============================================
    function onDragStart(e) {
        draggedItem = e.target.closest('.selected-test-item');
        draggedIndex = parseInt(draggedItem.dataset.index);
        draggedItem.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', '');
    }

    function onDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        const item = e.target.closest('.selected-test-item');
        if (item && item !== draggedItem) {
            // Remove drag-over from all
            document.querySelectorAll('.selected-test-item').forEach(i => i.classList.remove('drag-over'));
            item.classList.add('drag-over');
        }
    }

    function onDrop(e) {
        e.preventDefault();
        const targetItem = e.target.closest('.selected-test-item');
        if (!targetItem || !draggedItem || targetItem === draggedItem) return;

        const targetIndex = parseInt(targetItem.dataset.index);

        // Reorder
        const movedId = selectedTestIds.splice(draggedIndex, 1)[0];
        selectedTestIds.splice(targetIndex, 0, movedId);

        renderAll();
    }

    function onDragEnd(e) {
        document.querySelectorAll('.selected-test-item').forEach(i => {
            i.classList.remove('dragging', 'drag-over');
        });
        draggedItem = null;
        draggedIndex = -1;
    }

    // Touch drag support for mobile
    function addTouchDragSupport() {
        const items = document.querySelectorAll('.selected-test-item');
        let touchDragItem = null;
        let touchStartIndex = -1;
        let touchStartY = 0;

        items.forEach(item => {
            const handle = item.querySelector('.drag-handle');
            if (!handle) return;

            handle.addEventListener('touchstart', function(e) {
                touchDragItem = item;
                touchStartIndex = parseInt(item.dataset.index);
                touchStartY = e.touches[0].clientY;
                item.classList.add('dragging');
            }, { passive: true });

            handle.addEventListener('touchmove', function(e) {
                if (!touchDragItem) return;
                e.preventDefault();

                const touchY = e.touches[0].clientY;
                const items = document.querySelectorAll('.selected-test-item');

                items.forEach(i => i.classList.remove('drag-over'));

                // Find which item we're over
                items.forEach(i => {
                    const rect = i.getBoundingClientRect();
                    if (touchY > rect.top && touchY < rect.bottom && i !== touchDragItem) {
                        i.classList.add('drag-over');
                    }
                });
            }, { passive: false });

            handle.addEventListener('touchend', function(e) {
                if (!touchDragItem) return;

                const touchY = e.changedTouches[0].clientY;
                const items = document.querySelectorAll('.selected-test-item');
                let targetIndex = -1;

                items.forEach((i, idx) => {
                    const rect = i.getBoundingClientRect();
                    if (touchY > rect.top && touchY < rect.bottom && i !== touchDragItem) {
                        targetIndex = parseInt(i.dataset.index);
                    }
                });

                if (targetIndex >= 0 && targetIndex !== touchStartIndex) {
                    const movedId = selectedTestIds.splice(touchStartIndex, 1)[0];
                    selectedTestIds.splice(targetIndex, 0, movedId);
                    renderAll();
                } else {
                    items.forEach(i => i.classList.remove('dragging', 'drag-over'));
                }

                touchDragItem = null;
                touchStartIndex = -1;
            }, { passive: true });
        });
    }

    // ============================================
    // Save
    // ============================================
    function saveAssessment() {
        const name = document.getElementById('assessmentName').value.trim();
        if (!name) {
            document.getElementById('assessmentName').focus();
            document.getElementById('assessmentName').style.borderColor = 'var(--danger)';
            alert('Please enter an assessment name.');
            return;
        }

        if (selectedTestIds.length === 0) {
            alert('Please select at least one test.');
            return;
        }

        document.getElementById('formAssessmentName').value = name;
        document.getElementById('formTestIds').value = selectedTestIds.join(',');
        document.getElementById('assessmentForm').submit();
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
        renderAll();
    });
    </script>
</body>
</html>
