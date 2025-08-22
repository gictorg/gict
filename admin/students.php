<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

$user = getCurrentUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_student':
                $student_id = $_POST['student_id'] ?? 0;
                if ($student_id) {
                    try {
                        $sql = "DELETE FROM users WHERE id = ? AND user_type = 'student'";
                        $result = deleteData($sql, [$student_id]);
                        if ($result) {
                            $success_message = "Student deleted successfully!";
                        } else {
                            $error_message = "Failed to delete student.";
                        }
                    } catch (Exception $e) {
                        $error_message = "Error: " . $e->getMessage();
                    }
                }
                break;
                
            case 'toggle_status':
                $student_id = $_POST['student_id'] ?? 0;
                $new_status = $_POST['new_status'] ?? '';
                if ($student_id) {
                    try {
                        $sql = "UPDATE users SET status = ? WHERE id = ? AND user_type = 'student'";
                        $result = updateData($sql, [$new_status, $student_id]);
                        if ($result) {
                            $success_message = "Student status updated successfully!";
                        } else {
                            $error_message = "Failed to update student status.";
                        }
                    } catch (Exception $e) {
                        $error_message = "Error: " . $e->getMessage();
                    }
                }
                break;
                
            case 'update_password':
                $student_id = $_POST['student_id'] ?? 0;
                $new_password = $_POST['new_password'] ?? '';
                if ($student_id && !empty($new_password)) {
                    try {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ? AND user_type = 'student'";
                        $result = updateData($sql, [$hashed_password, $student_id]);
                        if ($result) {
                            $success_message = "Student password updated successfully!";
                        } else {
                            $error_message = "Failed to update student password.";
                        }
                    } catch (Exception $e) {
                        $error_message = "Error: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Please provide a new password.";
                }
                break;
        }
    }
}

