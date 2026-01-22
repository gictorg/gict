<?php
require_once '../includes/session_manager.php';
require_once '../config/database.php';

// Check if user is logged in and is faculty
if (!isLoggedIn() || !isFaculty()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get faculty information
$faculty = getRow("SELECT u.* FROM users u WHERE u.id = ?", [$user_id]);
if (!$faculty) {
    header('Location: ../login.php');
    exit;
}

// Get faculty statistics
$stats = [];

try {
    // Get courses assigned to this faculty (assuming faculty can teach multiple courses)
    $coursesSql = "SELECT COUNT(*) as count FROM courses WHERE status = 'active'";
    $coursesResult = getRow($coursesSql);
    $stats['total_courses'] = $coursesResult['count'] ?? 0;
    
    // Get total students enrolled in courses
    $studentsSql = "SELECT COUNT(DISTINCT se.user_id) as count 
                    FROM student_enrollments se 
                    JOIN sub_courses sc ON se.sub_course_id = sc.id 
                    JOIN courses c ON sc.course_id = c.id 
                    WHERE se.status = 'enrolled'";
    $studentsResult = getRow($studentsSql);
    $stats['total_students'] = $studentsResult['count'] ?? 0;
    
    // Get active enrollments
    $enrollmentsSql = "SELECT COUNT(*) as count FROM student_enrollments WHERE status = 'enrolled'";
    $enrollmentsResult = getRow($enrollmentsSql);
    $stats['active_enrollments'] = $enrollmentsResult['count'] ?? 0;
    
    // Get pending enrollments
    $pendingSql = "SELECT COUNT(*) as count FROM student_enrollments WHERE status = 'pending'";
    $pendingResult = getRow($pendingSql);
    $stats['pending_enrollments'] = $pendingResult['count'] ?? 0;
    
} catch (Exception $e) {
        // Error loading stats
}

// Get recent enrollments
$recent_enrollments = [];
try {
    $enrollmentsSql = "SELECT 
                        se.id, se.enrollment_date, se.status,
                        u.full_name, u.username, u.email,
                        sc.name as sub_course_name, c.name as course_name
                    FROM student_enrollments se
                    JOIN users u ON se.user_id = u.id
                    JOIN sub_courses sc ON se.sub_course_id = sc.id
                    JOIN courses c ON sc.course_id = c.id
                    WHERE se.status IN ('enrolled', 'pending')
                    ORDER BY se.enrollment_date DESC
                    LIMIT 10";
    $recent_enrollments = getRows($enrollmentsSql);
} catch (Exception $e) {
        // Error loading enrollments
}

// Get active courses
$active_courses = [];
try {
    $coursesSql = "SELECT 
                    c.id, c.name, c.description, c.duration, c.fee,
                    cc.name as category_name,
                    (SELECT COUNT(*) FROM student_enrollments se 
                     JOIN sub_courses sc ON se.sub_course_id = sc.id 
                     WHERE sc.course_id = c.id AND se.status = 'enrolled') as enrolled_students
                FROM courses c
                LEFT JOIN course_categories cc ON c.category_id = cc.id
                WHERE c.status = 'active'
                ORDER BY c.name";
    $active_courses = getRows($coursesSql);
} catch (Exception $e) {
        // Error loading courses
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - GICT Institute</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Faculty-specific custom styles that extend admin dashboard */
        .faculty-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--panel-bg);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #1f2937;
            font-size: 28px;
            font-weight: 800;
        }
        
        .stat-card p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }
        
        .stat-card.primary { border-left: 4px solid #0f6fb1; }
        .stat-card.success { border-left: 4px solid #10b981; }
        .stat-card.warning { border-left: 4px solid #f59e0b; }
        .stat-card.info { border-left: 4px solid #06b6d4; }
        
        /* Panel styles are now handled by admin-dashboard.css */
        
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .course-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            background: #f9fafb;
        }
        
        .course-card h3 {
            margin: 0 0 10px 0;
            color: #1f2937;
        }
        
        .course-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 15px 0;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 2px;
            font-weight: 600;
        }
        
        .info-value {
            font-weight: 500;
            color: #1f2937;
        }
        
        .enrollment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .enrollment-table th,
        .enrollment-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .enrollment-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #334155;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-enrolled {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: var(--panel-bg);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }
        
        .action-card:hover {
            transform: translateY(-2px);
            border-color: #0f6fb1;
        }
        
        .action-card i {
            font-size: 32px;
            color: #0f6fb1;
            margin-bottom: 15px;
        }
        
        .action-card h3 {
            margin: 0 0 10px 0;
            color: #1f2937;
        }
        
        .action-card p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .profile-item {
            display: flex;
            flex-direction: column;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .profile-item label {
            font-size: 12px;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .profile-item span {
            font-weight: 500;
            color: #1f2937;
            font-size: 16px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--muted);
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #d1d5db;
        }
        
        .attendance-info ul,
        .assignment-info ul {
            margin: 15px 0;
            padding-left: 20px;
        }
        
        .attendance-info li,
        .assignment-info li {
            margin-bottom: 8px;
            color: var(--muted);
        }
        
        /* Mobile overlay styles */
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        .mobile-overlay.active {
            display: block;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #1f2937;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close {
            color: #6b7280;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        
        .close:hover {
            color: #1f2937;
        }
        
        .modal form {
            padding: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #0f6fb1;
            box-shadow: 0 0 0 3px rgba(15, 111, 177, 0.1);
        }
        
        .form-group small {
            display: block;
            margin-top: 6px;
            color: #6b7280;
            font-size: 12px;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        /* Success/Error message styles */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-danger {
            background-color: #fee2e2;
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
                <div class="brand-title">FACULTY PORTAL</div>
            </div>
            <div class="profile-card-mini">
                <img src="../assets/images/default-faculty.png" alt="Profile" onerror="this.src='../assets/images/default-avatar.png'" />
                <div>
                    <div class="name"><?php echo htmlspecialchars(strtoupper($faculty['full_name'])); ?></div>
                    <div class="role">Faculty</div>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a class="active" href="#"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="#courses"><i class="fas fa-graduation-cap"></i> My Courses</a></li>
                <li><a href="#students"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="#attendance"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                <li><a href="#assignments"><i class="fas fa-tasks"></i> Assignments</a></li>
                <li><a href="#profile"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="menu-toggle"><i class="fas fa-bars"></i></button>
                <div class="breadcrumbs">
                    <a href="../index.php" class="topbar-home-link">
                        <i class="fas fa-home"></i>Home
                    </a> / 
                    <span>Faculty Dashboard</span>
                </div>
            </div>
            <div class="topbar-right">
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($faculty['full_name']); ?></span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="panel">
                <div class="panel-header">
                    <h1><i class="fas fa-chalkboard-teacher"></i> Faculty Dashboard</h1>
                </div>
                <div class="panel-body">
                    <p>Welcome to your teaching dashboard. Manage your courses, students, and academic activities.</p>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="faculty-stats">
                <div class="stat-card primary">
                    <h3><?php echo $stats['total_courses'] ?? 0; ?></h3>
                    <p>Active Courses</p>
                </div>
                <div class="stat-card success">
                    <h3><?php echo $stats['total_students'] ?? 0; ?></h3>
                    <p>Total Students</p>
                </div>
                <div class="stat-card warning">
                    <h3><?php echo $stats['active_enrollments'] ?? 0; ?></h3>
                    <p>Active Enrollments</p>
                </div>
                <div class="stat-card info">
                    <h3><?php echo $stats['pending_enrollments'] ?? 0; ?></h3>
                    <p>Pending Approvals</p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="action-card" onclick="scrollToSection('courses')">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>View Courses</h3>
                    <p>See all your assigned courses and student enrollments</p>
                </div>
                <div class="action-card" onclick="scrollToSection('students')">
                    <i class="fas fa-users"></i>
                    <h3>Manage Students</h3>
                    <p>View and manage student information and progress</p>
                </div>
                <div class="action-card" onclick="scrollToSection('attendance')">
                    <i class="fas fa-calendar-check"></i>
                    <h3>Take Attendance</h3>
                    <p>Mark student attendance for your classes</p>
                </div>
                <div class="action-card" onclick="scrollToSection('assignments')">
                    <i class="fas fa-tasks"></i>
                    <h3>Assignments</h3>
                    <p>Create and grade student assignments</p>
                </div>
            </div>
            
            <!-- My Courses Section -->
            <div class="panel" id="courses">
                <div class="panel-header">
                    <h2><i class="fas fa-graduation-cap"></i> My Courses</h2>
                </div>
                <div class="panel-body">
                    <?php if (empty($active_courses)): ?>
                        <div class="no-data">
                            <i class="fas fa-book-open" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                            <h3>No Courses Assigned</h3>
                            <p>You don't have any courses assigned yet. Contact the administration.</p>
                        </div>
                    <?php else: ?>
                        <div class="course-grid">
                            <?php foreach ($active_courses as $course): ?>
                                <div class="course-card">
                                    <h3><?php echo htmlspecialchars($course['name']); ?></h3>
                                    <p><?php echo htmlspecialchars($course['description'] ?? 'No description available'); ?></p>
                                    
                                    <div class="course-info">
                                        <div class="info-item">
                                            <span class="info-label">Category</span>
                                            <span class="info-value"><?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Duration</span>
                                            <span class="info-value"><?php echo htmlspecialchars($course['duration'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Fee</span>
                                            <span class="info-value">₹<?php echo number_format($course['fee'] ?? 0, 2); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Students</span>
                                            <span class="info-value"><?php echo $course['enrolled_students']; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-top: 15px;">
                                        <button class="btn btn-primary" onclick="viewCourseDetails(<?php echo $course['id']; ?>)">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        <button class="btn btn-secondary" onclick="manageStudents(<?php echo $course['id']; ?>)">
                                            <i class="fas fa-users"></i> Manage Students
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Enrollments Section -->
        
            
            <!-- Attendance Section -->
            <div class="panel" id="attendance">
                <div class="panel-header">
                    <h2><i class="fas fa-calendar-check"></i> Attendance Management</h2>
                </div>
                <div class="panel-body">
                    <div class="attendance-info">
                        <p>Attendance tracking system will be implemented here. Faculty can:</p>
                        <ul>
                            <li>Mark daily attendance for enrolled students</li>
                            <li>View attendance reports and statistics</li>
                            <li>Generate attendance certificates</li>
                            <li>Send attendance notifications to parents</li>
                        </ul>
                        <button class="btn btn-primary" disabled>
                            <i class="fas fa-calendar-check"></i> Take Attendance (Coming Soon)
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Assignments Section -->
            <div class="panel" id="assignments">
                <div class="panel-header">
                    <h2><i class="fas fa-tasks"></i> Assignment Management</h2>
                </div>
                <div class="panel-body">
                    <div class="assignment-info">
                        <p>Assignment management system will be implemented here. Faculty can:</p>
                        <ul>
                            <li>Create and assign homework/projects</li>
                            <li>Set due dates and submission guidelines</li>
                            <li>Grade submitted assignments</li>
                            <li>Provide feedback to students</li>
                        </ul>
                        <button class="btn btn-primary" disabled>
                            <i class="fas fa-plus"></i> Create Assignment (Coming Soon)
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Profile Section -->
            <div class="panel" id="profile">
                <div class="panel-header">
                    <h2><i class="fas fa-user"></i> My Profile</h2>
                </div>
                <div class="panel-body">
                    <div class="profile-info">
                        <div class="profile-grid">
                            <div class="profile-item">
                                <label>Full Name:</label>
                                <span><?php echo htmlspecialchars($faculty['full_name']); ?></span>
                            </div>
                            <div class="profile-item">
                                <label>Username:</label>
                                <span><?php echo htmlspecialchars($faculty['username']); ?></span>
                            </div>
                            <div class="profile-item">
                                <label>Email:</label>
                                <span><?php echo htmlspecialchars($faculty['email']); ?></span>
                            </div>
                            <div class="profile-item">
                                <label>Phone:</label>
                                <span><?php echo htmlspecialchars($faculty['phone'] ?? 'Not provided'); ?></span>
                            </div>
                            <div class="profile-item">
                                <label>Role:</label>
                                <span>Faculty Member</span>
                            </div>
                            <div class="profile-item">
                                <label>Status:</label>
                                <span class="status-badge status-<?php echo $faculty['status']; ?>">
                                    <?php echo ucfirst($faculty['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <button class="btn btn-primary" onclick="editProfile()">
                                <i class="fas fa-edit"></i> Edit Profile
                            </button>
                            <button class="btn btn-secondary" onclick="showChangePasswordModal()">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Mobile overlay for sidebar -->
    <div class="mobile-overlay"></div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-key"></i> Change Password</h2>
                <span class="close" onclick="closeChangePasswordModal()">&times;</span>
            </div>
            <form id="changePasswordForm" onsubmit="submitChangePassword(event)">
                <div class="form-group">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" name="currentPassword" required>
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" name="newPassword" required>
                    <small>Password must be at least 8 characters long and contain lowercase, uppercase, and number</small>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeChangePasswordModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Mobile menu toggle
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        });

        // Close sidebar when clicking overlay
        document.querySelector('.mobile-overlay').addEventListener('click', function() {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        });

        // Close modal when clicking outside
        document.getElementById('changePasswordModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeChangePasswordModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeChangePasswordModal();
            }
        });

        // Smooth scrolling to sections
        function scrollToSection(sectionId) {
            const element = document.getElementById(sectionId);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth' });
            }
        }

        // Course management functions
        function viewCourseDetails(courseId) {
            alert('Course details view will be implemented for course ID: ' + courseId);
        }

        function manageStudents(courseId) {
            alert('Student management will be implemented for course ID: ' + courseId);
        }

        // Student management functions
        function viewStudentProfile(enrollmentId) {
            alert('Student profile view will be implemented for enrollment ID: ' + enrollmentId);
        }

        function contactStudent(enrollmentId) {
            alert('Student contact form will be implemented for enrollment ID: ' + enrollmentId);
        }

        // Profile management functions
        function editProfile() {
            alert('Profile editing will be implemented');
        }

        // Change password modal functions
        function showChangePasswordModal() {
            document.getElementById('changePasswordModal').classList.add('show');
            // Clear previous form data and messages
            document.getElementById('changePasswordForm').reset();
            clearMessages();
        }

        function closeChangePasswordModal() {
            document.getElementById('changePasswordModal').classList.remove('show');
        }

        function clearMessages() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => alert.remove());
        }

        function showMessage(message, type) {
            clearMessages();
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            
            const form = document.getElementById('changePasswordForm');
            form.insertBefore(alertDiv, form.firstChild);
        }

        async function submitChangePassword(event) {
            event.preventDefault();
            
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            // Validation
            if (newPassword.length < 8) {
                showMessage('New password must be at least 8 characters long', 'danger');
                return;
            }
            
            // Password strength validation
            if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(newPassword)) {
                showMessage('Password must contain at least one lowercase letter, one uppercase letter, and one number', 'danger');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showMessage('New passwords do not match', 'danger');
                return;
            }
            
            if (currentPassword === newPassword) {
                showMessage('New password must be different from current password', 'danger');
                return;
            }
            
            try {
                const response = await fetch('../change_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        currentPassword: currentPassword,
                        newPassword: newPassword
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('Password changed successfully!', 'success');
                    // Close modal after 2 seconds
                    setTimeout(() => {
                        closeChangePasswordModal();
                    }, 2000);
                } else {
                    showMessage(result.message || 'Failed to change password', 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('An error occurred while changing password', 'danger');
            }
        }
    </script>
</body>
</html>
