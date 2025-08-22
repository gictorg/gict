<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

$user = getCurrentUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_course':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $duration = trim($_POST['duration']);
                $fee = floatval($_POST['fee']);
                $capacity = intval($_POST['capacity']);
                $status = $_POST['status'];
                
                // Set default capacity if not provided
                if ($capacity <= 0) {
                    $capacity = 50;
                }
                
                if (empty($name) || empty($description) || empty($duration) || $fee <= 0) {
                    throw new Exception("Please fill all required fields correctly.");
                }
                
                $sql = "INSERT INTO courses (name, description, duration, fee, capacity, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $result = insertData($sql, [$name, $description, $duration, $fee, $capacity, $status]);
                
                if ($result === false) {
                    throw new Exception("Failed to add course. Please try again.");
                }
                
                $success_message = "Course added successfully!";
                break;
                
            case 'update_course':
                $course_id = intval($_POST['course_id']);
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $duration = trim($_POST['duration']);
                $fee = floatval($_POST['fee']);
                $capacity = intval($_POST['capacity']);
                $status = $_POST['status'];
                
                // Set default capacity if not provided
                if ($capacity <= 0) {
                    $capacity = 50;
                }
                
                if (empty($name) || empty($description) || empty($duration) || $fee <= 0) {
                    throw new Exception("Please fill all required fields correctly.");
                }
                
                $sql = "UPDATE courses SET name = ?, description = ?, duration = ?, fee = ?, capacity = ?, status = ?, updated_at = NOW() 
                        WHERE id = ?";
                $result = updateData($sql, [$name, $description, $duration, $fee, $capacity, $status, $course_id]);
                
                if ($result === false) {
                    throw new Exception("Failed to update course. Please try again.");
                }
                
                $success_message = "Course updated successfully!";
                break;
                
            case 'delete_course':
                $course_id = intval($_POST['course_id']);
                
                // Check if course has enrollments
                $check_sql = "SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?";
                $enrollment_result = getRow($check_sql, [$course_id]);
                $enrollment_count = $enrollment_result['count'] ?? 0;
                
                if ($enrollment_count > 0) {
                    throw new Exception("Cannot delete course. It has {$enrollment_count} active enrollments.");
                }
                
                $sql = "DELETE FROM courses WHERE id = ?";
                $result = deleteData($sql, [$course_id]);
                
                if ($result === false) {
                    throw new Exception("Failed to delete course. Please try again.");
                }
                
                $success_message = "Course deleted successfully!";
                break;
                
            case 'toggle_status':
                $course_id = intval($_POST['course_id']);
                $new_status = $_POST['new_status'];
                
                $sql = "UPDATE courses SET status = ?, updated_at = NOW() WHERE id = ?";
                $result = updateData($sql, [$new_status, $course_id]);
                
                if ($result === false) {
                    throw new Exception("Failed to update course status. Please try again.");
                }
                
                $success_message = "Course status updated successfully!";
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get all courses with enrollment statistics
$courses = [];
try {
    $sql = "SELECT 
                c.*,
                COUNT(e.id) as total_enrollments,
                COUNT(CASE WHEN e.status = 'enrolled' THEN 1 END) as active_enrollments,
                COUNT(CASE WHEN e.status = 'completed' THEN 1 END) as completed_enrollments
            FROM courses c 
            LEFT JOIN enrollments e ON c.id = e.course_id 
            GROUP BY c.id 
            ORDER BY c.created_at DESC";
    $courses = getRows($sql);
} catch (Exception $e) {
    $error_message = "Error loading courses: " . $e->getMessage();
}

