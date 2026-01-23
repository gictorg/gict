<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

// Initialize session for flash messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get flash messages from session and clear them
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$user = getCurrentUser();

// Check if course_subjects table exists, if not show setup message
$table_exists = getRow("SHOW TABLES LIKE 'course_subjects'");
$needs_setup = !$table_exists;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$needs_setup) {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'add_subject':
                $sub_course_id = intval($_POST['sub_course_id']);
                $semester = intval($_POST['semester']);
                $subject_name = trim($_POST['subject_name']);
                $subject_code = trim($_POST['subject_code'] ?? '');
                $max_marks = intval($_POST['max_marks']);
                $theory_marks = intval($_POST['theory_marks'] ?? $max_marks);
                $practical_marks = intval($_POST['practical_marks'] ?? 0);
                $is_compulsory = isset($_POST['is_compulsory']) ? 1 : 0;
                $credit_hours = floatval($_POST['credit_hours'] ?? 3.0);
                $status = $_POST['status'] ?? 'active';

                if (empty($subject_name) || $sub_course_id <= 0 || $semester <= 0) {
                    throw new Exception("Please fill all required fields correctly.");
                }

                // Verify sub-course exists
                $sub_course_check = getRow("SELECT id, name FROM sub_courses WHERE id = ?", [$sub_course_id]);
                if (!$sub_course_check) {
                    throw new Exception("Invalid sub-course selected.");
                }

                // Check for duplicate subject in same semester
                $duplicate_check = getRow(
                    "SELECT id FROM course_subjects WHERE sub_course_id = ? AND semester = ? AND subject_name = ?",
                    [$sub_course_id, $semester, $subject_name]
                );
                if ($duplicate_check) {
                    throw new Exception("A subject with this name already exists in semester $semester.");
                }

                $sql = "INSERT INTO course_subjects (sub_course_id, semester, subject_name, subject_code, max_marks, theory_marks, practical_marks, is_compulsory, credit_hours, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $result = insertData($sql, [$sub_course_id, $semester, $subject_name, $subject_code, $max_marks, $theory_marks, $practical_marks, $is_compulsory, $credit_hours, $status]);

                if ($result === false) {
                    throw new Exception("Failed to add subject. Please try again.");
                }

                $success_message = "Subject '$subject_name' added successfully to {$sub_course_check['name']} (Semester $semester)!";
                break;

            case 'update_subject':
                $subject_id = intval($_POST['subject_id']);
                $semester = intval($_POST['semester']);
                $subject_name = trim($_POST['subject_name']);
                $subject_code = trim($_POST['subject_code'] ?? '');
                $max_marks = intval($_POST['max_marks']);
                $theory_marks = intval($_POST['theory_marks'] ?? $max_marks);
                $practical_marks = intval($_POST['practical_marks'] ?? 0);
                $is_compulsory = isset($_POST['is_compulsory']) ? 1 : 0;
                $credit_hours = floatval($_POST['credit_hours'] ?? 3.0);
                $status = $_POST['status'] ?? 'active';

                if (empty($subject_name) || $semester <= 0) {
                    throw new Exception("Please fill all required fields correctly.");
                }

                // Verify subject exists
                $subject_check = getRow("SELECT id FROM course_subjects WHERE id = ?", [$subject_id]);
                if (!$subject_check) {
                    throw new Exception("Invalid subject selected.");
                }

                $sql = "UPDATE course_subjects SET semester = ?, subject_name = ?, subject_code = ?, max_marks = ?, 
                        theory_marks = ?, practical_marks = ?, is_compulsory = ?, credit_hours = ?, status = ?, updated_at = NOW() 
                        WHERE id = ?";
                $result = updateData($sql, [$semester, $subject_name, $subject_code, $max_marks, $theory_marks, $practical_marks, $is_compulsory, $credit_hours, $status, $subject_id]);

                if ($result === false) {
                    throw new Exception("Failed to update subject. Please try again.");
                }

                $success_message = "Subject '$subject_name' updated successfully!";
                break;

            case 'delete_subject':
                $subject_id = intval($_POST['subject_id']);

                // Check if subject has marks entered
                $marks_check = getRow("SELECT COUNT(*) as count FROM student_marks WHERE subject_id = ?", [$subject_id]);
                if ($marks_check && $marks_check['count'] > 0) {
                    throw new Exception("Cannot delete subject. It has {$marks_check['count']} marks entries. Please delete marks first.");
                }

                // Verify subject exists
                $subject_check = getRow("SELECT id, subject_name FROM course_subjects WHERE id = ?", [$subject_id]);
                if (!$subject_check) {
                    throw new Exception("Invalid subject selected.");
                }

                $sql = "DELETE FROM course_subjects WHERE id = ?";
                $result = deleteData($sql, [$subject_id]);

                if ($result === false) {
                    throw new Exception("Failed to delete subject. Please try again.");
                }

                $success_message = "Subject '{$subject_check['subject_name']}' deleted successfully!";
                break;

            case 'toggle_status':
                $subject_id = intval($_POST['subject_id']);
                $new_status = $_POST['new_status'];

                $sql = "UPDATE course_subjects SET status = ?, updated_at = NOW() WHERE id = ?";
                $result = updateData($sql, [$new_status, $subject_id]);

                if ($result === false) {
                    throw new Exception("Failed to update subject status. Please try again.");
                }

                $success_message = "Subject status updated successfully!";
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }

    // Store messages in session and redirect to prevent form resubmission
    if (isset($success_message)) {
        $_SESSION['success_message'] = $success_message;
    }
    if (isset($error_message)) {
        $_SESSION['error_message'] = $error_message;
    }
    
    // Redirect back with sub_course_id if set
    $redirect_url = 'subjects.php';
    if (isset($_POST['sub_course_id']) && intval($_POST['sub_course_id']) > 0) {
        $redirect_url .= '?sub_course_id=' . intval($_POST['sub_course_id']);
    }
    header('Location: ' . $redirect_url);
    exit;
}

