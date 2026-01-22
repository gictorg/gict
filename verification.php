<?php
require_once 'config/database.php';
require_once 'header.php';
?>

<div class="main-content">
    <link rel="stylesheet" href="assets/css/student-corner.css">

    <div class="container student-corner-container">
        <?php
        $student = null;
        $error = null;
        $show_form = true;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['enrollment_no'])) {
            $enrollment_no = trim($_POST['enrollment_no']);

            // Query to fetch student and enrollment details
            $sql = "
                SELECT 
                    u.username as enrollment_no,
                    u.full_name,
                    u.date_of_birth,
                    u.profile_image,
                    'G.I.C.T COMPUTER COLLEGE OF IT & MANAGEMENT JAUNPUR' as center_name,
                    c.name as course_name,
                    sc.name as sub_course_name,
                    se.enrollment_date,
                    se.status
                FROM users u
                LEFT JOIN student_enrollments se ON u.id = se.user_id
                LEFT JOIN sub_courses sc ON se.sub_course_id = sc.id
                LEFT JOIN courses c ON sc.course_id = c.id
                WHERE u.username = ? AND u.user_type_id = 2
                ORDER BY se.enrollment_date DESC LIMIT 1
            ";

            $student = getRow($sql, [$enrollment_no]);

            if ($student) {
                $show_form = false;
            } else {
                $error = "No student found with this Enrollment No.";
            }
        }
        ?>

        <?php if ($show_form): ?>
            <div class="student-corner-card">
                <h2 class="student-corner-title">Student Verification</h2>

                <form method="POST" action="" class="verification-form">
                    <div class="form-group">
                        <label for="enrollment_no" class="form-label">Enter Roll No.*</label>
                        <input type="text" id="enrollment_no" name="enrollment_no" class="form-control" required
                            style="text-transform: uppercase;"
                            value="<?php echo isset($_POST['enrollment_no']) ? htmlspecialchars(strtoupper($_POST['enrollment_no'])) : ''; ?>">
                    </div>
                    <button type="submit" class="btn-verify">Verify</button>

                    <?php if ($error): ?>
                        <div class="error-message" style="width: 100%; margin-top: 20px;"><i
                                class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($student): ?>
            <?php
            // Calculate session from enrollment date
            $enrollment_year = date('Y', strtotime($student['enrollment_date']));
            $session = $enrollment_year . '-' . substr($enrollment_year + 1, -2);
            $course_display = $student['course_name'] . ' (' . $student['sub_course_name'] . ')';
            $dob = !empty($student['date_of_birth']) ? date('d-m-Y', strtotime($student['date_of_birth'])) : 'N/A';
            $photo = !empty($student['profile_image']) ? $student['profile_image'] : 'assets/images/default-student.png';
            ?>

            <div class="verification-result">
                <!-- Watermark -->
                <div class="watermark">
                    <img src="assets/images/logo bgremove.png" alt="Institute Logo">
                </div>

                <div class="verified-header">
                    <span class="verified-badge">
                        Verified <i class="fas fa-check-circle"></i>
                    </span>
                </div>

                <div class="student-details-container">
                    <div class="details-table-wrapper">
                        <table class="details-table">
                            <tr>
                                <td class="label-cell">Session :</td>
                                <td><?php echo htmlspecialchars($session); ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Enrollment No :</td>
                                <td><?php echo htmlspecialchars(strtoupper($student['enrollment_no'])); ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Student Name :</td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Date Of Birth :</td>
                                <td><?php echo htmlspecialchars($dob); ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Father Name :</td>
                                <td>N/A</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Course Name :</td>
                                <td><?php echo htmlspecialchars($course_display); ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Center Name :</td>
                                <td><?php echo htmlspecialchars($student['center_name']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="photo-wrapper">
                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="Student Photo" class="student-photo">
                    </div>
                </div>

                <!-- <div class="action-container" style="text-align: center; margin-top: 20px;">
                    <a href="verification.php" class="btn-verify"
                        style="text-decoration: none; display: inline-block;">Verify Another</a>
                </div> -->
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>