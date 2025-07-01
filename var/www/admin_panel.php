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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NNTPT Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <script>
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
    </script>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-light bg-white mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">NNTPT Admin</a>
        <div class="d-flex">
            <a href="admin_logout.php" class="btn btn-outline-danger">Logout</a>
        </div>
    </div>
</nav>

<!-- Tabs Navigation -->
<div class="container">
    <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="assessments-tab" data-bs-toggle="tab" data-bs-target="#assessments" type="button" role="tab">Assessments</button>
        </li>
        <?php if ($canManageUsers): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">User Management</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="results-tab" data-bs-toggle="tab" data-bs-target="#results" type="button" role="tab">Assessment Results</button>
        </li>
        <?php endif; ?>
    </ul>
</div>
<div class="container">
    <div class="tab-content" id="adminTabsContent">
        <!-- Assessments Tab -->
        <div class="tab-pane fade show active" id="assessments" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Assessments</h2>
                <a href="admin_assessment.php" class="btn btn-primary">Create/Edit New Assessment</a>
            </div>
            <?php
            // List assessments from JSON
            $assessmentsFile = __DIR__ . '/assets/assessments.json';
            $assessments = [];
            if (file_exists($assessmentsFile)) {
                $json = file_get_contents($assessmentsFile);
                $allAssessments = json_decode($json, true) ?: [];
                
                // Filter assessments based on user role
                if ($currentUserRole === 'test_creator') {
                    // Test creators can see admin assessments (read-only) and their own assessments
                    $assessments = $allAssessments;
                } else {
                    // Admins and site admins can see all assessments
                    $assessments = $allAssessments;
                }
            }
            ?>
            <table class="table table-bordered bg-white">
                <thead>
                    <tr>
                        <th>Assessment Name</th>
                        <th>Number of Tests</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($assessments)): ?>
                    <tr><td colspan="4" class="text-center">No assessments found.</td></tr>
                <?php else:
                    foreach ($assessments as $id => $assessment): 
                        $createdBy = $assessment['created_by'] ?? 'admin';
                        $isOwner = ($createdBy === $_SESSION['admin_user']);
                        $canEdit = $canManageAssessments && ($isOwner || in_array($currentUserRole, ['admin', 'site_admin']));
                        $canDelete = $canEdit;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($assessment['name']) ?></td>
                            <td><?= isset($assessment['tests']) ? count($assessment['tests']) : 0 ?></td>
                            <td>
                                <span class="badge <?= $createdBy === 'admin' ? 'bg-danger' : 'bg-info' ?>">
                                    <?= htmlspecialchars($createdBy) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($canEdit): ?>
                                    <a href="admin_assessment.php?id=<?= urlencode($id) ?>" class="btn btn-sm btn-outline-secondary me-2">Edit</a>
                                <?php else: ?>
                                    <a href="admin_assessment.php?id=<?= urlencode($id) ?>" class="btn btn-sm btn-outline-info me-2">View</a>
                                <?php endif; ?>
                                
                                <?php if ($canDelete): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteAssessment('<?= htmlspecialchars($id) ?>', '<?= htmlspecialchars($assessment['name']) ?>')">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach;
                endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Users Tab -->
        <?php if ($canManageUsers): ?>
        <div class="tab-pane fade" id="users" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>User Management</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">Create New User</button>
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
            
            <table class="table table-bordered bg-white">
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
                                $badgeClass = 'bg-secondary';
                                $roleDisplay = ucwords(str_replace('_', ' ', $user['role']));
                                switch($user['role']) {
                                    case 'admin':
                                        $badgeClass = 'bg-danger';
                                        $roleDisplay = 'Master Admin';
                                        break;
                                    case 'site_admin':
                                        $badgeClass = 'bg-warning';
                                        break;
                                    case 'test_creator':
                                        $badgeClass = 'bg-info';
                                        break;
                                    case 'test_taker':
                                        $badgeClass = 'bg-success';
                                        break;
                                }
                                ?>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= htmlspecialchars($roleDisplay) ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                // Show created_by only for test_taker role
                                if ($user['role'] === 'test_taker'): 
                                    if ($createdBy === 'System' || empty($createdBy)): ?>
                                        <span class="badge bg-secondary">System</span>
                                    <?php else:
                                        // Check if creator is admin - treat as System
                                        $isAdminCreator = isset($users[$createdBy]) && $users[$createdBy]['role'] === 'admin';
                                        if ($isAdminCreator): ?>
                                            <span class="badge bg-secondary">System</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary"><?= htmlspecialchars($createdBy) ?></span>
                                        <?php endif;
                                    endif;
                                else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($user['created_at'] ?? 'Unknown') ?></td>
                            <td>
                                <?php if ($canEditUser): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="editUser('<?= htmlspecialchars($email) ?>', '<?= htmlspecialchars($user['name'] ?? $user['username'] ?? '') ?>', '<?= htmlspecialchars($user['role']) ?>', '<?= htmlspecialchars($user['created_by'] ?? '') ?>')">Edit</button>
                                <?php endif; ?>
                                
                                <?php if ($canEditUser && $email !== $_SESSION['admin_user'] && $user['role'] !== 'admin'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteUser('<?= htmlspecialchars($email) ?>')">Delete</button>
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
                $dbFile = dirname(__DIR__) . '/data/test_results.db';
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
                
                <table class="table table-bordered bg-white" id="resultsTable">
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
                    <button type="button" class="btn btn-secondary" onclick="hideUserDetails()">Back to Summary</button>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="createUser()">Create User</button>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateUser()">Update User</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
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
        
        if (password && password !== confirmPassword) {
            alert('Passwords do not match');
            return;
        }
        
        if (password && password.length < 6) {
            alert('Password must be at least 6 characters long');
            return;
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
    
    // Display detailed user results in a table
    function displayUserResults(results) {
        if (!results || results.length === 0) {
            document.getElementById('userDetailsContent').innerHTML = 
                '<div class="alert alert-info">No test results found for this user.</div>';
            return;
        }
        
        const isAdmin = <?= json_encode(in_array($currentUserRole, ['admin', 'site_admin'])) ?>;
        
        let html = `
            <table class="table table-bordered table-striped bg-white">
                <thead class="table-dark">
                    <tr>
                        <th>Test Name</th>
                        <th>Test Date</th>
                        <th>Left Accuracy</th>
                        <th>Center Accuracy</th>
                        <th>Right Accuracy</th>
                        <th>Test Time (ms)</th>
                        <th>Avg Response Time (ms)</th>
                        ${isAdmin ? '<th style="width: 80px;">Actions</th>' : ''}
                    </tr>
                </thead>
                <tbody>
        `;
        
        // Helper function to format numbers with commas
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        
        results.forEach((result, index) => {
            const totalTrials = result.trials ? result.trials.length : 0;
            const correctTrials = result.trials ? result.trials.filter(t => t.correct).length : 0;
            const scorePercentage = totalTrials > 0 ? Math.round((correctTrials / totalTrials) * 100) : 0;
            
            // Calculate accuracy percentages for each position
            const leftTrials = result.leftCorrect + result.leftIncorrect;
            const centerTrials = result.centerCorrect + result.centerIncorrect;
            const rightTrials = result.rightCorrect + result.rightIncorrect;
            
            const leftAccuracy = leftTrials > 0 ? Math.round((result.leftCorrect / leftTrials) * 100) : 0;
            const centerAccuracy = centerTrials > 0 ? Math.round((result.centerCorrect / centerTrials) * 100) : 0;
            const rightAccuracy = rightTrials > 0 ? Math.round((result.rightCorrect / rightTrials) * 100) : 0;
            
            html += `
                <tr>
                    <td>${escapeHtml(result.testName)}</td>
                    <td>${formatDate(result.testDate)}</td>
                    <td class="text-center">${result.leftCorrect}/${leftTrials} (${leftAccuracy}%)</td>
                    <td class="text-center">${result.centerCorrect}/${centerTrials} (${centerAccuracy}%)</td>
                    <td class="text-center">${result.rightCorrect}/${rightTrials} (${rightAccuracy}%)</td>
                    <td>${formatNumber(result.testTimeMs || 0)}</td>
                    <td>${formatNumber(result.avgResponseTime || 0)}</td>
                    ${isAdmin ? `<td class="text-center"><img src="assets/images/trash_icon.svg" alt="Delete" style="width: 32px; height: 32px; cursor: pointer;" onclick="deleteTestSession('${escapeHtml(result.sessionId)}', '${escapeHtml(result.testName)}', '${formatDate(result.testDate)}')" title="Delete this test session"></td>` : ''}
                </tr>
                <tr>
                    <td colspan="${isAdmin ? '8' : '7'}" style="padding: 8px 12px; background-color: #f8f9fa;">
                        <div class="d-flex align-items-center gap-3">
                            <small class="text-muted">Trial Results:</small>
                            <div class="score-bar d-flex gap-1" style="flex-grow: 1;">
                                ${result.trials ? result.trials.map((trial, trialIndex) => {
                                    const bgColor = trial.correct ? '#28a745' : '#dc3545';
                                    const title = `Trial ${trialIndex + 1}: ${trial.userAnswer} (correct: ${trial.correctAnswer}) - ${trial.responseTime}ms`;
                                    const channelLetter = trial.correctAnswer.charAt(0).toUpperCase(); // L, C, or R
                                    return `<div class="score-indicator" style="width: 32px; height: 32px; background-color: ${bgColor}; border-radius: 3px; position: relative;" title="${title}">
                                        <span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 16px; font-weight: bold;">
                                            ${channelLetter}
                                        </span>
                                    </div>`;
                                }).join('') : ''}
                            </div>
                            <small class="text-muted">${correctTrials}/${totalTrials} (${scorePercentage}%)</small>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
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
