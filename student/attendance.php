<?php
require_once '../includes/session_manager.php';
require_once '../config/database.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get student information for sidebar
$student = getRow("SELECT u.* FROM users u WHERE u.id = ?", [$user_id]);
if (!$student) {
    header('Location: ../login.php');
    exit;
}

// Get all enrollments for this student
$enrollments = getRows("
    SELECT se.id, sc.name as sub_course_name 
    FROM student_enrollments se
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    WHERE se.user_id = ? AND se.status = 'enrolled'
", [$user_id]);

$selected_enrollment_id = intval($_GET['enrollment_id'] ?? ($enrollments[0]['id'] ?? 0));
$current_page = basename($_SERVER['PHP_SELF']);

// Get attendance stats
$stats = null;
$attendance_logs = [];

if ($selected_enrollment_id) {
    $stats = getRow("
        SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days
        FROM student_attendance
        WHERE enrollment_id = ?
    ", [$selected_enrollment_id]);

    $attendance_logs = getRows("
        SELECT * FROM student_attendance 
        WHERE enrollment_id = ? 
        ORDER BY attendance_date DESC
    ", [$selected_enrollment_id]);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Student Portal</title>
    <link rel="icon" type="image/png" href="../logo.png">
    <link rel="stylesheet" href="../assets/css/student-portal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="student-portal-body">
    <div class="student-layout">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-container" style="width: 100%; overflow: auto;">
            <!-- Topbar -->
            <header class="admin-topbar">
                <div class="topbar-left">
                    <div class="breadcrumbs">
                        <a href="dashboard.php">Dashboard</a> / <span>Attendance</span>
                    </div>
                </div>
            </header>

            <main class="admin-content">
                <div class="panel">
                    <div class="panel-header"
                        style="display: flex; justify-content: space-between; align-items: center;">
                        <h1><i class="fas fa-calendar-check"></i> Attendance Record</h1>
                        <select onchange="window.location.href='?enrollment_id='+this.value"
                            style="padding: 10px 15px; border-radius: 10px; border: 1px solid #f1f5f9; font-weight: 600; color: #1e293b; background: #f8fafc;">
                            <?php foreach ($enrollments as $e): ?>
                                <option value="<?php echo $e['id']; ?>" <?php echo $selected_enrollment_id == $e['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($e['sub_course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="panel-body">
                        <?php if (!$stats || $stats['total_days'] == 0): ?>
                            <div style="text-align: center; padding: 60px; color: #64748b;">
                                <i class="fas fa-calendar-minus"
                                    style="font-size: 4rem; margin-bottom: 20px; opacity: 0.2;"></i>
                                <h3 style="margin-bottom: 10px; color: #1e293b;">No Records Yet</h3>
                                <p>Attendance has not been marked for this course.</p>
                            </div>
                        <?php else:
                            $present_perc = round(($stats['present_days'] + ($stats['late_days'] * 0.5)) / $stats['total_days'] * 100);
                            ?>
                            <div
                                style="display: flex; gap: 40px; align-items: center; background: #f8fafc; padding: 40px; border-radius: 20px; margin-bottom: 30px;">
                                <div
                                    style="width: 150px; height: 150px; border-radius: 50%; background: white; border: 8px solid #667eea; display: flex; align-items: center; justify-content: center; flex-direction: column; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);">
                                    <span
                                        style="font-size: 2.2rem; font-weight: 800; color: #1e293b;"><?php echo $present_perc; ?>%</span>
                                    <span
                                        style="font-size: 0.7rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Overall</span>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; flex: 1;">
                                    <div
                                        style="background: white; padding: 15px 25px; border-radius: 15px; border-left: 4px solid #6366f1;">
                                        <div style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">
                                            <?php echo $stats['total_days']; ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: #64748b; font-weight: 600;">Total Classes
                                        </div>
                                    </div>
                                    <div
                                        style="background: white; padding: 15px 25px; border-radius: 15px; border-left: 4px solid #10b981;">
                                        <div style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">
                                            <?php echo $stats['present_days']; ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: #64748b; font-weight: 600;">Present</div>
                                    </div>
                                    <div
                                        style="background: white; padding: 15px 25px; border-radius: 15px; border-left: 4px solid #f59e0b;">
                                        <div style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">
                                            <?php echo $stats['late_days']; ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: #64748b; font-weight: 600;">Late Entries</div>
                                    </div>
                                    <div
                                        style="background: white; padding: 15px 25px; border-radius: 15px; border-left: 4px solid #ef4444;">
                                        <div style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">
                                            <?php echo $stats['absent_days']; ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: #64748b; font-weight: 600;">Absent</div>
                                    </div>
                                </div>
                            </div>

                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead style="background: #f8fafc; border-bottom: 2px solid #f1f5f9;">
                                        <tr>
                                            <th
                                                style="padding: 15px; text-align: left; font-size: 0.85rem; color: #64748b;">
                                                DATE</th>
                                            <th
                                                style="padding: 15px; text-align: left; font-size: 0.85rem; color: #64748b;">
                                                DAY</th>
                                            <th
                                                style="padding: 15px; text-align: left; font-size: 0.85rem; color: #64748b;">
                                                STATUS</th>
                                            <th
                                                style="padding: 15px; text-align: left; font-size: 0.85rem; color: #64748b;">
                                                REMARKS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendance_logs as $log): ?>
                                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                                <td style="padding: 15px; font-weight: 600; color: #1e293b;">
                                                    <?php echo date('M d, Y', strtotime($log['attendance_date'])); ?>
                                                </td>
                                                <td style="padding: 15px; color: #64748b;">
                                                    <?php echo date('l', strtotime($log['attendance_date'])); ?>
                                                </td>
                                                <td style="padding: 15px;">
                                                    <span
                                                        style="padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;
                                                        background: <?php echo $log['status'] === 'present' ? '#dcfce7' : ($log['status'] === 'absent' ? '#fee2e2' : '#fef3c7'); ?>;
                                                        color: <?php echo $log['status'] === 'present' ? '#166534' : ($log['status'] === 'absent' ? '#991b1b' : '#92400e'); ?>;">
                                                        <?php echo $log['status']; ?>
                                                    </span>
                                                </td>
                                                <td style="padding: 15px; color: #64748b; font-size: 0.9rem;">
                                                    <?php echo htmlspecialchars($log['remarks'] ?: '-'); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }
    </script>
</body>

</html>