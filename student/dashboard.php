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

// Get student enrollment information
$enrolled_courses = getRows("
    SELECT se.id as enrollment_id, se.sub_course_id, sc.name as sub_course_name, c.name as course_name, se.status as enrollment_status, se.enrollment_date, se.completion_date
    FROM student_enrollments se
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    WHERE se.user_id = ?
    ORDER BY se.enrollment_date DESC
", [$user_id]);

$sub_course_ids = array_map(fn($c) => $c['sub_course_id'], array_filter($enrolled_courses, fn($e) => $e['enrollment_status'] === 'enrolled'));

// Get certificates for completed courses
$certificates = getRows("
    SELECT c.*, se.sub_course_id, sc.name as sub_course_name, co.name as course_name, cc.name as category_name
    FROM certificates c
    JOIN student_enrollments se ON c.enrollment_id = se.id
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses co ON sc.course_id = co.id
    JOIN course_categories cc ON co.category_id = cc.id
    WHERE se.user_id = ?
    ORDER BY c.generated_at DESC
", [$user_id]);

$total_enrolled = count($enrolled_courses);
$active_courses = count(array_filter($enrolled_courses, fn($e) => $e['enrollment_status'] === 'enrolled'));
$completed_courses = count(array_filter($enrolled_courses, fn($e) => $e['enrollment_status'] === 'completed'));
$certificates_count = count($certificates);

// Prepare tokens for results and certificates
$encryption_key = "GICT_SECURE_KEY_2026";
$cipher_method = "aes-256-cbc";

// Check for marks in each enrollment
foreach ($enrolled_courses as &$course) {
    $m_res = getRow("SELECT COUNT(*) as count FROM student_marks WHERE enrollment_id = ?", [$course['enrollment_id']]);
    $course['has_marks'] = ($m_res['count'] > 0);

    if ($course['has_marks']) {
        // Generate result link (using rid base64)
        $course['result_url'] = "../result.php?rid=" . base64_encode($student['username']);

        // Generate certificate link (using encryption token)
        $plaintext = $student['username'] . '|' . $student['date_of_birth'];
        $iv_length = openssl_cipher_iv_length($cipher_method);
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted = openssl_encrypt($plaintext, $cipher_method, $encryption_key, 0, $iv);
        $course['cert_url'] = "../certificate.php?token=" . urlencode(base64_encode($encrypted . '::' . $iv));
    }
}
unset($course);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - GICT Institute</title>
    <link rel="icon" type="image/png" href="../logo.png">
    <link rel="stylesheet" href="../assets/css/student-portal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- html2canvas and jsPDF for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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
                        <a href="dashboard.php">Dashboard</a> / <span>Overview</span>
                    </div>
                </div>
            </header>

            <main class="admin-content">
                <!-- Welcome Section -->
                <div class="welcome-section"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2.5rem; border-radius: 20px; text-align: left; margin-bottom: 2rem;">
                    <h1 style="margin: 0; font-size: 2.2rem;">Hello,
                        <?php echo htmlspecialchars(explode(' ', $student['full_name'])[0]); ?>! 👋
                    </h1>
                    <p style="margin-top: 10px; opacity: 0.9; font-size: 1.1rem;">Welcome back to your academic portal.
                        Here's your current progress.</p>
                </div>

                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $active_courses; ?></div>
                        <div class="stat-label">Active Courses</div>
                    </div>
                    <div class="stat-card" onclick="window.location.href='assignments.php'" style="cursor: pointer;">
                        <div class="stat-number">
                            <?php
                            $assignment_count = 0;
                            if (!empty($sub_course_ids)) {
                                $placeholders = implode(',', array_fill(0, count($sub_course_ids), '?'));
                                $count_res = getRow("SELECT COUNT(*) as count FROM assignments WHERE sub_course_id IN ($placeholders) AND status = 'active'", $sub_course_ids);
                                $assignment_count = $count_res['count'] ?? 0;
                            }
                            echo $assignment_count;
                            ?>
                        </div>
                        <div class="stat-label">Pending Assignments</div>
                    </div>
                    <div class="stat-card" onclick="window.location.href='attendance.php'" style="cursor: pointer;">
                        <div class="stat-number">
                            <?php
                            $attendance_perc = 0;
                            if (!empty($sub_course_ids)) {
                                $placeholders = implode(',', array_fill(0, count($sub_course_ids), '?'));
                                $att_res = getRow("
                                    SELECT 
                                        COUNT(*) as total,
                                        SUM(CASE WHEN status = 'present' THEN 1 WHEN status = 'late' THEN 0.5 ELSE 0 END) as attended
                                    FROM student_attendance 
                                    WHERE enrollment_id IN (SELECT id FROM student_enrollments WHERE user_id = ? AND sub_course_id IN ($placeholders))
                                ", array_merge([$user_id], $sub_course_ids));

                                if ($att_res && $att_res['total'] > 0) {
                                    $attendance_perc = round(($att_res['attended'] / $att_res['total']) * 100);
                                }
                            }
                            echo $attendance_perc . '%';
                            ?>
                        </div>
                        <div class="stat-label">Attendance Rate</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $completed_courses; ?></div>
                        <div class="stat-label">Courses Completed</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 30px;">
                    <!-- Enrolled Courses -->
                    <div class="panel">
                        <div class="panel-header">
                            <h1><i class="fas fa-book"></i> My Learning</h1>
                        </div>
                        <div class="panel-body">
                            <?php if (empty($enrolled_courses)): ?>
                                <p class="text-muted">You haven't enrolled in any courses yet.</p>
                            <?php else: ?>
                                <div style="display: grid; gap: 15px;">
                                    <?php foreach ($enrolled_courses as $course): ?>
                                        <div
                                            style="padding: 20px; border: 1px solid #f1f5f9; border-radius: 15px; display: flex; flex-direction: column; gap: 15px;">
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <div>
                                                    <h4 style="margin: 0; color: #1e293b;">
                                                        <?php echo htmlspecialchars($course['sub_course_name']); ?>
                                                    </h4>
                                                    <p style="margin: 5px 0 0; font-size: 0.85rem; color: #64748b;">
                                                        <?php echo htmlspecialchars($course['course_name']); ?>
                                                    </p>
                                                </div>
                                                <div style="text-align: right;">
                                                    <span
                                                        style="padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; background: <?php echo $course['enrollment_status'] === 'enrolled' ? '#dcfce7' : '#dbeafe'; ?>; color: <?php echo $course['enrollment_status'] === 'enrolled' ? '#166534' : '#1e40af'; ?>;">
                                                        <?php echo ucfirst($course['enrollment_status']); ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <?php if ($course['has_marks']): ?>
                                                <div
                                                    style="display: flex; gap: 10px; border-top: 1px solid #f1f5f9; pt: 15px; padding-top: 15px;">
                                                    <a href="<?php echo $course['result_url']; ?>" target="_blank"
                                                        class="btn-primary"
                                                        style="flex: 1; font-size: 0.75rem; padding: 8px 12px; background: #3498db; justify-content: center;">
                                                        <i class="fas fa-file-invoice"></i> View Marksheet
                                                    </a>
                                                    <a href="<?php echo $course['cert_url']; ?>" target="_blank" class="btn-primary"
                                                        style="flex: 1; font-size: 0.75rem; padding: 8px 12px; background: #c5a059; justify-content: center;">
                                                        <i class="fas fa-certificate"></i> View Certificate
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($enrolled_courses)): ?>
                        <!-- Digital ID Quick Link -->
                        <div class="panel">
                            <div class="panel-header">
                                <h1><i class="fas fa-id-card"></i> Student ID</h1>
                            </div>
                            <div class="panel-body" style="text-align: center;">
                                <div
                                    style="padding: 20px; background: #f8fafc; border-radius: 15px; border: 1px dashed #cbd5e1;">
                                    <i class="fas fa-id-badge"
                                        style="font-size: 3rem; color: #667eea; margin-bottom: 15px;"></i>
                                    <h6 style="margin: 0 0 10px; font-size: 1rem;">Digital Identity Card</h6>
                                    <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 20px;">Access your official
                                        student ID for verification.</p>
                                    <button onclick="viewID()" class="btn-primary"
                                        style="width: 100%; justify-content: center;">
                                        View Digital ID
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Certificates -->
                <?php if (!empty($certificates)): ?>
                    <div class="panel" style="margin-top: 2rem;">
                        <div class="panel-header">
                            <h1><i class="fas fa-award"></i> My Certificates</h1>
                        </div>
                        <div class="panel-body">
                            <div
                                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                                <?php foreach ($certificates as $cert): ?>
                                    <div
                                        style="background: white; border: 1px solid #f1f5f9; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                                        <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 15px;">
                                            <div
                                                style="width: 50px; height: 50px; background: #fff7ed; color: #ea580c; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                                                <i class="fas fa-certificate"></i>
                                            </div>
                                            <div>
                                                <h5 style="margin: 0;"><?php echo htmlspecialchars($cert['sub_course_name']); ?>
                                                </h5>
                                                <small style="color: #64748b;">Issued on
                                                    <?php echo date('M d, Y', strtotime($cert['generated_at'])); ?></small>
                                            </div>
                                        </div>
                                        <div style="display: flex; gap: 10px;">
                                            <a href="../<?php echo $cert['certificate_url']; ?>" target="_blank"
                                                class="btn-primary" style="flex: 1; font-size: 0.8rem; padding: 10px;">
                                                Certificate
                                            </a>
                                            <a href="../<?php echo $cert['marksheet_url']; ?>" target="_blank"
                                                class="btn-primary"
                                                style="flex: 1; font-size: 0.8rem; padding: 10px; background: #64748b;">
                                                Marksheet
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- ID Card Modal -->
    <div id="idCardModal" class="modal"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div class="modal-content"
            style="background: white; border-radius: 20px; width: 90%; max-width: 500px; overflow: hidden; animation: slideUp 0.3s ease-out;">
            <div class="modal-header"
                style="padding: 20px; background: var(--student-sidebar-bg); color: white; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">Identity Card</h3>
                <button onclick="closeModal()"
                    style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body" id="idCardContainer" style="padding: 30px;">
                <!-- Content loaded via viewID() -->
            </div>
            <div class="modal-actions" style="padding: 20px; border-top: 1px solid #f1f5f9; text-align: center;">
                <button id="modalDownloadBtn" onclick="downloadFromModal()" class="btn-primary">
                    <i class="fas fa-download"></i> Download PDF
                </button>
            </div>
        </div>
    </div>

    <style>
        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal {
            display: none;
        }

        .modal.show {
            display: flex !important;
        }
    </style>

    <script>
        function viewID() {
            const modal = document.getElementById('idCardModal');
            const container = document.getElementById('idCardContainer');
            modal.classList.add('show');
            container.innerHTML = '<div style="text-align: center;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

            const formData = new FormData();
            formData.append('student_id', '<?php echo $student['id']; ?>');

            fetch('../id.php', { method: 'POST', body: formData })
                .then(response => response.text())
                .then(html => {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    const idCard = tempDiv.querySelector('#idCard');
                    if (idCard) {
                        idCard.style.margin = '0 auto';
                        idCard.style.boxShadow = 'none';
                        container.innerHTML = idCard.outerHTML;
                    } else {
                        container.innerHTML = 'Error loading ID card.';
                    }
                });
        }

        function closeModal() {
            document.getElementById('idCardModal').classList.remove('show');
        }

        function downloadFromModal() {
            const idCardElement = document.querySelector('#idCardContainer #idCard');
            if (!idCardElement) return;

            const downloadBtn = document.querySelector('#modalDownloadBtn');
            downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            downloadBtn.disabled = true;

            html2canvas(idCardElement, { scale: 3, useCORS: true }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'px',
                    format: [idCardElement.offsetWidth, idCardElement.offsetHeight]
                });
                pdf.addImage(imgData, 'PNG', 0, 0, idCardElement.offsetWidth, idCardElement.offsetHeight);
                pdf.save('student-id-card.pdf');
                downloadBtn.innerHTML = '<i class="fas fa-download"></i> Download PDF';
                downloadBtn.disabled = false;
            });
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }
    </script>
</body>

</html>