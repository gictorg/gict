<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

$user = getCurrentUser();

// Get enrollment ID and semester from URL
$enrollment_id = intval($_GET['enrollment_id'] ?? 0);
$selected_semester = intval($_GET['semester'] ?? 1);

// Initialize session for flash messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle flash messages
$success_message = $_SESSION['success_message'] ?? ($_GET['success'] ?? null);
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle marks submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $current_enrollment_id = intval($_POST['enrollment_id'] ?? 0);
    $current_semester = intval($_POST['semester'] ?? 1);

    try {
        switch ($action) {
            case 'submit_marks':
                $enrollment_id = intval($_POST['enrollment_id']);
                $semester = intval($_POST['semester']);
                $checked_by = !empty($_POST['checked_by']) ? intval($_POST['checked_by']) : null;
                $marks_data = $_POST['marks'] ?? [];

                if (!$checked_by) {
                    throw new Exception("Please select a faculty in 'Checked By' before saving marks.");
                }

                // Get enrollment details
                $enrollment = getRow("
                    SELECT se.*, sc.name as sub_course_name, c.name as course_name, u.full_name as student_name
                    FROM student_enrollments se
                    JOIN sub_courses sc ON se.sub_course_id = sc.id
                    JOIN courses c ON sc.course_id = c.id
                    JOIN users u ON se.user_id = u.id
                    WHERE se.id = ? AND se.status IN ('enrolled', 'completed')
                ", [$enrollment_id]);

                if (!$enrollment) {
                    throw new Exception("Enrollment not found or not eligible for marks entry.");
                }

                // Generate marksheet number automatically ONLY on Semester 1 entry if not exists
                if ($semester == 1 && empty($enrollment['marksheet_no'])) {
                    $marksheet_no = generateUniqueNumber(12);
                    updateData("UPDATE student_enrollments SET marksheet_no = ? WHERE id = ?", [$marksheet_no, $enrollment_id]);
                    $enrollment['marksheet_no'] = $marksheet_no;
                }

                // Process marks for each subject
                foreach ($marks_data as $subject_id => $marks_info) {
                    $subject = getRow("SELECT * FROM course_subjects WHERE id = ?", [$subject_id]);
                    if (!$subject)
                        continue;

                    $theory_obtained = min(intval($marks_info['theory'] ?? 0), $subject['theory_marks']);
                    $practical_obtained = min(intval($marks_info['practical'] ?? 0), $subject['practical_marks']);
                    $total_obtained = $theory_obtained + $practical_obtained;
                    $remarks = $marks_info['remarks'] ?? '';
                    $max_marks = $subject['max_marks'];

                    // Calculate grade based on marks
                    $percentage = ($total_obtained / $max_marks) * 100;
                    $grade = '';
                    if ($percentage >= 90)
                        $grade = 'A+';
                    elseif ($percentage >= 80)
                        $grade = 'A';
                    elseif ($percentage >= 70)
                        $grade = 'B+';
                    elseif ($percentage >= 60)
                        $grade = 'B';
                    elseif ($percentage >= 50)
                        $grade = 'C';
                    elseif ($percentage >= 40)
                        $grade = 'D';
                    else
                        $grade = 'F';

                    // Insert or update marks using the new table structure
                    $marks_sql = "INSERT INTO student_marks (enrollment_id, subject_id, semester, theory_marks, practical_marks, total_marks, grade, remarks, checked_by) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) 
                                 ON DUPLICATE KEY UPDATE 
                                 theory_marks = VALUES(theory_marks),
                                 practical_marks = VALUES(practical_marks),
                                 total_marks = VALUES(total_marks), 
                                 grade = VALUES(grade), 
                                 remarks = VALUES(remarks),
                                 checked_by = VALUES(checked_by),
                                 updated_at = NOW()";

                    updateData($marks_sql, [$enrollment_id, $subject_id, $semester, $theory_obtained, $practical_obtained, $total_obtained, $grade, $remarks, $checked_by]);
                }

                $success_message = "Marks submitted successfully for Semester $semester.";
                $_SESSION['success_message'] = $success_message;

                header("Location: marks-management.php?enrollment_id=$current_enrollment_id&semester=$current_semester");
                exit;
                break;

            case 'complete_course':
                $enrollment_id = intval($_POST['enrollment_id']);

                // Get enrollment details
                $enrollment = getRow("
                    SELECT se.*, sc.name as sub_course_name, u.full_name as student_name, sc.number_of_semesters
                    FROM student_enrollments se
                    JOIN sub_courses sc ON se.sub_course_id = sc.id
                    JOIN users u ON se.user_id = u.id
                    WHERE se.id = ? AND se.status = 'enrolled'
                ", [$enrollment_id]);

                if (!$enrollment) {
                    throw new Exception("Enrollment not found.");
                }

                // Check if all subjects for all semesters have marks
                $total_subjects = getRow("
                    SELECT COUNT(*) as count FROM course_subjects WHERE sub_course_id = ?
                ", [$enrollment['sub_course_id']])['count'];

                $marks_count = getRow("
                    SELECT COUNT(*) as count FROM student_marks WHERE enrollment_id = ?
                ", [$enrollment_id])['count'];

                if ($marks_count < $total_subjects) {
                    throw new Exception("Cannot complete course. Marks for all subjects across all semesters must be entered.");
                }

                $completion_sql = "UPDATE student_enrollments SET status = 'completed', completion_date = CURDATE() WHERE id = ?";
                if (updateData($completion_sql, [$enrollment_id])) {
                    $_SESSION['success_message'] = "Course completed successfully for {$enrollment['student_name']}! You can now generate the certificate from Certificate Management.";
                } else {
                    throw new Exception("Failed to update status.");
                }

                header("Location: marks-management.php?enrollment_id=$current_enrollment_id");
                exit;
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get enrolled students with their counts
$enrolled_students = getRows("
    SELECT se.*, sc.name as sub_course_name, c.name as course_name, 
           u.full_name as student_name, u.username, sc.number_of_semesters,
           (SELECT COUNT(*) FROM student_marks WHERE enrollment_id = se.id) as marks_entered,
           (SELECT COUNT(*) FROM course_subjects WHERE sub_course_id = se.sub_course_id) as total_subjects
    FROM student_enrollments se
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    JOIN users u ON se.user_id = u.id
    WHERE se.status IN ('enrolled', 'completed')
    ORDER BY se.enrollment_date DESC
");

// Get faculty list for "Checked By" dropdown
$faculties = getRows("SELECT id, full_name FROM users WHERE user_type_id = 3 AND status = 'active' ORDER BY full_name");

// If specific enrollment is selected, get detailed info
$selected_enrollment = null;
$subjects = [];
$existing_marks = [];
$current_checked_by = null;

if ($enrollment_id) {
    $selected_enrollment = getRow("
        SELECT se.*, sc.name as sub_course_name, c.name as course_name, 
               u.full_name as student_name, sc.number_of_semesters
        FROM student_enrollments se
        JOIN sub_courses sc ON se.sub_course_id = sc.id
        JOIN courses c ON sc.course_id = c.id
        JOIN users u ON se.user_id = u.id
        WHERE se.id = ? AND se.status IN ('enrolled', 'completed')
    ", [$enrollment_id]);

    if ($selected_enrollment) {
        // Get subjects for selected semester
        $subjects = getRows("
            SELECT * FROM course_subjects 
            WHERE sub_course_id = ? AND semester = ? 
            ORDER BY id
        ", [$selected_enrollment['sub_course_id'], $selected_semester]);

        // Get existing marks for this enrollment
        $marks_list = getRows("
            SELECT * FROM student_marks WHERE enrollment_id = ?
        ", [$enrollment_id]);

        foreach ($marks_list as $mark) {
            $existing_marks[$mark['subject_id']] = $mark;
            if ($mark['semester'] == $selected_semester && $mark['checked_by']) {
                $current_checked_by = $mark['checked_by'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marks Management - GICT Admin</title>
    <link rel="icon" type="image/png" href="../logo.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --premium-blue: #0ea5e9;
            --premium-blue-light: #e0f2fe;
            --premium-blue-dark: #0284c7;
            --premium-slate: #1e293b;
            --premium-gray: #f8fafc;
            --premium-border: #eef2f7;
            --premium-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        }

        .marks-layout {
            display: block;
            margin-bottom: 30px;
        }

        /* Modal Styles */
        .premium-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            z-index: 1000;
            display: none;
            /* Controlled by PHP/JS */
            align-items: center;
            justify-content: center;
            padding: 24px;
            animation: fadeIn 0.3s ease;
        }

        .premium-modal-overlay.active {
            display: flex;
        }

        .premium-modal-content {
            background: white;
            width: 100%;
            max-width: 1000px;
            max-height: 90vh;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: modalSlideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes modalSlideUp {
            from {
                transform: translateY(40px) scale(0.95);
                opacity: 0;
            }

            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        .modal-close-trigger {
            position: absolute;
            top: 24px;
            right: 24px;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #64748b;
            transition: all 0.2s;
            z-index: 10;
        }

        .modal-close-trigger:hover {
            background: #fee2e2;
            color: #ef4444;
            transform: rotate(90deg);
        }

        .students-panel {
            background: white;
            border-radius: 20px;
            display: none;
            /* Hide sidebar list in favor of main table */
            flex-direction: column;
            overflow: hidden;
            box-shadow: var(--premium-shadow);
            border: 1px solid var(--premium-border);
            height: fit-content;
            max-height: 800px;
        }

        .panel-header {
            padding: 20px 24px;
            background: white;
            border-bottom: 1px solid var(--premium-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .panel-header span {
            font-weight: 700;
            color: var(--premium-slate);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-header span i {
            color: var(--premium-blue);
        }

        .students-search {
            padding: 16px 20px;
            background: var(--premium-gray);
            border-bottom: 1px solid var(--premium-border);
        }

        .search-input-wrapper {
            position: relative;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
        }

        .search-input-wrapper input {
            width: 100%;
            padding: 10px 12px 10px 35px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13px;
            transition: all 0.2s;
            background: white;
        }

        .search-input-wrapper input:focus {
            outline: none;
            border-color: var(--premium-blue);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        .students-list {
            overflow-y: auto;
            padding: 8px;
            max-height: 600px;
        }

        .student-item {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 4px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid transparent;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .student-item:hover {
            background: var(--premium-gray);
            transform: translateX(4px);
        }

        .student-item.active {
            background: var(--premium-blue-light);
            border-color: rgba(14, 165, 233, 0.2);
        }

        .student-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .student-avatar-mini {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #f1f5f9;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            transition: all 0.2s;
        }

        .student-item.active .student-avatar-mini {
            background: white;
            color: var(--premium-blue);
            box-shadow: 0 2px 5px rgba(14, 165, 233, 0.1);
        }

        .student-name {
            font-weight: 700;
            color: var(--premium-slate);
            font-size: 14px;
            margin: 0;
            line-height: 1.2;
        }

        .student-course {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        .marks-progress {
            margin-top: 4px;
        }

        .progress-labels {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .progress-bar-container {
            height: 5px;
            background: rgba(0, 0, 0, 0.04);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: var(--premium-blue);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .student-item.active .progress-labels {
            color: var(--premium-blue);
        }

        .form-panel {
            background: white;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: var(--premium-shadow);
            border: 1px solid var(--premium-border);
            min-height: 500px;
        }

        .semester-tabs {
            display: flex;
            gap: 12px;
            padding: 12px 24px;
            background: var(--premium-gray);
            border-bottom: 1px solid var(--premium-border);
        }

        .semester-tab {
            padding: 8px 18px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            color: #64748b;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .semester-tab:hover {
            border-color: var(--premium-blue);
            color: var(--premium-blue);
        }

        .semester-tab.active {
            background: var(--premium-blue);
            color: white;
            border-color: var(--premium-blue);
            box-shadow: 0 4px 10px rgba(14, 165, 233, 0.2);
        }

        .marks-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .marks-table th {
            text-align: left;
            padding: 16px 24px;
            background: #f8fafc;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #334155;
            border-bottom: 2px solid #e2e8f0;
        }

        .marks-table td {
            padding: 20px 24px;
            border-bottom: 1px solid var(--premium-border);
            vertical-align: middle;
        }

        .m-input {
            width: 90px;
            padding: 10px;
            border: 2px solid #f1f5f9;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 800;
            text-align: center;
            color: var(--premium-slate);
            transition: all 0.2s;
            background: #f8fafc;
        }

        .m-input:focus {
            outline: none;
            border-color: var(--premium-blue);
            background: white;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.1);
        }

        .row-total {
            font-weight: 900;
            font-size: 18px;
            color: var(--premium-slate);
        }

        .row-grade {
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .grade-S,
        .grade-A {
            background: #dcfce7;
            color: #166534;
        }

        .grade-B {
            background: #e0f2fe;
            color: #0369a1;
        }

        .grade-C {
            background: #fef9c3;
            color: #854d0e;
        }

        .grade-D {
            background: #ffedd5;
            color: #9a3412;
        }

        .grade-F {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-premium {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .btn-p-primary {
            background: var(--premium-blue);
            color: white;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.2);
        }

        .btn-p-primary:hover {
            background: var(--premium-blue-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.3);
        }

        .btn-p-outline {
            background: white;
            color: var(--premium-blue);
            border: 2px solid var(--premium-blue-light);
            text-decoration: none;
        }

        .btn-p-outline:hover {
            border-color: var(--premium-blue);
            background: var(--premium-blue-light);
        }

        .dashboard-overview-container {
            margin-top: 40px;
            animation: fadeIn 0.5s ease-out;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 24px;
        }

        .stat-card-premium {
            background: white;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid var(--premium-border);
            box-shadow: var(--premium-shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .stat-card-premium:hover {
            transform: translateY(-5px);
        }

        .stat-card-premium .icon-box {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-blue .icon-box {
            background: #e0f2fe;
            color: #0ea5e9;
        }

        .stat-green .icon-box {
            background: #dcfce7;
            color: #166534;
        }

        .stat-orange .icon-box {
            background: #ffedd5;
            color: #9a3412;
        }

        .stat-purple .icon-box {
            background: #f3e8ff;
            color: #7e22ce;
        }

        .stat-card-premium h3 {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            line-height: 1.1;
        }

        .stat-card-premium p {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 4px 0 0 0;
        }

        .dashboard-main-premium {
            background: white;
            border-radius: 24px;
            padding: 32px;
            border: 1px solid var(--premium-border);
            box-shadow: var(--premium-shadow);
        }

        .table-responsive {
            margin-top: 20px;
            border-radius: 16px;
            border: 1px solid var(--premium-border);
            overflow: hidden;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }

        .custom-table th {
            background: #f1f5f9;
            padding: 16px 20px;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: left;
        }

        .custom-table td {
            padding: 18px 20px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            color: #1e293b;
        }

        .custom-table tr:hover {
            background: #f8fafc;
        }

        .badge-premium {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-blue {
            background: #e0f2fe;
            color: #0369a1;
        }

        .badge-green {
            background: #dcfce7;
            color: #166534;
        }

        .badge-orange {
            background: #ffedd5;
            color: #9a3412;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .empty-state-lux {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 60px;
            color: #94a3b8;
        }

        .empty-icon-box {
            width: 120px;
            height: 120px;
            background: var(--premium-gray);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 30px;
            position: relative;
        }

        .empty-icon-box::after {
            content: '';
            position: absolute;
            inset: -10px;
            border: 2px dashed #e2e8f0;
            border-radius: 35px;
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        /* Filter Optimization */
        .filter-group {
            display: flex;
            align-items: flex-end;
            gap: 24px;
            padding: 24px;
            background: #f8fafc;
            border-radius: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .filter-item {
            flex: 1;
            min-width: 250px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-item label {
            font-size: 11px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-left: 4px;
        }

        .filter-item select,
        .filter-item input {
            height: 48px;
            padding: 0 16px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: white;
            font-size: 14px;
            font-weight: 600;
            color: var(--premium-slate);
            transition: all 0.2s;
        }

        .filter-item select:focus,
        .filter-item input:focus {
            outline: none;
            border-color: var(--premium-blue);
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
        }

        .search-input-wrapper {
            position: relative;
            width: 100%;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            z-index: 10;
        }

        .search-input-wrapper input {
            padding-left: 44px !important;
            width: 100% !important;
        }

        /* Responsive Styles */
        @media (max-width: 1200px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .stat-card-premium {
                padding: 18px;
            }

            .stat-card-premium h3 {
                font-size: 24px;
            }

            .stat-card-premium .icon-box {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }

            .dashboard-main-premium {
                padding: 16px;
                border-radius: 16px;
            }

            .table-responsive {
                overflow-x: auto;
            }

            .custom-table th,
            .custom-table td {
                padding: 12px;
                font-size: 12px;
                white-space: nowrap;
            }

            .semester-tabs {
                padding: 12px 16px;
                overflow-x: auto;
                flex-wrap: nowrap;
            }

            .semester-tab {
                flex-shrink: 0;
                padding: 8px 14px;
                font-size: 12px;
            }

            .marks-table th,
            .marks-table td {
                padding: 12px 16px;
            }

            .m-input {
                width: 70px;
                padding: 8px;
                font-size: 14px;
            }

            .btn-premium {
                padding: 10px 16px;
                font-size: 13px;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .stats-row {
                gap: 12px;
            }

            .stat-card-premium {
                padding: 14px;
                gap: 12px;
            }

            .stat-card-premium h3 {
                font-size: 20px;
            }

            .stat-card-premium p {
                font-size: 10px;
            }

            .stat-card-premium .icon-box {
                width: 40px;
                height: 40px;
                font-size: 18px;
                border-radius: 12px;
            }

            .dashboard-main-premium {
                padding: 12px;
            }

            .custom-table th,
            .custom-table td {
                padding: 10px 8px;
                font-size: 11px;
            }

            .badge-premium {
                padding: 4px 8px;
                font-size: 10px;
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
                <img src="<?php echo !empty($user['profile_image']) ? $user['profile_image'] : '../assets/images/default-avatar.png'; ?>"
                    alt="Profile" onerror="this.src='../assets/images/default-avatar.png'" />
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
                <li><a href="marks-management.php" class="active"><i class="fas fa-chart-line"></i> Marks Management</a></li>
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
                    <span>Marks Management</span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="page-header">
                <h1 style="color: #1e293b; font-weight: 700;"><i class="fas fa-award"></i> Marks Management</h1>
                <p style="color: #475569; font-weight: 500;">Academic tracking, grade allotment, and student results
                    management</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <strong>Success:</strong>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <strong>Error:</strong>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Dashboard Overview & Global Table (PRIMARY VIEW) -->
            <div class="dashboard-overview-container">
                <div class="stats-row">
                    <?php
                    $total_e = count($enrolled_students);
                    $total_c = 0;
                    $total_p = 0;
                    foreach ($enrolled_students as $s) {
                        if ($s['marks_entered'] >= $s['total_subjects'] && $s['total_subjects'] > 0)
                            $total_c++;
                        else if ($s['marks_entered'] > 0)
                            $total_p++;
                    }
                    ?>
                    <div class="stat-card-premium stat-blue">
                        <div class="icon-box"><i class="fas fa-users-rays"></i></div>
                        <div>
                            <h3><?php echo $total_e; ?></h3>
                            <p>Global Enrollment</p>
                        </div>
                    </div>
                    <div class="stat-card-premium stat-green">
                        <div class="icon-box"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <h3><?php echo $total_c; ?></h3>
                            <p>Completed Path</p>
                        </div>
                    </div>
                    <div class="stat-card-premium stat-orange">
                        <div class="icon-box"><i class="fas fa-chart-line"></i></div>
                        <div>
                            <h3><?php echo $total_p; ?></h3>
                            <p>Active Progression</p>
                        </div>
                    </div>
                    <div class="stat-card-premium stat-purple">
                        <div class="icon-box"><i class="fas fa-hourglass-start"></i></div>
                        <div>
                            <h3><?php echo $total_e - $total_c - $total_p; ?></h3>
                            <p>Awaiting Data</p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-main-premium">
                    <div class="dashboard-header">
                        <div>
                            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin: 0;">
                                Student Marks Overview</h2>
                            <p style="color: #475569; font-size: 14px; font-weight: 500; margin-top: 5px;">A live
                                view of all enrolled students and their academic progress</p>
                        </div>
                        <button class="btn-premium btn-p-outline" style="padding: 10px 20px;"
                            onclick="location.reload()">
                            <i class="fas fa-arrows-rotate"></i> Refresh Dataset
                        </button>
                    </div>

                    <div class="filter-group">
                        <div class="filter-item">
                            <label>Course Vertical</label>
                            <select id="mainCourseFilter" onchange="applyGlobalFilters()">
                                <option value="">--- ALL ACADEMIC PROGRAMS ---</option>
                                <?php
                                $categories = array_unique(array_column($enrolled_students, 'course_name'));
                                foreach ($categories as $cat) {
                                    echo "<option value='" . strtolower($cat) . "'>$cat</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="filter-item">
                            <label>Enrollment Status</label>
                            <select id="statusFilter" onchange="applyGlobalFilters()">
                                <option value="">--- ALL STATUSES ---</option>
                                <option value="complete">COMPLETED</option>
                                <option value="partial">IN PROGRESS</option>
                                <option value="pending">PENDING INITIAL ENTRY</option>
                            </select>
                        </div>
                        <div class="filter-item">
                            <label>Quick Search</label>
                            <div class="search-input-wrapper">
                                <i class="fas fa-magnifying-glass"></i>
                                <input type="text" id="globalSearch" placeholder="Filter by Name, ID or Marksheet No..."
                                    onkeyup="applyGlobalFilters()">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Candidate Details</th>
                                    <th>Academic Program</th>
                                    <th>Credential ID</th>
                                    <th>Learning Progress</th>
                                    <th>Verification</th>
                                    <th style="text-align: right;">Interact</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrolled_students as $student):
                                    $progress_pct = ($student['total_subjects'] > 0) ? round(($student['marks_entered'] / $student['total_subjects']) * 100) : 0;
                                    $status_code = $progress_pct == 100 ? 'complete' : ($progress_pct > 0 ? 'partial' : 'pending');
                                    $status_label = $progress_pct == 100 ? 'CERTIFIED' : ($progress_pct > 0 ? 'IN PROGRESS' : 'NOT STARTED');
                                    $badge_class = $progress_pct == 100 ? 'badge-green' : ($progress_pct > 0 ? 'badge-blue' : 'badge-orange');
                                    ?>
                                    <tr class="global-student-row"
                                        data-name="<?php echo strtolower($student['student_name']); ?>"
                                        data-course="<?php echo strtolower($student['course_name']); ?>"
                                        data-status="<?php echo $status_code; ?>">
                                        <td>
                                            <div style="font-weight: 600; color: #1e293b; font-size: 15px;">
                                                <?php echo htmlspecialchars($student['student_name']); ?>
                                            </div>
                                            <div style="font-size: 13px; color: #475569; font-weight: 500;">
                                                REG ID: <?php echo htmlspecialchars($student['username']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">
                                                <?php echo htmlspecialchars($student['sub_course_name']); ?>
                                            </div>
                                            <div style="font-size: 13px; color: #475569; margin-top: 2px;">
                                                <?php echo htmlspecialchars($student['course_name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($student['marksheet_no'])): ?>
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-certificate" style="color: #0ea5e9;"></i>
                                                    <span style="font-weight: 500; color: #0ea5e9; font-size: 13px;">
                                                        <?php echo $student['marksheet_no']; ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span style="font-size: 12px; color: #9ca3af;">
                                                    <i class="fas fa-minus-circle" style="margin-right: 4px;"></i>Not
                                                    Credentialed
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="width: 180px;">
                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                                <span style="font-size: 13px; font-weight: 500; color: #475569;">
                                                    <?php echo $student['marks_entered']; ?>/<?php echo $student['total_subjects']; ?>
                                                    Units
                                                </span>
                                                <span
                                                    style="font-size: 13px; font-weight: 700; color: #0369a1;"><?php echo $progress_pct; ?>%</span>
                                            </div>
                                            <div
                                                style="height: 6px; background: #e5e7eb; border-radius: 10px; overflow: hidden;">
                                                <div
                                                    style="height: 100%; width: <?php echo $progress_pct; ?>%; background: linear-gradient(90deg, #0ea5e9, #06b6d4); border-radius: 10px;">
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-premium <?php echo $badge_class; ?>">
                                                <?php if ($progress_pct == 100): ?><i class="fas fa-check-circle"
                                                        style="margin-right: 4px;"></i><?php elseif ($progress_pct > 0): ?><i
                                                        class="fas fa-spinner" style="margin-right: 4px;"></i><?php else: ?><i
                                                        class="fas fa-clock"
                                                        style="margin-right: 4px;"></i><?php endif; ?><?php echo $status_label; ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <button class="btn-premium btn-p-primary"
                                                onclick="selectStudent(<?php echo $student['id']; ?>)"
                                                style="padding: 10px 20px; font-size: 13px; margin-left: auto; width: 140px; justify-content: center;">
                                                Enter Marks <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Grade Entry Modal -->
            <?php if ($selected_enrollment): ?>
                <div class="premium-modal-overlay active" id="gradeEntryModal">
                    <div class="premium-modal-content">
                        <div class="modal-close-trigger" onclick="closeModal()">
                            <i class="fas fa-times"></i>
                        </div>

                        <div class="form-panel" style="box-shadow: none; border: none; height: 100%;">
                            <div class="panel-header"
                                style="background: #fff; border-bottom: 1px solid var(--premium-border); padding: 24px 30px;">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div
                                        style="width: 44px; height: 44px; border-radius: 12px; background: var(--premium-blue-light); color: var(--premium-blue); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                        <i class="fas fa-user-edit"></i>
                                    </div>
                                    <div>
                                        <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin: 0;">
                                            <?php echo htmlspecialchars($selected_enrollment['student_name']); ?>
                                        </h2>
                                        <p
                                            style="font-size: 13px; color: #475569; margin: 2px 0 0 0; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;">
                                            Assessment Record & Curriculum Allotment</p>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if (!empty($selected_enrollment['marksheet_no'])): ?>
                                        <div
                                            style="font-size: 13px; background: #f1f5f9; padding: 8px 16px; border-radius: 10px; border: 1px solid #cbd5e1; color: #1e293b; font-weight: 700;">
                                            <i class="fas fa-shield-halved" style="color: #0369a1; margin-right: 6px;"></i> CRD:
                                            <?php echo $selected_enrollment['marksheet_no']; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($selected_enrollment['marks_entered'] > 0): ?>
                                        <a href="../result.php?rid=<?php echo base64_encode($selected_enrollment['username']); ?>"
                                            target="_blank" class="btn-premium btn-p-outline"
                                            style="padding: 8px 16px; font-size: 12px;">
                                            <i class="fas fa-external-link-alt"></i> Preview Results
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Semester Tabs -->
                            <div class="semester-tabs" style="padding: 10px 30px; background: #f8fafc;">
                                <?php for ($i = 1; $i <= $selected_enrollment['number_of_semesters']; $i++): ?>
                                    <button class="semester-tab <?php echo $selected_semester == $i ? 'active' : ''; ?>"
                                        onclick="changeSemester(<?php echo $enrollment_id; ?>, <?php echo $i; ?>)">
                                        <i class="fas fa-layer-group"></i> Semester <?php echo $i; ?>
                                    </button>
                                <?php endfor; ?>
                            </div>

                            <div style="flex: 1; overflow-y: auto; padding: 0 30px 30px;">
                                <!-- Readiness Banner -->
                                <?php if ($selected_enrollment['marks_entered'] >= $selected_enrollment['total_subjects'] && $selected_enrollment['total_subjects'] > 0): ?>
                                    <div class="completion-banner"
                                        style="margin: 24px 0; border: 2px solid #bbf7d0; background: #f0fdf4;">
                                        <div style="display: flex; gap: 20px; align-items: center;">
                                            <div
                                                style="width: 50px; height: 50px; background: white; color: #166534; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                                                <i class="fas fa-graduation-cap"></i>
                                            </div>
                                            <div>
                                                <h4 style="margin: 0; color: #166534; font-size: 16px; font-weight: 800;">Course
                                                    Milestones Reached</h4>
                                                <p
                                                    style="margin: 4px 0 0 0; font-size: 12px; color: #15803d; font-weight: 600;">
                                                    All assessment units have been successfully uploaded for this candidate.</p>
                                            </div>
                                        </div>
                                        <form method="POST" action="marks-management.php">
                                            <input type="hidden" name="action" value="complete_course">
                                            <input type="hidden" name="enrollment_id"
                                                value="<?php echo $selected_enrollment['id']; ?>">
                                            <button type="submit" class="btn-premium btn-p-primary"
                                                style="background: #166534; color: white; padding: 10px 20px;"
                                                onclick="return confirm('Finalize course and move to alumni archives?')">
                                                Finalize Completion <i class="fas fa-chevron-right"
                                                    style="font-size: 10px; margin-left: 8px;"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>

                                <form method="POST"
                                    action="marks-management.php?enrollment_id=<?php echo $enrollment_id; ?>&semester=<?php echo $selected_semester; ?>">
                                    <input type="hidden" name="action" value="submit_marks">
                                    <input type="hidden" name="enrollment_id"
                                        value="<?php echo $selected_enrollment['id']; ?>">
                                    <input type="hidden" name="semester" value="<?php echo $selected_semester; ?>">

                                    <table class="marks-table">
                                        <thead>
                                            <tr>
                                                <th>Learning Unit</th>
                                                <th style="width: 130px;">Theory</th>
                                                <th style="width: 130px;">Practical</th>
                                                <th style="width: 120px; text-align: center;">Grand Total</th>
                                                <th style="width: 100px; text-align: center;">Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($subjects)): ?>
                                                <tr>
                                                    <td colspan="5" style="text-align: center; padding: 60px; color: #94a3b8;">
                                                        <div style="font-size: 2.5rem; opacity: 0.1; margin-bottom: 15px;"><i
                                                                class="fas fa-folder-open"></i></div>
                                                        <p style="font-weight: 700;">No curriculum subjects defined for this
                                                            term.</p>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($subjects as $subject):
                                                    $m = $existing_marks[$subject['id']] ?? null;
                                                    ?>
                                                    <tr class="subject-row">
                                                        <td>
                                                            <div class="subj-name"
                                                                style="font-size: 16px; color: #1e293b; font-weight: 600;">
                                                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                                                            </div>
                                                            <div class="subj-code"
                                                                style="font-weight: 700; color: #475569; font-size: 13px; letter-spacing: 0.5px;">
                                                                <?php echo htmlspecialchars($subject['subject_code']); ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                                <input type="number"
                                                                    name="marks[<?php echo $subject['id']; ?>][theory]"
                                                                    class="m-input"
                                                                    value="<?php echo $m ? $m['theory_marks'] : ''; ?>"
                                                                    max="<?php echo $subject['theory_marks']; ?>" min="0"
                                                                    oninput="updateRowTotal(this, <?php echo $subject['max_marks']; ?>)">
                                                                <span
                                                                    style="font-size: 11px; color: #475569; font-weight: 700; text-align: center;">MAX
                                                                    <?php echo $subject['theory_marks']; ?></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                                <input type="number"
                                                                    name="marks[<?php echo $subject['id']; ?>][practical]"
                                                                    class="m-input"
                                                                    value="<?php echo $m ? $m['practical_marks'] : ''; ?>"
                                                                    max="<?php echo $subject['practical_marks']; ?>" min="0"
                                                                    oninput="updateRowTotal(this, <?php echo $subject['max_marks']; ?>)">
                                                                <span
                                                                    style="font-size: 11px; color: #475569; font-weight: 700; text-align: center;">MAX
                                                                    <?php echo $subject['practical_marks']; ?></span>
                                                            </div>
                                                        </td>
                                                        <td style="text-align: center;">
                                                            <div class="row-total" style="color: #1e293b;">
                                                                <?php echo $m ? $m['total_marks'] : '0'; ?></div>
                                                            <div style="font-size: 11px; color: #475569; font-weight: 700;">UNIT
                                                                TOTAL <?php echo $subject['max_marks']; ?></div>
                                                        </td>
                                                        <td style="text-align: center;">
                                                            <span class="row-grade grade-<?php
                                                            $g = $m['grade'] ?? '-';
                                                            echo str_replace('+', '', $g);
                                                            ?>">
                                                                <?php echo $m ? $m['grade'] : '-'; ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>

                                    <?php if (!empty($subjects)): ?>
                                        <div
                                            style="padding: 32px; background: #fff; border-top: 1px solid var(--premium-border); display: flex; align-items: center; justify-content: space-between;">
                                            <div style="display: flex; align-items: center; gap: 15px;">
                                                <label
                                                    style="font-size: 14px; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px;">Verification
                                                    Authority:</label>
                                                <select name="checked_by" class="m-input"
                                                    style="width: 280px; text-align: left; font-size: 13px; padding-left: 15px;"
                                                    required>
                                                    <option value="">-- AUTHORIZED FACULTY --</option>
                                                    <?php foreach ($faculties as $fac): ?>
                                                        <option value="<?php echo $fac['id']; ?>" <?php echo $current_checked_by == $fac['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($fac['full_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn-premium btn-p-primary" style="height: 48px;">
                                                <i class="fas fa-shield-check"></i> Finalize This Term's Record
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Initialize Premium Interface
        document.addEventListener('DOMContentLoaded', function () {
            // Auto-hide alert with smooth fade
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 4000);
            }
        });

        function selectStudent(id) {
            window.location.href = `marks-management.php?enrollment_id=${id}`;
        }

        function changeSemester(id, sem) {
            window.location.href = `marks-management.php?enrollment_id=${id}&semester=${sem}`;
        }

        function closeModal() {
            window.location.href = 'marks-management.php';
        }

        function filterStudents() {
            const query = document.getElementById('studentSearch').value.toLowerCase();
            const items = document.querySelectorAll('.student-item');

            items.forEach(item => {
                const name = item.dataset.name;
                const course = item.dataset.course;
                if (name.includes(query) || course.includes(query)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function applyGlobalFilters() {
            const query = document.getElementById('globalSearch').value.toLowerCase();
            const course = document.getElementById('mainCourseFilter').value.toLowerCase();
            const status = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('.global-student-row');

            rows.forEach(row => {
                const rName = row.dataset.name;
                const rCourse = row.dataset.course;
                const rStatus = row.dataset.status;
                const rText = row.innerText.toLowerCase();

                const matchesQuery = rText.includes(query);
                const matchesCourse = course === "" || rCourse === course;
                const matchesStatus = status === "" || rStatus === status;

                if (matchesQuery && matchesCourse && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function updateRowTotal(input, maxTotal) {
            const row = input.closest('tr');
            const theoryInput = row.querySelector('input[name*="[theory]"]');
            const practicalInput = row.querySelector('input[name*="[practical]"]');

            const theory = parseFloat(theoryInput.value) || 0;
            const practical = parseFloat(practicalInput.value) || 0;

            // Constrain input
            if (theory > parseFloat(theoryInput.max)) theoryInput.value = theoryInput.max;
            if (practical > parseFloat(practicalInput.max)) practicalInput.value = practicalInput.max;

            const finalTheory = parseFloat(theoryInput.value) || 0;
            const finalPractical = parseFloat(practicalInput.value) || 0;
            const total = finalTheory + finalPractical;

            const totalDisplay = row.querySelector('.row-total');
            totalDisplay.textContent = total;

            const gradeDisplay = row.querySelector('.row-grade');
            const pct = (total / maxTotal) * 100;
            let grade = 'F';
            let gClass = 'grade-F';

            if (pct >= 85) { grade = 'S'; gClass = 'grade-S'; }
            else if (pct >= 75) { grade = 'A'; gClass = 'grade-A'; }
            else if (pct >= 65) { grade = 'B'; gClass = 'grade-B'; }
            else if (pct >= 55) { grade = 'C'; gClass = 'grade-C'; }
            else if (pct >= 45) { grade = 'D'; gClass = 'grade-D'; }

            gradeDisplay.textContent = grade;
            gradeDisplay.className = `row-grade ${gClass}`;
        }
    </script>
    <script src="../assets/js/mobile-menu.js"></script>
</body>

</html>