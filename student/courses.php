<?php
require_once '../includes/session_manager.php';
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get student information
$student = getRow("SELECT * FROM users WHERE id = ? AND user_type = 'student'", [$user_id]);
if (!$student) {
    header('Location: ../login.php');
    exit;
}

// Get enrolled courses
$enrolled_courses = getRows("
    SELECT c.*, se.status as enrollment_status, se.enrollment_date, se.final_marks
    FROM courses c
    JOIN student_enrollments se ON c.id = se.course_id
    WHERE se.user_id = ?
    ORDER BY se.enrollment_date DESC
", [$user_id]);

// Get available courses for enrollment
$available_courses = getRows("
    SELECT c.* FROM courses c
    WHERE c.status = 'active' 
    AND c.id NOT IN (
        SELECT course_id FROM student_enrollments WHERE user_id = ?
    )
    ORDER BY c.name
", [$user_id]);

$total_enrolled = count($enrolled_courses);
$completed_courses = count(array_filter($enrolled_courses, fn($c) => $c['enrollment_status'] === 'completed'));
$active_courses = count(array_filter($enrolled_courses, fn($c) => $c['enrollment_status'] === 'active'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Student Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .course-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
        }
        
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.15);
        }
        
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .course-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        
        .course-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #e8f5e8;
            color: #28a745;
        }
        
        .status-completed {
            background: #e8f4fd;
            color: #007bff;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .course-details {
            margin-bottom: 20px;
        }
        
        .course-detail {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #666;
            font-size: 14px;
        }
        
        .course-detail i {
            width: 20px;
            margin-right: 10px;
            color: #667eea;
        }
        
        .course-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php 
        $page_title = 'My Courses';
        include 'includes/sidebar.php'; 
        ?>
        
        <?php include 'includes/topbar.php'; ?>
        
        <!-- Main Content -->
        <main class="admin-content">
            <!-- Statistics -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_enrolled; ?></div>
                    <div class="stat-label">Total Enrolled</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $active_courses; ?></div>
                    <div class="stat-label">Active Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $completed_courses; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            
            <!-- Enrolled Courses -->
            <div class="section">
                <div class="section-header">
                    <h2><i class="fas fa-book"></i> My Enrolled Courses</h2>
                </div>
                <div class="section-body">
                    <?php if (empty($enrolled_courses)): ?>
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <i class="fas fa-book-open" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                            <h3>No Courses Enrolled</h3>
                            <p>You haven't enrolled in any courses yet.</p>
                            <a href="#available-courses" class="btn btn-primary">Browse Available Courses</a>
                        </div>
                    <?php else: ?>
                        <div class="course-grid">
                            <?php foreach ($enrolled_courses as $course): ?>
                                <div class="course-card">
                                    <div class="course-header">
                                        <h3 class="course-title"><?php echo htmlspecialchars($course['name']); ?></h3>
                                        <span class="course-status status-<?php echo $course['enrollment_status']; ?>">
                                            <?php echo ucfirst($course['enrollment_status']); ?>
                                        </span>
                                    </div>
                                    <div class="course-details">
                                        <div class="course-detail">
                                            <i class="fas fa-clock"></i>
                                            <span>Duration: <?php echo htmlspecialchars($course['duration']); ?></span>
                                        </div>
                                        <div class="course-detail">
                                            <i class="fas fa-calendar"></i>
                                            <span>Enrolled: <?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?></span>
                                        </div>
                                        <?php if ($course['final_marks']): ?>
                                            <div class="course-detail">
                                                <i class="fas fa-star"></i>
                                                <span>Final Marks: <?php echo $course['final_marks']; ?>%</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="course-actions">
                                        <?php if ($course['enrollment_status'] === 'active'): ?>
                                            <a href="#" class="btn btn-primary">Continue Learning</a>
                                        <?php elseif ($course['enrollment_status'] === 'completed'): ?>
                                            <a href="#" class="btn btn-success">View Certificate</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Available Courses -->
            <div class="section" id="available-courses">
                <div class="section-header">
                    <h2><i class="fas fa-plus-circle"></i> Available Courses</h2>
                </div>
                <div class="section-body">
                    <?php if (empty($available_courses)): ?>
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                            <h3>All Courses Enrolled</h3>
                            <p>You have enrolled in all available courses!</p>
                        </div>
                    <?php else: ?>
                        <div class="course-grid">
                            <?php foreach ($available_courses as $course): ?>
                                <div class="course-card">
                                    <div class="course-header">
                                        <h3 class="course-title"><?php echo htmlspecialchars($course['name']); ?></h3>
                                        <span class="course-status status-pending">Available</span>
                                    </div>
                                    <div class="course-details">
                                        <div class="course-detail">
                                            <i class="fas fa-clock"></i>
                                            <span>Duration: <?php echo htmlspecialchars($course['duration']); ?></span>
                                        </div>
                                        <div class="course-detail">
                                            <i class="fas fa-info-circle"></i>
                                            <span><?php echo htmlspecialchars($course['description'] ?? 'No description available'); ?></span>
                                        </div>
                                    </div>
                                    <div class="course-actions">
                                        <a href="#" class="btn btn-primary">Enroll Now</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/mobile-menu.js"></script>
</body>
</html>
