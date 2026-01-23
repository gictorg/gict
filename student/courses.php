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
$student = getRow("SELECT u.* FROM users u WHERE u.id = ?", [$user_id]);
if (!$student) {
    header('Location: ../login.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

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

// Get marks for all enrollments
$marks_data = [];
foreach ($enrolled_courses as $course) {
    if (!empty($course['enrollment_id'])) {
        $marks = getRows("
            SELECT cs.subject_name, sm.theory_marks, sm.practical_marks, sm.total_marks, cs.max_marks, sm.grade, sm.remarks, cs.semester
            FROM student_marks sm
            JOIN course_subjects cs ON sm.subject_id = cs.id
            WHERE sm.enrollment_id = ?
            ORDER BY cs.semester, cs.subject_name
        ", [$course['enrollment_id']]);
        $marks_data[$course['enrollment_id']] = $marks;
    }
}

$total_enrolled = count($enrolled_courses);
$completed_courses = count(array_filter($enrolled_courses, fn($c) => $c['enrollment_status'] === 'completed'));
$active_courses = count(array_filter($enrolled_courses, fn($c) => $c['enrollment_status'] === 'enrolled'));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Student Portal</title>
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
                        <a href="dashboard.php">Dashboard</a> / <span>My Courses</span>
                    </div>
                </div>
            </header>

            <main class="admin-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_enrolled; ?></div>
                        <div class="stat-label">Total Enrollments</div>
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
                        <h1><i class="fas fa-graduation-cap"></i> My Enrolled Courses</h1>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($enrolled_courses)): ?>
                            <div style="text-align: center; padding: 40px; color: #64748b;">
                                <i class="fas fa-book-open" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                                <p>You haven't enrolled in any courses yet.</p>
                            </div>
                        <?php else: ?>
                            <div
                                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px;">
                                <?php foreach ($enrolled_courses as $course): ?>
                                    <div style="background: white; border: 1px solid #f1f5f9; border-radius: 20px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: transform 0.3s ease;"
                                        onmouseover="this.style.transform='translateY(-5px)'"
                                        onmouseout="this.style.transform='translateY(0)'">
                                        <div
                                            style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                                            <div>
                                                <h3 style="margin: 0; color: #1e293b; font-size: 1.2rem;">
                                                    <?php echo htmlspecialchars($course['sub_course_name']); ?>
                                                </h3>
                                                <span
                                                    style="font-size: 0.8rem; color: #64748b; font-weight: 500;"><?php echo htmlspecialchars($course['category_name']); ?></span>
                                            </div>
                                            <span
                                                style="padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; 
                                                background: <?php echo $course['enrollment_status'] === 'enrolled' ? '#dcfce7' : ($course['enrollment_status'] === 'completed' ? '#dbeafe' : '#fef3c7'); ?>; 
                                                color: <?php echo $course['enrollment_status'] === 'enrolled' ? '#166534' : ($course['enrollment_status'] === 'completed' ? '#1e40af' : '#92400e'); ?>;">
                                                <?php echo ucfirst($course['enrollment_status'] === 'enrolled' ? 'active' : $course['enrollment_status']); ?>
                                            </span>
                                        </div>

                                        <div
                                            style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 25px; font-size: 0.9rem; color: #475569;">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <i class="fas fa-calendar-alt" style="width: 20px; color: #667eea;"></i>
                                                <span>Enrolled:
                                                    <?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?></span>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <i class="fas fa-clock" style="width: 20px; color: #667eea;"></i>
                                                <span>Duration: <?php echo $course['duration']; ?> Months</span>
                                            </div>
                                            <?php if ($course['completion_date']): ?>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <i class="fas fa-check-circle" style="width: 20px; color: #10b981;"></i>
                                                    <span>Completed:
                                                        <?php echo date('M d, Y', strtotime($course['completion_date'])); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                            <?php if ($course['enrollment_status'] === 'enrolled'): ?>
                                                <button class="btn-primary"
                                                    style="flex: 1; justify-content: center; font-size: 0.85rem;">
                                                    <i class="fas fa-play"></i> Continue Learning
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($course['enrollment_status'] === 'completed'): ?>
                                                <?php if (!empty($course['certificate_url'])): ?>
                                                    <a href="../<?php echo htmlspecialchars($course['certificate_url']); ?>"
                                                        target="_blank" class="btn-primary"
                                                        style="flex: 1; justify-content: center; font-size: 0.85rem;">
                                                        <i class="fas fa-award"></i> Certificate
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($course['marksheet_url'])): ?>
                                                    <a href="../<?php echo htmlspecialchars($course['marksheet_url']); ?>"
                                                        target="_blank" class="btn-primary"
                                                        style="flex: 1; justify-content: center; font-size: 0.85rem; background: #64748b;">
                                                        <i class="fas fa-file-invoice"></i> Marksheet
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <?php if (!empty($marks_data[$course['enrollment_id']])): ?>
                                                <button onclick="showMarks(<?php echo $course['enrollment_id']; ?>)"
                                                    class="btn-primary"
                                                    style="flex: 1; justify-content: center; font-size: 0.85rem; background: #1e293b;">
                                                    <i class="fas fa-poll"></i> Performance
                                                </button>
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

    <!-- Marks Modal -->
    <div id="marksModal" class="modal"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div class="modal-content"
            style="background: white; border-radius: 20px; width: 90%; max-width: 600px; overflow: hidden;">
            <div class="modal-header"
                style="padding: 20px; background: var(--student-sidebar-bg); color: white; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">Exam Performance</h3>
                <button onclick="closeMarksModal()"
                    style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body" id="marksContainer" style="padding: 30px;">
                <!-- Marks content will be injected here -->
            </div>
        </div>
    </div>

    <script>
        const marksData = <?php echo json_encode($marks_data); ?>;

        function showMarks(enrollmentId) {
            const marks = marksData[enrollmentId] || [];
            if (marks.length === 0) return;

            const modal = document.getElementById('marksModal');
            const container = document.getElementById('marksContainer');
            modal.style.display = 'flex';

            let html = '<table style="width: 100%; border-collapse: collapse;">';
            html += '<thead style="background: #f8fafc;"><tr style="text-align: left;"><th style="padding: 12px;">Subject</th><th style="padding: 12px;">Marks</th><th style="padding: 12px;">Grade</th></tr></thead><tbody>';

            let totalObtained = 0;
            let totalMax = 0;

            marks.forEach(m => {
                html += `<tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 12px;">
                        <div>${m.subject_name}</div>
                        <div style="font-size: 10px; color: #94a3b8;">Sem ${m.semester}</div>
                    </td>
                    <td style="padding: 12px;">${m.total_marks} / ${m.max_marks}</td>
                    <td style="padding: 12px;"><span style="font-weight: 700;">${m.grade || '-'}</span></td>
                </tr>`;
                totalObtained += parseFloat(m.total_marks) || 0;
                totalMax += parseFloat(m.max_marks) || 0;
            });

            const overall = totalMax > 0 ? (totalObtained / totalMax) * 100 : 0;
            html += `</tbody><tfoot><tr style="background: #f8fafc; font-weight: 700;">
                <td style="padding: 12px;">Aggregate Total</td>
                <td style="padding: 12px;">${totalObtained} / ${totalMax}</td>
                <td style="padding: 12px;">${overall.toFixed(1)}%</td>
            </tr></tfoot></table>`;

            container.innerHTML = html;
        }

        function closeMarksModal() {
            document.getElementById('marksModal').style.display = 'none';
        }

        function enrollInCourse(subCourseId) {
            if (confirm('Proceed with enrollment?')) {
                const formData = new FormData();
                formData.append('enroll', '1');
                formData.append('sub_course_id', subCourseId);

                fetch('enroll.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert('Enrolled successfully!');
                            location.reload();
                        } else {
                            alert(data.message || 'Error occurred');
                        }
                    });
            }
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }

        // Close modal on outside click
        window.onclick = function (event) {
            const modal = document.getElementById('marksModal');
            if (event.target == modal) closeMarksModal();
        }
    </script>
</body>

</html>