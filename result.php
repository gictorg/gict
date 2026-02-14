<?php
require_once 'config/database.php';
require_once 'header.php';
require_once 'includes/qr_helper.php';
?>

<div class="main-content">
    <link rel="stylesheet" href="assets/css/student-corner.css">
    <link rel="stylesheet" href="assets/css/marksheet.css">
    <link rel="stylesheet" href="assets/css/professional-marksheet.css">

    <div class="container student-corner-container">
        <?php
        $student = null;
        $marks = [];
        $enrollment = null;
        $error = null;
        $show_form = true;

        if (($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['enrollment_no'])) || isset($_GET['rid'])) {
            $enrollment_no = isset($_GET['rid']) ? base64_decode($_GET['rid']) : trim(strtoupper($_POST['enrollment_no']));
            $submitted_dob = $_POST['dob'] ?? null;

            // 1. Fetch Student & Enrollment Info
            $sql_student = "
                SELECT 
                    u.id as user_id,
                    u.username as enrollment_no,
                    u.full_name,
                    u.father_name,
                    u.mother_name,
                    u.date_of_birth,
                    u.address,
                    u.profile_image,
                    se.id as enrollment_id,
                    se.sub_course_id,
                    se.enrollment_date,
                    se.marksheet_no,
                    sc.name as sub_course_name,
                    sc.duration as course_duration
                FROM users u
                JOIN student_enrollments se ON u.id = se.user_id
                JOIN sub_courses sc ON se.sub_course_id = sc.id
                WHERE u.username = ? AND u.user_type_id = 2
                ORDER BY se.enrollment_date DESC LIMIT 1
            ";

            $student = getRow($sql_student, [$enrollment_no]);

            if ($student) {
                // Verify DOB if it's a POST request (direct links via rid currently skip DOB for convenience, 
                // but we can enforce it if needed. For now, let's enforce it for POST.)
                $is_verified = false;
                if (isset($_GET['rid'])) {
                    $is_verified = true; // Trust the direct link for now
                } elseif ($submitted_dob && $student['date_of_birth'] === $submitted_dob) {
                    $is_verified = true;
                }

                if ($is_verified) {
                    // Calculate Session automatically
                    $enrollDate = new DateTime($student['enrollment_date']);
                    $startYear = $enrollDate->format('Y');

                    // Parse duration to estimate end year
                    $durationStr = $student['course_duration'];
                    $months = 0;
                    if (preg_match('/(\d+)\s*month/', $durationStr, $matches)) {
                        $months = (int) $matches[1];
                    } elseif (preg_match('/(\d+)\s*year/', $durationStr, $matches)) {
                        $months = (int) $matches[1] * 12;
                    } else {
                        $months = 12; // Default to 1 year 
                    }

                    $endDate = clone $enrollDate;
                    $endDate->modify("+$months months");
                    $endYear = $endDate->format('Y');

                    if ($startYear == $endYear) {
                        $student['session'] = $startYear . "-" . ($startYear + 1);
                    } else {
                        $student['session'] = $startYear . "-" . $endYear;
                    }

                    // Set default institute info if table doesn't exist
                    $student['institute_name'] = 'G.I.C.T COMPUTER COLLEGE OF IT & MANAGEMENT JAUNPUR';
                    $student['institute_address'] = 'MADARDIH, RAIPUR, JAUNPUR (U.P.)';

                    // 2. Fetch Marks Join with Subjects and Faculty
                    $sql_marks = "
                        SELECT 
                            cs.subject_name,
                            cs.semester,
                            cs.max_marks,
                            sm.theory_marks,
                            sm.practical_marks,
                            sm.total_marks,
                            sm.grade,
                            u.full_name as checked_by_name
                        FROM course_subjects cs
                        LEFT JOIN student_marks sm ON cs.id = sm.subject_id AND sm.enrollment_id = ?
                        LEFT JOIN users u ON sm.checked_by = u.id
                        WHERE cs.sub_course_id = ?
                        ORDER BY cs.semester, cs.subject_name
                    ";
                    $marks = getRows($sql_marks, [$student['enrollment_id'], $student['sub_course_id']]);

                    $has_some_marks = false;
                    foreach ($marks as $m) {
                        if ($m['total_marks'] !== null) {
                            $has_some_marks = true;
                            break;
                        }
                    }

                    if (!$has_some_marks) {
                        $error = "Marks for this enrollment have not been uploaded yet.";
                        $student = null;
                    } else {
                        // Extract checked_by name from first record that has it
                        $student['checked_by'] = '';
                        foreach ($marks as $m) {
                            if (!empty($m['checked_by_name'])) {
                                $student['checked_by'] = $m['checked_by_name'];
                                break;
                            }
                        }
                        // Enable professional marksheet template view
                        if (session_status() === PHP_SESSION_NONE)
                            session_start();
                        $_SESSION['marks_viewing'] = true;

                        $show_form = false;
                    }
                } else {
                    $error = "Invalid Date of Birth for the provided Enrollment No.";
                    $student = null;
                }
            } else {
                $error = "No student record found with Enrollment/Roll No: " . htmlspecialchars($enrollment_no);
            }
        }
        ?>

        <?php if ($show_form): ?>
            <div class="student-corner-card">
                <h2 class="student-corner-title">Check Student Result</h2>

                <form method="POST" action="" class="verification-form">
                    <div class="form-group">
                        <label for="enrollment_no" class="form-label">Roll No / Enrollment No:*</label>
                        <input type="text" id="enrollment_no" name="enrollment_no" class="form-control" required
                            style="text-transform: uppercase;"
                            value="<?php echo isset($_POST['enrollment_no']) ? htmlspecialchars(strtoupper($_POST['enrollment_no'])) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="dob" class="form-label">Date of Birth:*</label>
                        <input type="date" id="dob" name="dob" class="form-control" required
                            value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
                    </div>
                    <button type="submit" class="btn-verify">Get Statement of Marks</button>

                    <?php if ($error): ?>
                        <div id="error-alert" class="error-message"
                            style="width: 100%; margin-top: 20px; transition: opacity 0.5s ease-out;">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                        <script>                     setTimeout(function () { var errorAlert = document.getElementById('error-alert'); if (errorAlert) { errorAlert.style.opacity = '0'; setTimeout(function () { errorAlert.style.display = 'none'; }, 500); } }, 2000);
                        </script>
                    <?php endif; ?>
                </form>
            </div>
        <?php else: ?>
            <!-- Professional Marksheet View -->
            <div class="professional-marksheet-wrapper">
                <div class="marksheet-outer-container" id="printableMarksheet"
                    style="background-image: url('secure_marksheet_template.php<?php echo isset($_SESSION['marks_image_token']) ? "?t=" . $_SESSION['marks_image_token'] : ""; ?>');">
                    <div class="m-overlay mo-name"><?php echo strtoupper($student['full_name']); ?></div>
                    <div class="m-overlay mo-father"><?php echo strtoupper($student['father_name'] ?: 'N/A'); ?></div>
                    <div class="m-overlay mo-atc">GICT COMPUTER COLLEGE OF IT & MANAGEMENT JAUNPUR</div>
                    <div class="m-overlay mo-course"><?php echo strtoupper($student['sub_course_name']); ?></div>

                    <?php if (!empty($student['profile_image'])): ?>
                        <img src="<?php echo $student['profile_image']; ?>" class="m-overlay mo-photo" alt="Student Photo">
                    <?php else: ?>
                        <div class="m-overlay mo-photo"
                            style="display: flex; justify-content: center; align-items: center; background: #f9f9f9; font-size: 10px; color: #aaa;">
                            No Photo</div>
                    <?php endif; ?>

                    <div class="m-overlay mo-course-code"><?php echo strtoupper($student['sub_course_id']); ?></div>
                    <div class="m-overlay mo-student-id"><?php echo strtoupper($student['enrollment_no']); ?></div>
                    <div class="m-overlay mo-dob"><?php echo date('d-m-Y', strtotime($student['date_of_birth'])); ?></div>
                    <div class="m-overlay mo-marksheet-id">
                        <?php echo $student['marksheet_no'] ?: 'GICT/' . date('Y') . '/' . $student['enrollment_id']; ?>
                    </div>

                    <?php
                    $row_top = 435;
                    $idx = 0;
                    $total_max = 0;
                    $total_obtained = 0;
                    $total_th_obt = 0;
                    $total_th_max = 0;
                    $total_pr_obt = 0;
                    $total_pr_max = 0;
                    $current_semester = null;
                    foreach ($marks as $mark):
                        if ($mark['total_marks'] !== null):
                            // Detect semester change
                            if ($current_semester !== $mark['semester']):
                                $current_semester = $mark['semester'];
                                $sem_top = $row_top + ($idx * 24.5);
                                ?>
                                <div class="m-table-row" style="top: <?php echo $sem_top; ?>px;">
                                    <div class="m-overlay mo-subject"
                                        style="font-weight: 700; color: #3498db; text-decoration: underline;">
                                        SEMESTER - <?php echo $current_semester; ?>
                                    </div>
                                </div>
                                <?php
                                $idx++;
                            endif;

                            $curr_top = $row_top + ($idx * 24.5);
                            $total_max += $mark['max_marks'];
                            $total_obtained += $mark['total_marks'];

                            $th_max = $mark['theory_marks'] !== null ? 100 : 0;
                            $pr_max = $mark['practical_marks'] !== null ? ($mark['max_marks'] - 100 > 0 ? $mark['max_marks'] - 100 : 50) : 0;

                            $total_th_obt += (int) $mark['theory_marks'];
                            $total_th_max += $th_max;
                            $total_pr_obt += (int) $mark['practical_marks'];
                            $total_pr_max += $pr_max;
                            ?>
                            <div class="m-table-row" style="top: <?php echo $curr_top; ?>px;">
                                <div class="m-overlay mo-subject"><?php echo $mark['subject_name']; ?></div>
                                <div class="m-overlay mo-th-obt">
                                    <?php echo $mark['theory_marks'] !== null ? $mark['theory_marks'] : '--'; ?>
                                </div>
                                <div class="m-overlay mo-th-max"><?php echo $mark['theory_marks'] !== null ? 100 : '--'; ?></div>
                                <div class="m-overlay mo-pr-obt">
                                    <?php echo $mark['practical_marks'] !== null ? $mark['practical_marks'] : '--'; ?>
                                </div>
                                <div class="m-overlay mo-pr-max">
                                    <?php echo $mark['practical_marks'] !== null ? ($mark['max_marks'] - 100 > 0 ? $mark['max_marks'] - 100 : 50) : '--'; ?>
                                </div>
                            </div>
                            <?php
                            $idx++;
                        endif;
                    endforeach;

                    // Header Row for Grand Total
                    $total_row_top = $row_top + ($idx * 24.5);
                    ?>
                    <?php
                    // Position the summary total row at the fixed location on the template
                    $summary_row_top = 718;
                    ?>
                    <div class="m-table-row" style="top: <?php echo $summary_row_top; ?>px;">
                        <div class="m-overlay mo-subject"></div>
                        <div class="m-overlay mo-sum-th-obt">
                            <?php echo $total_th_obt; ?>
                        </div>
                        <div class="m-overlay mo-sum-th-max">
                            <?php echo $total_th_max; ?>
                        </div>
                        <div class="m-overlay mo-sum-pr-obt">
                            <?php echo $total_pr_obt; ?>
                        </div>
                        <div class="m-overlay mo-sum-pr-max">
                            <?php echo $total_pr_max; ?>
                        </div>
                    </div>

                    <?php
                    $percentage = ($total_max > 0) ? ($total_obtained / $total_max) * 100 : 0;
                    $res_status = ($percentage >= 33) ? 'PASS' : 'FAIL';

                    // Generate Verification URL for QR Code
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                    $host = $_SERVER['HTTP_HOST'];
                    $verify_url = "$protocol://$host/result.php?id=" . urlencode($student['enrollment_no']) . "&dob=" . urlencode($student['date_of_birth']);
                    ?>

                    <div class="m-overlay mo-result"
                        style="color: <?php echo ($res_status == 'PASS' ? '#27ae60' : '#e74c3c'); ?>">
                        <?php echo $res_status; ?>
                    </div>
                    <div class="m-overlay mo-percentage"><?php echo number_format($percentage, 2); ?>%</div>
                    <div class="m-overlay mo-total-max"><?php echo $total_max; ?></div>
                    <div class="m-overlay mo-total-obt"><?php echo $total_obtained; ?></div>


                    <div class="m-overlay mo-date"><?php echo date('d-m-Y'); ?></div>


                    <div class="m-overlay mo-qr">
                        <?php echo generateUrlQRCode($verify_url, 85); ?>
                    </div>
                </div>

                <div class="action-container no-print"
                    style="text-align: center; margin-top: 30px; display: flex; justify-content: center;">
                    <button onclick="window.print()" class="btn-verify" style="background: #3498db;">
                        <i class="fas fa-print"></i> Print Marksheet
                    </button>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>