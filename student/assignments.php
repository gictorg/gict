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

// Get all enrolled sub_course_ids
$enrolled_courses = getRows("
    SELECT se.sub_course_id, sc.name as sub_course_name
    FROM student_enrollments se
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    WHERE se.user_id = ? AND se.status = 'enrolled'
", [$user_id]);

$sub_course_ids = array_column($enrolled_courses, 'sub_course_id');
$current_page = basename($_SERVER['PHP_SELF']);

// Get assignments for these courses
$assignments = [];
if (!empty($sub_course_ids)) {
    $placeholders = implode(',', array_fill(0, count($sub_course_ids), '?'));
    $assignments = getRows("
        SELECT a.*, sc.name as sub_course_name, 
               asub.status as submission_status, asub.score, asub.feedback, asub.submission_date
        FROM assignments a
        JOIN sub_courses sc ON a.sub_course_id = sc.id
        LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.enrollment_id IN (
            SELECT id FROM student_enrollments WHERE user_id = ? AND sub_course_id = a.sub_course_id
        )
        WHERE a.sub_course_id IN ($placeholders) AND a.status = 'active'
        ORDER BY a.due_date ASC
    ", array_merge([$user_id], $sub_course_ids));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments - Student Portal</title>
    <link rel="icon" type="image/png" href="../logo.png">
    <link rel="stylesheet" href="../assets/css/student-portal.css?v=1769203382">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="student-portal-body">
    <div class="student-layout">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-container">
            <!-- Topbar -->
            <header class="admin-topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="breadcrumbs">
                        <a href="dashboard.php">Dashboard</a> / <span>Assignments</span>
                    </div>
                </div>
            </header>

            <main class="admin-content">
                <div class="panel">
                    <div class="panel-header">
                        <h1><i class="fas fa-tasks"></i> Academic Assignments</h1>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($assignments)): ?>
                            <div style="text-align: center; padding: 60px; color: #64748b;">
                                <i class="fas fa-clipboard-check"
                                    style="font-size: 4rem; margin-bottom: 20px; opacity: 0.2;"></i>
                                <h3 style="margin-bottom: 10px; color: #1e293b;">No Assignments Found</h3>
                                <p>You're all caught up! There are no active assignments for your courses.</p>
                            </div>
                        <?php else: ?>
                            <div style="display: grid; gap: 20px;">
                                <?php foreach ($assignments as $a):
                                    $is_overdue = strtotime($a['due_date']) < time() && !$a['submission_status'];
                                    $border_color = $a['submission_status'] === 'graded' ? '#10b981' : ($is_overdue ? '#ef4444' : ($a['submission_status'] ? '#6366f1' : '#f59e0b'));
                                    ?>
                                    <div
                                        style="background: white; border: 1px solid #f1f5f9; border-left: 6px solid <?php echo $border_color; ?>; border-radius: 15px; padding: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; gap: 30px;">
                                        <div style="flex: 1;">
                                            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 8px;">
                                                <h3 style="margin: 0; font-size: 1.2rem; color: #1e293b;">
                                                    <?php echo htmlspecialchars($a['title']); ?>
                                                </h3>
                                                <span
                                                    style="font-size: 0.7rem; padding: 3px 10px; border-radius: 10px; background: #f1f5f9; color: #475569; font-weight: 600;">
                                                    <?php echo htmlspecialchars($a['sub_course_name']); ?>
                                                </span>
                                            </div>
                                            <p style="margin: 0 0 15px; font-size: 0.9rem; color: #64748b;">
                                                <?php echo htmlspecialchars($a['description']); ?>
                                            </p>

                                            <div style="display: flex; gap: 20px; font-size: 0.8rem; color: #64748b;">
                                                <span><i class="fas fa-clock" style="color: #667eea; margin-right: 5px;"></i>
                                                    Due: <?php echo date('M d, h:i A', strtotime($a['due_date'])); ?></span>
                                                <?php if ($a['submission_status']): ?>
                                                    <span><i class="fas fa-check-double"
                                                            style="color: #10b981; margin-right: 5px;"></i> Submitted:
                                                        <?php echo date('M d, Y', strtotime($a['submission_date'])); ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($a['submission_status'] === 'graded'): ?>
                                                <div
                                                    style="margin-top: 15px; padding: 12px; background: #f0fdf4; border-radius: 10px; border: 1px solid #dcfce7;">
                                                    <span style="font-weight: 700; color: #166534;">Score:
                                                        <?php echo $a['score']; ?>/<?php echo $a['max_score'] ?? '100'; ?></span>
                                                    <?php if ($a['feedback']): ?>
                                                        <p style="margin: 5px 0 0; font-size: 0.8rem; color: #15803d;">Feedback:
                                                            <?php echo htmlspecialchars($a['feedback']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div style="text-align: right; min-width: 150px;">
                                            <?php if (!$a['submission_status']): ?>
                                                <?php if (!$is_overdue): ?>
                                                    <a href="submit-assignment.php?id=<?php echo $a['id']; ?>" class="btn-primary"
                                                        style="padding: 10px 20px; font-size: 0.85rem;">
                                                        Submit Now
                                                    </a>
                                                <?php else: ?>
                                                    <span style="color: #ef4444; font-weight: 700; font-size: 0.9rem;">Deadline
                                                        Missed</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div style="color: #10b981; font-weight: 700;">
                                                    <i class="fas fa-check-circle"></i>
                                                    <?php echo ucfirst($a['submission_status']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    </script>
</body>

</html>