// Get selected sub-course
$selected_sub_course_id = intval($_GET['sub_course_id'] ?? 0);

// Get all courses with their sub-courses for the sidebar
$courses_with_sub_courses = getRows("
    SELECT c.id as course_id, c.name as course_name, 
           sc.id as sub_course_id, sc.name as sub_course_name, sc.duration,
           (SELECT COUNT(*) FROM course_subjects cs WHERE cs.sub_course_id = sc.id) as subjects_count
    FROM courses c
    JOIN sub_courses sc ON c.id = sc.course_id
    WHERE c.status = 'active' AND sc.status = 'active'
    ORDER BY c.name, sc.name
");

// Group sub-courses by course
$courses_grouped = [];
foreach ($courses_with_sub_courses as $row) {
    $course_id = $row['course_id'];
    if (!isset($courses_grouped[$course_id])) {
        $courses_grouped[$course_id] = [
            'name' => $row['course_name'],
            'sub_courses' => []
        ];
    }
    $courses_grouped[$course_id]['sub_courses'][] = [
        'id' => $row['sub_course_id'],
        'name' => $row['sub_course_name'],
        'duration' => $row['duration'],
        'subjects_count' => $row['subjects_count']
    ];
}

// Get selected sub-course details
$selected_sub_course = null;
$subjects_by_semester = [];
$max_semesters = 1;

if ($selected_sub_course_id > 0 && !$needs_setup) {
    $selected_sub_course = getRow("
        SELECT sc.*, c.name as course_name 
        FROM sub_courses sc 
        JOIN courses c ON sc.course_id = c.id 
        WHERE sc.id = ?
    ", [$selected_sub_course_id]);

    if ($selected_sub_course) {
        // Get subjects grouped by semester
        $subjects = getRows("
            SELECT * FROM course_subjects 
            WHERE sub_course_id = ? 
            ORDER BY semester, id
        ", [$selected_sub_course_id]);

        foreach ($subjects as $subject) {
            $sem = $subject['semester'];
            if (!isset($subjects_by_semester[$sem])) {
                $subjects_by_semester[$sem] = [];
            }
            $subjects_by_semester[$sem][] = $subject;
            if ($sem > $max_semesters) {
                $max_semesters = $sem;
            }
        }

        // Default to at least 1 semester display if course has no subjects yet
        if (empty($subjects_by_semester)) {
            // Estimate semesters based on duration
            $duration = strtolower($selected_sub_course['duration']);
            if (strpos($duration, '12') !== false || strpos($duration, 'year') !== false) {
                $max_semesters = 4;
            } elseif (strpos($duration, '6') !== false) {
                $max_semesters = 2;
            } elseif (strpos($duration, '9') !== false) {
                $max_semesters = 3;
            } else {
                $max_semesters = 1;
            }
        }
    }
}

// Get subject for editing
$edit_subject = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit']) && !$needs_setup) {
    $subject_id = intval($_GET['edit']);
    $edit_subject = getRow("
        SELECT cs.*, sc.name as sub_course_name 
        FROM course_subjects cs
        JOIN sub_courses sc ON cs.sub_course_id = sc.id
        WHERE cs.id = ?
    ", [$subject_id]);
    
    if ($edit_subject) {
        $selected_sub_course_id = $edit_subject['sub_course_id'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management - GICT Admin</title>
    <link rel="icon" type="image/png" href="../logo.png">
    <link rel="shortcut icon" type="image/png" href="../logo.png">
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

        /* profile-card-mini styles are now globally handled in admin-dashboard.css */

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
            background: rgba(255, 255, 255, 0.09);
        }

        .admin-content {
            flex: 1;
            margin-left: 260px;
            margin-top: 60px;
            padding: 20px;
        }

        .admin-topbar {
            background: #0f6fb1;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 18px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
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

        .breadcrumbs a {
            color: #ffffff;
            text-decoration: none;
        }

        .breadcrumbs a:hover {
            text-decoration: underline;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header h1 {
            color: #333;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #666;
            font-size: 14px;
        }

        .subjects-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
        }

        .course-selector {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            overflow: hidden;
        }

        .course-selector-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }

        .course-selector-header h3 {
            margin: 0;
            font-size: 16px;
        }

        .course-group {
            border-bottom: 1px solid #e9ecef;
        }

        .course-group-header {
            padding: 15px;
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .course-group-header:hover {
            background: #e9ecef;
        }

        .course-group-header i {
            transition: transform 0.3s ease;
        }

        .course-group.expanded .course-group-header i {
            transform: rotate(180deg);
        }

        .sub-course-list {
            display: none;
            background: white;
        }

        .course-group.expanded .sub-course-list {
            display: block;
        }

        .sub-course-item {
            padding: 12px 15px 12px 25px;
            border-bottom: 1px solid #f1f3f4;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sub-course-item:hover {
            background: #f8f9fa;
        }

        .sub-course-item.active {
            background: #e3f2fd;
            border-left: 3px solid #667eea;
        }

        .sub-course-item .name {
            font-size: 14px;
            color: #333;
        }

        .sub-course-item .badge {
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
        }

        .subjects-content {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            overflow: hidden;
        }

        .subjects-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .subjects-header h2 {
            margin: 0;
            font-size: 18px;
        }

        .add-subject-btn {
            background: white;
            color: #667eea;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .add-subject-btn:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }

        .semester-tabs {
            display: flex;
            border-bottom: 1px solid #e9ecef;
            overflow-x: auto;
        }

        .semester-tab {
            padding: 15px 25px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            color: #666;
            white-space: nowrap;
            transition: all 0.3s ease;
        }

        .semester-tab:hover {
            color: #667eea;
            background: #f8f9fa;
        }

        .semester-tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .add-semester-tab {
            padding: 15px;
            cursor: pointer;
            color: #667eea;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .add-semester-tab:hover {
            background: #f8f9fa;
        }

        .subjects-body {
            padding: 20px;
        }

        .semester-content {
            display: none;
        }

        .semester-content.active {
            display: block;
        }

        .subjects-table {
            width: 100%;
            border-collapse: collapse;
        }

        .subjects-table th,
        .subjects-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .subjects-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            font-size: 13px;
            text-transform: uppercase;
        }

        .subjects-table tr:hover {
            background: #f8f9fa;
        }

        .subject-code {
            color: #667eea;
            font-weight: 600;
        }

        .marks-split {
            font-size: 12px;
            color: #666;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s ease;
        }

        .btn-edit {
            background: #17a2b8;
            color: white;
        }

        .btn-edit:hover {
            background: #138496;
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

        .alert-info {
            background: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
        }

        /* Modal Styles */
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

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
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

        .setup-notice {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 16px;
            text-align: center;
        }

        .setup-notice h2 {
            margin: 0 0 15px 0;
        }

        .setup-notice p {
            margin: 0 0 20px 0;
            opacity: 0.9;
        }

        .setup-notice code {
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
        }

        @media (max-width: 1024px) {
            .subjects-layout {
                grid-template-columns: 1fr;
            }

            .course-selector {
                max-height: 300px;
                overflow-y: auto;
            }
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
                <li><a href="courses.php"><i class="fas fa-graduation-cap"></i> Courses</a></li>
                <li><a class="active" href="subjects.php"><i class="fas fa-book-open"></i> Subjects</a></li>
                <li><a href="marks-management.php"><i class="fas fa-chart-line"></i> Marks Management</a></li>
                <li><a href="certificate-management.php"><i class="fas fa-certificate"></i> Certificate Management</a></li>
                <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="inquiries.php"><i class="fas fa-question-circle"></i> Course Inquiries</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="menu-toggle"><i class="fas fa-bars"></i></button>
                <div class="breadcrumbs">
                    <a href="../index.php" class="topbar-home-link"><i class="fas fa-home"></i> Home</a>
                    <span style="opacity:.7; margin: 0 6px;">/</span>
                    <a href="../dashboard.php">Dashboard</a>
                    <span style="opacity:.7; margin: 0 6px;">/</span>
                    <a href="courses.php">Courses</a>
                    <span style="opacity:.7; margin: 0 6px;">/</span>
                    <span>Subjects</span>
                </div>
            </div>
        </header>

        <main class="admin-content">
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-book-open"></i> Subject Management</h1>
                    <p>Manage subjects for sub-courses organized by semesters</p>
                </div>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" id="successAlert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" id="errorAlert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($needs_setup): ?>
                <div class="setup-notice">
                    <i class="fas fa-database" style="font-size: 48px; margin-bottom: 20px;"></i>
                    <h2>Database Setup Required</h2>
                    <p>The <code>course_subjects</code> table needs to be created. Please run the following SQL file:</p>
                    <p><code>database_subjects_semester.sql</code></p>
                    <p style="margin-top: 20px; font-size: 14px;">Or run the SQL migration in your phpMyAdmin/MySQL client.</p>
                </div>
            <?php else: ?>
                <div class="subjects-layout">
                    <!-- Course/Sub-Course Selector -->
                    <div class="course-selector">
                        <div class="course-selector-header">
                            <h3><i class="fas fa-folder-tree"></i> Select Sub-Course</h3>
                        </div>
                        <?php if (empty($courses_grouped)): ?>
                            <div class="empty-state" style="padding: 30px;">
                                <p>No courses available. <a href="courses.php">Add courses first</a>.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($courses_grouped as $course_id => $course): ?>
                                <div class="course-group <?php echo $selected_sub_course && in_array($selected_sub_course_id, array_column($course['sub_courses'], 'id')) ? 'expanded' : ''; ?>">
                                    <div class="course-group-header" onclick="toggleCourseGroup(this)">
                                        <span><i class="fas fa-folder"></i> <?php echo htmlspecialchars($course['name']); ?></span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="sub-course-list">
                                        <?php foreach ($course['sub_courses'] as $sub_course): ?>
                                            <div class="sub-course-item <?php echo $selected_sub_course_id == $sub_course['id'] ? 'active' : ''; ?>"
                                                onclick="selectSubCourse(<?php echo $sub_course['id']; ?>)">
                                                <span class="name"><?php echo htmlspecialchars($sub_course['name']); ?></span>
                                                <span class="badge"><?php echo $sub_course['subjects_count']; ?> subjects</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Subjects Content -->
                    <div class="subjects-content">
                        <?php if (!$selected_sub_course): ?>
                            <div class="empty-state" style="padding: 80px 20px;">
                                <i class="fas fa-hand-point-left"></i>
                                <h3>Select a Sub-Course</h3>
                                <p>Choose a sub-course from the left panel to manage its subjects.</p>
                            </div>
                        <?php else: ?>
                            <div class="subjects-header">
                                <div>
                                    <h2><?php echo htmlspecialchars($selected_sub_course['name']); ?></h2>
                                    <small><?php echo htmlspecialchars($selected_sub_course['course_name']); ?> • <?php echo htmlspecialchars($selected_sub_course['duration']); ?></small>
                                </div>
                                <button class="add-subject-btn" onclick="openAddSubjectModal()">
                                    <i class="fas fa-plus"></i> Add Subject
                                </button>
                            </div>

                            <!-- Semester Tabs -->
                            <div class="semester-tabs">
                                <?php for ($i = 1; $i <= max($max_semesters, 1); $i++): ?>
                                    <div class="semester-tab <?php echo $i === 1 ? 'active' : ''; ?>" 
                                         onclick="switchSemester(<?php echo $i; ?>)" data-semester="<?php echo $i; ?>">
                                        <i class="fas fa-calendar-alt"></i> Semester <?php echo $i; ?>
                                        <?php if (isset($subjects_by_semester[$i])): ?>
                                            <span class="badge" style="background: rgba(102, 126, 234, 0.2); color: #667eea; font-size: 11px; padding: 2px 6px; border-radius: 10px; margin-left: 5px;">
                                                <?php echo count($subjects_by_semester[$i]); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>
                                <div class="add-semester-tab" onclick="addNewSemester()">
                                    <i class="fas fa-plus"></i>
                                </div>
                            </div>

                            <!-- Subjects Body -->
                            <div class="subjects-body">
                                <?php for ($i = 1; $i <= max($max_semesters, 1); $i++): ?>
                                    <div class="semester-content <?php echo $i === 1 ? 'active' : ''; ?>" id="semester-<?php echo $i; ?>">
                                        <?php if (empty($subjects_by_semester[$i])): ?>
                                            <div class="empty-state">
                                                <i class="fas fa-book"></i>
                                                <h3>No Subjects in Semester <?php echo $i; ?></h3>
                                                <p>Click "Add Subject" to add subjects to this semester.</p>
                                            </div>
                                        <?php else: ?>
                                            <table class="subjects-table">
                                                <thead>
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Subject Name</th>
                                                        <th>Max Marks</th>
                                                        <th>Theory/Practical</th>
                                                        <th>Credits</th>
                                                        <th>Type</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($subjects_by_semester[$i] as $subject): ?>
                                                        <tr>
                                                            <td class="subject-code"><?php echo htmlspecialchars($subject['subject_code'] ?? '-'); ?></td>
                                                            <td><strong><?php echo htmlspecialchars($subject['subject_name']); ?></strong></td>
                                                            <td><?php echo $subject['max_marks']; ?></td>
                                                            <td class="marks-split">
                                                                <?php echo ($subject['theory_marks'] ?? 0); ?> / <?php echo ($subject['practical_marks'] ?? 0); ?>
                                                            </td>
                                                            <td><?php echo $subject['credit_hours'] ?? '3.0'; ?></td>
                                                            <td>
                                                                <?php if ($subject['is_compulsory']): ?>
                                                                    <span style="color: #28a745;"><i class="fas fa-check-circle"></i> Compulsory</span>
                                                                <?php else: ?>
                                                                    <span style="color: #6c757d;"><i class="fas fa-circle"></i> Elective</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="status-badge <?php echo $subject['status'] ?? 'active'; ?>">
                                                                    <?php echo ucfirst($subject['status'] ?? 'active'); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button class="btn-small btn-edit" onclick="editSubject(<?php echo $subject['id']; ?>)">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button class="btn-small btn-delete" onclick="deleteSubject(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars(addslashes($subject['subject_name'])); ?>')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Add/Edit Subject Modal -->
    <div id="subjectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-<?php echo isset($edit_subject) ? 'edit' : 'plus'; ?>"></i>
                    <?php echo isset($edit_subject) ? 'Edit Subject' : 'Add New Subject'; ?>
                </h2>
                <span class="close" onclick="closeSubjectModal()">&times;</span>
            </div>

            <form method="POST" action="subjects.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="<?php echo isset($edit_subject) ? 'update_subject' : 'add_subject'; ?>">
                    <input type="hidden" name="sub_course_id" value="<?php echo $selected_sub_course_id; ?>">
                    <?php if (isset($edit_subject)): ?>
                        <input type="hidden" name="subject_id" value="<?php echo $edit_subject['id']; ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="subject_name">Subject Name *</label>
                            <input type="text" id="subject_name" name="subject_name" required
                                value="<?php echo isset($edit_subject) ? htmlspecialchars($edit_subject['subject_name']) : ''; ?>"
                                placeholder="e.g., Computer Fundamentals">
                        </div>
                        <div class="form-group">
                            <label for="subject_code">Subject Code</label>
                            <input type="text" id="subject_code" name="subject_code"
                                value="<?php echo isset($edit_subject) ? htmlspecialchars($edit_subject['subject_code'] ?? '') : ''; ?>"
                                placeholder="e.g., CS101">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="semester">Semester *</label>
                            <select id="semester" name="semester" required>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo (isset($edit_subject) && $edit_subject['semester'] == $i) ? 'selected' : ''; ?>>
                                        Semester <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="max_marks">Maximum Marks *</label>
                            <input type="number" id="max_marks" name="max_marks" required min="1"
                                value="<?php echo isset($edit_subject) ? $edit_subject['max_marks'] : '100'; ?>"
                                placeholder="100">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="theory_marks">Theory Marks</label>
                            <input type="number" id="theory_marks" name="theory_marks" min="0"
                                value="<?php echo isset($edit_subject) ? ($edit_subject['theory_marks'] ?? $edit_subject['max_marks']) : '70'; ?>"
                                placeholder="70">
                        </div>
                        <div class="form-group">
                            <label for="practical_marks">Practical Marks</label>
                            <input type="number" id="practical_marks" name="practical_marks" min="0"
                                value="<?php echo isset($edit_subject) ? ($edit_subject['practical_marks'] ?? 0) : '30'; ?>"
                                placeholder="30">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="credit_hours">Credit Hours</label>
                            <input type="number" id="credit_hours" name="credit_hours" step="0.5" min="0.5" max="10"
                                value="<?php echo isset($edit_subject) ? ($edit_subject['credit_hours'] ?? '3.0') : '3.0'; ?>"
                                placeholder="3.0">
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo (isset($edit_subject) && ($edit_subject['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($edit_subject) && ($edit_subject['status'] ?? 'active') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_compulsory" name="is_compulsory" 
                                <?php echo (!isset($edit_subject) || $edit_subject['is_compulsory']) ? 'checked' : ''; ?>>
                            <label for="is_compulsory" style="margin-bottom: 0;">Compulsory Subject</label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeSubjectModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-<?php echo isset($edit_subject) ? 'save' : 'plus'; ?>"></i>
                        <?php echo isset($edit_subject) ? 'Update Subject' : 'Add Subject'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/mobile-menu.js"></script>
    <script>
        // Open modal if editing
        <?php if (isset($edit_subject)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                openSubjectModal();
            });
        <?php endif; ?>

        function toggleMobileMenu() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('open');
        }

        function toggleCourseGroup(header) {
            const group = header.parentElement;
            group.classList.toggle('expanded');
        }

        function selectSubCourse(subCourseId) {
            window.location.href = `subjects.php?sub_course_id=${subCourseId}`;
        }

        function switchSemester(semester) {
            // Update tabs
            document.querySelectorAll('.semester-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`.semester-tab[data-semester="${semester}"]`).classList.add('active');

            // Update content
            document.querySelectorAll('.semester-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`semester-${semester}`).classList.add('active');
        }

        function addNewSemester() {
            const currentMax = document.querySelectorAll('.semester-tab:not(.add-semester-tab)').length;
            const newSemester = currentMax + 1;
            
            if (newSemester > 8) {
                alert('Maximum 8 semesters allowed.');
                return;
            }

            // For now, just open modal with new semester selected
            document.getElementById('semester').value = newSemester;
            openSubjectModal();
        }

        function openSubjectModal() {
            document.getElementById('subjectModal').style.display = 'block';
        }

        function openAddSubjectModal() {
            // Reset form
            const form = document.querySelector('#subjectModal form');
            form.reset();
            form.action.value = 'add_subject';
            
            // Set default values
            document.getElementById('max_marks').value = '100';
            document.getElementById('theory_marks').value = '70';
            document.getElementById('practical_marks').value = '30';
            document.getElementById('credit_hours').value = '3.0';
            document.getElementById('is_compulsory').checked = true;
            
            // Set current active semester
            const activeTab = document.querySelector('.semester-tab.active');
            if (activeTab) {
                document.getElementById('semester').value = activeTab.dataset.semester;
            }
            
            openSubjectModal();
        }

        function closeSubjectModal() {
            document.getElementById('subjectModal').style.display = 'none';
            // Redirect to clear edit mode
            if (window.location.search.includes('edit=')) {
                window.location.href = `subjects.php?sub_course_id=<?php echo $selected_sub_course_id; ?>`;
            }
        }

        function editSubject(subjectId) {
            window.location.href = `subjects.php?sub_course_id=<?php echo $selected_sub_course_id; ?>&edit=${subjectId}`;
        }

        function deleteSubject(subjectId, subjectName) {
            if (confirm(`Are you sure you want to delete subject "${subjectName}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'subjects.php';

                const inputs = [
                    { name: 'action', value: 'delete_subject' },
                    { name: 'subject_id', value: subjectId },
                    { name: 'sub_course_id', value: '<?php echo $selected_sub_course_id; ?>' }
                ];

                inputs.forEach(input => {
                    const el = document.createElement('input');
                    el.type = 'hidden';
                    el.name = input.name;
                    el.value = input.value;
                    form.appendChild(el);
                });

                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('subjectModal');
            if (event.target === modal) {
                closeSubjectModal();
            }
        }

        // Auto-dismiss alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
