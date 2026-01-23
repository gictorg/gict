<?php
require_once 'config/database.php';
require_once 'header.php';
?>

<div class="main-content">
    <link rel="stylesheet" href="assets/css/student-corner.css">
    <link rel="stylesheet" href="assets/css/marksheet.css">

    <div class="container student-corner-container">
        <?php
        $student = null;
        $marks = [];
        $enrollment = null;
        $error = null;
        $show_form = true;

        if (($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['enrollment_no'])) || isset($_GET['rid'])) {
            $enrollment_no = isset($_GET['rid']) ? base64_decode($_GET['rid']) : trim($_POST['enrollment_no']);
            $submitted_dob = $_POST['dob'] ?? null;

            // 1. Fetch Student & Enrollment Info
            $sql_student = "
                SELECT 
                    u.id as user_id,
                    u.username as enrollment_no,
                    u.full_name,
                    u.father_name,
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
                        <script>
                            setTimeout(function () {
                                var errorAlert = document.getElementById('error-alert');
                                if (errorAlert) {
                                    errorAlert.style.opacity = '0';
                                    setTimeout(function () {
                                        errorAlert.style.display = 'none';
                                    }, 500);
                                }
                            }, 2000);
                        </script>
                    <?php endif; ?>
                </form>
            </div>
        <?php else: ?>
            <!-- Professional Marksheet View -->
            <div class="marksheet-container">
                <div class="marksheet-inner">
                    <?php if (!empty($student['marksheet_no'])): ?>
                        <div class="marksheet-no-top-left">
                            Certificate No.: <span><?php echo htmlspecialchars($student['marksheet_no']); ?></span>
                        </div>
                    <?php endif; ?>
                    <!-- Watermark -->
                    <img src="assets/images/logo bgremove.png" class="marksheet-watermark" alt="">
                    <div class="watermark-text"></div>

                    <div class="marksheet-header">
                        <img src="logo.png" alt="GICT Logo" class="institute-logo">
                        <h1 class="institute-name"><?php echo htmlspecialchars($student['institute_name']); ?></h1>
                        <p class="institute-address">
                            <?php echo htmlspecialchars($student['institute_address']); ?>
                        </p>
                        <div class="marksheet-title">STATEMENT OF MARKS</div>
                    </div>

                    <div class="student-info-grid">
                        <div class="student-details">
                            <div class="detail-row">
                                <span class="detail-label">Enrollment No:</span>
                                <span
                                    class="detail-value"><?php echo htmlspecialchars(strtoupper($student['enrollment_no'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Candidate Name:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($student['full_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Father's Name:</span>
                                <span
                                    class="detail-value"><?php echo htmlspecialchars($student['father_name'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Session:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($student['session']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Course Name:</span>
                                <span
                                    class="detail-value"><?php echo htmlspecialchars($student['sub_course_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Duration:</span>
                                <span
                                    class="detail-value"><?php echo htmlspecialchars($student['course_duration']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Institute/Center:</span>
                                <span
                                    class="detail-value"><?php echo htmlspecialchars($student['institute_name'] ?: 'GICT Main Center'); ?></span>
                            </div>
                        </div>
                        <div class="student-photo-box">
                            <img src="<?php echo !empty($student['profile_image']) ? htmlspecialchars($student['profile_image']) : 'assets/images/default-student.png'; ?>"
                                alt="Student Photo">
                        </div>
                    </div>

                    <?php
                    $semesters = [];
                    foreach ($marks as $mark) {
                        $sem = $mark['semester'] ?: 1;
                        $semesters[$sem][] = $mark;
                    }
                    ksort($semesters);

                    $total_max = 0;
                    $total_obtained = 0;

                    foreach ($semesters as $sem_num => $sem_marks):
                        $has_sem_marks = false;
                        foreach ($sem_marks as $m) {
                            if ($m['total_marks'] !== null) {
                                $has_sem_marks = true;
                                break;
                            }
                        }
                        if (!$has_sem_marks)
                            continue;

                        $sem_label = is_numeric($sem_num) ? "Semester " . $sem_num : $sem_num;
                        ?>
                        <div class="semester-divider"
                            style="text-align: left; margin: 20px 0 10px; font-weight: 800; color: #2c3e50; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #c5a059; display: inline-block;">
                            <?php echo htmlspecialchars($sem_label); ?>
                        </div>
                        <table class="marks-table">
                            <thead>
                                <tr>
                                    <th class="subject-name">Subject / Module Name</th>
                                    <th>Max Marks</th>
                                    <th>Min Marks</th>
                                    <th>Marks Obtained</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sem_marks as $mark):
                                    if ($mark['total_marks'] !== null):
                                        $obt = (int) $mark['total_marks'];
                                        $total_max += $mark['max_marks'];
                                        $total_obtained += $obt;
                                        ?>
                                        <tr>
                                            <td class="subject-name"><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                                            <td><?php echo $mark['max_marks']; ?></td>
                                            <td><?php echo round($mark['max_marks'] * 0.33); ?></td>
                                            <td><?php echo $mark['total_marks']; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endforeach; ?>

                    <?php
                    $percentage = ($total_max > 0) ? ($total_obtained / $total_max) * 100 : 0;
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

                    $result_status = ($percentage >= 33) ? 'PASS' : 'FAIL';
                    ?>

                    <div class="marks-summary">
                        <div class="summary-item">
                            <span class="summary-label">Grand Total</span>
                            <span class="summary-value"><?php echo $total_obtained; ?> / <?php echo $total_max; ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Percentage</span>
                            <span class="summary-value"><?php echo number_format($percentage, 2); ?>%</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Grade</span>
                            <span class="summary-value"><?php echo $grade; ?></span>
                        </div>
                        <div class="summary-item result-item">
                            <span class="summary-label">Result</span>
                            <span
                                class="summary-value result-status <?php echo ($result_status == 'PASS') ? 'status-pass' : 'status-fail'; ?>">
                                <?php echo $result_status; ?>
                            </span>
                            <img src="assets/images/logo bgremove.png" class="result-seal" alt="GICT Seal">
                        </div>
                    </div>

                    <div class="marksheet-footer">
                        <div class="signature-box">
                            <div class="sig-line"></div>
                            <span class="sig-label">Prepared By</span>
                        </div>
                        <div class="signature-box">
                            <div class="sig-line"></div>
                            <span class="sig-label">Checked By</span>
                            <div class="sig-name"><?php echo htmlspecialchars($student['checked_by'] ?: 'Faculty'); ?></div>
                        </div>
                        <div class="signature-box">
                            <div class="sig-line"></div>
                            <span class="sig-label">Controller of Examination</span>
                        </div>
                    </div>
                </div>

                <div class="action-container no-print" style="text-align: center; margin-top: 30px;">
                    <button onclick="window.print()" class="btn-verify" style="background: #3498db;">
                        <i class="fas fa-print"></i> Print Marksheet
                    </button>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>