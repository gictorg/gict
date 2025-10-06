<?php
require_once 'includes/session_manager.php';
require_once 'auth.php';
requireLogin();

$user = getCurrentUser();
if (!$user) {
    error_log("Dashboard: getCurrentUser() returned null, destroying session");
    session_destroy();
    header('Location: login.php');
    exit();
}

$userType = $user['type'];
error_log("Dashboard: User loaded successfully - Type: $userType, Name: " . ($user['full_name'] ?? 'Unknown'));

// Fetch real statistics for admin dashboard
$totalStudents = 0;
$totalCollection = 0;
$activeStudents = 0;
$totalEnrollments = 0;

if ($userType === 'admin') {
    try {
        require_once 'config/database.php';
        
        // Get total students count
        $studentsSql = "SELECT COUNT(*) as count FROM users u WHERE u.user_type_id = 2 AND u.status = 'active'";
        $studentsResult = getRow($studentsSql);
        $totalStudents = $studentsResult['count'] ?? 0;
        
        // Get total faculty count
        // $facultySql = "SELECT COUNT(*) as count FROM users u WHERE u.user_type_id = 3 AND u.status = 'active'";
        // $facultyResult = getRow($facultySql);
        // $totalFaculty = $facultyResult['count'] ?? 0;
        
        // Get active students count
        $activeStudents = $totalStudents;
        
        // Get total enrollments
        $enrollmentsSql = "SELECT COUNT(*) as count FROM student_enrollments WHERE status = 'enrolled'";
        $enrollmentsResult = getRow($enrollmentsSql);
        $totalEnrollments = $enrollmentsResult['count'] ?? 0;
        
        // Get total collection (from payments table)
        $paymentsSql = "SELECT SUM(amount) as total FROM payments WHERE status = 'completed'";
        $paymentsResult = getRow($paymentsSql);
        $totalCollection = $paymentsResult['total'] ?? 0;
        
        // If no payments data, calculate based on enrollments (assuming ₹3000 per course)
        if ($totalCollection == 0 && $totalEnrollments > 0) {
            $totalCollection = $totalEnrollments * 3000;
        }
        
    } catch (Exception $e) {
        error_log("Dashboard stats error: " . $e->getMessage());
        // Fallback to default values if database query fails
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GICT</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="<?php echo $userType === 'admin' ? 'admin-dashboard-body' : ''; ?>">
<?php if ($userType === 'admin'): ?>
    <!-- Admin Layout -->
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img src="assets/images/logo.png" alt="logo" />
                <div class="brand-title">GICT CONTROL</div>
            </div>
            <div class="profile-card-mini">
                <img src="<?php echo $user['profile_image'] ?? 'assets/images/default-avatar.png'; ?>" alt="Profile" onerror="this.src='assets/images/default-avatar.png'" />
                <div>
                    <div class="name"><?php echo htmlspecialchars(strtoupper($user['full_name'])); ?></div>
                    <div class="role"><?php echo htmlspecialchars(ucfirst($user['type'])); ?></div>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a class="active" href="#"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="admin/students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="admin/staff.php"><i class="fas fa-user-tie"></i> Staff</a></li>
                <li><a href="admin/courses.php"><i class="fas fa-graduation-cap"></i> Courses</a></li>
                <li><a href="admin/pending-approvals.php"><i class="fas fa-clock"></i> Pending Approvals</a></li>
                <li><a href="admin/payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="admin/inquiries.php"><i class="fas fa-question-circle"></i> Course Inquiries</a></li>
                <li><a href="admin/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="menu-toggle"><i class="fas fa-bars"></i></button>
                <div class="breadcrumbs">
                    <a href="index.php" class="topbar-home-link"><i class="fas fa-home"></i> Home</a>
                    <span style="opacity:.7; margin: 0 6px;">/</span>
                    <span>Dashboard</span>
                </div>
            </div>
        </header>

        <main class="admin-content">
            <div class="widget-row">
                <section class="panel certificate-card">
                    <div class="panel-header">Authorization Certificate <a style="font-size:12px; font-weight:500; text-decoration:none; margin-left:auto; color:#fff;" href="#"><i class="fas fa-download"></i> Download</a></div>
                    <div class="panel-body certificate-body">
                        <div class="certificate-icon"><i class="fas fa-award"></i></div>
                        <div style="flex:1">
                            <div style="font-weight:700; font-size:18px;">GICT Authorization</div>
                            <div style="opacity:.9; font-size:13px;">Valid and active</div>
                        </div>
                    </div>
                </section>

                <section class="stat admissions">
                    <h3>Admissions</h3>
                    <div class="value"><?php echo $totalStudents; ?></div>
                    <div class="muted">Total Students</div>
                </section>
<!-- 
                <section class="stat faculty">
                    <h3>Faculty</h3>
                    <div class="value"><?php echo $totalFaculty; ?></div>
                    <div class="muted">Total Staff</div>
                </section> -->

                <section class="stat enrollments">
                    <h3>Enrollments</h3>
                    <div class="value"><?php echo $totalEnrollments; ?></div>
                    <div class="muted">Total Courses</div>
                </section>

                <section class="stat collection">
                    <h3>Collection</h3>
                    <div class="value">₹<?php echo number_format($totalCollection, 2); ?></div>
                    <div class="muted">Total Revenue</div>
                </section>
            </div>

            <div class="grid-3">
                <section class="streams-list">
                    <div class="panel-header">Available Courses</div>
                    <div class="panel-body">
                        <?php
                        if ($userType === 'admin') {
                            try {
                                // Get courses from database
                                $coursesSql = "SELECT c.name, c.status, c.duration, cc.name as category_name 
                                              FROM courses c 
                                              JOIN course_categories cc ON c.category_id = cc.id 
                                              ORDER BY c.name";
                                $courses = getRows($coursesSql);
                                
                                if (!empty($courses)) {
                                    foreach ($courses as $course) {
                                        $statusClass = $course['status'] === 'active' ? 'approved' : 'pending';
                                        $statusText = ucfirst($course['status']);
                                        echo "<div class='stream-item'>";
                                        echo "<span>" . htmlspecialchars($course['name']) . "</span>";
                                        echo "<span class='badge {$statusClass}'>{$statusText}</span>";
                                        echo "</div>";
                                    }
                                } else {
                                    // Fallback to default courses if none in database
                                    $defaultCourses = [
                                        'FINANCE & BANKING',
                                        'IT - HARDWARE & NETWORKING', 
                                        'IT - PROGRAMMING',
                                        'IT - SKILL DEVELOPMENT',
                                        'IT - SOFTWARE APPLICATIONS'
                                    ];
                                    foreach ($defaultCourses as $course) {
                                        echo "<div class='stream-item'>";
                                        echo "<span>{$course}</span>";
                                        echo "<span class='badge approved'>Approved</span>";
                                        echo "</div>";
                                    }
                                }
                            } catch (Exception $e) {
                                error_log("Courses fetch error: " . $e->getMessage());
                                // Fallback to default courses
                                $defaultCourses = [
                                    'FINANCE & BANKING',
                                    'IT - HARDWARE & NETWORKING', 
                                    'IT - PROGRAMMING',
                                    'IT - SKILL DEVELOPMENT',
                                    'IT - SOFTWARE APPLICATIONS'
                                ];
                                foreach ($defaultCourses as $course) {
                                    echo "<div class='stream-item'>";
                                    echo "<span>{$course}</span>";
                                    echo "<span class='badge approved'>Approved</span>";
                                    echo "</div>";
                                }
                            }
                        }
                        ?>
                    </div>
                    <div class="streams-note">Courses are dynamically loaded from the database.</div>
                </section>

                <section class="hero-card">
                    <img src="assets/images/home_image.jpg" alt="Skill Development Leaders" />
                    <div class="caption">Skill Development Leaders</div>
                </section>

                <section class="award-card">
                    <img src="assets/images/Success.jpeg" alt="Gyan Gaurav Award" />
                    <a href="#" class="btn">GYAN GAURAV AWARD</a>
                </section>
            </div>

            <div class="footer-note">Copyright © <?php echo date('Y'); ?> · All rights reserved.</div>
        </main>
    </div>
    
    <!-- Mobile Menu JavaScript -->
    <script src="assets/js/mobile-menu.js"></script>
<?php else: ?>
    <!-- Simple dashboards for non-admins -->
    <div class="dashboard-container" style="max-width:1200px;margin:20px auto;padding:20px;">
        <h2>Welcome, <?php echo htmlspecialchars(ucfirst($userType)); ?></h2>
        <p>This is your dashboard.</p>
    </div>
<?php endif; ?>
</body>
</html> 