// Get course for editing
$edit_course = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $course_id = intval($_GET['edit']);
    try {
        $sql = "SELECT * FROM courses WHERE id = ?";
        $edit_course = getRow($sql, [$course_id]);
        
        if (!$edit_course) {
            $error_message = "Course not found.";
        }
    } catch (Exception $e) {
        $error_message = "Error loading course: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - GICT Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 260px;
            background: #1f2d3d;
            color: #e9eef3;
            padding: 18px 14px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        .admin-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }
        .admin-brand img {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            object-fit: cover;
        }
        .brand-title {
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .profile-card-mini {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 14px;
            display: grid;
            grid-template-columns: 56px 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .profile-card-mini img {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.25);
        }
        .profile-card-mini .name {
            font-weight: 600;
        }
        .profile-card-mini .role {
            color: #cbd5e1;
            font-size: 12px;
            margin-top: 2px;
        }
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 8px 0 0 0;
        }
        .sidebar-nav li {
            margin: 4px 0;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            text-decoration: none;
            color: #e9eef3;
            border-radius: 8px;
            transition: background 0.2s ease;
        }
        .sidebar-nav a i {
            width: 18px;
            text-align: center;
        }
        .sidebar-nav a.active,
        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.09);
        }
        .admin-content {
            flex: 1;
            margin-left: 260px;
            margin-top: 60px;
            padding: 20px;
        }
        
        /* Topbar */
        .admin-topbar {
            background: #0f6fb1;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 18px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            position: fixed;
            top: 0;
            right: 0;
            left: 260px;
            height: 60px;
            z-index: 999;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .topbar-left .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
        }
        .breadcrumbs {
            font-size: 13px;
            opacity: 0.9;
        }
        .topbar-home-link {
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
        }
        .topbar-home-link i {
            margin-right: 6px;
        }
        .topbar-home-link:hover {
            text-decoration: underline;
        }
        .topbar-right {
            display: flex;
            align-items: center;
        }
        .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.1);
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .user-chip img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .page-header p {
            color: #666;
            font-size: 16px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            text-align: center;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        .stat-card h3 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 16px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .stat-card .value {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 8px;
        }
        .stat-card .label {
            color: #6c757d;
            font-size: 14px;
        }

        .courses-table {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
        }

        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
            text-transform: uppercase;
        }
        td {
            color: #333;
            font-size: 14px;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .course-name {
            font-weight: 600;
            color: #667eea;
        }
        .course-description {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .course-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            min-width: 80px;
        }
        .course-status.active {
            background: #d4edda;
            color: #155724;
        }
        .course-status.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .course-status.draft {
            background: #fff3cd;
            color: #856404;
        }
        .enrollment-count {
            text-align: center;
            font-weight: 600;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
        }
        .btn-edit {
            background: #17a2b8;
            color: white;
        }
        .btn-edit:hover {
            background: #138496;
        }
        .btn-toggle {
            background: #ffc107;
            color: #212529;
        }
        .btn-toggle:hover {
            background: #e0a800;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background: #c82333;
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
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        .close:hover {
            opacity: 0.7;
        }
        .modal-body {
            padding: 30px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 600;
            font-size: 14px;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #495057;
        }
        .empty-state p {
            margin: 0;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .admin-sidebar.open {
                transform: translateX(0);
            }
            .admin-topbar {
                left: 0;
            }
            .topbar-left .menu-toggle {
                display: block;
            }
            .admin-content {
                margin-left: 0;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .add-course-btn {
                text-align: center;
                justify-content: center;
            }
            .action-buttons {
                flex-direction: column;
            }
            .btn-small {
                text-align: center;
            }
        }
            .form-row {
                grid-template-columns: 1fr;
            }
            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .add-course-btn {
                text-align: center;
                justify-content: center;
            }
            .action-buttons {
                flex-direction: column;
            }
            .btn-small {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img src="../assets/images/logo.png" alt="logo" />
                <div class="brand-title">GICT CONTROL</div>
            </div>
            <div class="profile-card-mini">
                <img src="../assets/images/brijendra.jpeg" alt="Profile" />
                <div>
                    <div class="name"><?php echo htmlspecialchars(strtoupper($user['full_name'])); ?></div>
                    <div class="role"><?php echo htmlspecialchars(ucfirst($user['type'])); ?></div>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-user-tie"></i> Staff</a></li>
                <li><a class="active" href="courses.php"><i class="fas fa-graduation-cap"></i> Courses</a></li>
                <li><a href="#"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="breadcrumbs">
                    <a href="../index.php" class="topbar-home-link"><i class="fas fa-home"></i> Home</a>
                    <span style="opacity:.7; margin: 0 6px;">/</span>
                    <a href="../dashboard.php">Dashboard</a>
                    <span style="opacity:.7; margin: 0 6px;">/</span>
                    <span>Courses</span>
                </div>
            </div>
            <div class="topbar-right">
                <div class="user-chip">
                    <img src="../assets/images/brijendra.jpeg" alt="" /> 
                    <?php echo htmlspecialchars($user['full_name']); ?>
                </div>
            </div>
        </header>

        <main class="admin-content">
            <div class="page-header">
                <h1><i class="fas fa-graduation-cap"></i> Course Management</h1>
                <button class="add-course-btn" onclick="openAddCourseModal()">
                    <i class="fas fa-plus"></i> Add New Course
                </button>
            </div>

            <div class="section-header">
                <h2><i class="fas fa-list"></i> All Courses</h2>
                <p>Manage all courses, add new courses, and track enrollment statistics</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Courses</h3>
                    <div class="value"><?php echo count($courses); ?></div>
                    <div class="label">Available Programs</div>
                </div>
                <div class="stat-card">
                    <h3>Active Courses</h3>
                    <div class="value">
                        <?php echo count(array_filter($courses, function($c) { return $c['status'] === 'active'; })); ?>
                    </div>
                    <div class="label">Currently Running</div>
                </div>
                <div class="stat-card">
                    <h3>Total Enrollments</h3>
                    <div class="value">
                        <?php echo array_sum(array_column($courses, 'total_enrollments')); ?>
                    </div>
                    <div class="label">Student Registrations</div>
                </div>
                <div class="stat-card">
                    <h3>Active Students</h3>
                    <div class="value">
                        <?php echo array_sum(array_column($courses, 'active_enrollments')); ?>
                    </div>
                    <div class="label">Currently Enrolled</div>
                </div>
            </div>



            <!-- Courses Table -->
            <div class="courses-table">
                
                <?php if (empty($courses)): ?>
                    <div class="empty-state">
                        <i class="fas fa-graduation-cap"></i>
                        <h3>No Courses Found</h3>
                        <p>Start by adding your first course to the system.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Description</th>
                                    <th>Duration</th>
                                    <th>Fee (₹)</th>
                                    <th>Capacity</th>
                                    <th>Status</th>
                                    <th>Enrollments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td class="course-name"><?php echo htmlspecialchars($course['name']); ?></td>
                                        <td class="course-description" title="<?php echo htmlspecialchars($course['description']); ?>">
                                            <?php echo htmlspecialchars($course['description']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($course['duration']); ?></td>
                                        <td>₹<?php echo number_format($course['fee']); ?></td>
                                        <td><?php echo $course['capacity'] ?? 100; ?></td>
                                        <td>
                                            <span class="course-status <?php echo $course['status']; ?>">
                                                <?php echo ucfirst($course['status']); ?>
                                            </span>
                                        </td>
                                        <td class="enrollment-count">
                                            <div><?php echo $course['total_enrollments']; ?> total</div>
                                            <small><?php echo $course['active_enrollments']; ?> active</small>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?edit=<?php echo $course['id']; ?>" class="btn-small btn-edit">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <button class="btn-small btn-toggle" onclick="toggleCourseStatus(<?php echo $course['id']; ?>, '<?php echo $course['status'] === 'active' ? 'inactive' : 'active'; ?>')">
                                                    <i class="fas fa-toggle-on"></i> 
                                                    <?php echo $course['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                                <button class="btn-small btn-delete" onclick="deleteCourse(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['name']); ?>')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add/Edit Course Modal -->
    <div id="courseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-<?php echo $edit_course ? 'edit' : 'plus'; ?>"></i>
                    <?php echo $edit_course ? 'Edit Course' : 'Add New Course'; ?>
                </h2>
                <span class="close" onclick="closeCourseModal()">&times;</span>
            </div>
            
            <form method="POST" action="courses.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="<?php echo $edit_course ? 'update_course' : 'add_course'; ?>">
                    <?php if ($edit_course): ?>
                        <input type="hidden" name="course_id" value="<?php echo $edit_course['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Course Name *</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo $edit_course ? htmlspecialchars($edit_course['name']) : ''; ?>"
                                   placeholder="e.g., Web Development, Data Science">
                        </div>
                        <div class="form-group">
                            <label for="duration">Duration *</label>
                            <input type="text" id="duration" name="duration" required 
                                   value="<?php echo $edit_course ? htmlspecialchars($edit_course['duration']) : ''; ?>"
                                   placeholder="e.g., 6 months, 1 year">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fee">Course Fee (₹) *</label>
                            <input type="number" id="fee" name="fee" required min="0" step="0.01"
                                   value="<?php echo $edit_course ? $edit_course['fee'] : ''; ?>"
                                   placeholder="e.g., 15000">
                        </div>
                        <div class="form-group">
                            <label for="capacity">Maximum Capacity</label>
                            <input type="number" id="capacity" name="capacity" min="1" max="1000"
                                   value="<?php echo $edit_course ? ($edit_course['capacity'] ?? 50) : '50'; ?>"
                                   placeholder="e.g., 50">
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="description">Course Description *</label>
                        <textarea id="description" name="description" required rows="4"
                                  placeholder="Describe the course content, objectives, and what students will learn..."><?php echo $edit_course ? htmlspecialchars($edit_course['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" <?php echo ($edit_course && $edit_course['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_course && $edit_course['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="draft" <?php echo ($edit_course && $edit_course['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCourseModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-<?php echo $edit_course ? 'save' : 'plus'; ?>"></i>
                        <?php echo $edit_course ? 'Update Course' : 'Add Course'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mobile Menu JavaScript -->
    <!-- <script src="../assets/js/mobile-menu.js"></script> -->
    
    <script>
        // Open modal if editing
        <?php if ($edit_course): ?>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Edit mode detected, opening modal...');
            openCourseModal();
        });
        <?php endif; ?>
        
        // Debug: Log edit course data
        <?php if ($edit_course): ?>
        console.log('Edit course data:', <?php echo json_encode($edit_course); ?>);
        <?php endif; ?>
        
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('open');
        }
        
        function openAddCourseModal() {
            console.log('Opening add course modal...');
            document.getElementById('courseModal').style.display = 'block';
            // Reset form if not editing
            if (!<?php echo $edit_course ? 'true' : 'false'; ?>) {
                document.querySelector('#courseModal form').reset();
            }
        }
        
        function openCourseModal() {
            console.log('Opening course modal...');
            document.getElementById('courseModal').style.display = 'block';
        }
        
        function closeCourseModal() {
            document.getElementById('courseModal').style.display = 'none';
            // Redirect to clear edit mode
            if (window.location.search.includes('edit=')) {
                window.location.href = 'courses.php';
            }
        }
        
        function toggleCourseStatus(courseId, newStatus) {
            const action = newStatus === 'active' ? 'activate' : 'deactivate';
            if (confirm(`Are you sure you want to ${action} this course?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'courses.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'toggle_status';
                
                const courseIdInput = document.createElement('input');
                courseIdInput.type = 'hidden';
                courseIdInput.name = 'course_id';
                courseIdInput.value = courseId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'new_status';
                statusInput.value = newStatus;
                
                form.appendChild(actionInput);
                form.appendChild(courseIdInput);
                form.appendChild(statusInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteCourse(courseId, courseName) {
            if (confirm(`Are you sure you want to delete course "${courseName}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'courses.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_course';
                
                const courseIdInput = document.createElement('input');
                courseIdInput.type = 'hidden';
                courseIdInput.name = 'course_id';
                courseIdInput.value = courseId;
                
                form.appendChild(actionInput);
                form.appendChild(courseIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('courseModal');
            if (event.target === modal) {
                closeCourseModal();
            }
        }
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.admin-sidebar');
            const topbarMenuToggle = document.querySelector('.topbar-left .menu-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !topbarMenuToggle.contains(event.target) &&
                sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });
    </script>
</body>
</html>
