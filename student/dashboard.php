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
    header('Location: login.php');
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

// Get pending payments
$pending_payments = getRows("
    SELECT c.name as course_name, sp.amount, sp.payment_date, sp.status
    FROM student_payments sp
    JOIN courses c ON sp.course_id = c.id
    WHERE sp.user_id = ? AND sp.status = 'pending'
    ORDER BY sp.payment_date DESC
", [$user_id]);

// Get total fees paid
$total_paid = getRow("
    SELECT SUM(amount) as total FROM student_payments 
    WHERE user_id = ? AND status = 'completed'
", [$user_id])['total'] ?? 0;

// Get documents
$documents = getRows("
    SELECT * FROM student_documents 
    WHERE user_id = ? 
    ORDER BY uploaded_at DESC
", [$user_id]);

$total_courses = count($enrolled_courses);
$completed_courses = count(array_filter($enrolled_courses, fn($c) => $c['enrollment_status'] === 'completed'));
$pending_documents = count(array_filter($documents, fn($d) => $d['status'] === 'pending'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - GICT Institute</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        /* Custom styles for student dashboard */
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 25px;
            padding: 50px 40px;
            margin-bottom: 40px;
            text-align: center;
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
            position: relative;
            overflow: hidden;
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .welcome-section h1 {
            font-size: 42px;
            font-weight: 700;
            margin: 0 0 20px 0;
            position: relative;
            z-index: 2;
            line-height: 1.2;
        }
        
        .welcome-section p {
            font-size: 18px;
            opacity: 0.9;
            margin: 0;
            position: relative;
            z-index: 2;
            line-height: 1.5;
        }
        
        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(102, 126, 234, 0.05);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 0 20px 0 60px;
            opacity: 0.1;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.15);
            border-color: rgba(102, 126, 234, 0.2);
        }
        
        .stat-card .icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }
        
        .stat-card .icon i {
            font-size: 24px;
            color: white;
        }
        
        .stat-card .number {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
            position: relative;
            z-index: 2;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            z-index: 2;
        }
        
        .stat-card .trend {
            font-size: 16px;
            color: #28a745;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            opacity: 0.9;
            line-height: 1.4;
        }
        
        /* Section Styling */
        .section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 35px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(102, 126, 234, 0.05);
        }
        
        .section:hover {
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.12);
            transform: translateY(-3px);
            border-color: rgba(102, 126, 234, 0.15);
        }
        
        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .section-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .section-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 18px;
            position: relative;
            z-index: 2;
            line-height: 1.3;
        }
        
        .section-header h2 i {
            font-size: 30px;
            opacity: 0.9;
        }
        
        .section-body {
            padding: 35px;
        }
        
        /* Course Cards */
        .course-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid #28a745;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .course-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 0 12px 0 40px;
            opacity: 0.1;
        }
        
        .course-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .course-card h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 20px;
            font-weight: 600;
        }
        
        .course-card p {
            margin: 0 0 15px 0;
            color: #666;
            line-height: 1.6;
        }
        
        .course-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }
        
        .meta-item i {
            color: #667eea;
            width: 16px;
        }
        
        .course-actions {
            display: flex;
            gap: 15px;
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
        
        /* Document Items */
        .document-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        
        .document-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .document-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .document-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .document-details h4 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }
        
        .document-details p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .document-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-approved {
            background: #e8f5e8;
            color: #28a745;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Quick Actions */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .quick-actions-grid .btn {
            padding: 20px;
            text-align: center;
            justify-content: center;
            font-size: 16px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .quick-actions-grid .btn:hover {
            transform: translateY(-5px) scale(1.03);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }
        
        .quick-actions-grid .btn i {
            font-size: 20px;
            margin-right: 12px;
        }
        
        /* Digital ID & Certificates Styles */
        .id-certificates-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .digital-id-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
        }
        
        .id-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .id-header i {
            font-size: 24px;
            opacity: 0.9;
        }
        
        .id-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .id-content {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .id-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }
        
        .id-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .id-photo-placeholder {
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }
        
        .id-details p {
            margin: 8px 0;
            font-size: 14px;
        }
        
        .id-details strong {
            font-weight: 600;
        }
        
        .status-active {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .id-actions {
            display: flex;
            gap: 15px;
        }
        
        .id-actions .btn {
            flex: 1;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .id-actions .btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .certificates-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }
        
        .certificates-section h3 {
            margin: 0 0 25px 0;
            color: #333;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .no-certificates {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .certificates-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .certificate-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #28a745;
        }
        
        .cert-info h4 {
            margin: 0 0 8px 0;
            color: #333;
            font-size: 16px;
        }
        
        .cert-info p {
            margin: 5px 0;
            color: #666;
            font-size: 13px;
        }
        
        .cert-actions .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .id-certificates-grid {
                grid-template-columns: 1fr;
            }
            
            .id-content {
                flex-direction: column;
                text-align: center;
            }
            
            .id-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php 
        $page_title = 'Student Dashboard';
        include 'includes/sidebar.php'; 
        ?>

        <?php include 'includes/topbar.php'; ?>

        <!-- Mobile Overlay -->
        <div class="mobile-overlay" onclick="toggleMobileMenu()"></div>
        
        <!-- Main Content -->
        <main class="admin-content">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Welcome back, <?php echo htmlspecialchars($student['full_name']); ?>! 👋</h1>
            <p>Here's your academic overview and progress summary</p>
        </div>

        <!-- Digital ID & Certificates -->
        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-id-card"></i> Digital ID & Certificates</h2>
            </div>
            <div class="section-body">
                <div class="id-certificates-grid">
                    <div class="digital-id-card">
                        <div class="id-header">
                            <i class="fas fa-id-card"></i>
                            <h3>Digital Student ID</h3>
                        </div>
                        <div class="id-content">
                            <div class="id-photo">
                                <?php if (!empty($student['profile_image']) && filter_var($student['profile_image'], FILTER_VALIDATE_URL)): ?>
                                    <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Student Photo">
                                <?php else: ?>
                                    <div class="id-photo-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="id-details">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
                                <p><strong>ID:</strong> <?php echo $student['id']; ?></p>
                                <p><strong>Username:</strong> <?php echo htmlspecialchars($student['username']); ?></p>
                                <p><strong>Status:</strong> <span class="status-active">Active</span></p>
                            </div>
                        </div>
                                                  <div class="id-actions">
                              <button onclick="downloadID()" class="btn btn-primary">
                                  <i class="fas fa-download"></i> Download ID
                              </button>
                              <a href="view-id.php" class="btn btn-success">
                                  <i class="fas fa-eye"></i> View ID
                              </a>
                          </div>
                    </div>
                    
                    <div class="certificates-section">
                        <h3><i class="fas fa-certificate"></i> Approved Certificates</h3>
                        <?php
                        // Get approved certificates
                        $approved_certificates = getRows("
                            SELECT c.id as course_id, c.name as course_name, se.final_marks, se.completion_date, se.certificate_url
                            FROM student_enrollments se
                            JOIN courses c ON se.course_id = c.id
                            WHERE se.user_id = ? AND se.status = 'completed' AND se.final_marks >= 40
                            ORDER BY se.completion_date DESC
                        ", [$user_id]);
                        
                        if (empty($approved_certificates)):
                        ?>
                            <div class="no-certificates">
                                <i class="fas fa-certificate" style="font-size: 48px; opacity: 0.3; margin-bottom: 15px;"></i>
                                <p>No certificates available yet</p>
                                <small>Complete courses with passing marks to get certificates</small>
                            </div>
                        <?php else: ?>
                            <div class="certificates-list">
                                <?php foreach ($approved_certificates as $cert): ?>
                                    <div class="certificate-item">
                                        <div class="cert-info">
                                            <h4><?php echo htmlspecialchars($cert['course_name']); ?></h4>
                                            <p>Marks: <?php echo $cert['final_marks']; ?>%</p>
                                            <p>Completed: <?php echo date('M d, Y', strtotime($cert['completion_date'])); ?></p>
                                        </div>
                                        <div class="cert-actions">
                                            <a href="download-certificate.php?course_id=<?php echo $cert['course_id']; ?>" class="btn btn-success btn-sm">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            </div>
            <div class="section-body">
                <div class="quick-actions-grid">
                    <a href="documents.php" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Documents
                    </a>
                    <a href="courses.php" class="btn btn-success">
                        <i class="fas fa-book-open"></i> Browse Courses
                    </a>
                    <a href="payments.php" class="btn btn-warning">
                        <i class="fas fa-credit-card"></i> Make Payment
                    </a>
                    <a href="profile.php" class="btn btn-primary">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-book"></i> Enrolled Courses</h3>
                <div class="number"><?php echo $total_courses; ?></div>
                <div class="trend">Active enrollments</div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-check-circle"></i> Completed Courses</h3>
                <div class="number"><?php echo $completed_courses; ?></div>
                <div class="trend">Successfully finished</div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-file-alt"></i> Pending Documents</h3>
                <div class="number"><?php echo $pending_documents; ?></div>
                <div class="trend">Awaiting approval</div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-rupee-sign"></i> Total Paid</h3>
                <div class="number">₹<?php echo number_format($total_paid, 2); ?></div>
                <div class="trend">Fees completed</div>
            </div>
        </div>

        <!-- Enrolled Courses -->
        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-book"></i> My Enrolled Courses</h2>
                <a href="courses.php" class="btn btn-primary">View All</a>
            </div>
            <div class="section-body">
                <?php if (empty($enrolled_courses)): ?>
                    <div class="empty-state">
                        <i class="fas fa-book-open"></i>
                        <h3>No Courses Enrolled</h3>
                        <p>You haven't enrolled in any courses yet.</p>
                        <a href="../courses.php" class="btn btn-primary">Browse Courses</a>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($enrolled_courses, 0, 3) as $course): ?>
                        <div class="course-card <?php echo $course['enrollment_status']; ?>">
                            <div class="course-name">
                                <i class="fas fa-graduation-cap"></i>
                                <?php echo htmlspecialchars($course['name']); ?>
                            </div>
                            <div class="course-details">
                                <div class="course-detail">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><strong>Enrolled:</strong> <?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?></span>
                                </div>
                                <div class="course-detail">
                                    <i class="fas fa-clock"></i>
                                    <span><strong>Duration:</strong> <?php echo htmlspecialchars($course['duration']); ?></span>
                                </div>
                                <div class="course-detail">
                                    <i class="fas fa-rupee-sign"></i>
                                    <span><strong>Fee:</strong> ₹<?php echo number_format($course['fee'], 2); ?></span>
                                </div>
                                <?php if ($course['final_marks']): ?>
                                    <div class="course-detail">
                                        <i class="fas fa-star"></i>
                                        <span><strong>Marks:</strong> <?php echo $course['final_marks']; ?>%</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="course-actions">
                                <span class="status-badge status-<?php echo $course['enrollment_status']; ?>">
                                    <i class="fas fa-<?php echo $course['enrollment_status'] === 'completed' ? 'check-circle' : ($course['enrollment_status'] === 'in_progress' ? 'play-circle' : 'user-graduate'); ?>"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $course['enrollment_status'])); ?>
                                </span>
                                <?php if ($course['enrollment_status'] === 'completed'): ?>
                                    <a href="download-marksheet.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success">
                                        <i class="fas fa-download"></i> Download Marksheet
                                    </a>
                                <?php elseif ($course['enrollment_status'] === 'in_progress'): ?>
                                    <a href="courses.php" class="btn btn-warning">
                                        <i class="fas fa-play"></i> Continue Learning
                                    </a>
                                <?php else: ?>
                                    <a href="courses.php" class="btn btn-primary">
                                        <i class="fas fa-book-open"></i> Start Course
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Documents -->
        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-file-alt"></i> Recent Documents</h2>
                <a href="documents.php" class="btn btn-primary">View All</a>
            </div>
            <div class="section-body">
                <?php if (empty($documents)): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <h3>No Documents Uploaded</h3>
                        <p>You haven't uploaded any documents yet.</p>
                        <a href="documents.php" class="btn btn-primary">Upload Documents</a>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($documents, 0, 5) as $doc): ?>
                        <div class="document-item">
                            <div class="document-info">
                                <div class="document-icon">
                                    <i class="fas fa-<?php echo $doc['document_type'] === 'marksheet' ? 'file-alt' : ($doc['document_type'] === 'aadhaar' ? 'id-card' : 'image'); ?>"></i>
                                </div>
                                <div class="document-details">
                                    <h4><?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?></h4>
                                    <p><i class="fas fa-calendar"></i> Uploaded: <?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></p>
                                </div>
                            </div>
                            <div class="document-actions">
                                <span class="status-badge status-<?php echo $doc['status']; ?>">
                                    <i class="fas fa-<?php echo $doc['status'] === 'approved' ? 'check-circle' : ($doc['status'] === 'rejected' ? 'times-circle' : 'clock'); ?>"></i>
                                    <?php echo ucfirst($doc['status']); ?>
                                </span>
                                <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Payments -->
        <?php if (!empty($pending_payments)): ?>
        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-credit-card"></i> Pending Payments</h2>
                <a href="payments.php" class="btn btn-primary">View All</a>
            </div>
            <div class="section-body">
                <?php foreach ($pending_payments as $payment): ?>
                    <div class="document-item">
                        <div class="document-info">
                            <div class="document-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="document-details">
                                <h4><?php echo htmlspecialchars($payment['course_name']); ?></h4>
                                <p><i class="fas fa-rupee-sign"></i> Amount: ₹<?php echo number_format($payment['amount'], 2); ?></p>
                            </div>
                        </div>
                        <div class="document-actions">
                            <span class="status-badge status-pending">
                                <i class="fas fa-clock"></i> Pending
                            </span>
                            <a href="payments.php" class="btn btn-warning">
                                <i class="fas fa-credit-card"></i> Pay Now
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        </main>
    </div>

    <script>
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
        }
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.admin-sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                sidebar.classList.remove('open');
                document.querySelector('.mobile-overlay').classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        // Close menu on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const sidebar = document.querySelector('.admin-sidebar');
                const overlay = document.querySelector('.mobile-overlay');
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        // Download ID function
        function downloadID() {
            // Open download page in new tab for printing
            window.open('download-id.php', '_blank');
        }
        
        // Generate QR codes when page loads
        document.addEventListener('DOMContentLoaded', function() {
            generateAllQRCodes();
        });
        
        function generateAllQRCodes() {
            const qrContainers = document.querySelectorAll('.qr-code-container');
            qrContainers.forEach(container => {
                const data = container.getAttribute('data-qr');
                if (data) {
                    // Clear the container first
                    container.innerHTML = '';
                    
                    QRCode.toCanvas(container, data, {
                        width: container.offsetWidth - 4, // Account for border
                        height: container.offsetHeight - 4,
                        margin: 2,
                        color: {
                            dark: '#000000',
                            light: '#FFFFFF'
                        },
                        errorCorrectionLevel: 'M'
                    }, function(error, canvas) {
                        if (error) {
                            console.error('QR Code generation failed:', error);
                            // Fallback to text display
                            container.innerHTML = '<div style="color: white; text-align: center; padding: 10px; font-size: 10px; line-height: 1.2;">VERIFY<br>QR</div>';
                        } else {
                            // Clear container and append canvas
                            container.appendChild(canvas);
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
