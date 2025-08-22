<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: login.php');
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
    <style>
        /* Main Layout - Using CSS Grid like admin dashboard */
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            background: #f3f5f9;
        }
        
        .admin-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            grid-template-rows: 60px calc(100vh - 60px);
            grid-template-areas:
                "sidebar topbar"
                "sidebar content";
            height: 100vh;
        }
        
        .student-content {
            grid-area: content;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            overflow-y: auto;
        }
        
        /* Welcome Section */
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
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .welcome-section h1 {
            font-size: 42px;
            margin-bottom: 20px;
            font-weight: 800;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 2;
            line-height: 1.2;
        }
        
        .welcome-section p {
            font-size: 24px;
            opacity: 0.95;
            margin: 0;
            font-weight: 500;
            position: relative;
            z-index: 2;
            line-height: 1.4;
        }
        
        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }
        
        .stat-card {
            background: white;
            padding: 35px 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-left: 6px solid #667eea;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 0 20px 0 80px;
            opacity: 0.08;
        }
        
        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.15);
            border-color: rgba(102, 126, 234, 0.3);
        }
        
        .stat-card h3 {
            margin: 0 0 25px 0;
            color: #495057;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            line-height: 1.3;
        }
        
        .stat-card h3 i {
            color: #667eea;
            font-size: 22px;
        }
        
        .stat-card .number {
            font-size: 48px;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
            line-height: 1;
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
        
        .course-card.completed {
            border-left-color: #28a745;
        }
        
        .course-card.in_progress {
            border-left-color: #ffc107;
        }
        
        .course-card.enrolled {
            border-left-color: #17a2b8;
        }
        
        .course-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #495057;
            line-height: 1.3;
        }
        
        .course-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .course-detail {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #6c757d;
            font-size: 16px;
            line-height: 1.4;
        }
        
        .course-detail i {
            width: 24px;
            color: #667eea;
            font-size: 18px;
        }
        
        .course-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
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
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
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
            transition: all 0.3s ease;
            border-left: 4px solid #667eea;
        }
        
        .document-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .document-info {
            display: flex;
            align-items: center;
            gap: 20px;
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
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .document-details h4 {
            margin: 0 0 10px 0;
            color: #495057;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.3;
        }
        
        .document-details p {
            margin: 0;
            color: #6c757d;
            font-size: 16px;
            line-height: 1.4;
        }
        
        .document-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 8px 18px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
        }
        
        .status-approved {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .status-rejected {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        
        .status-enrolled {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
        }
        
        .status-in_progress {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
        }
        
        .status-completed {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 25px;
            color: #dee2e6;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 24px;
            font-weight: 600;
        }
        
        .empty-state p {
            margin: 0 0 25px 0;
            font-size: 16px;
            color: #6c757d;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-layout {
                grid-template-columns: 1fr;
                grid-template-rows: 60px 1fr;
                grid-template-areas:
                    "topbar"
                    "content";
            }
            
            .admin-sidebar {
                position: fixed;
                left: -260px;
                top: 0;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease;
                background: var(--sidebar-bg);
            }
            
            .admin-sidebar.open {
                left: 0;
            }
            
            .student-content {
                padding: 20px;
            }
            
            .welcome-section {
                padding: 30px 20px;
            }
            
            .welcome-section h1 {
                font-size: 24px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .course-details {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .document-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .document-actions {
                width: 100%;
                justify-content: flex-start;
            }
            
            .section-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
            
            /* Mobile Menu Toggle Button */
            .menu-toggle {
                display: block !important;
            }
            
            /* Mobile Overlay */
            .mobile-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }
            
            .mobile-overlay.active {
                display: block;
            }
        }
        
        /* Hide menu toggle on desktop */
        @media (min-width: 769px) {
            .menu-toggle {
                display: none !important;
            }
        }
        
        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Enhanced Visual Effects */
        .stat-card .number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .course-card .course-name i {
            color: #667eea;
            margin-right: 10px;
        }
        
        .document-item .document-icon i {
            font-size: 18px;
        }
        
        /* CSS Variables */
        :root {
            --primary: #0f6fb1;
            --sidebar-bg: #1f2d3d;
            --sidebar-text: #e9eef3;
            --panel-bg: #ffffff;
            --muted: #6b7280;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --shadow: 0 8px 20px rgba(0,0,0,0.08);
        }
        
        /* Enhanced Sidebar Styling */
        .admin-sidebar {
            grid-area: sidebar;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            padding: 18px 14px;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header {
            background: var(--primary);
            padding: 20px 16px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .sidebar-header h3 {
            color: white;
            margin: 10px 0 0 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .sidebar-nav li a {
            color: #ecf0f1;
            padding: 15px 20px;
            border-radius: 0;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .sidebar-nav li a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: translateX(5px);
            border-left-color: #f39c12;
            color: white;
        }
        
        .sidebar-nav li.active a {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            border-left-color: #f39c12;
            color: white;
        }
        
        .sidebar-nav li a i {
            width: 20px;
            margin-right: 12px;
            font-size: 16px;
        }
        
        /* Enhanced Topbar */
        .admin-topbar {
            grid-area: topbar;
            background: var(--primary, #0f6fb1);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 18px;
            box-shadow: var(--shadow, 0 8px 20px rgba(0,0,0,0.08));
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .menu-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .menu-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }
        
        .breadcrumb {
            color: white;
            font-size: 18px;
            font-weight: 600;
        }
        
        .user-chip {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 8px 16px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
        }
        
        .user-chip:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.4);
        }
        
        .user-chip span {
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        .user-avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Smooth Transitions */
        * {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Enhanced Empty States */
        .empty-state .btn {
            margin-top: 15px;
        }
        
        /* Course Status Indicators */
        .course-card.completed::before {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        .course-card.in_progress::before {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }
        
        .course-card.enrolled::before {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
        }
        
        /* Quick Actions Styling */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        
        .quick-actions-grid .btn {
            height: 80px;
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            border: none;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .quick-actions-grid .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }
        
        .quick-actions-grid .btn:hover::before {
            left: 100%;
        }
        
        .quick-actions-grid .btn:hover {
            transform: translateY(-5px) scale(1.03);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }
        
        .quick-actions-grid .btn i {
            font-size: 20px;
            margin-right: 12px;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="GICT Logo" class="logo" style="width: 36px; height: 36px; border-radius: 6px; object-fit: cover;">
                <h3 style="margin: 10px 0 0 0; font-size: 18px; font-weight: 600;">Student Portal</h3>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="active"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="courses.php"><i class="fas fa-book"></i> My Courses</a></li>
                    <li><a href="documents.php"><i class="fas fa-file-alt"></i> Documents</a></li>
                    <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <!-- Topbar -->
        <div class="admin-topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="breadcrumb">
                    <span>Student Dashboard</span>
                </div>
            </div>
            <div class="topbar-right">
                <div class="user-chip">
                    <?php if (!empty($student['profile_image']) && filter_var($student['profile_image'], FILTER_VALIDATE_URL)): ?>
                        <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile" class="user-avatar">
                    <?php else: ?>
                        <div class="user-avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($student['full_name']); ?></span>
                </div>
            </div>
        </div>

        <!-- Mobile Overlay -->
        <div class="mobile-overlay" onclick="toggleMobileMenu()"></div>
        
        <!-- Main Content -->
        <div class="student-content">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Welcome back, <?php echo htmlspecialchars($student['full_name']); ?>! 👋</h1>
            <p>Here's your academic overview and progress summary</p>
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
        </div>
    </div>
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
    </script>
</body>
</html>
