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
        $course_id = $_POST['course_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        if ($course_id && in_array($status, ['active', 'inactive'])) {
            $result = updateData("UPDATE courses SET status = ? WHERE id = ?", [$status, $course_id]);
            if ($result) {
                $message = 'Course status updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to update course status.';
                $message_type = 'error';
            }
        }
    } elseif ($action === 'delete_course') {
        $course_id = $_POST['course_id'] ?? 0;
        
        if ($course_id) {
            // Check if course has any sub-courses or enrollments
            $sub_courses_count = getRow("SELECT COUNT(*) as count FROM sub_courses WHERE course_id = ?", [$course_id])['count'];
            
            if ($sub_courses_count > 0) {
                $message = 'Cannot delete course with existing sub-courses.';
                $message_type = 'error';
            } else {
                $result = deleteData("DELETE FROM courses WHERE id = ?", [$course_id]);
                if ($result) {
                    $message = 'Course deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to delete course.';
                    $message_type = 'error';
                }
            }
        }
    }
}

// Get filter parameters
$institute_filter = $_GET['institute'] ?? '';
$status_filter = $_GET['status'] ?? 'active';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($institute_filter) {
    $where_conditions[] = "c.institute_id = ?";
    $params[] = $institute_filter;
}

if ($status_filter) {
    $where_conditions[] = "c.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(c.name LIKE ? OR c.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get all courses with institute information and sub-course counts
$courses = getRows("
    SELECT 
        c.*,
        i.name as institute_name,
        i.slug as institute_slug,
        COUNT(sc.id) as sub_courses_count,
        COUNT(DISTINCT se.user_id) as enrolled_students
    FROM courses c
    LEFT JOIN institutes i ON c.institute_id = i.id
    LEFT JOIN sub_courses sc ON c.id = sc.course_id AND sc.status = 'active'
    LEFT JOIN student_enrollments se ON sc.id = se.sub_course_id
    $where_clause
    GROUP BY c.id, c.name, c.description, c.duration, c.fee, c.institute_id, c.status, c.created_at, c.updated_at, i.name, i.slug
    ORDER BY c.created_at DESC
", $params);

// Get institutes for filter
$institutes = getRows("SELECT id, name FROM institutes ORDER BY name");

// Get course statistics
$course_stats = getRow("
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive,
        COUNT(DISTINCT institute_id) as institutes_with_courses
    FROM courses
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Super Admin</title>
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
        
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .course-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .course-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .course-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            padding: 1.5rem;
        }
        
        .course-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .course-institute {
            font-size: 0.9rem;
            opacity: 0.9;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            display: inline-block;
        }
        
        .course-body {
            padding: 1.5rem;
        }
        
        .course-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .course-stat {
            text-align: center;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .course-stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e3a8a;
        }
        
        .course-stat-label {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .course-details {
            margin-bottom: 1rem;
        }
        
        .course-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .course-detail i {
            width: 16px;
            color: #6b7280;
        }
        
        .course-description {
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .course-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
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
                <li><a href="users.php"><i class="fas fa-users"></i> All Users</a></li>
                <li><a href="courses.php" class="active"><i class="fas fa-book"></i> All Courses</a></li>
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
                    <span>Manage Courses</span>
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
                    <div class="stat-number"><?php echo $course_stats['total']; ?></div>
                    <div class="stat-label">Total Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $course_stats['active']; ?></div>
                    <div class="stat-label">Active Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $course_stats['inactive']; ?></div>
                    <div class="stat-label">Inactive Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $course_stats['institutes_with_courses']; ?></div>
                    <div class="stat-label">Institutes with Courses</div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <form method="GET" class="filters-grid">
                    <div class="form-group">
                        <label for="search">Search Courses</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Course name or description">
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
                        <a href="courses.php" class="btn btn-warning">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Courses Grid -->
            <div class="courses-grid">
                <?php if (empty($courses)): ?>
                    <div style="grid-column: 1 / -1; padding: 2rem; text-align: center; color: #6b7280;">
                        <i class="fas fa-book" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No courses found matching your criteria.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <div class="course-header">
                                <div class="course-name"><?php echo htmlspecialchars($course['name']); ?></div>
                                <div class="course-institute"><?php echo htmlspecialchars($course['institute_name']); ?></div>
                            </div>
                            <div class="course-body">
                                <div class="course-stats">
                                    <div class="course-stat">
                                        <div class="course-stat-number"><?php echo $course['sub_courses_count']; ?></div>
                                        <div class="course-stat-label">Sub Courses</div>
                                    </div>
                                    <div class="course-stat">
                                        <div class="course-stat-number"><?php echo $course['enrolled_students']; ?></div>
                                        <div class="course-stat-label">Enrolled Students</div>
                                    </div>
                                </div>
                                
                                <div class="course-description">
                                    <?php echo htmlspecialchars($course['description'] ?: 'No description available.'); ?>
                                </div>
                                
                                <div class="course-details">
                                    <div class="course-detail">
                                        <i class="fas fa-clock"></i>
                                        <span>Duration: <?php echo htmlspecialchars($course['duration']); ?></span>
                                    </div>
                                    
                                    <div class="course-detail">
                                        <i class="fas fa-rupee-sign"></i>
                                        <span>Fee: ₹<?php echo number_format($course['fee']); ?></span>
                                    </div>
                                    
                                    <div class="course-detail">
                                        <i class="fas fa-calendar"></i>
                                        <span>Created: <?php echo date('M d, Y', strtotime($course['created_at'])); ?></span>
                                    </div>
                                    
                                    <div class="course-detail">
                                        <i class="fas fa-circle"></i>
                                        <span class="status-badge status-<?php echo $course['status']; ?>">
                                            <?php echo ucfirst($course['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="course-actions">
                                    <a href="course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <button class="btn btn-success btn-sm" onclick="changeStatus(<?php echo $course['id']; ?>, '<?php echo $course['status']; ?>')">
                                        <i class="fas fa-toggle-on"></i> Status
                                    </button>
                                    <?php if ($course['sub_courses_count'] == 0): ?>
                                        <button class="btn btn-danger btn-sm" onclick="deleteCourse(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['name']); ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Status Change Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Change Course Status</div>
                <button class="modal-close" onclick="closeModal('statusModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="course_id" id="statusCourseId">
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
                <div class="modal-title">Delete Course</div>
                <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete_course">
                <input type="hidden" name="course_id" id="deleteCourseId">
                <div class="modal-body">
                    <p>Are you sure you want to delete the course "<span id="deleteCourseName"></span>"?</p>
                    <p><strong>This action cannot be undone!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Course</button>
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
        
        function changeStatus(courseId, currentStatus) {
            document.getElementById('statusCourseId').value = courseId;
            document.getElementById('status').value = currentStatus === 'active' ? 'inactive' : 'active';
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function deleteCourse(courseId, courseName) {
            document.getElementById('deleteCourseId').value = courseId;
            document.getElementById('deleteCourseName').textContent = courseName;
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
