<?php
require_once 'config/database.php';
require_once 'header.php';
require_once 'includes/qr_helper.php';

// Security configuration
$encryption_key = "GICT_SECURE_KEY_2026";
$cipher_method = "aes-256-cbc";
?>

<div class="main-content">
    <link rel="stylesheet" href="assets/css/student-corner.css">
    <link rel="stylesheet" href="assets/css/professional-marksheet.css">


    <style>
        /* Search form styles */
        .search-card {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            margin-bottom: 40px;
        }
    </style>

    <div class="professional-marksheet-wrapper">
        <?php
        $student = null;
        $marks = [];
        $error = null;
        $show_marksheet = false;

        $enrollment_no = $_POST['enrollment_no'] ?? $_GET['id'] ?? null;
        if ($enrollment_no)
            $enrollment_no = trim(strtoupper($enrollment_no));
        $dob = $_POST['dob'] ?? $_GET['dob'] ?? null;

        if ($enrollment_no && $dob) {
            $sql = "
                SELECT 
                    u.id as user_id,
                    u.username as enrollment_no,
                    u.full_name,
                    u.father_name,
                    u.mother_name,
                    u.profile_image,
                    u.date_of_birth,
                    se.id as enrollment_id,
                    se.sub_course_id,
                    se.enrollment_date,
                    se.marksheet_no,
                    sc.name as sub_course_name,
                    sc.duration as course_duration,
                    sc.course_id
                FROM users u
                JOIN student_enrollments se ON u.id = se.user_id
                JOIN sub_courses sc ON se.sub_course_id = sc.id
                WHERE u.username = ? AND u.date_of_birth = ?
                LIMIT 1
            ";
            $student = getRow($sql, [$enrollment_no, $dob]);

            if ($student) {
                // Fetch marks
                $sql_marks = "
                    SELECT 
                        cs.subject_name,
                        cs.semester,
                        cs.max_marks,
                        cs.theory_marks as max_theory,
                        cs.practical_marks as max_practical,
                        sm.theory_marks,
                        sm.practical_marks,
                        sm.total_marks,
                        sm.grade,
                        u_chk.full_name as checked_by
                    FROM course_subjects cs
                    LEFT JOIN student_marks sm ON cs.id = sm.subject_id AND sm.enrollment_id = ?
                    LEFT JOIN users u_chk ON sm.checked_by = u_chk.id
                    WHERE cs.sub_course_id = ?
                    ORDER BY cs.semester, cs.subject_name
                ";
                $marks = getRows($sql_marks, [$student['enrollment_id'], $student['sub_course_id']]);

                if (empty($marks)) {
                    $error = "Marks not uploaded for this student yet.";
                } else {
                    $show_marksheet = true;
                    // Token for secure imaging
                    if (session_status() === PHP_SESSION_NONE)
                        session_start();
                    $img_token = bin2hex(random_bytes(16));
                    $_SESSION['marks_viewing'] = true;
                    $_SESSION['marks_image_tokens'][$img_token] = time() + 300;
                    $_SESSION['marks_image_token'] = $img_token;
                }
            } else {
                $error = "Student not found or incorrect Details.";
            }
        }

        if (!$show_marksheet): ?>
            <div class="search-card no-print">
                <h2 class="student-corner-title">Print Official Marksheet</h2>
                <form method="POST" class="verification-form">
                    <div class="form-group">
                        <label class="form-label">Enrollment No / Roll No:</label>
                        <input type="text" name="enrollment_no" class="form-control" required
                            style="text-transform: uppercase;" value="<?= htmlspecialchars($enrollment_no) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth:</label>
                        <input type="date" name="dob" class="form-control" required value="<?= htmlspecialchars($dob) ?>">
                    </div>
                    <button type="submit" class="btn-verify">View Marksheet</button>
                    <a href="result.php" class="btn-verify"
                        style="background: #95a5a6; margin-top: 10px; display: block; text-align: center; text-decoration: none;">Back
                        to Results</a>
                    <?php if ($error): ?>
                        <p style="color: #e74c3c; margin-top: 15px; font-weight: 500; text-align: center;">
                            <?= $error ?>
                        </p>
                    <?php endif; ?>
                </form>
            </div>
        <?php else:
            $total_max = 0;
            $total_obt = 0;
            foreach ($marks as $m) {
                $total_max += ($m['max_marks'] ?: 0);
                $total_obt += ($m['total_marks'] ?: 0);
            }
            $total_th_obt = 0;
            $total_th_max = 0;
            $total_pr_obt = 0;
            $total_pr_max = 0;
            $percentage = $total_max > 0 ? ($total_obt / $total_max) * 100 : 0;
            $result_status = $percentage >= 40 ? "PASS" : "FAIL";

            // Generate Verification URL for QR Code
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $verify_url = "$protocol://$host/result.php?id=" . urlencode($student['enrollment_no']) . "&dob=" . urlencode($student['date_of_birth']);
            $qr_code_html = generateQRCode($student['enrollment_no'], $student['full_name'], 85);
            ?>
            <div class="action-container no-print" style="margin-bottom: 20px; display: flex; gap: 15px;">
                <a href="result.php" class="btn-verify" style="background: #95a5a6; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <button onclick="window.print()" class="btn-verify" style="background: #3498db;">
                    <i class="fas fa-print"></i> Print Official Marksheet
                </button>
            </div>

            <div class="marksheet-outer-container" id="marksheet"
                style="background-image: url('secure_marksheet_template.php<?php echo isset($_SESSION['marks_image_token']) ? "?t=" . $_SESSION['marks_image_token'] : ""; ?>');">
                <!-- Data Overlays -->
                <div class="m-overlay mo-name">
                    <?= strtoupper($student['full_name']) ?>
                </div>
                <div class="m-overlay mo-father">
                    <?= strtoupper($student['father_name'] ?: '---') ?>
                </div>
                <div class="m-overlay mo-atc">GICT COMPUTER COLLEGE OF IT & MANAGEMENT JAUNPUR
                </div>
                <div class="m-overlay mo-course">
                    <?= strtoupper($student['sub_course_name']) ?>
                </div>

                <?php if (!empty($student['profile_image'])): ?>
                    <img src="<?= $student['profile_image'] ?>" class="m-overlay mo-photo" alt="Student Photo">
                <?php else: ?>
                    <div class="m-overlay mo-photo"
                        style="display: flex; justify-content: center; align-items: center; background: #f9f9f9; font-size: 10px; color: #aaa;">
                        No Photo</div>
                <?php endif; ?>

                <div class="m-overlay mo-course-code">
                    <?= strtoupper($student['course_id']) ?>
                </div>
                <div class="m-overlay mo-student-id">
                    <?= strtoupper($student['enrollment_no']) ?>
                </div>
                <div class="m-overlay mo-dob">
                    <?= date('d-m-Y', strtotime($student['date_of_birth'])) ?>
                </div>
                <div class="m-overlay mo-marksheet-id">
                    <?= $student['marksheet_no'] ?: 'GICT/' . date('Y') . '/' . $student['enrollment_id'] ?>
                </div>

                <!-- Subjects Table Rows -->
                <?php
                $row_top = 435;
                $row_height = 24.5;
                $display_idx = 0;
                $current_semester = null;
                foreach ($marks as $m):
                    // Detect semester change
                    if ($current_semester !== $m['semester']):
                        $current_semester = $m['semester'];
                        $sem_top = $row_top + ($display_idx * $row_height);
                        if ($display_idx < 12): // Safeguard
                            ?>
                            <div class="m-table-row" style="top: <?= $sem_top ?>px;">
                                <div class="m-overlay mo-subject" style="font-weight: 700; color: #3498db; text-decoration: underline;">
                                    SEMESTER - <?= $current_semester ?>
                                </div>
                            </div>
                            <?php
                            $display_idx++;
                        endif;
                    endif;

                    $current_top = $row_top + ($display_idx * $row_height);
                    if ($display_idx >= 12)
                        break; // Limit total rows
                    ?>
                    <div class="m-table-row" style="top: <?= $current_top ?>px;">
                        <div class="m-overlay mo-subject">
                            <?= $m['subject_name'] ?>
                        </div>
                        <div class="m-overlay mo-th-obt">
                            <?= $m['theory_marks'] !== null ? $m['theory_marks'] : '--' ?>
                        </div>
                        <div class="m-overlay mo-th-max">
                            <?= $m['max_theory'] ?: '100' ?>
                        </div>
                        <div class="m-overlay mo-pr-obt">
                            <?= $m['practical_marks'] !== null ? $m['practical_marks'] : '--' ?>
                        </div>
                        <div class="m-overlay mo-pr-max">
                            <?= $m['max_practical'] ?: '0' ?>
                        </div>
                    </div>
                    <?php
                    $total_th_obt += (int) $m['theory_marks'];
                    $total_th_max += (int) ($m['max_theory'] ?: 100);
                    $total_pr_obt += (int) $m['practical_marks'];
                    $total_pr_max += (int) ($m['max_practical'] ?: 0);
                    $display_idx++;
                endforeach;

                // Grand Total Row
                $total_row_top = $row_top + ($display_idx * $row_height);
                ?>
                <?php
                // Position the summary total row at the fixed location on the template
                $summary_row_top = 718;
                ?>
                <div class="m-table-row" style="top: <?= $summary_row_top ?>px;">
                    <div class="m-overlay mo-subject"></div>
                    <div class="m-overlay mo-sum-th-obt">
                        <?= $total_th_obt ?>
                    </div>
                    <div class="m-overlay mo-sum-th-max">
                        <?= $total_th_max ?>
                    </div>
                    <div class="m-overlay mo-sum-pr-obt">
                        <?= $total_pr_obt ?>
                    </div>
                    <div class="m-overlay mo-sum-pr-max">
                        <?= $total_pr_max ?>
                    </div>
                </div>

                <!-- Summary -->
                <div class="m-overlay mo-result" style="color: <?= $result_status == 'PASS' ? '#27ae60' : '#e74c3c' ?>;">
                    <?= $result_status ?>
                </div>
                <div class="m-overlay mo-percentage">
                    <?= number_format($percentage, 2) ?>%
                </div>
                <div class="m-overlay mo-total-max">
                    <?= $total_max ?>
                </div>
                <div class="m-overlay mo-total-obt">
                    <?= $total_obt ?>
                </div>


                <div class="m-overlay mo-date">
                    <?= date('d-m-Y') ?>
                </div>

                <!-- Verification QR Code -->
                <div class="m-overlay mo-qr">
                    <?= generateUrlQRCode($verify_url, 85) ?>
                </div> <!-- end marksheet-outer-container -->
            </div> <!-- end professional-marksheet-wrapper -->

            <div class="action-container no-print">
                <button onclick="window.print()" class="btn-marksheet">
                    <i class="fas fa-print"></i> Print Marksheet
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>