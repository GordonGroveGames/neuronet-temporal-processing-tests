<?php
require_once __DIR__ . '/admin_session.php';
require_admin_login();

// Check if user can access admin panel
if (!can_access_admin_panel()) {
    header('HTTP/1.0 403 Forbidden');
    echo '<h1>403 Forbidden</h1><p>You do not have permission to access the admin panel.</p>';
    exit();
}

$currentUserRole = get_user_role();
$canManageUsers = can_manage_users();
$canManageAssessments = can_manage_assessments();

// Run migration (idempotent: extracts embedded tests into tests.json)
require_once __DIR__ . '/migrate_tests.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — The Fluency Factor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/touch-fixes.css">
    <link rel="stylesheet" href="assets/css/admin-styles.css">
    <script>
        function playAudio(filePath) {
            const existingAudio = document.getElementById('preview_audio');
            if (existingAudio) {
                existingAudio.pause();
                existingAudio.remove();
            }
            const audio = document.createElement('audio');
            audio.id = 'preview_audio';
            audio.src = filePath;
            audio.volume = 0.5;
            audio.play().catch(error => {
                console.error('Audio playback failed:', error);
                alert('Could not play audio file.');
            });
            audio.addEventListener('ended', function() { audio.remove(); });
            document.body.appendChild(audio);
        }

        function deleteAssessment(assessmentId, assessmentName) {
            if (confirm(`Are you sure you want to delete the assessment "${assessmentName}"? This action cannot be undone.`)) {
                fetch('admin_delete_assessment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        assessment_id: assessmentId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Assessment deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error deleting assessment: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Delete error:', error);
                    alert('Error deleting assessment: ' + error.message);
                });
            }
        }

        function deleteTest(testId, testName) {
            if (confirm(`Are you sure you want to delete the test "${testName}"? This action cannot be undone.`)) {
                fetch('admin_delete_test.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        test_id: testId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Test deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Delete error:', error);
                    alert('Error deleting test: ' + error.message);
                });
            }
        }
    </script>
</head>
<body>
<nav class="navbar-admin d-flex justify-content-between align-items-center">
    <a class="navbar-brand" href="#">The Fluency Factor</a>
    <div class="d-flex align-items-center gap-3">
        <a href="login.php" class="nav-link-light"><i class="fa-solid fa-arrow-right-from-bracket fa-flip-horizontal me-1"></i> Test Login</a>
        <a href="admin_logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket me-1"></i> Logout</a>
    </div>
</nav>

<!-- Tabs Navigation -->
<div class="container mt-4">
    <ul class="nav nav-tabs-modern mb-4" id="adminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="assessments-tab" data-bs-toggle="tab" data-bs-target="#assessments" type="button" role="tab"><i class="fa-solid fa-clipboard-list me-1"></i> Assessments</button>
        </li>
        <?php if ($canManageUsers): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab"><i class="fa-solid fa-users me-1"></i> Users</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="results-tab" data-bs-toggle="tab" data-bs-target="#results" type="button" role="tab"><i class="fa-solid fa-chart-bar me-1"></i> Results</button>
        </li>
        <?php endif; ?>
    </ul>
