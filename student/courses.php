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
$student = getRow("SELECT u.*, ut.name as user_type FROM users u JOIN user_types ut ON u.user_type_id = ut.id WHERE u.id = ? AND ut.name = 'student'", [$user_id]);
if (!$student) {
    header('Location: ../login.php');
    exit;
}

// Get enrolled courses with fees and certificate info
$enrolled_courses = getRows("
    SELECT c.name as course_name, cc.name as category_name, sc.name as sub_course_name, 
           sc.fee, sc.duration, se.id as enrollment_id, se.status as enrollment_status, 
           se.enrollment_date, se.completion_date, se.paid_fees, se.remaining_fees, se.total_fee,
           cert.id as certificate_id, cert.certificate_url, cert.marksheet_url, cert.certificate_number
    FROM student_enrollments se
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    JOIN course_categories cc ON c.category_id = cc.id
    LEFT JOIN certificates cert ON cert.enrollment_id = se.id
    WHERE se.user_id = ?
    ORDER BY se.enrollment_date DESC
", [$user_id]);

// Get marks for completed courses
$marks_data = [];
foreach ($enrolled_courses as $course) {
    if ($course['enrollment_status'] === 'completed' && !empty($course['enrollment_id'])) {
        $marks = getRows("
            SELECT subject_name, marks_obtained, max_marks, grade, remarks
            FROM student_marks
            WHERE enrollment_id = ?
            ORDER BY subject_name
        ", [$course['enrollment_id']]);
        $marks_data[$course['enrollment_id']] = $marks;
    }
}

// Get available sub-courses for enrollment
$available_courses = getRows("
    SELECT sc.id, c.name as course_name, cc.name as category_name, sc.name as sub_course_name, 
           sc.fee, sc.duration, sc.description
    FROM sub_courses sc
    JOIN courses c ON sc.course_id = c.id
    JOIN course_categories cc ON c.category_id = cc.id
    WHERE sc.status = 'active' 
    AND sc.id NOT IN (
        SELECT sub_course_id FROM student_enrollments WHERE user_id = ?
    )
    ORDER BY c.name, sc.name
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
    <link rel="icon" type="image/png" href="../logo.png">
    <link rel="shortcut icon" type="image/png" href="../logo.png">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Student-specific overrides to match admin dashboard exactly */
        .admin-sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .admin-topbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        /* Digital ID Badge */
        .digital-id-badge {
            position: absolute;
            bottom: -5px;
            right: -5px;
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: 2px solid #fff;
        }
        
        .digital-id-badge:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .digital-id-badge i {
            color: white;
            font-size: 14px;
        }
        
        .profile-card-mini {
            position: relative;
        }
        
        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        /* Course cards */
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .course-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
        }
        
        .course-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .course-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        
        .course-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-completed {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .course-details {
            margin-bottom: 1rem;
        }
        
        .course-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .course-actions {
            display: flex;
            gap: 0.5rem;
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
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-success {
            background: #16a34a;
            color: white;
        }
        
        .btn-success:hover {
            background: #15803d;
        }
        
        .btn-info {
            background: #0891b2;
            color: white;
        }
        
        .btn-info:hover {
            background: #0e7490;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }
        
        .text-muted {
            color: #6b7280;
        }
    </style>
</head>
<body class="admin-dashboard-body">
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img src="../assets/images/logo.png" alt="logo" />
                <div class="brand-title">STUDENT PORTAL</div>
            </div>
            
            <div class="profile-card-mini">
                <div style="position: relative;">
                    <img src="<?php echo $student['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" alt="Profile" onerror="this.src='../assets/images/default-avatar.png'" />
                    <div class="digital-id-badge" onclick="viewID()" title="View Digital ID">
                        <i class="fas fa-id-card"></i>
                    </div>
                </div>
                <div>
                    <div class="name"><?php echo htmlspecialchars(strtoupper($student['full_name'])); ?></div>
                    <div class="role">Student</div>
                </div>
            </div>
            
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="courses.php" class="active"><i class="fas fa-book"></i> My Courses</a></li>
                <li><a href="documents.php"><i class="fas fa-file-upload"></i> Documents</a></li>
                <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
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
                    <span>My Courses</span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-content">
            <!-- Statistics Cards -->
            <div class="stats-grid">
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
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-book"></i> Enrolled Courses</span>
                </div>
                <div class="panel-body">
                    <?php if (empty($enrolled_courses)): ?>
                        <p class="text-muted">You haven't enrolled in any courses yet.</p>
                    <?php else: ?>
                        <div class="course-grid">
                            <?php foreach ($enrolled_courses as $course): ?>
                                <div class="course-card">
                                    <div class="course-header">
                                        <h6 class="course-title"><?php echo htmlspecialchars($course['sub_course_name']); ?></h6>
                                        <span class="course-status status-<?php echo $course['enrollment_status']; ?>">
                                            <?php echo ucfirst($course['enrollment_status']); ?>
                                        </span>
                                    </div>
                                    <div class="course-details">
                                        <div class="course-detail">
                                            <i class="fas fa-book"></i>
                                            <span>Course: <?php echo htmlspecialchars($course['course_name']); ?></span>
                                        </div>
                                        <div class="course-detail">
                                            <i class="fas fa-tag"></i>
                                            <span>Category: <?php echo htmlspecialchars($course['category_name']); ?></span>
                                        </div>
                                        <div class="course-detail">
                                            <i class="fas fa-calendar"></i>
                                            <span>Enrolled: <?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?></span>
                                        </div>
                                        <?php if ($course['completion_date']): ?>
                                            <div class="course-detail">
                                                <i class="fas fa-trophy"></i>
                                                <span>Completed: <?php echo date('M d, Y', strtotime($course['completion_date'])); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="course-actions">
                                        <?php if ($course['enrollment_status'] !== 'completed'): ?>
                                            <button class="btn btn-info btn-sm">
                                                <i class="fas fa-play"></i> Continue Learning
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($course['enrollment_status'] === 'completed'): ?>
                                            <?php if (!empty($course['certificate_url'])): ?>
                                                <a href="<?php echo htmlspecialchars($course['certificate_url']); ?>" target="_blank" class="btn btn-success btn-sm">
                                                    <i class="fas fa-certificate"></i> View Certificate
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" disabled title="Certificate pending">
                                                    <i class="fas fa-certificate"></i> Certificate Pending
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($course['marksheet_url'])): ?>
                                                <a href="<?php echo htmlspecialchars($course['marksheet_url']); ?>" target="_blank" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-file-alt"></i> View Marksheet
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($marks_data[$course['enrollment_id']])): ?>
                                                <button class="btn btn-info btn-sm" onclick="showMarks(<?php echo $course['enrollment_id']; ?>)">
                                                    <i class="fas fa-chart-line"></i> View Marks
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Available Courses -->
            <div class="panel" style="margin-top: 1.5rem;">
                <div class="panel-header">
                    <span><i class="fas fa-plus-circle"></i> Available Courses</span>
                </div>
                <div class="panel-body">
                    <?php if (empty($available_courses)): ?>
                        <p class="text-muted">No new courses available for enrollment.</p>
                    <?php else: ?>
                        <div class="course-grid">
                            <?php foreach ($available_courses as $course): ?>
                                                                <div class="course-card">
                                    <div class="course-header">
                                        <h6 class="course-title"><?php echo htmlspecialchars($course['sub_course_name']); ?></h6>
                                    </div>
                                    <div class="course-details">
                                        <div class="course-detail">
                                            <i class="fas fa-book"></i>
                                            <span>Course: <?php echo htmlspecialchars($course['course_name']); ?></span>
                                        </div>
                                        <div class="course-detail">
                                            <i class="fas fa-tag"></i>
                                            <span>Category: <?php echo htmlspecialchars($course['category_name']); ?></span>
                                        </div>
                                        <div class="course-detail">
                                            <i class="fas fa-clock"></i>
                                            <span>Duration: <?php echo $course['duration']; ?> months</span>
                                        </div>
                                        <div class="course-detail">
                                            <i class="fas fa-rupee-sign"></i>
                                            <span>Fee: ₹<?php echo number_format($course['fee'], 2); ?></span>
                                        </div>
                                        <?php if ($course['description']): ?>
                                            <div class="course-detail">
                                                <i class="fas fa-info-circle"></i>
                                                <span><?php echo htmlspecialchars($course['description']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="course-actions">
                                        <button class="btn btn-primary btn-sm" onclick="enrollInCourse(<?php echo $course['id']; ?>)">
                                            <i class="fas fa-plus"></i> Enroll Now
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('mobile-open');
        }
        
        // Enroll in course function
        function enrollInCourse(subCourseId) {
            if (confirm('Are you sure you want to enroll in this course?')) {
                // Create form data
                const formData = new FormData();
                formData.append('enroll', '1');
                formData.append('sub_course_id', subCourseId);
                
                // Send enrollment request
                fetch('enroll.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Successfully enrolled in the course!');
                        location.reload(); // Refresh the page to show updated data
                    } else {
                        alert('Enrollment failed: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Enrollment failed. Please try again.');
                });
            }
        }
        // Marks Modal
        const marksData = <?php echo json_encode($marks_data); ?>;
        
        function showMarks(enrollmentId) {
            const marks = marksData[enrollmentId] || [];
            if (marks.length === 0) {
                alert('No marks available for this course.');
                return;
            }
            
            let marksHtml = '<div class="marks-modal-content" style="background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 50px auto;">';
            marksHtml += '<h3 style="margin-bottom: 20px;"><i class="fas fa-chart-line"></i> Course Marks</h3>';
            marksHtml += '<table style="width: 100%; border-collapse: collapse;">';
            marksHtml += '<thead><tr style="background: #f8f9fa;"><th style="padding: 10px; text-align: left;">Subject</th><th style="padding: 10px; text-align: center;">Marks</th><th style="padding: 10px; text-align: center;">Grade</th></tr></thead>';
            marksHtml += '<tbody>';
            
            let totalObtained = 0;
            let totalMax = 0;
            
            marks.forEach(mark => {
                const percentage = (mark.marks_obtained / mark.max_marks) * 100;
                marksHtml += `<tr>
                    <td style="padding: 10px;">${mark.subject_name}</td>
                    <td style="padding: 10px; text-align: center;">${mark.marks_obtained} / ${mark.max_marks}</td>
                    <td style="padding: 10px; text-align: center;"><strong>${mark.grade || 'N/A'}</strong></td>
                </tr>`;
                totalObtained += parseFloat(mark.marks_obtained);
                totalMax += parseFloat(mark.max_marks);
            });
            
            const overallPercentage = (totalObtained / totalMax) * 100;
            marksHtml += `<tr style="background: #f8f9fa; font-weight: bold;">
                <td style="padding: 10px;">Total</td>
                <td style="padding: 10px; text-align: center;">${totalObtained.toFixed(2)} / ${totalMax.toFixed(2)}</td>
                <td style="padding: 10px; text-align: center;">${overallPercentage.toFixed(2)}%</td>
            </tr>`;
            marksHtml += '</tbody></table>';
            marksHtml += '<button onclick="closeMarksModal()" style="margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">Close</button>';
            marksHtml += '</div>';
            
            const modal = document.createElement('div');
            modal.id = 'marksModal';
            modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;';
            modal.innerHTML = marksHtml;
            document.body.appendChild(modal);
        }
        
        function closeMarksModal() {
            const modal = document.getElementById('marksModal');
            if (modal) {
                modal.remove();
            }
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.id === 'marksModal') {
                closeMarksModal();
            }
        });
    </script>
</body>
</html>
