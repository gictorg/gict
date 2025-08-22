<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

$user = getCurrentUser();

// Get all students with their enrollment information
$students = [];
try {
    $sql = "SELECT 
                u.id, u.username, u.email, u.full_name, u.phone, u.address, 
                u.date_of_birth, u.gender, u.joining_date, u.status, u.created_at,
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

// Get course statistics
$courseStats = [];
try {
    $sql = "SELECT 
                c.name as course_name,
                COUNT(e.id) as total_students,
                COUNT(CASE WHEN e.status = 'completed' THEN 1 END) as completed_students,
                COUNT(CASE WHEN e.status = 'enrolled' THEN 1 END) as active_students
            FROM courses c 
            LEFT JOIN enrollments e ON c.id = e.course_id 
            GROUP BY c.id 
            ORDER BY total_students DESC";
    $courseStats = getRows($sql);
} catch (Exception $e) {
    $error_message = "Error loading course statistics: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Admissions - GICT Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-content {
            padding: 20px;
        }
        .page-header {
            margin-bottom: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .stat-card .label {
            color: #999;
            font-size: 12px;
        }
        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .student-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #28a745;
        }
        .student-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .student-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
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
            margin-bottom: 15px;
            font-size: 14px;
        }
        .student-info strong {
            color: #666;
            min-width: 80px;
            display: inline-block;
        }
        .enrollment-stats {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .enrollment-stats h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 14px;
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
            gap: 10px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        .btn-view { background: #007bff; color: white; }
        .btn-edit { background: #28a745; color: white; }
        .btn:hover { opacity: 0.8; }
        .course-stats-section {
            margin-top: 40px;
        }
        .course-stats-table {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .course-stats-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .course-stats-table th, .course-stats-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .course-stats-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .course-stats-table tr:hover {
            background: #f8f9fa;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            transition: width 0.3s ease;
        }
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
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-user-tie"></i> Staff</a></li>
                <li><a class="active" href="admissions.php"><i class="fas fa-chart-line"></i> Admissions</a></li>
                <li><a href="#"><i class="fas fa-book"></i> Courses</a></li>
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
                    <a href="../dashboard.php">Dashboard</a> / Admissions
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
                <h1><i class="fas fa-user-graduate"></i> Student Admissions</h1>
            </div>

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

            <!-- Students Grid -->
            <h2><i class="fas fa-users"></i> All Students</h2>
            <div class="students-grid">
                <?php foreach ($students as $student): ?>
                    <div class="student-card">
                        <div class="student-header">
                            <div class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
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
                            <?php if ($student['total_enrollments'] > 0): ?>
                                <div class="progress-bar" style="margin-top: 10px;">
                                    <div class="progress-fill" style="width: <?php echo ($student['completed_courses'] / $student['total_enrollments']) * 100; ?>%"></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="student-actions">
                            <a href="#" class="btn btn-view">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <a href="#" class="btn btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Course Statistics -->
            <div class="course-stats-section">
                <h2><i class="fas fa-chart-bar"></i> Course Enrollment Statistics</h2>
                <div class="course-stats-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Total Students</th>
                                <th>Active Students</th>
                                <th>Completed</th>
                                <th>Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courseStats as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                    <td><?php echo $course['total_students']; ?></td>
                                    <td><?php echo $course['active_students']; ?></td>
                                    <td><?php echo $course['completed_students']; ?></td>
                                    <td>
                                        <?php 
                                        if ($course['total_students'] > 0) {
                                            $rate = ($course['completed_students'] / $course['total_students']) * 100;
                                            echo number_format($rate, 1) . '%';
                                        } else {
                                            echo '0%';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Mobile Menu JavaScript -->
    <script src="../assets/js/mobile-menu.js"></script>
    
    <script>
        // Add any additional JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize any interactive features
        });
    </script>
</body>
</html>
