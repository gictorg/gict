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
    
    if ($action === 'add_institute') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $website = trim($_POST['website'] ?? '');
        
        if (empty($name) || empty($slug)) {
            $message = 'Institute name and slug are required.';
            $message_type = 'error';
        } else {
            // Check if slug already exists
            $existing = getRow("SELECT id FROM institutes WHERE slug = ?", [$slug]);
            if ($existing) {
                $message = 'Institute slug already exists. Please choose a different one.';
                $message_type = 'error';
            } else {
                $result = insertData("
                    INSERT INTO institutes (name, slug, address, phone, email, website, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')
                ", [$name, $slug, $address, $phone, $email, $website]);
                
                if ($result) {
                    $message = 'Institute added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to add institute. Please try again.';
                    $message_type = 'error';
                }
            }
        }
    } elseif ($action === 'update_status') {
        $institute_id = $_POST['institute_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        if ($institute_id && in_array($status, ['active', 'inactive', 'pending'])) {
            $result = updateData("UPDATE institutes SET status = ? WHERE id = ?", [$status, $institute_id]);
            if ($result) {
                $message = 'Institute status updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to update institute status.';
                $message_type = 'error';
            }
        }
    } elseif ($action === 'delete_institute') {
        $institute_id = $_POST['institute_id'] ?? 0;
        
        if ($institute_id) {
            // Check if institute has any users or courses
            $users_count = getRow("SELECT COUNT(*) as count FROM users WHERE institute_id = ?", [$institute_id])['count'];
            $courses_count = getRow("SELECT COUNT(*) as count FROM courses WHERE institute_id = ?", [$institute_id])['count'];
            
            if ($users_count > 0 || $courses_count > 0) {
                $message = 'Cannot delete institute with existing users or courses.';
                $message_type = 'error';
            } else {
                $result = deleteData("DELETE FROM institutes WHERE id = ?", [$institute_id]);
                if ($result) {
                    $message = 'Institute deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to delete institute.';
                    $message_type = 'error';
                }
            }
        }
    }
}

