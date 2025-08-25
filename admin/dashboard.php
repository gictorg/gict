<?php
require_once '../includes/session_manager.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$institute_id = getCurrentInstituteId();
$institute = getCurrentInstitute();

if (!$institute) {
    header('Location: ../login.php');
    exit;
}

// Get institute-specific statistics
$total_students = getRow("SELECT COUNT(*) as count FROM users WHERE institute_id = ? AND user_type = 'student' AND status = 'active'", [$institute_id])['count'];
$total_faculty = getRow("SELECT COUNT(*) as count FROM users WHERE institute_id = ? AND user_type = 'faculty' AND status = 'active'", [$institute_id])['count'];
$total_courses = getRow("SELECT COUNT(*) as count FROM courses WHERE institute_id = ? AND status = 'active'", [$institute_id])['count'];
$total_sub_courses = getRow("
    SELECT COUNT(*) as count 
    FROM sub_courses sc 
    JOIN courses c ON sc.course_id = c.id 
    WHERE c.institute_id = ? AND sc.status = 'active'
", [$institute_id])['count'];

// Get recent enrollments
$recent_enrollments = getRows("
    SELECT se.*, u.full_name as student_name, sc.name as sub_course_name, c.name as course_name
    FROM student_enrollments se
    JOIN users u ON se.user_id = u.id
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    WHERE c.institute_id = ?
    ORDER BY se.enrollment_date DESC
    LIMIT 5
", [$institute_id]);

// Get recent payments
$recent_payments = getRows("
    SELECT p.*, u.full_name as student_name, sc.name as sub_course_name
    FROM payments p
    JOIN users u ON p.user_id = u.id
    JOIN sub_courses sc ON p.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    WHERE c.institute_id = ?
    ORDER BY p.payment_date DESC
    LIMIT 5
", [$institute_id]);

// Get course statistics
$course_stats = getRows("
    SELECT 
        c.name as course_name,
        COUNT(sc.id) as sub_courses,
        COUNT(DISTINCT se.user_id) as enrolled_students,
        SUM(sc.fee) as total_fees
    FROM courses c
    LEFT JOIN sub_courses sc ON c.id = sc.course_id AND sc.status = 'active'
    LEFT JOIN student_enrollments se ON sc.id = se.sub_course_id AND se.status = 'active'
    WHERE c.institute_id = ? AND c.status = 'active'
    GROUP BY c.id, c.name
    ORDER BY enrolled_students DESC
", [$institute_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo htmlspecialchars($institute['name']); ?></title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-weight: 600;
        }
        
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
        }
        
        .course-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .course-meta {
            font-size: 0.9rem;
            opacity: 0.9;
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
            color: #667eea;
        }
        
        .course-stat-label {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .course-actions {
            display: flex;
            gap: 0.5rem;
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
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
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
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }
        
        .institute-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .institute-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .institute-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .institute-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .institute-detail i {
            width: 20px;
            opacity: 0.8;
        }
    </style>
</head>
<body class="admin-dashboard-body">
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img src="../assets/images/logo.png" alt="logo" />
                <div class="brand-title"><?php echo htmlspecialchars(strtoupper($institute['name'])); ?></div>
            </div>
            
            <div class="profile-card-mini">
                <img src="../assets/images/default-avatar.png" alt="Profile" />
                <div>
                    <div class="name"><?php echo htmlspecialchars(strtoupper($_SESSION['full_name'])); ?></div>
                    <div class="role">Institute Admin</div>
                </div>
            </div>
            
            <ul class="sidebar-nav">
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="faculty.php"><i class="fas fa-chalkboard-teacher"></i> Faculty</a></li>
                <li><a href="courses.php"><i class="fas fa-book"></i> Courses</a></li>
                <li><a href="enrollments.php"><i class="fas fa-user-plus"></i> Enrollments</a></li>
                <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
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
                    <span>Admin Dashboard</span>
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
            <!-- Institute Info -->
            <div class="institute-info">
                <div class="institute-name">
                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($institute['name']); ?>
                </div>
                <div class="institute-details">
                    <div class="institute-detail">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($institute['address']); ?></span>
                    </div>
                    <div class="institute-detail">
                        <i class="fas fa-phone"></i>
                        <span><?php echo htmlspecialchars($institute['phone']); ?></span>
                    </div>
                    <div class="institute-detail">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($institute['email']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_faculty; ?></div>
                    <div class="stat-label">Faculty Members</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_courses; ?></div>
                    <div class="stat-label">Main Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_sub_courses; ?></div>
                    <div class="stat-label">Sub Courses</div>
                </div>
            </div>
            
            <!-- Course Overview -->
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-book"></i> Course Overview</span>
                    <a href="courses.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add Course
                    </a>
                </div>
                <div class="panel-body">
                    <div class="course-grid">
                        <?php foreach ($course_stats as $course): ?>
                            <div class="course-card">
                                <div class="course-header">
                                    <div class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                    <div class="course-meta">
                                        <i class="fas fa-graduation-cap"></i> Main Course
                                    </div>
                                </div>
                                <div class="course-body">
                                    <div class="course-stats">
                                        <div class="course-stat">
                                            <div class="course-stat-number"><?php echo $course['sub_courses']; ?></div>
                                            <div class="course-stat-label">Sub Courses</div>
                                        </div>
                                        <div class="course-stat">
                                            <div class="course-stat-number"><?php echo $course['enrolled_students']; ?></div>
                                            <div class="course-stat-label">Students</div>
                                        </div>
                                        <div class="course-stat">
                                            <div class="course-stat-number">₹<?php echo number_format($course['total_fees']); ?></div>
                                            <div class="course-stat-label">Total Fees</div>
                                        </div>
                                        <div class="course-stat">
                                            <div class="course-stat-number"><?php echo $course['sub_courses'] > 0 ? round(($course['enrolled_students'] / $course['sub_courses']) * 100) : 0; ?>%</div>
                                            <div class="course-stat-label">Enrollment Rate</div>
                                        </div>
                                    </div>
                                    <div class="course-actions">
                                        <a href="course-details.php?id=<?php echo $course['course_id'] ?? ''; ?>" class="btn btn-primary">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                        <a href="manage-course.php?id=<?php echo $course['course_id'] ?? ''; ?>" class="btn btn-success">
                                            <i class="fas fa-cog"></i> Manage
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-clock"></i> Recent Enrollments</span>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Sub Course</th>
                                    <th>Enrollment Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_enrollments as $enrollment): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($enrollment['student_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($enrollment['course_name']); ?></td>
                                        <td><?php echo htmlspecialchars($enrollment['sub_course_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $enrollment['status']; ?>">
                                                <?php echo ucfirst($enrollment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Recent Payments -->
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-credit-card"></i> Recent Payments</span>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Sub Course</th>
                                    <th>Amount</th>
                                    <th>Payment Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_payments as $payment): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($payment['student_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($payment['sub_course_name']); ?></td>
                                        <td>₹<?php echo number_format($payment['amount']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $payment['status']; ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('mobile-open');
        }
    </script>
</body>
</html>
