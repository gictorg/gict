<?php
require_once '../includes/session_manager.php';
require_once '../config/database.php';

// Check if user is logged in and is faculty
if (!isLoggedIn() || !isFaculty()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get assigned courses for this faculty
$assigned_courses = getRows("
    SELECT sc.id, sc.name as sub_course_name, c.name as course_name 
    FROM sub_courses sc
    JOIN courses c ON sc.course_id = c.id
    JOIN faculty_courses fc ON sc.id = fc.sub_course_id
    WHERE fc.faculty_id = ? AND fc.status = 'active'
", [$user_id]);

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_attendance') {
    $sub_course_id = intval($_POST['sub_course_id']);
    $attendance_date = $_POST['attendance_date'];
    $attendance_data = $_POST['attendance'] ?? [];

    try {
        beginTransaction();

        foreach ($attendance_data as $enrollment_id => $status) {
            $remarks = $_POST['remarks'][$enrollment_id] ?? '';

            $sql = "INSERT INTO student_attendance (enrollment_id, attendance_date, status, remarks, marked_by) 
                    VALUES (?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    status = VALUES(status), 
                    remarks = VALUES(remarks), 
                    marked_by = VALUES(marked_by),
                    updated_at = NOW()";

            updateData($sql, [$enrollment_id, $attendance_date, $status, $remarks, $user_id]);
        }

        commitTransaction();
        $success_message = "Attendance marked successfully for " . date('M d, Y', strtotime($attendance_date));
    } catch (Exception $e) {
        rollbackTransaction();
        $error_message = "Error marking attendance: " . $e->getMessage();
    }
}

// Get selected course and date
$selected_course_id = intval($_GET['course_id'] ?? ($assigned_courses[0]['id'] ?? 0));
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Get students for the selected course
$students = [];
if ($selected_course_id) {
    $students = getRows("
        SELECT se.id as enrollment_id, u.full_name, u.username,
               sa.status as current_status, sa.remarks as current_remarks
        FROM student_enrollments se
        JOIN users u ON se.user_id = u.id
        LEFT JOIN student_attendance sa ON se.id = sa.enrollment_id AND sa.attendance_date = ?
        WHERE se.sub_course_id = ? AND se.status = 'enrolled'
        ORDER BY u.full_name
    ", [$selected_date, $selected_course_id]);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Faculty Portal</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .attendance-filters {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 14px;
            font-weight: 600;
            color: #475569;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 14px;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .attendance-table th {
            background: #f1f5f9;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #334155;
            border-bottom: 2px solid #e2e8f0;
        }

        .attendance-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
        }

        .status-options {
            display: flex;
            gap: 15px;
        }

        .status-option {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .status-option input {
            cursor: pointer;
        }

        .remarks-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 13px;
        }

        .btn-attendance {
            background: #0f6fb1;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-attendance:hover {
            background: #0d5a8f;
        }

        .student-info {
            display: flex;
            flex-direction: column;
        }

        .student-name {
            font-weight: 600;
            color: #1e293b;
        }

        .student-id {
            font-size: 12px;
            color: #64748b;
        }

        .present-label {
            color: #059669;
        }

        .absent-label {
            color: #dc2626;
        }

        .late-label {
            color: #d97706;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
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
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="active" href="#"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                <li><a href="dashboard.php#courses"><i class="fas fa-graduation-cap"></i> My Courses</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <header class="admin-topbar">
                <div class="topbar-left">
                    <div class="breadcrumbs">
                        <a href="dashboard.php">Dashboard</a> / <span>Mark Attendance</span>
                    </div>
                </div>
            </header>

            <div class="panel">
                <div class="panel-header">
                    <h1><i class="fas fa-calendar-check"></i> Mark Daily Attendance</h1>
                </div>
                <div class="panel-body">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="GET" action="" class="attendance-filters">
                        <div class="filter-group">
                            <label for="course_id">Select Course</label>
                            <select name="course_id" id="course_id" onchange="this.form.submit()">
                                <?php if (empty($assigned_courses)): ?>
                                    <option value="">No courses assigned</option>
                                <?php else: ?>
                                    <?php foreach ($assigned_courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" <?php echo $selected_course_id == $course['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['sub_course_name']); ?> (
                                            <?php echo htmlspecialchars($course['course_name']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="date">Date</label>
                            <input type="date" name="date" id="date" value="<?php echo $selected_date; ?>"
                                onchange="this.form.submit()" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </form>

                    <?php if (empty($students)): ?>
                        <div class="no-data">
                            <i class="fas fa-users-slash"></i>
                            <h3>No Students Found</h3>
                            <p>No students are currently enrolled in this course.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="mark_attendance">
                            <input type="hidden" name="sub_course_id" value="<?php echo $selected_course_id; ?>">
                            <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">

                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th>Student Information</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td>
                                                <div class="student-info">
                                                    <span class="student-name">
                                                        <?php echo htmlspecialchars($student['full_name']); ?>
                                                    </span>
                                                    <span class="student-id">
                                                        <?php echo htmlspecialchars($student['username']); ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="status-options">
                                                    <label class="status-option present-label">
                                                        <input type="radio"
                                                            name="attendance[<?php echo $student['enrollment_id']; ?>]"
                                                            value="present" <?php echo ($student['current_status'] === 'present' || !$student['current_status']) ? 'checked' : ''; ?>>
                                                        Present
                                                    </label>
                                                    <label class="status-option absent-label">
                                                        <input type="radio"
                                                            name="attendance[<?php echo $student['enrollment_id']; ?>]"
                                                            value="absent" <?php echo ($student['current_status'] === 'absent') ? 'checked' : ''; ?>>
                                                        Absent
                                                    </label>
                                                    <label class="status-option late-label">
                                                        <input type="radio"
                                                            name="attendance[<?php echo $student['enrollment_id']; ?>]"
                                                            value="late" <?php echo ($student['current_status'] === 'late') ? 'checked' : ''; ?>>
                                                        Late
                                                    </label>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text" name="remarks[<?php echo $student['enrollment_id']; ?>]"
                                                    class="remarks-input" placeholder="Optional remarks..."
                                                    value="<?php echo htmlspecialchars($student['current_remarks'] ?? ''); ?>">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <div style="margin-top: 30px; text-align: right;">
                                <button type="submit" class="btn-attendance">
                                    <i class="fas fa-save"></i> Save Attendance
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>