</div>
<div class="container">
    <div class="tab-content" id="adminTabsContent">
        <!-- Assessments Tab -->
        <div class="tab-pane fade show active" id="assessments" role="tabpanel">
            <?php
            // Load tests and assessments
            $testsFile = __DIR__ . '/assets/tests.json';
            $assessmentsFile = __DIR__ . '/assets/assessments.json';
            $allTests = file_exists($testsFile) ? json_decode(file_get_contents($testsFile), true) : [];
            $allAssessments = file_exists($assessmentsFile) ? json_decode(file_get_contents($assessmentsFile), true) : [];
            ?>

            <!-- Tests Sub-Section -->
            <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
                <h3 class="sub-section-header mb-0"><i class="fa-solid fa-vial"></i> Tests</h3>
                <a href="admin_test.php" class="btn btn-modern-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> Create Test</a>
            </div>
            <?php if (empty($allTests)): ?>
                <div class="alert alert-info text-center mb-4">No tests created yet. Create a test to get started.</div>
            <?php else: ?>
                <?php foreach ($allTests as $testId => $testData):
                    $createdBy = $testData['created_by'] ?? 'admin';
                    $isOwner = ($createdBy === $_SESSION['admin_user']);
                    $canEdit = $canManageAssessments && ($isOwner || in_array($currentUserRole, ['admin', 'site_admin']));
                    $canDelete = $canEdit;
                    $leftImg = $testData['left_image'] ?? '';
                    $centerImg = $testData['center_image'] ?? '';
                    $rightImg = $testData['right_image'] ?? '';
                ?>
                <div class="test-compact-card">
                    <div class="test-compact-thumbs">
                        <?php if ($leftImg): ?>
                            <img src="<?= htmlspecialchars($leftImg) ?>" alt="L">
                        <?php else: ?>
                            <div class="thumb-placeholder-sm">L</div>
                        <?php endif; ?>
                        <?php if ($centerImg): ?>
                            <img src="<?= htmlspecialchars($centerImg) ?>" alt="C">
                        <?php else: ?>
                            <div class="thumb-placeholder-sm">C</div>
                        <?php endif; ?>
                        <?php if ($rightImg): ?>
                            <img src="<?= htmlspecialchars($rightImg) ?>" alt="R">
                        <?php else: ?>
                            <div class="thumb-placeholder-sm">R</div>
                        <?php endif; ?>
                    </div>
                    <span class="test-compact-name"><?= htmlspecialchars($testData['name'] ?? $testId) ?></span>
                    <span class="badge-pill <?= $createdBy === 'admin' ? 'badge-admin' : 'badge-creator' ?>" style="font-size:0.65rem;">
                        <?= htmlspecialchars($createdBy) ?>
                    </span>
                    <div class="test-compact-actions">
                        <a href="test.php?test_id=<?= urlencode($testId) ?>&practice=1" class="btn-ghost" style="font-size:0.8rem;color:var(--primary);"><i class="fa-solid fa-play me-1"></i>Practice</a>
                        <?php if ($canEdit): ?>
                            <a href="admin_test.php?id=<?= urlencode($testId) ?>" class="btn-ghost" style="font-size:0.8rem;"><i class="fa-solid fa-pen-to-square me-1"></i>Edit</a>
                        <?php else: ?>
                            <a href="admin_test.php?id=<?= urlencode($testId) ?>" class="btn-ghost" style="font-size:0.8rem;"><i class="fa-solid fa-eye me-1"></i>View</a>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                            <button type="button" class="btn-ghost-danger" style="font-size:0.8rem;" onclick="deleteTest('<?= htmlspecialchars($testId) ?>', '<?= htmlspecialchars(addslashes($testData['name'] ?? $testId)) ?>')"><i class="fa-solid fa-trash-can me-1"></i>Delete</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <hr style="margin:2rem 0 1.5rem;">

            <!-- Assessments Sub-Section -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="sub-section-header mb-0"><i class="fa-solid fa-clipboard-list"></i> Assessments</h3>
                <a href="admin_assessment.php" class="btn btn-modern-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> Create Assessment</a>
            </div>
            <?php if (empty($allAssessments)): ?>
                <div class="alert alert-info text-center">No assessments found.</div>
            <?php else:
                foreach ($allAssessments as $id => $assessment):
                    $createdBy = $assessment['created_by'] ?? 'admin';
                    $isOwner = ($createdBy === $_SESSION['admin_user']);
                    $canEdit = $canManageAssessments && ($isOwner || in_array($currentUserRole, ['admin', 'site_admin']));
                    $canDelete = $canEdit;

                    // Resolve first test for preview thumbnails
                    $previewTest = [];
                    if (isset($assessment['test_ids']) && is_array($assessment['test_ids']) && !empty($assessment['test_ids'])) {
                        $firstTestId = $assessment['test_ids'][0];
                        $previewTest = $allTests[$firstTestId] ?? [];
                    } elseif (isset($assessment['tests'][0])) {
                        $previewTest = $assessment['tests'][0];
                    }
                    $leftImg = $previewTest['left_image'] ?? '';
                    $centerImg = $previewTest['center_image'] ?? '';
                    $rightImg = $previewTest['right_image'] ?? '';

                    // Test count
                    $testCount = 0;
                    if (isset($assessment['test_ids'])) {
                        $testCount = count($assessment['test_ids']);
                    } elseif (isset($assessment['tests'])) {
                        $testCount = count($assessment['tests']);
                    }
                ?>
                <div class="card-modern mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <strong style="font-size:1.05rem;"><?= htmlspecialchars($assessment['name']) ?></strong>
                            <span class="test-count-badge"><?= $testCount ?> test<?= $testCount !== 1 ? 's' : '' ?></span>
                            <span class="badge-pill <?= $createdBy === 'admin' ? 'badge-admin' : 'badge-creator' ?>">
                                <?= htmlspecialchars($createdBy) ?>
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="test.php?assessments=<?= urlencode($id) ?>&practice=1" class="btn-ghost" style="color:var(--primary);"><i class="fa-solid fa-play me-1"></i>Practice</a>
                            <?php if ($canEdit): ?>
                                <a href="admin_assessment.php?id=<?= urlencode($id) ?>" class="btn-ghost"><i class="fa-solid fa-pen-to-square me-1"></i>Edit</a>
                            <?php else: ?>
                                <a href="admin_assessment.php?id=<?= urlencode($id) ?>" class="btn-ghost"><i class="fa-solid fa-eye me-1"></i>View</a>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                                <button type="button" class="btn-ghost-danger" onclick="deleteAssessment('<?= htmlspecialchars($id) ?>', '<?= htmlspecialchars($assessment['name']) ?>')"><i class="fa-solid fa-trash-can me-1"></i>Delete</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body" style="padding:0.75rem 1rem;">
                        <?php
                        // Resolve all tests for this assessment
                        $assessmentTests = [];
                        if (isset($assessment['test_ids']) && is_array($assessment['test_ids'])) {
                            foreach ($assessment['test_ids'] as $idx => $tid) {
                                if (isset($allTests[$tid])) {
                                    $assessmentTests[] = $allTests[$tid];
                                }
                            }
                        } elseif (isset($assessment['tests']) && is_array($assessment['tests'])) {
                            $assessmentTests = $assessment['tests'];
                        }
                        ?>
                        <?php if (empty($assessmentTests)): ?>
                            <div class="text-muted" style="font-size:0.85rem;"><i class="fa-solid fa-info-circle me-1"></i>No tests assigned</div>
                        <?php else: ?>
                            <ol style="margin:0;padding-left:1.25rem;list-style:decimal;">
                                <?php foreach ($assessmentTests as $idx => $aTest):
                                    $tName = $aTest['name'] ?? ('Test ' . ($idx + 1));
                                    $tLeft = $aTest['left_image'] ?? '';
                                    $tCenter = $aTest['center_image'] ?? '';
                                    $tRight = $aTest['right_image'] ?? '';
                                ?>
                                <li style="padding:0.3rem 0;font-size:0.88rem;display:flex;align-items:center;gap:0.5rem;">
                                    <span class="test-compact-thumbs" style="gap:2px;">
                                        <?php if ($tLeft): ?>
                                            <img src="<?= htmlspecialchars($tLeft) ?>" alt="L" style="width:28px;height:28px;object-fit:contain;border-radius:3px;border:1px solid var(--border-light);background:var(--surface-1);">
                                        <?php endif; ?>
                                        <?php if ($tCenter): ?>
                                            <img src="<?= htmlspecialchars($tCenter) ?>" alt="C" style="width:28px;height:28px;object-fit:contain;border-radius:3px;border:1px solid var(--border-light);background:var(--surface-1);">
                                        <?php endif; ?>
                                        <?php if ($tRight): ?>
                                            <img src="<?= htmlspecialchars($tRight) ?>" alt="R" style="width:28px;height:28px;object-fit:contain;border-radius:3px;border:1px solid var(--border-light);background:var(--surface-1);">
                                        <?php endif; ?>
                                    </span>
                                    <span style="color:var(--text-primary);font-weight:500;"><?= htmlspecialchars($tName) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach;
            endif; ?>
        </div>

        <!-- Users Tab -->
        <?php if ($canManageUsers): ?>
        <div class="tab-pane fade" id="users" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>User Management</h2>
                <button type="button" class="btn btn-modern-primary" data-bs-toggle="modal" data-bs-target="#createUserModal"><i class="fa-solid fa-user-plus me-1"></i> Create New User</button>
            </div>
            
            <?php
            // Load users from JSON file
            $usersFile = __DIR__ . '/assets/users.json';
            $users = [];
            if (file_exists($usersFile)) {
                $json = file_get_contents($usersFile);
                $users = json_decode($json, true) ?: [];
            }
            
            // If no users file exists, create with default admin user
            if (empty($users)) {
                $users = [
                    'admin@example.com' => [
                        'name' => 'Administrator',
                        'email' => 'admin@example.com',
                        'password' => password_hash('admin123', PASSWORD_DEFAULT),
                        'role' => 'admin',
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                ];
                file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
            }
            ?>
            
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created By</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" class="text-center">No users found.</td></tr>
                <?php else:
                    // Filter users based on role permissions
                    $filteredUsers = [];
                    $currentUserEmail = $_SESSION['admin_user'];
                    
                    foreach ($users as $email => $user) {
                        // Admins and site admins can see all users
                        if (in_array($currentUserRole, ['admin', 'site_admin'])) {
                            $filteredUsers[$email] = $user;
                        }
                        // Test creators can only see users they created and admin users
                        elseif ($currentUserRole === 'test_creator') {
                            if ($user['role'] === 'admin' || 
                                (isset($user['created_by']) && $user['created_by'] === $currentUserEmail) ||
                                $email === $currentUserEmail) {
                                $filteredUsers[$email] = $user;
                            }
                        }
                    }
                    
                    foreach ($filteredUsers as $email => $user): 
                        $createdBy = $user['created_by'] ?? 'System';
                        $canEditUser = false;
                        
                        // Permission logic for editing users
                        if ($currentUserRole === 'admin' || $currentUserRole === 'site_admin') {
                            // Admins and site admins can edit users (except site admins can't edit other admins)
                            $canEditUser = !($currentUserRole === 'site_admin' && $user['role'] === 'admin');
                        } elseif ($currentUserRole === 'test_creator') {
                            // Test creators can only edit users they created (excluding admin users) or themselves
                            $canEditUser = ($email === $currentUserEmail) || 
                                          (isset($user['created_by']) && $user['created_by'] === $currentUserEmail && $user['role'] !== 'admin');
                        }
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($user['name'] ?? $user['username'] ?? 'No name') ?></td>
                            <td><?= htmlspecialchars($email) ?></td>
                            <td>
                                <?php
                                $badgeClass = 'badge-system';
                                $roleDisplay = ucwords(str_replace('_', ' ', $user['role']));
                                switch($user['role']) {
                                    case 'admin':
                                        $badgeClass = 'badge-admin';
                                        $roleDisplay = 'Master Admin';
                                        break;
                                    case 'site_admin':
                                        $badgeClass = 'badge-site-admin';
                                        break;
                                    case 'test_creator':
                                        $badgeClass = 'badge-creator';
                                        break;
                                    case 'test_taker':
                                        $badgeClass = 'badge-taker';
                                        break;
                                }
                                ?>
                                <span class="badge-pill <?= $badgeClass ?>">
                                    <?= htmlspecialchars($roleDisplay) ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                // Show created_by only for test_taker role
                                if ($user['role'] === 'test_taker'): 
                                    if ($createdBy === 'System' || empty($createdBy)): ?>
                                        <span class="badge-pill badge-system">System</span>
                                    <?php else:
                                        // Check if creator is admin - treat as System
                                        $isAdminCreator = isset($users[$createdBy]) && $users[$createdBy]['role'] === 'admin';
                                        if ($isAdminCreator): ?>
                                            <span class="badge-pill badge-system">System</span>
                                        <?php else: ?>
                                            <span class="badge-pill badge-creator"><?= htmlspecialchars($createdBy) ?></span>
                                        <?php endif;
                                    endif;
                                else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($user['created_at'] ?? 'Unknown') ?></td>
                            <td>
                                <?php if ($canEditUser): ?>
                                    <button type="button" class="btn-ghost me-1" onclick="editUser('<?= htmlspecialchars($email) ?>', '<?= htmlspecialchars($user['name'] ?? $user['username'] ?? '') ?>', '<?= htmlspecialchars($user['role']) ?>', '<?= htmlspecialchars($user['created_by'] ?? '') ?>')"><i class="fa-solid fa-pen-to-square me-1"></i>Edit</button>
                                <?php endif; ?>

                                <?php if ($canEditUser && $email !== $_SESSION['admin_user'] && $user['role'] !== 'admin'): ?>
                                    <button type="button" class="btn-ghost-danger" onclick="deleteUser('<?= htmlspecialchars($email) ?>')"><i class="fa-solid fa-trash-can me-1"></i>Delete</button>
                                <?php endif; ?>
                                
                                <?php if ($email === $_SESSION['admin_user']): ?>
                                    <span class="text-muted small">Current User</span>
                                <?php elseif ($user['role'] === 'admin' && !$canEditUser): ?>
                                    <span class="text-muted small">Master Admin</span>
                                <?php elseif (!$canEditUser): ?>
                                    <span class="text-muted small">No Permission</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach;
                endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Assessment Results Tab -->
        <div class="tab-pane fade" id="results" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Assessment Results</h2>
            </div>
            
            <!-- Results Counter -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted" id="resultsCount"></span>
                </div>
            </div>
            
            <!-- User Results Summary Table -->
            <div id="userResultsTable">
                <?php
                // Load test results from database
                $dbFile = __DIR__ . '/var/data/test_results.db';
                $userResultsData = [];
                
                if (file_exists($dbFile)) {
                    try {
                        $pdo = new PDO('sqlite:' . $dbFile);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        
                        // Get summary data for each user
                        $query = "
                            SELECT 
                                fullName,
                                email,
                                COUNT(*) as tests_taken,
                                MAX(datetime(timestamp)) as last_test_date
                            FROM test_results 
                            GROUP BY email, fullName
                            ORDER BY fullName
                        ";
                        
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $allResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Filter results based on user permissions
                        foreach ($allResults as $result) {
                            $userEmail = $result['email'];
                            
                            // Check if current user should see this result
                            $shouldInclude = false;
                            
                            if (in_array($currentUserRole, ['admin', 'site_admin'])) {
                                // Admins and site admins see all results
                                $shouldInclude = true;
                            } elseif ($currentUserRole === 'test_creator') {
                                // Test creators see results from users they created
                                if (isset($users[$userEmail]) && 
                                    isset($users[$userEmail]['created_by']) && 
                                    $users[$userEmail]['created_by'] === $_SESSION['admin_user']) {
                                    $shouldInclude = true;
                                }
                            }
                            
                            if ($shouldInclude) {
                                $userResultsData[] = $result;
                            }
                        }
                        
                    } catch (Exception $e) {
                        $error = "Error loading test results: " . $e->getMessage();
                    }
                }
                ?>
                
                <table class="table-modern" id="resultsTable">
                    <thead>
                        <tr>
                            <th>
                                <div class="d-flex flex-column">
                                    <span>Name</span>
                                    <div class="mt-1">
                                        <input type="text" class="form-control form-control-sm column-filter" 
                                               id="filterName" placeholder="Filter names..." style="font-weight: normal;">
                                    </div>
                                </div>
                            </th>
                            <th>
                                <div class="d-flex flex-column">
                                    <span>Email</span>
                                    <div class="mt-1">
                                        <input type="text" class="form-control form-control-sm column-filter" 
                                               id="filterEmail" placeholder="Filter emails..." style="font-weight: normal;">
                                    </div>
                                </div>
                            </th>
                            <th>
                                <div class="d-flex flex-column">
                                    <span>Tests Taken</span>
                                </div>
                            </th>
                            <th>
                                <div class="d-flex flex-column">
                                    <span>Last Test Date</span>
                                    <div class="mt-1">
                                        <div class="d-flex flex-column gap-1">
                                            <input type="date" class="form-control form-control-sm" 
                                                   id="filterDateFrom" style="font-weight: normal; font-size: 0.75rem;">
                                            <input type="date" class="form-control form-control-sm" 
                                                   id="filterDateTo" style="font-weight: normal; font-size: 0.75rem;">
                                        </div>
                                    </div>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="resultsTableBody">
                    <?php if (empty($userResultsData)): ?>
                        <tr><td colspan="4" class="text-center">No test results found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($userResultsData as $userData): ?>
                            <tr data-user-email="<?= htmlspecialchars($userData['email']) ?>">
                                <td>
                                    <a href="#" class="text-primary fw-bold" style="text-decoration: none;" 
                                       onclick="showUserDetails('<?= htmlspecialchars($userData['email']) ?>', '<?= htmlspecialchars($userData['fullName']) ?>')">
                                        <?= htmlspecialchars($userData['fullName']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($userData['email']) ?></td>
                                <td><?= $userData['tests_taken'] ?></td>
                                <td><?= $userData['last_test_date'] ? date('M j, Y g:i A', strtotime($userData['last_test_date'])) : 'Never' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Detailed Results Table (Initially Hidden) -->
            <div id="userDetailsTable" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 id="userDetailsTitle">Test Results for User</h4>
                    <button type="button" class="btn-ghost" onclick="hideUserDetails()"><i class="fa-solid fa-arrow-left me-1"></i> Back to Summary</button>
                </div>
                
                <div id="userDetailsContent">
                    <!-- Content will be loaded via JavaScript -->
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create User Modal -->
<?php if ($canManageUsers): ?>
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createUserForm">
                    <div class="mb-3">
                        <label class="form-label">Name (First Last)</label>
                        <input type="text" class="form-control" id="createName" required placeholder="John Doe">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address (will be used for login)</label>
                        <input type="email" class="form-control" id="createEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" id="createPassword" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="createConfirmPassword" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" id="createRole" required>
                            <option value="test_taker">Test Taker</option>
                            <option value="test_creator">Test Creator</option>
                            <option value="site_admin">Site Admin</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-modern-primary" onclick="createUser()">Create User</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="editOriginalEmail">
                    <div class="mb-3">
                        <label class="form-label">Name (First Last)</label>
                        <input type="text" class="form-control" id="editName" required placeholder="John Doe">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address (used for login)</label>
                        <input type="email" class="form-control" id="editEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" id="editPassword">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="editConfirmPassword">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" id="editRole" required>
                            <option value="test_taker">Test Taker</option>
                            <option value="test_creator">Test Creator</option>
                            <option value="site_admin">Site Admin</option>
                        </select>
                    </div>
                    <div class="mb-3" id="editCreatedBySection">
                        <label class="form-label">Created By (leave blank to remove assignment)</label>
                        <select class="form-select" id="editCreatedBy">
                            <option value="">No Assignment (System)</option>
                        </select>
                        <small class="form-text text-muted">Only admins and site admins can modify this field</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-modern-primary" onclick="updateUser()">Update User</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Toast notification for save confirmations -->
<div id="saveToast" class="save-toast" style="display:none;">
    <i class="fa-solid fa-circle-check me-2"></i>
    <span id="saveToastMsg">Saved!</span>
</div>
<style>
.save-toast {
    position: fixed;
    top: 72px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--success, #28a745);
    color: #fff;
    padding: 10px 24px;
    border-radius: var(--radius-pill, 9999px);
    font-size: 0.9rem;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0,0,0,0.18);
    z-index: 9999;
    display: flex;
    align-items: center;
    animation: toastSlideIn 0.3s ease;
}
@keyframes toastSlideIn {
    from { opacity: 0; transform: translateX(-50%) translateY(-12px); }
    to   { opacity: 1; transform: translateX(-50%) translateY(0); }
}
@keyframes toastSlideOut {
    from { opacity: 1; transform: translateX(-50%) translateY(0); }
    to   { opacity: 0; transform: translateX(-50%) translateY(-12px); }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Show toast notification if redirected after save
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const saved = urlParams.get('saved');
        if (saved) {
            const toast = document.getElementById('saveToast');
            const msg = document.getElementById('saveToastMsg');
            msg.textContent = saved === 'test' ? 'Test Saved' : saved === 'assessment' ? 'Assessment Saved' : 'Saved!';
            toast.style.display = 'flex';
            // Remove ?saved param from URL without reload
            const cleanUrl = window.location.pathname + window.location.hash;
            history.replaceState(null, '', cleanUrl);
            // Auto-hide after 2 seconds
            setTimeout(function() {
                toast.style.animation = 'toastSlideOut 0.3s ease forwards';
                setTimeout(function() { toast.style.display = 'none'; }, 300);
            }, 2000);
        }
    });

    // Tab persistence functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Get saved tab from localStorage or URL hash
        const savedTab = localStorage.getItem('adminActiveTab') || window.location.hash.substring(1);
        
        // If we have a saved tab, activate it
        if (savedTab) {
            const tabElement = document.querySelector(`[data-bs-target="#${savedTab}"]`);
            if (tabElement) {
                const tab = new bootstrap.Tab(tabElement);
                tab.show();
            }
        }
        
        // Save tab when user switches
        const tabElements = document.querySelectorAll('[data-bs-toggle="tab"]');
        tabElements.forEach(function(tabElement) {
            tabElement.addEventListener('shown.bs.tab', function(event) {
                const targetTab = event.target.getAttribute('data-bs-target').substring(1);
                localStorage.setItem('adminActiveTab', targetTab);
                // Also update URL hash without triggering scroll
                history.replaceState(null, null, '#' + targetTab);
            });
        });
    });
    // Edit User functionality
    function editUser(email, name, role, createdBy = '') {
        document.getElementById('editOriginalEmail').value = email;
        document.getElementById('editName').value = name;
        document.getElementById('editEmail').value = email;
        document.getElementById('editRole').value = role;
        document.getElementById('editPassword').value = '';
        document.getElementById('editConfirmPassword').value = '';
        
        // Populate created_by dropdown and set current value
        populateCreatedByDropdown();
        
        // If created by admin or empty, treat as System (empty value)
        const users = <?= json_encode($users ?? []) ?>;
        const isAdminCreated = createdBy && users[createdBy] && users[createdBy].role === 'admin';
        
        if (!createdBy || isAdminCreated) {
            document.getElementById('editCreatedBy').value = '';
        } else {
            document.getElementById('editCreatedBy').value = createdBy;
        }
        
        // Show/hide created_by section based on user permissions and role being edited
        const currentUserRole = '<?= $currentUserRole ?>';
        const createdBySection = document.getElementById('editCreatedBySection');
        
        // Only show for test_taker role and only for admin/site_admin users
        if (role === 'test_taker' && (currentUserRole === 'admin' || currentUserRole === 'site_admin')) {
            createdBySection.style.display = 'block';
        } else {
            createdBySection.style.display = 'none';
        }
        
        // Listen for role changes to show/hide created_by section
        document.getElementById('editRole').addEventListener('change', function() {
            const selectedRole = this.value;
            if (selectedRole === 'test_taker' && (currentUserRole === 'admin' || currentUserRole === 'site_admin')) {
                createdBySection.style.display = 'block';
            } else {
                createdBySection.style.display = 'none';
            }
        });
        
        const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
        editModal.show();
    }
    
    // Populate the created_by dropdown with available users
    function populateCreatedByDropdown() {
        const dropdown = document.getElementById('editCreatedBy');
        
        // Clear existing options except the first one
        dropdown.innerHTML = '<option value="">System (Admin/No Assignment)</option>';
        
        // Get users from PHP and add to dropdown
        const users = <?= json_encode($users ?? []) ?>;
        
        Object.keys(users).forEach(function(userEmail) {
            const user = users[userEmail];
            // Only show site_admin and test_creator users as potential creators
            // Admin is treated as System, so don't show admin users separately
            if (user.role === 'site_admin' || user.role === 'test_creator') {
                const option = document.createElement('option');
                option.value = userEmail;
                option.textContent = (user.name || userEmail) + ' (' + user.role.replace('_', ' ') + ')';
                dropdown.appendChild(option);
            }
        });
    }
    
    // Update User functionality
    function updateUser() {
        const originalEmail = document.getElementById('editOriginalEmail').value;
        const name = document.getElementById('editName').value;
        const email = document.getElementById('editEmail').value;
        const password = document.getElementById('editPassword').value;
        const confirmPassword = document.getElementById('editConfirmPassword').value;
        const role = document.getElementById('editRole').value;
        
        if (!name.trim()) {
            alert('Name is required');
            return;
        }
        
        if (password || confirmPassword) {
            if (!password || !confirmPassword) {
                alert('Please fill in both password fields');
                return;
            }
            if (password !== confirmPassword) {
                alert('Passwords do not match');
                return;
            }
            if (password.length < 6) {
                alert('Password must be at least 6 characters long');
                return;
            }
        }
        
        // Basic email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Please enter a valid email address');
            return;
        }
        
        const requestData = {
            original_email: originalEmail,
            name: name,
            email: email,
            role: role
        };
        
        if (password) {
            requestData.password = password;
        }
        
        // Include created_by only for test_taker role and if user has permission to modify it
        const currentUserRole = '<?= $currentUserRole ?>';
        if (role === 'test_taker' && (currentUserRole === 'admin' || currentUserRole === 'site_admin')) {
            const createdBy = document.getElementById('editCreatedBy').value;
            requestData.created_by = createdBy || null; // null means remove assignment (System)
        }
        
        fetch('admin_edit_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal first, then show alert and reload
                const modalElement = document.getElementById('editUserModal');
                const modal = bootstrap.Modal.getInstance(modalElement);
                
                if (modal) {
                    // Listen for modal close event, then show success and reload
                    modalElement.addEventListener('hidden.bs.modal', function() {
                        alert('User updated successfully!');
                        location.reload();
                    }, { once: true });
                    
                    modal.hide();
                } else {
                    alert('User updated successfully!');
                    location.reload();
                }
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating user: ' + error.message);
        });
    }
    
    // Create User functionality
    function createUser() {
        const name = document.getElementById('createName').value;
        const email = document.getElementById('createEmail').value;
        const password = document.getElementById('createPassword').value;
        const confirmPassword = document.getElementById('createConfirmPassword').value;
        const role = document.getElementById('createRole').value;
        
        if (!name.trim()) {
            alert('Name is required');
            return;
        }
        
        if (password !== confirmPassword) {
            alert('Passwords do not match');
            return;
        }
        
        if (password.length < 6) {
            alert('Password must be at least 6 characters long');
            return;
        }
        
        // Basic email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Please enter a valid email address');
            return;
        }
        
        fetch('admin_create_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: name,
                email: email,
                password: password,
                role: role
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal first, then show alert and reload
                const modalElement = document.getElementById('createUserModal');
                const modal = bootstrap.Modal.getInstance(modalElement);
                
                if (modal) {
                    // Listen for modal close event, then show success and reload
                    modalElement.addEventListener('hidden.bs.modal', function() {
                        alert('User created successfully!');
                        location.reload();
                    }, { once: true });
                    
                    modal.hide();
                } else {
                    alert('User created successfully!');
                    location.reload();
                }
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error creating user: ' + error.message);
        });
    }
    
    // Delete User functionality
    function deleteUser(email) {
        if (confirm(`Are you sure you want to delete user "${email}"? This action cannot be undone.`)) {
            fetch('admin_delete_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting user: ' + error.message);
            });
        }
    }
    
    // Show detailed test results for a user
    function showUserDetails(email, fullName) {
        // Store current user email for delete operations
        window.currentDetailUserEmail = email;
        
        document.getElementById('userDetailsTitle').textContent = `Test Results for ${fullName}`;
        document.getElementById('userResultsTable').style.display = 'none';
        document.getElementById('userDetailsTable').style.display = 'block';
        
        // Load detailed results via AJAX
        fetch('admin_get_user_results.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayUserResults(data.results);
            } else {
                document.getElementById('userDetailsContent').innerHTML = 
                    '<div class="alert alert-danger">Error loading results: ' + (data.error || 'Unknown error') + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('userDetailsContent').innerHTML = 
                '<div class="alert alert-danger">Error loading results: ' + error.message + '</div>';
        });
    }
    
    // Display detailed user results
    function displayUserResults(results) {
        if (!results || results.length === 0) {
            document.getElementById('userDetailsContent').innerHTML =
                '<div class="alert alert-info">No test results found for this user.</div>';
            return;
        }

        const isAdmin = <?= json_encode(in_array($currentUserRole, ['admin', 'site_admin'])) ?>;

        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function filenameFromPath(path) {
            if (!path) return '';
            const parts = path.replace(/\\/g, '/').split('/');
            const file = parts[parts.length - 1];
            // Strip extension
            return file.replace(/\.[^.]+$/, '');
        }

        function buildChannelCard(label, channelClass, imgSrc, imgName, audioSrc, audioName, trials, correct, accuracy, avgMs) {
            const thumbHtml = imgSrc
                ? `<img src="${imgSrc}" class="channel-thumb" alt="${escapeHtml(imgName)}">`
                : `<div class="channel-thumb-placeholder">${label.charAt(0)}</div>`;

            const playBtn = audioSrc
                ? `<button type="button" class="btn-play" onclick="playAudio('${escapeHtml(audioSrc)}')" title="Play ${escapeHtml(audioName)}"><i class="fa-solid fa-play"></i></button>`
                : '';

            const scoreClass = accuracy >= 80 ? 'score-high' : accuracy >= 50 ? 'score-medium' : 'score-low';

            return `
                <div class="channel-card ${channelClass}">
                    <div class="channel-label">${label}</div>
                    <div class="channel-content">
                        <div class="channel-media">
                            ${thumbHtml}
                            <div class="channel-img-name" title="${escapeHtml(imgName)}">${escapeHtml(imgName || '—')}</div>
                        </div>
                        <div class="channel-audio">
                            <span class="channel-audio-name" title="${escapeHtml(audioName)}">${escapeHtml(audioName || '—')}</span>
                            ${playBtn}
                        </div>
                    </div>
                    <div class="channel-stats">
                        <div class="stat-label">Trials:</div>
                        <div class="stat-value">${trials}</div>
                        <div class="stat-label">Accuracy:</div>
                        <div class="stat-value ${scoreClass}">${correct}/${trials} (${accuracy}%)</div>
                        <div class="stat-label">Avg RT:</div>
                        <div class="stat-value">${formatNumber(avgMs)} ms</div>
                    </div>
                </div>`;
        }

        let html = '';

        results.forEach((result) => {
            const totalTrials = result.trials ? result.trials.length : 0;
            const correctTrials = result.trials ? result.trials.filter(t => t.correct).length : 0;
            const scorePercentage = totalTrials > 0 ? Math.round((correctTrials / totalTrials) * 100) : 0;

            const leftTrials = result.leftCorrect + result.leftIncorrect;
            const centerTrials = result.centerCorrect + result.centerIncorrect;
            const rightTrials = result.rightCorrect + result.rightIncorrect;

            const leftAccuracy = leftTrials > 0 ? Math.round((result.leftCorrect / leftTrials) * 100) : 0;
            const centerAccuracy = centerTrials > 0 ? Math.round((result.centerCorrect / centerTrials) * 100) : 0;
            const rightAccuracy = rightTrials > 0 ? Math.round((result.rightCorrect / rightTrials) * 100) : 0;

            const cfg = result.config || {};
            const leftImgName = filenameFromPath(cfg.left_image);
            const centerImgName = filenameFromPath(cfg.center_image);
            const rightImgName = filenameFromPath(cfg.right_image);
            const leftAudioName = filenameFromPath(cfg.left_sound);
            const centerAudioName = filenameFromPath(cfg.center_sound);
            const rightAudioName = filenameFromPath(cfg.right_sound);

            const scoreClass = scorePercentage >= 80 ? 'score-high' : scorePercentage >= 50 ? 'score-medium' : 'score-low';

            html += `
            <div class="card-modern mb-3">
                <div class="result-card-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong style="font-size:1.05rem;">${escapeHtml(result.testName)}</strong>
                        <span style="color:var(--text-muted);margin-left:0.75rem;font-size:0.85rem;">${formatDate(result.testDate)}</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span style="font-size:0.85rem;color:var(--text-secondary);">Total Time: <strong>${formatNumber(result.testTimeMs || 0)} ms</strong></span>
                        <span style="font-size:0.85rem;font-weight:600;" class="${scoreClass}">${correctTrials}/${totalTrials} (${scorePercentage}%)</span>
                        ${isAdmin ? `<button class="btn-ghost-danger" style="padding:0.2rem 0.5rem;" onclick="deleteTestSession('${escapeHtml(result.sessionId)}', '${escapeHtml(result.testName)}', '${formatDate(result.testDate)}')" title="Delete this test session"><i class="fa-solid fa-trash-can"></i></button>` : ''}
                    </div>
                </div>
                <div class="card-body">
                    <div style="display:flex;gap:12px;margin-bottom:12px;">
                        ${buildChannelCard('Left', 'channel-left', cfg.left_image, leftImgName, cfg.left_sound, leftAudioName, leftTrials, result.leftCorrect, leftAccuracy, result.leftAvgResponseTime || 0)}
                        ${buildChannelCard('Center', 'channel-center', cfg.center_image, centerImgName, cfg.center_sound, centerAudioName, centerTrials, result.centerCorrect, centerAccuracy, result.centerAvgResponseTime || 0)}
                        ${buildChannelCard('Right', 'channel-right', cfg.right_image, rightImgName, cfg.right_sound, rightAudioName, rightTrials, result.rightCorrect, rightAccuracy, result.rightAvgResponseTime || 0)}
                    </div>
                    <div class="trial-results-bar">
                        <div class="d-flex align-items-center gap-3">
                            <small style="white-space:nowrap;color:var(--text-muted);font-weight:500;">Trial Results:</small>
                            <div class="d-flex gap-1" style="flex-grow:1;flex-wrap:wrap;">
                                ${result.trials ? result.trials.map((trial, trialIndex) => {
                                    const bgColor = trial.correct ? 'var(--success)' : 'var(--danger)';
                                    const title = `Trial ${trialIndex + 1}: ${trial.userAnswer} (correct: ${trial.correctAnswer}) - ${trial.responseTime}ms`;
                                    const channelLetter = trial.correctAnswer.charAt(0).toUpperCase();
                                    return `<div class="trial-dot" style="background-color:${bgColor};" title="${title}">
                                        <span>${channelLetter}</span>
                                    </div>`;
                                }).join('') : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
        });

        document.getElementById('userDetailsContent').innerHTML = html;
    }
    
    // Hide user details and return to summary
    function hideUserDetails() {
        document.getElementById('userDetailsTable').style.display = 'none';
        document.getElementById('userResultsTable').style.display = 'block';
    }
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Helper function to format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Assessment Results Filtering and Sorting
    let originalResultsData = [];
    
    // Store original data when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Store original table data for filtering
        const tableBody = document.getElementById('resultsTableBody');
        if (tableBody) {
            const rows = tableBody.querySelectorAll('tr');
            originalResultsData = [];
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const isAdmin = <?= json_encode(in_array($currentUserRole, ['admin', 'site_admin'])) ?>;
                const minCells = isAdmin ? 5 : 4; // Admin has extra checkbox and action columns
                
                if (cells.length >= minCells) {
                    // Account for potential checkbox column offset
                    const offset = (isAdmin && cells[0].classList.contains('bulk-select-column')) ? 1 : 0;
                    
                    // Extract the onclick parameter to get email
                    const nameLink = cells[offset].querySelector('a');
                    let email = '';
                    if (nameLink && nameLink.onclick) {
                        const onclickStr = nameLink.onclick.toString();
                        const emailMatch = onclickStr.match(/showUserDetails\s*\(\s*['"]([^'"]+)['"]/);
                        if (emailMatch) {
                            email = emailMatch[1];
                        }
                    }
                    
                    const name = cells[offset].textContent.trim();
                    const emailText = cells[offset + 1].textContent.trim();
                    const testsCount = parseInt(cells[offset + 2].textContent.trim()) || 0;
                    const lastTestDate = cells[offset + 3].textContent.trim();
                    
                    originalResultsData.push({
                        name: name,
                        email: emailText,
                        emailForDetails: email,
                        testsCount: testsCount,
                        lastTestDate: lastTestDate,
                        lastTestDateRaw: parseDate(lastTestDate),
                        originalRow: row.outerHTML
                    });
                }
            });
            
            updateResultsCount(originalResultsData.length);
        }
        
        // Add real-time filtering for column filters
        document.getElementById('filterName').addEventListener('input', applyFilters);
        document.getElementById('filterEmail').addEventListener('input', applyFilters);
        document.getElementById('filterDateFrom').addEventListener('change', applyFilters);
        document.getElementById('filterDateTo').addEventListener('change', applyFilters);
    });
    
    // Parse date string to Date object
    function parseDate(dateStr) {
        if (dateStr === 'Never') return new Date(0);
        return new Date(dateStr);
    }
    
    
    // Apply filters and sorting
    function applyFilters() {
        const nameFilter = document.getElementById('filterName').value.toLowerCase();
        const emailFilter = document.getElementById('filterEmail').value.toLowerCase();
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        
        let filteredData = [...originalResultsData];
        
        // Apply name filter
        if (nameFilter) {
            filteredData = filteredData.filter(item => 
                item.name.toLowerCase().includes(nameFilter)
            );
        }
        
        // Apply email filter
        if (emailFilter) {
            filteredData = filteredData.filter(item => 
                item.email.toLowerCase().includes(emailFilter)
            );
        }
        
        // Apply date range filter
        if (dateFrom || dateTo) {
            const fromDate = dateFrom ? new Date(dateFrom) : new Date(0);
            const toDate = dateTo ? new Date(dateTo) : new Date();
            
            filteredData = filteredData.filter(item => {
                const testDate = item.lastTestDateRaw;
                return testDate >= fromDate && testDate <= toDate;
            });
        }
        
        // Update table
        updateTable(filteredData);
        updateResultsCount(filteredData.length);
    }
    
    // Update the table with filtered/sorted data
    function updateTable(data) {
        const tableBody = document.getElementById('resultsTableBody');
        const isAdmin = <?= json_encode(in_array($currentUserRole, ['admin', 'site_admin'])) ?>;
        const colspanCount = isAdmin ? 6 : 4;
        
        if (data.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="${colspanCount}" class="text-center">No results match your filters.</td></tr>`;
            return;
        }
        
        tableBody.innerHTML = data.map(item => item.originalRow).join('');
        
        // Re-attach click handlers for user details
        const nameLinks = tableBody.querySelectorAll('a[onclick*="showUserDetails"]');
        nameLinks.forEach(link => {
            const originalOnclick = link.getAttribute('onclick');
            link.removeAttribute('onclick');
            link.addEventListener('click', function(e) {
                e.preventDefault();
                eval(originalOnclick);
            });
        });
        
        // Re-attach delete button handlers
        const deleteButtons = tableBody.querySelectorAll('button[onclick*="deleteUserResults"]');
        deleteButtons.forEach(button => {
            const originalOnclick = button.getAttribute('onclick');
            button.removeAttribute('onclick');
            button.addEventListener('click', function(e) {
                e.preventDefault();
                eval(originalOnclick);
            });
        });
        
        // Set up bulk selection if in bulk mode
        if (bulkDeleteMode) {
            const selectColumns = tableBody.querySelectorAll('.bulk-select-column');
            selectColumns.forEach(col => col.style.display = 'table-cell');
            
            // Re-attach checkbox event listeners
            const checkboxes = tableBody.querySelectorAll('.user-select-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectAllState);
            });
        }
    }
    
    // Update results count display
    function updateResultsCount(count) {
        const total = originalResultsData.length;
        const countElement = document.getElementById('resultsCount');
        if (count === total) {
            countElement.textContent = `Showing ${count} user${count !== 1 ? 's' : ''}`;
        } else {
            countElement.textContent = `Showing ${count} of ${total} user${total !== 1 ? 's' : ''}`;
        }
    }
    
    
    // Test Results Deletion Functions
    let bulkDeleteMode = false;
    
    // Toggle bulk delete mode
    function toggleBulkDelete() {
        bulkDeleteMode = !bulkDeleteMode;
        const bulkSelectContainer = document.getElementById('bulkSelectContainer');
        const selectColumnHeaders = document.querySelectorAll('.bulk-select-column');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        
        if (bulkDeleteMode) {
            // Enter bulk delete mode
            bulkSelectContainer.style.display = 'block';
            selectColumnHeaders.forEach(el => el.style.display = 'table-cell');
            bulkDeleteBtn.classList.remove('btn-outline-danger');
            bulkDeleteBtn.classList.add('btn-danger');
            bulkDeleteBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';
            
            // Show bulk action buttons
            showBulkActionButtons();
        } else {
            // Exit bulk delete mode
            bulkSelectContainer.style.display = 'none';
            selectColumnHeaders.forEach(el => el.style.display = 'none');
            bulkDeleteBtn.classList.remove('btn-danger');
            bulkDeleteBtn.classList.add('btn-outline-danger');
            bulkDeleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete Results';
            
            // Clear all selections
            document.getElementById('selectAll').checked = false;
            document.querySelectorAll('.user-select-checkbox').forEach(cb => cb.checked = false);
            
            // Hide bulk action buttons
            hideBulkActionButtons();
        }
    }
    
    // Show bulk action buttons
    function showBulkActionButtons() {
        const bulkSelectContainer = document.getElementById('bulkSelectContainer');
        
        if (!document.getElementById('bulkActionButtons')) {
            const actionDiv = document.createElement('div');
            actionDiv.id = 'bulkActionButtons';
            actionDiv.className = 'd-flex gap-2 ms-3';
            actionDiv.innerHTML = `
                <button type="button" class="btn btn-danger btn-sm" onclick="deleteSelectedUsers()">
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
            `;
            bulkSelectContainer.appendChild(actionDiv);
        }
        
        // Set up select all functionality
        const selectAllCheckbox = document.getElementById('selectAll');
        selectAllCheckbox.addEventListener('change', function() {
            const userCheckboxes = document.querySelectorAll('.user-select-checkbox');
            userCheckboxes.forEach(cb => cb.checked = this.checked);
        });
        
        // Set up individual checkbox listeners
        document.querySelectorAll('.user-select-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectAllState);
        });
    }
    
    // Hide bulk action buttons
    function hideBulkActionButtons() {
        const bulkActionButtons = document.getElementById('bulkActionButtons');
        if (bulkActionButtons) {
            bulkActionButtons.remove();
        }
    }
    
    // Update select all checkbox state based on individual selections
    function updateSelectAllState() {
        const userCheckboxes = document.querySelectorAll('.user-select-checkbox');
        const selectAllCheckbox = document.getElementById('selectAll');
        const checkedCount = document.querySelectorAll('.user-select-checkbox:checked').length;
        
        if (checkedCount === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (checkedCount === userCheckboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
        }
    }
    
    // Delete test results for a single user
    function deleteUserResults(email, userName) {
        if (confirm(`Are you sure you want to delete ALL test results for "${userName}" (${email})?\n\nThis action cannot be undone.`)) {
            fetch('admin_delete_results.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete_user_results',
                    email: email
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // Refresh to show updated results
                } else {
                    alert('Error deleting results: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting results: ' + error.message);
            });
        }
    }
    
    // Delete test results for selected users
    function deleteSelectedUsers() {
        const selectedCheckboxes = document.querySelectorAll('.user-select-checkbox:checked');
        
        if (selectedCheckboxes.length === 0) {
            alert('Please select at least one user to delete results for.');
            return;
        }
        
        const selectedEmails = Array.from(selectedCheckboxes).map(cb => cb.value);
        const userCount = selectedEmails.length;
        
        if (confirm(`Are you sure you want to delete ALL test results for ${userCount} selected user(s)?\n\nThis action cannot be undone.`)) {
            fetch('admin_delete_results.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete_multiple_users',
                    emails: selectedEmails
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // Refresh to show updated results
                } else {
                    alert('Error deleting results: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting results: ' + error.message);
            });
        }
    }
    
    // Delete individual test session
    function deleteTestSession(sessionId, testName, testDate) {
        const currentUserEmail = getCurrentUserEmail(); // Get current user's email from the details view
        
        if (confirm(`Are you sure you want to delete the test session "${testName}" from ${testDate}?\n\nThis action cannot be undone.`)) {
            fetch('admin_delete_results.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete_test_session',
                    email: currentUserEmail,
                    session_id: sessionId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Refresh the user details to show updated results
                    const userDetailsTitle = document.getElementById('userDetailsTitle').textContent;
                    const userName = userDetailsTitle.replace('Test Results for ', '');
                    showUserDetails(currentUserEmail, userName);
                } else {
                    alert('Error deleting test session: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting test session: ' + error.message);
            });
        }
    }
    
    // Helper function to get current user email from the context
    function getCurrentUserEmail() {
        // This will be set when showUserDetails is called
        return window.currentDetailUserEmail || '';
    }
</script>
</body>
</html>
