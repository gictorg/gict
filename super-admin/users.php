<?php
require_once '../includes/session_manager.php';

// Check if user is super admin
if (!isSuperAdmin()) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $user_id = $_POST['user_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        if ($user_id && in_array($status, ['active', 'inactive'])) {
            $result = updateData("UPDATE users SET status = ? WHERE id = ?", [$status, $user_id]);
            if ($result) {
                $message = 'User status updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to update user status.';
                $message_type = 'error';
            }
        }
    } elseif ($action === 'delete_user') {
        $user_id = $_POST['user_id'] ?? 0;
        
        if ($user_id) {
            // Check if user has any enrollments or payments
            $enrollments_count = getRow("SELECT COUNT(*) as count FROM student_enrollments WHERE user_id = ?", [$user_id])['count'];
            $payments_count = getRow("SELECT COUNT(*) as count FROM payments WHERE user_id = ?", [$user_id])['count'];
            
            if ($enrollments_count > 0 || $payments_count > 0) {
                $message = 'Cannot delete user with existing enrollments or payments.';
                $message_type = 'error';
            } else {
                $result = deleteData("DELETE FROM users WHERE id = ?", [$user_id]);
                if ($result) {
                    $message = 'User deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to delete user.';
                    $message_type = 'error';
                }
            }
        }
    }
}

// Get filter parameters
$institute_filter = $_GET['institute'] ?? '';
$user_type_filter = $_GET['user_type'] ?? '';
$status_filter = $_GET['status'] ?? 'active';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($institute_filter) {
    $where_conditions[] = "u.institute_id = ?";
    $params[] = $institute_filter;
}

if ($user_type_filter) {
    $where_conditions[] = "u.user_type = ?";
    $params[] = $user_type_filter;
}

