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
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.25rem;
            width: 100%;
            max-width: 1100px;
        }
        .assessment-card {
            border: 2px solid var(--border-light);
            border-radius: var(--radius-lg);
            background: var(--surface-0);
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: all var(--transition);
            overflow: hidden;
            user-select: none;
            -webkit-user-select: none;
            text-decoration: none;
            display: block;
        }
        .assessment-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-3px);
            border-color: var(--primary);
        }
        .assessment-card.completed {
            border-color: var(--success);
        }
        .assessment-card.completed:hover {
            border-color: var(--success);
        }
        .card-thumbnails {
            display: flex;
            align-items: stretch;
            aspect-ratio: 3 / 1;
            background: var(--surface-1);
        }
        .card-thumbnails img {
            flex: 1;
            object-fit: contain;
            min-width: 0;
            padding: 8px;
            background: var(--surface-1);
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
        .card-footer-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border-top: 1px solid var(--border-light);
        }
        .card-assessment-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-primary);
        }
        .card-check-done {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--success);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            flex-shrink: 0;
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
        @media (max-width: 420px) {
            .assessment-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (pointer: coarse) {
            .assessment-card {
                min-height: 44px;
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
        <div class="page-title">Assessments</div>
        <div class="page-subtitle">Tap an assessment to begin.</div>

        <div class="assessment-grid">
            <?php foreach ($assessments as $id => $assessment):
                $test = $assessment['tests'][0] ?? [];
                $leftImg = $test['left_image'] ?? '';
                $centerImg = $test['center_image'] ?? '';
                $rightImg = $test['right_image'] ?? '';
            ?>
            <a class="assessment-card" data-id="<?= htmlspecialchars($id) ?>" href="test.php?assessments=<?= urlencode($id) ?>">
                <div class="card-thumbnails">
                    <?php if ($leftImg): ?>
                        <img src="<?= htmlspecialchars($leftImg) ?>" alt="Left" draggable="false">
                    <?php else: ?>
                        <div class="thumb-placeholder">L</div>
                    <?php endif; ?>
                    <?php if ($centerImg): ?>
                        <img src="<?= htmlspecialchars($centerImg) ?>" alt="Center" draggable="false">
                    <?php else: ?>
                        <div class="thumb-placeholder">C</div>
                    <?php endif; ?>
                    <?php if ($rightImg): ?>
                        <img src="<?= htmlspecialchars($rightImg) ?>" alt="Right" draggable="false">
                    <?php else: ?>
                        <div class="thumb-placeholder">R</div>
                    <?php endif; ?>
                </div>
                <div class="card-footer-row">
                    <span class="card-assessment-name"><?= htmlspecialchars($assessment['name']) ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // Mark completed assessments with green check
    document.addEventListener('DOMContentLoaded', function() {
        const completed = JSON.parse(sessionStorage.getItem('completedAssessments') || '{}');
        const today = new Date().toISOString().slice(0, 10);

        document.querySelectorAll('.assessment-card').forEach(function(card) {
            const id = card.dataset.id;
            if (completed[id] === today) {
                card.classList.add('completed');
                const footer = card.querySelector('.card-footer-row');
                const check = document.createElement('div');
                check.className = 'card-check-done';
                check.innerHTML = '<i class="fa-solid fa-check"></i>';
                footer.appendChild(check);
            }
        });
    });
</script>
</body>
</html>
