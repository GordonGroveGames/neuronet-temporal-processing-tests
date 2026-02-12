<?php
require_once __DIR__ . '/check_test_session.php';
require_test_user_login();

$userInfo = get_test_user_info();

// Load assessments
$assessmentsFile = __DIR__ . '/assets/assessments.json';
$assessments = [];
if (file_exists($assessmentsFile)) {
    $json = file_get_contents($assessmentsFile);
    $assessments = json_decode($json, true) ?: [];
}

// If only one assessment, skip straight to test
if (count($assessments) === 1) {
    $id = array_key_first($assessments);
    header('Location: test.php?assessments=' . urlencode($id));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Select Assessments — The Fluency Factor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/touch-fixes.css">
    <link rel="stylesheet" href="assets/css/admin-styles.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .page-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        .page-subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }
        .assessment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1rem;
            width: 100%;
            max-width: 900px;
        }
        .assessment-card {
            border: 2px solid var(--border-light);
            border-radius: var(--radius-lg);
            background: var(--surface-0);
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: all var(--transition);
            overflow: hidden;
            position: relative;
            user-select: none;
            -webkit-user-select: none;
        }
        .assessment-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .assessment-card.selected {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light), var(--shadow-md);
        }
        .assessment-card.selected .card-check {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }
        .card-check {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            width: 28px;
            height: 28px;
            border: 2px solid var(--border-default);
            border-radius: var(--radius-sm);
            background: var(--surface-0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            color: transparent;
            transition: all var(--transition);
            z-index: 2;
        }
        .card-thumbnails {
            display: flex;
            align-items: stretch;
            height: 140px;
            background: var(--surface-1);
        }
        .card-thumbnails img {
            flex: 1;
            object-fit: cover;
            min-width: 0;
        }
        .card-thumbnails .thumb-placeholder {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--surface-2);
            color: var(--text-muted);
            font-weight: 600;
            font-size: 1.5rem;
        }
        .card-body-label {
            padding: 0.75rem 1rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-primary);
        }
        .start-section {
            margin-top: 2rem;
            text-align: center;
        }
        .btn-start {
            background: var(--primary);
            color: #fff;
            border: 1px solid var(--primary-dark);
            border-radius: var(--radius-sm);
            padding: 0.75rem 2.5rem;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: inset 0 1px rgba(255,255,255,0.15), 0 2px 6px rgba(79,70,229,0.2);
            transition: all var(--transition);
            cursor: pointer;
        }
        .btn-start:hover:not(:disabled) {
            background: var(--primary-dark);
            color: #fff;
            box-shadow: inset 0 1px rgba(255,255,255,0.1), 0 4px 12px rgba(79,70,229,0.3);
            transform: translateY(-1px);
        }
        .btn-start:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            transform: none;
        }
        .selection-count {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }
        .empty-state i {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        @media (pointer: coarse) {
            .assessment-card {
                min-height: 44px;
            }
            .btn-start {
                min-height: 54px;
                padding: 0.85rem 3rem;
            }
        }
    </style>
</head>
<body>
<nav class="navbar-admin d-flex justify-content-between align-items-center">
    <span class="navbar-brand">The Fluency Factor</span>
    <div class="d-flex align-items-center gap-3">
        <span style="color:rgba(255,255,255,0.7);font-size:0.85rem;"><?= htmlspecialchars($userInfo['username']) ?></span>
        <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket me-1"></i> Logout</a>
    </div>
</nav>

<div class="page-content">
    <?php if (empty($assessments)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-clipboard-list d-block"></i>
            <h3>No Assessments Available</h3>
            <p>Please contact your administrator to set up assessments.</p>
        </div>
    <?php else: ?>
        <div class="page-title">Select Assessments</div>
        <div class="page-subtitle">Choose which assessments to take, then press Start.</div>

        <div class="assessment-grid">
            <?php foreach ($assessments as $id => $assessment):
                $test = $assessment['tests'][0] ?? [];
                $leftImg = $test['left_image'] ?? '';
                $centerImg = $test['center_image'] ?? '';
                $rightImg = $test['right_image'] ?? '';
            ?>
            <div class="assessment-card" data-id="<?= htmlspecialchars($id) ?>" onclick="toggleCard(this)">
                <div class="card-check"><i class="fa-solid fa-check"></i></div>
                <div class="card-thumbnails">
                    <?php if ($leftImg): ?>
                        <img src="<?= htmlspecialchars($leftImg) ?>" alt="Left">
                    <?php else: ?>
                        <div class="thumb-placeholder">L</div>
                    <?php endif; ?>
                    <?php if ($centerImg): ?>
                        <img src="<?= htmlspecialchars($centerImg) ?>" alt="Center">
                    <?php else: ?>
                        <div class="thumb-placeholder">C</div>
                    <?php endif; ?>
                    <?php if ($rightImg): ?>
                        <img src="<?= htmlspecialchars($rightImg) ?>" alt="Right">
                    <?php else: ?>
                        <div class="thumb-placeholder">R</div>
                    <?php endif; ?>
                </div>
                <div class="card-body-label"><?= htmlspecialchars($assessment['name']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="start-section">
            <button class="btn-start" id="startBtn" disabled onclick="startSelected()">
                <i class="fa-solid fa-play me-2"></i>Start
            </button>
            <div class="selection-count" id="selectionCount">No assessments selected</div>
        </div>
    <?php endif; ?>
</div>

<script>
    function toggleCard(card) {
        card.classList.toggle('selected');
        updateStartButton();
    }

    function updateStartButton() {
        const selected = document.querySelectorAll('.assessment-card.selected');
        const btn = document.getElementById('startBtn');
        const count = document.getElementById('selectionCount');
        const n = selected.length;

        btn.disabled = n === 0;

        if (n === 0) {
            count.textContent = 'No assessments selected';
        } else if (n === 1) {
            count.textContent = '1 assessment selected';
        } else {
            count.textContent = n + ' assessments selected';
        }
    }

    function startSelected() {
        const selected = document.querySelectorAll('.assessment-card.selected');
        if (selected.length === 0) return;

        const ids = Array.from(selected).map(c => c.dataset.id);
        window.location.href = 'test.php?assessments=' + encodeURIComponent(ids.join(','));
    }
</script>
</body>
</html>