if ($status_filter) {
    $where_conditions[] = "u.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get all users with institute information
$users = getRows("
    SELECT 
        u.*,
        i.name as institute_name,
        i.slug as institute_slug
    FROM users u
    LEFT JOIN institutes i ON u.institute_id = i.id
    $where_clause
    ORDER BY u.created_at DESC
", $params);

// Get institutes for filter
$institutes = getRows("SELECT id, name FROM institutes ORDER BY name");

// Get user type counts
$user_stats = getRow("
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN user_type = 'admin' THEN 1 END) as admins,
        COUNT(CASE WHEN user_type = 'student' THEN 1 END) as students,
        COUNT(CASE WHEN user_type = 'faculty' THEN 1 END) as faculty,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive
    FROM users
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Super Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .admin-sidebar {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
        }
        
        .admin-topbar {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #1e3a8a;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1e40af;
        }
        
        .btn-success {
            background: #059669;
            color: white;
        }
        
        .btn-success:hover {
            background: #047857;
        }
        
        .btn-warning {
            background: #d97706;
            color: white;
        }
        
        .btn-warning:hover {
            background: #b45309;
        }
        
        .btn-danger {
            background: #dc2626;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b91c1c;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }
        
        .users-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            color: #374151;
        }
        
        .table-body {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .user-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr 120px;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            align-items: center;
        }
        
        .user-row:hover {
            background: #f9fafb;
        }
        
        .user-row:last-child {
            border-bottom: none;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-details h4 {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: #111827;
        }
        
        .user-details p {
            margin: 0;
            font-size: 0.8rem;
            color: #6b7280;
        }
        
        .user-type {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .type-admin {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .type-student {
            background: #dcfce7;
            color: #166534;
        }
        
        .type-faculty {
            background: #fef3c7;
            color: #92400e;
        }
        
        .type-super-admin {
            background: #f3e8ff;
            color: #7c3aed;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .user-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            position: relative;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }
        
        .modal-close:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<body class="admin-dashboard-body">
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img src="../assets/images/logo.png" alt="logo" />
                <div class="brand-title">SUPER ADMIN</div>
            </div>
            
            <div class="profile-card-mini">
                <img src="../assets/images/default-avatar.png" alt="Profile" />
                <div>
                    <div class="name"><?php echo htmlspecialchars(strtoupper($_SESSION['full_name'])); ?></div>
                    <div class="role">Super Administrator</div>
                </div>
            </div>
            
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="institutes.php"><i class="fas fa-building"></i> Institutes</a></li>
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> All Users</a></li>
                <li><a href="courses.php"><i class="fas fa-book"></i> All Courses</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="breadcrumbs">
                    <span>Manage Users</span>
                </div>
            </div>
            <div class="topbar-right">
                <div class="user-chip">
                    <img src="../assets/images/default-avatar.png" alt="Profile" />
                    <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-content">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $user_stats['total']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $user_stats['admins']; ?></div>
                    <div class="stat-label">Admins</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $user_stats['students']; ?></div>
                    <div class="stat-label">Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $user_stats['faculty']; ?></div>
                    <div class="stat-label">Faculty</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $user_stats['active']; ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $user_stats['inactive']; ?></div>
                    <div class="stat-label">Inactive</div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <form method="GET" class="filters-grid">
                    <div class="form-group">
                        <label for="search">Search Users</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Name, username, or email">
                    </div>
                    
                    <div class="form-group">
                        <label for="institute">Institute</label>
                        <select id="institute" name="institute" class="form-control">
                            <option value="">All Institutes</option>
                            <?php foreach ($institutes as $institute): ?>
                                <option value="<?php echo $institute['id']; ?>" 
                                        <?php echo $institute_filter == $institute['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($institute['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_type">User Type</label>
                        <select id="user_type" name="user_type" class="form-control">
                            <option value="">All Types</option>
                            <option value="admin" <?php echo $user_type_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="student" <?php echo $user_type_filter === 'student' ? 'selected' : ''; ?>>Student</option>
                            <option value="faculty" <?php echo $user_type_filter === 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="users.php" class="btn btn-warning">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Users Table -->
            <div class="users-table">
                <div class="table-header">
                    <div class="user-row">
                        <div>User</div>
                        <div>Institute</div>
                        <div>Type</div>
                        <div>Contact</div>
                        <div>Status</div>
                        <div>Actions</div>
                    </div>
                </div>
                <div class="table-body">
                    <?php if (empty($users)): ?>
                        <div style="padding: 2rem; text-align: center; color: #6b7280;">
                            <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>No users found matching your criteria.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <div class="user-row">
                                <div class="user-info">
                                    <img src="<?php echo $user['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" 
                                         alt="Avatar" class="user-avatar" 
                                         onerror="this.src='../assets/images/default-avatar.png'">
                                    <div class="user-details">
                                        <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                        <p>@<?php echo htmlspecialchars($user['username']); ?></p>
                                    </div>
                                </div>
                                
                                <div>
                                    <?php if ($user['institute_name']): ?>
                                        <strong><?php echo htmlspecialchars($user['institute_name']); ?></strong>
                                        <br>
                                        <small style="color: #6b7280;"><?php echo htmlspecialchars($user['institute_slug']); ?></small>
                                    <?php else: ?>
                                        <span style="color: #6b7280;">-</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <span class="user-type type-<?php echo $user['user_type']; ?>">
                                        <?php echo ucfirst($user['user_type']); ?>
                                    </span>
                                </div>
                                
                                <div>
                                    <div><?php echo htmlspecialchars($user['email']); ?></div>
                                    <?php if ($user['phone']): ?>
                                        <small style="color: #6b7280;"><?php echo htmlspecialchars($user['phone']); ?></small>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="user-actions">
                                    <button class="btn btn-success btn-sm" 
                                            onclick="changeStatus(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>')">
                                        <i class="fas fa-toggle-on"></i>
                                    </button>
                                    <?php if ($user['user_type'] !== 'super_admin'): ?>
                                        <button class="btn btn-danger btn-sm" 
                                                onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Status Change Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Change User Status</div>
                <button class="modal-close" onclick="closeModal('statusModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="user_id" id="statusUserId">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" onclick="closeModal('statusModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Delete User</div>
                <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="deleteUserId">
                <div class="modal-body">
                    <p>Are you sure you want to delete the user "<span id="deleteUserName"></span>"?</p>
                    <p><strong>This action cannot be undone!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('mobile-open');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function changeStatus(userId, currentStatus) {
            document.getElementById('statusUserId').value = userId;
            document.getElementById('status').value = currentStatus === 'active' ? 'inactive' : 'active';
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function deleteUser(userId, userName) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