// Get all institutes with statistics
$institutes = getRows("
    SELECT 
        i.*,
        COUNT(DISTINCT u.id) as total_users,
        COUNT(DISTINCT CASE WHEN u.user_type = 'student' THEN u.id END) as students,
        COUNT(DISTINCT CASE WHEN u.user_type = 'admin' THEN u.id END) as admins,
        COUNT(DISTINCT c.id) as courses
    FROM institutes i
    LEFT JOIN users u ON i.id = u.institute_id AND u.status = 'active'
    LEFT JOIN courses c ON i.id = c.institute_id AND c.status = 'active'
    GROUP BY i.id, i.name, i.slug, i.address, i.phone, i.email, i.website, i.logo_url, i.banner_url, i.status, i.created_at, i.updated_at
    ORDER BY i.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Institutes - Super Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .admin-sidebar {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
        }
        
        .admin-topbar {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
        }
        
        .institute-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .institute-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .institute-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .institute-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            padding: 1.5rem;
        }
        
        .institute-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .institute-slug {
            font-size: 0.9rem;
            opacity: 0.9;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            display: inline-block;
        }
        
        .institute-body {
            padding: 1.5rem;
        }
        
        .institute-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .institute-stat {
            text-align: center;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .institute-stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e3a8a;
        }
        
        .institute-stat-label {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .institute-details {
            margin-bottom: 1rem;
        }
        
        .institute-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .institute-detail i {
            width: 16px;
            color: #6b7280;
        }
        
        .institute-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.5rem 1rem;
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
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
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
            max-width: 500px;
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
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
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
                <li><a href="institutes.php" class="active"><i class="fas fa-building"></i> Institutes</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> All Users</a></li>
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
                    <span>Manage Institutes</span>
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
            
            <!-- Header -->
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-building"></i> Franchise Institutes</span>
                    <button class="btn btn-primary btn-sm" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add Institute
                    </button>
                </div>
                <div class="panel-body">
                    <p>Manage all GICT Institute franchises. Each institute has its own admin, students, and courses.</p>
                </div>
            </div>
            
            <!-- Institutes Grid -->
            <div class="institute-grid">
                <?php foreach ($institutes as $institute): ?>
                    <div class="institute-card">
                        <div class="institute-header">
                            <div class="institute-name"><?php echo htmlspecialchars($institute['name']); ?></div>
                            <div class="institute-slug"><?php echo htmlspecialchars($institute['slug']); ?></div>
                        </div>
                        <div class="institute-body">
                            <div class="institute-stats">
                                <div class="institute-stat">
                                    <div class="institute-stat-number"><?php echo $institute['total_users']; ?></div>
                                    <div class="institute-stat-label">Total Users</div>
                                </div>
                                <div class="institute-stat">
                                    <div class="institute-stat-number"><?php echo $institute['students']; ?></div>
                                    <div class="institute-stat-label">Students</div>
                                </div>
                                <div class="institute-stat">
                                    <div class="institute-stat-number"><?php echo $institute['admins']; ?></div>
                                    <div class="institute-stat-label">Admins</div>
                                </div>
                                <div class="institute-stat">
                                    <div class="institute-stat-number"><?php echo $institute['courses']; ?></div>
                                    <div class="institute-stat-label">Courses</div>
                                </div>
                            </div>
                            
                            <div class="institute-details">
                                <?php if ($institute['address']): ?>
                                    <div class="institute-detail">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($institute['address']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($institute['phone']): ?>
                                    <div class="institute-detail">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($institute['phone']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($institute['email']): ?>
                                    <div class="institute-detail">
                                        <i class="fas fa-envelope"></i>
                                        <span><?php echo htmlspecialchars($institute['email']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="institute-detail">
                                    <i class="fas fa-calendar"></i>
                                    <span>Created: <?php echo date('M d, Y', strtotime($institute['created_at'])); ?></span>
                                </div>
                                
                                <div class="institute-detail">
                                    <i class="fas fa-circle"></i>
                                    <span class="status-badge status-<?php echo $institute['status']; ?>">
                                        <?php echo ucfirst($institute['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="institute-actions">
                                <a href="institute-details.php?id=<?php echo $institute['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <button class="btn btn-warning btn-sm" onclick="editInstitute(<?php echo $institute['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-success btn-sm" onclick="changeStatus(<?php echo $institute['id']; ?>, '<?php echo $institute['status']; ?>')">
                                    <i class="fas fa-toggle-on"></i> Status
                                </button>
                                <?php if ($institute['total_users'] == 0 && $institute['courses'] == 0): ?>
                                    <button class="btn btn-danger btn-sm" onclick="deleteInstitute(<?php echo $institute['id']; ?>, '<?php echo htmlspecialchars($institute['name']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Add Institute Modal -->
    <div id="addInstituteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Add New Institute</div>
                <button class="modal-close" onclick="closeModal('addInstituteModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_institute">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Institute Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">Institute Slug *</label>
                        <input type="text" id="slug" name="slug" class="form-control" required>
                        <small>URL-friendly identifier (e.g., gict-main, gict-north)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="website">Website</label>
                        <input type="url" id="website" name="website" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" onclick="closeModal('addInstituteModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Institute</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Change Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Change Institute Status</div>
                <button class="modal-close" onclick="closeModal('statusModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="institute_id" id="statusInstituteId">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending</option>
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
                <div class="modal-title">Delete Institute</div>
                <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete_institute">
                <input type="hidden" name="institute_id" id="deleteInstituteId">
                <div class="modal-body">
                    <p>Are you sure you want to delete the institute "<span id="deleteInstituteName"></span>"?</p>
                    <p><strong>This action cannot be undone!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Institute</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('mobile-open');
        }
        
        function openAddModal() {
            document.getElementById('addInstituteModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function changeStatus(instituteId, currentStatus) {
            document.getElementById('statusInstituteId').value = instituteId;
            document.getElementById('status').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function deleteInstitute(instituteId, instituteName) {
            document.getElementById('deleteInstituteId').value = instituteId;
            document.getElementById('deleteInstituteName').textContent = instituteName;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function editInstitute(instituteId) {
            // Redirect to edit page (to be created)
            window.location.href = `edit-institute.php?id=${instituteId}`;
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
        
        // Auto-generate slug from name
        document.getElementById('name').addEventListener('input', function() {
            const name = this.value;
            const slug = name.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            document.getElementById('slug').value = slug;
        });
    </script>
</body>
</html>