// Get all students with their enrollment information
$students = [];
try {
    $sql = "SELECT 
                u.id, u.username, u.email, u.full_name, u.phone, u.address, 
                u.date_of_birth, u.gender, u.joining_date, u.status, u.created_at, u.profile_image,
                COUNT(e.id) as total_enrollments,
                COUNT(CASE WHEN e.status = 'completed' THEN 1 END) as completed_courses,
                COUNT(CASE WHEN e.status = 'enrolled' THEN 1 END) as active_enrollments
            FROM users u 
            LEFT JOIN enrollments e ON u.id = e.student_id 
            WHERE u.user_type = 'student' 
            GROUP BY u.id 
            ORDER BY u.created_at DESC";
    $students = getRows($sql);
} catch (Exception $e) {
    $error_message = "Error loading students: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - GICT Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-content {
            padding: 20px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            padding: 25px 0;
            border-bottom: 2px solid #e9ecef;
        }
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .page-header h1 i {
            color: #667eea;
            font-size: 32px;
        }
        .add-student-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        .add-student-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        
        .section-header {
            margin-bottom: 25px;
            text-align: center;
        }
        .section-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .section-header h2 i {
            color: #667eea;
        }
        .section-header p {
            color: #6c757d;
            font-size: 16px;
            margin: 0;
        }
        
        /* Search Section Styling */
        .search-section {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 18px;
        }
        .search-box input {
            width: 100%;
            padding: 15px 50px 15px 45px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .clear-search {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .clear-search:hover {
            background: #c82333;
            transform: translateY(-50%) scale(1.1);
        }
        .search-filters {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .search-filters select {
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .search-filters select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }
        .search-filters select:hover {
            border-color: #667eea;
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
        }
        
        .btn-edit {
            background: #17a2b8;
            color: white;
        }
        
        .btn-edit:hover {
            background: #138496;
        }
        
        .btn-password {
            background: #6f42c1;
            color: white;
        }
        
        .btn-password:hover {
            background: #5a32a3;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .btn-toggle {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-toggle:hover {
            background: #e0a800;
        }
        .add-student-btn:hover {
            transform: translateY(-2px);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }
        .stat-card h3 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 16px;
            font-weight: 600;
        }
        .stat-card .value {
            font-size: 36px;
            font-weight: 800;
            color: #333;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .stat-card .label {
            color: #6c757d;
            font-size: 13px;
            font-weight: 500;
        }
        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .students-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 15px;
            }
            .student-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            .student-actions {
                justify-content: center;
            }
            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            .search-filters {
                flex-direction: column;
            }
            .search-filters select {
                width: 100%;
            }
        }
        .student-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .student-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #28a745, #20c997, #17a2b8);
        }
        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        .student-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }
        .student-avatar {
            flex-shrink: 0;
        }
        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #667eea;
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        .default-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            border: 3px solid #667eea;
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        .student-details {
            flex: 1;
        }
        .student-name {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 4px;
        }
        .student-username {
            font-size: 14px;
            color: #667eea;
            font-weight: 500;
        }
        .student-status {
            background: #f8f9fa;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .student-status.active { background: #d4edda; color: #155724; }
        .student-status.inactive { background: #f8d7da; color: #721c24; }
        .student-info {
            margin-bottom: 20px;
            font-size: 14px;
            background: #f8f9fa;
            padding: 18px;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        .student-info strong {
            color: #495057;
            min-width: 100px;
            display: inline-block;
            font-weight: 600;
        }
        .enrollment-stats {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        .enrollment-stats h4 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .enrollment-stats h4::before {
            content: '📊';
            font-size: 18px;
        }
        .stats-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .stats-row:last-child { margin-bottom: 0; }
        .stats-label { color: #666; font-size: 12px; }
        .stats-value { color: #333; font-weight: 500; font-size: 12px; }
        .student-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            min-width: 80px;
            justify-content: center;
        }
        .btn-edit { 
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); 
            color: white; 
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
        }
        .btn-password { 
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); 
            color: #212529; 
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
        }
        .btn-toggle { 
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%); 
            color: white; 
            box-shadow: 0 2px 4px rgba(108, 117, 125, 0.3);
        }
        .btn-delete { 
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); 
            color: white; 
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
        }
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .status-active { color: #28a745; }
        .status-inactive { color: #dc3545; }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        .close:hover { color: #000; }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        .btn-primary { background: #007bff; color: white; padding: 12px 24px; }
        .btn-secondary { background: #6c757d; color: white; padding: 12px 24px; }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body class="admin-dashboard-body">
    <div class="admin-layout">
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
                <li><a class="active" href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-user-tie"></i> Staff</a></li>
                <li><a href="courses.php"><i class="fas fa-graduation-cap"></i> Courses</a></li>
                <li><a href="#"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="menu-toggle"><i class="fas fa-bars"></i></button>
                <div class="breadcrumbs">
                    <a href="../index.php" class="home-link">Home</a> / 
                    <a href="../dashboard.php">Dashboard</a> / Students
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
                <h1><i class="fas fa-user-graduate"></i> Student Management</h1>
                <a href="add-student.php" class="add-student-btn">
                    <i class="fas fa-plus"></i> Add New Student
                </a>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <div class="value"><?php echo count($students); ?></div>
                    <div class="label">Enrolled Students</div>
                </div>
                <div class="stat-card">
                    <h3>Active Students</h3>
                    <div class="value"><?php echo count(array_filter($students, function($s) { return $s['status'] === 'active'; })); ?></div>
                    <div class="label">Currently Active</div>
                </div>
                <div class="stat-card">
                    <h3>Total Enrollments</h3>
                    <div class="value"><?php echo array_sum(array_column($students, 'total_enrollments')); ?></div>
                    <div class="label">Course Enrollments</div>
                </div>
                <div class="stat-card">
                    <h3>Completed Courses</h3>
                    <div class="value"><?php echo array_sum(array_column($students, 'completed_courses')); ?></div>
                    <div class="label">Successfully Completed</div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="search-section">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="studentSearch" placeholder="Search students by name, username, email, or phone..." onkeyup="searchStudents()">
                    <button class="clear-search" onclick="clearSearch()" id="clearSearchBtn" style="display: none;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="search-filters">
                    <select id="statusFilter" onchange="filterStudents()">
                        <option value="active">Active Only</option>
                        <option value="">All Status</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                    <select id="sortBy" onchange="sortStudents()">
                        <option value="created_at">Sort by: Join Date</option>
                        <option value="full_name">Sort by: Name</option>
                        <option value="username">Sort by: Username</option>
                        <option value="total_enrollments">Sort by: Enrollments</option>
                    </select>
                </div>
            </div>

            <!-- Students Grid -->
            <div class="section-header">
                <h2><i class="fas fa-users"></i> All Students</h2>
                <p>Manage student accounts, view profiles, and track enrollments</p>
            </div>
            <div class="students-grid" id="studentsGrid">
                <?php foreach ($students as $student): ?>
                    <div class="student-card">
                        <div class="student-header">
                            <div class="student-avatar">
                                <?php if (!empty($student['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile" class="profile-img">
                                <?php else: ?>
                                    <div class="default-avatar">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="student-details">
                                <div class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                <div class="student-username">@<?php echo htmlspecialchars($student['username']); ?></div>
                            </div>
                            <div class="student-status <?php echo $student['status']; ?>">
                                <?php echo htmlspecialchars(ucfirst($student['status'])); ?>
                            </div>
                        </div>
                        
                        <div class="student-info">
                            <strong>Username:</strong> <?php echo htmlspecialchars($student['username']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?><br>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($student['phone']); ?><br>
                            <strong>Gender:</strong> <?php echo htmlspecialchars(ucfirst($student['gender'] ?? 'N/A')); ?><br>
                            <strong>Joined:</strong> <?php echo date('M d, Y', strtotime($student['created_at'])); ?>
                        </div>

                        <div class="enrollment-stats">
                            <h4>Enrollment Statistics</h4>
                            <div class="stats-row">
                                <span class="stats-label">Total Enrollments:</span>
                                <span class="stats-value"><?php echo $student['total_enrollments']; ?></span>
                            </div>
                            <div class="stats-row">
                                <span class="stats-label">Active Courses:</span>
                                <span class="stats-value"><?php echo $student['active_enrollments']; ?></span>
                            </div>
                            <div class="stats-row">
                                <span class="stats-label">Completed:</span>
                                <span class="stats-value"><?php echo $student['completed_courses']; ?></span>
                            </div>
                        </div>

                        <div class="student-actions">
                            <button class="btn btn-edit" onclick="editStudent(<?php echo $student['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-password" onclick="openPasswordModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['username']); ?>')">
                                <i class="fas fa-key"></i> Password
                            </button>
                            <button class="btn btn-toggle" onclick="toggleStatus(<?php echo $student['id']; ?>, '<?php echo $student['status'] === 'active' ? 'inactive' : 'active'; ?>')">
                                <i class="fas fa-toggle-on"></i> 
                                <?php echo $student['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                            </button>
                            <button class="btn btn-delete" onclick="deleteStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['full_name']); ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>



    <!-- Password Update Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-key"></i> Update Password</h2>
                <span class="close" onclick="closePasswordModal()">&times;</span>
            </div>
            
            <form method="POST" action="students.php">
                <input type="hidden" name="action" value="update_password">
                <input type="hidden" id="password_student_id" name="student_id">
                
                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required 
                           minlength="6" placeholder="Enter new password (min 6 characters)">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           minlength="6" placeholder="Confirm new password">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mobile Menu JavaScript -->
    <script src="../assets/js/mobile-menu.js"></script>
    
    <script>
        function editStudent(studentId) {
            // For now, redirect to a potential edit page or show a message
            alert('Edit functionality will be implemented in the next update. Student ID: ' + studentId);
        }
        
        function deleteStudent(studentId, studentName) {
            if (confirm(`Are you sure you want to delete student "${studentName}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'students.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_student';
                
                const studentIdInput = document.createElement('input');
                studentIdInput.type = 'hidden';
                studentIdInput.name = 'student_id';
                studentIdInput.value = studentId;
                
                form.appendChild(actionInput);
                form.appendChild(studentIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function openPasswordModal(studentId, username) {
            document.getElementById('password_student_id').value = studentId;
            document.getElementById('passwordModal').style.display = 'block';
            document.getElementById('new_password').focus();
        }
        
        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
        }
        
        function toggleStatus(studentId, newStatus) {
            const action = newStatus === 'active' ? 'activate' : 'deactivate';
            if (confirm(`Are you sure you want to ${action} this student?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'students.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'toggle_status';
                
                const studentIdInput = document.createElement('input');
                studentIdInput.type = 'hidden';
                studentIdInput.name = 'student_id';
                studentIdInput.value = studentId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'new_status';
                statusInput.value = newStatus;
                
                form.appendChild(actionInput);
                form.appendChild(studentIdInput);
                form.appendChild(statusInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addStudentModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
        
        // Search, Filter, and Sort Functions
        let allStudents = [];
        

        
        function searchStudents() {
            const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
            const clearBtn = document.getElementById('clearSearchBtn');
            
            if (searchTerm.length > 0) {
                clearBtn.style.display = 'block';
            } else {
                clearBtn.style.display = 'none';
            }
            
            // Apply both search and status filter
            filterStudents();
        }
        
        function clearSearch() {
            document.getElementById('studentSearch').value = '';
            document.getElementById('clearSearchBtn').style.display = 'none';
            
            // Re-apply status filter after clearing search
            filterStudents();
        }
        
        function filterStudents() {
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
            
            allStudents.forEach(student => {
                let show = true;
                
                // Apply status filter
                if (statusFilter && student.status !== statusFilter) {
                    show = false;
                }
                
                // Apply search filter
                if (searchTerm && !(student.name.includes(searchTerm) || 
                                   student.username.includes(searchTerm) || 
                                   student.email.includes(searchTerm))) {
                    show = false;
                }
                
                student.element.style.display = show ? 'block' : 'none';
            });
            
            updateStudentCount();
        }
        
        // Apply default filter on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Store all students data for search/filter operations
            const studentCards = document.querySelectorAll('.student-card');
            allStudents = Array.from(studentCards).map(card => ({
                element: card,
                name: card.querySelector('.student-name').textContent.toLowerCase(),
                username: card.querySelector('.student-username').textContent.toLowerCase(),
                email: card.querySelector('.student-info').textContent.toLowerCase(),
                status: card.querySelector('.student-status').textContent.toLowerCase().trim(),
                enrollments: parseInt(card.querySelector('.stats-value').textContent) || 0,
                created_at: card.querySelector('.student-info').textContent.includes('Joined:') ? 
                    new Date(card.querySelector('.student-info').textContent.split('Joined:')[1].trim()) : new Date(0)
            }));
            

            
            // Apply default filter to show only active students
            filterStudents();
        });
        
        function sortStudents() {
            const sortBy = document.getElementById('sortBy').value;
            const studentsGrid = document.getElementById('studentsGrid');
            const visibleStudents = allStudents.filter(student => 
                student.element.style.display !== 'none'
            );
            
            // Sort the visible students
            visibleStudents.sort((a, b) => {
                switch(sortBy) {
                    case 'full_name':
                        return a.name.localeCompare(b.name);
                    case 'username':
                        return a.username.localeCompare(b.username);
                    case 'total_enrollments':
                        return b.enrollments - a.enrollments;
                    case 'created_at':
                    default:
                        return b.created_at - a.created_at;
                }
            });
            
            // Reorder the DOM elements
            visibleStudents.forEach(student => {
                studentsGrid.appendChild(student.element);
            });
        }
        
        function updateStudentCount() {
            const visibleCount = allStudents.filter(student => 
                student.element.style.display !== 'none'
            ).length;
            
            const totalCount = allStudents.length;
            const sectionHeader = document.querySelector('.section-header h2');
            sectionHeader.innerHTML = `<i class="fas fa-users"></i> Students (${visibleCount}/${totalCount})`;
        }
    </script>
</body>
</html>
