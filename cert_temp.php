<?php
require_once 'config/database.php';
require_once 'header.php';
require_once 'includes/qr_helper.php';
?>

<div class="main-content">
    <link rel="stylesheet" href="assets/css/student-corner.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Great+Vibes&display=swap');

        .certificate-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 0;
            background: #f0f2f5;
        }

        /* Certificate Container - Fixed Pixel Size to match Template Image */
        .certificate-container.gict-official-cert {
            width: 596px;
            /* Exact width of your template jpg */
            height: 842px;
            /* Exact height of your template jpg */
            background-image: url('assets/certificates/gict_cert_template.jpg');
            background-size: cover;
            background-position: center;
            position: relative;
            background-color: #fff;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
            margin: 0 auto;
            overflow: hidden;
            border: none;
        }

        /* Pixel-Perfect Overlays - Final Alignment Fixes */
        .cert-overlay {
            position: absolute;
            z-index: 10;
            color: #1a2a6c;
            font-family: 'Playfair Display', serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ATC Code area */
        .overlay-atc-code {
            top: 312px;
            left: 71px;
            width: 70px;
            height: 25px;
            font-size: 9px;
            font-weight: 700;
            justify-content: center;
            align-items: center;
        }

        /* ATC Name area */
        .overlay-atc-name {
            top: 312px;
            left: 153px;
            width: 275px;
            height: 25px;
            font-size: 8px;
            font-weight: 700;
            justify-content: center;
            align-items: center;
            line-height: 1.2;
        }

        /* Student Photo */
        .overlay-photo {
            top: 273px;
            left: 470px;
            width: 50px;
            height: 63px;
            background: #fff;
            border: none;
        }

        .overlay-photo img {
            margin-right: 7px;
            width: 130%;
            margin-top: 9px;
            height: 113%;
            object-fit: cover;
        }

        /* Student Name */
        .overlay-name {
            top: 358px;
            left: 60px;
            right: 60px;
            height: 28px;
            font-size: 18px;
            font-weight: 700;
            color: #1a2a6c;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        /* Letter Grade before the word "Grade" */
        .overlay-grade-letter {
            top: 407px;
            left: 290px;
            /* Adjust based on template */
            width: 40px;
            height: 18px;
            font-size: 16px;
            font-weight: 700;
            color: #000;
        }

        /* Percentage inside ( ) */
        .overlay-grade-percent {
            top: 406px;
            left: 372px;
            width: 25px;
            height: 18px;
            font-size: 14px;
            font-weight: 700;
            color: #000;
        }

        /* Course Name */
        .overlay-course {
            top: 450px;
            left: 60px;
            right: 60px;
            height: 32px;
            font-size: 17px;
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Certificate Number */
        .overlay-cert-no {
            top: 600px;
            left: 60px;
            right: 60px;
            height: 18px;
            font-size: 13px;
            font-weight: 700;
            color: #c5a021;
            /* Premium Gold */
            text-shadow: 0.5px 0.5px 1px rgba(0, 0, 0, 0.1);
        }

        /* Date of Issue */
        .overlay-date-issue {
            top: 628px;
            left: 60px;
            right: 60px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .overlay-date-issue .date-label {
            font-size: 9px;
            font-weight: 600;
            color: #010101;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-right: 6px;
        }

        .overlay-date-issue .date-value {
            font-size: 11px;
            font-weight: 700;
            color: #333;
        }

        @media print {
            @page {
                size: portrait;
                margin: 0;
            }

            body {
                background: white;
            }

            header,
            footer,
            nav,
            .no-print,
            .action-container,
            .page-header {
                display: none !important;
            }

            .certificate-wrapper {
                padding: 0;
                background: white;
            }

            .certificate-container.gict-official-cert {
                box-shadow: none;
                margin: 0;
                width: 210mm;
                height: 297mm;
            }

            /* Adjust print positions to scale from 723px to A4 width */
            .cert-overlay {
                font-size: 1.25em;
            }
        }

        /* Responsive scaling for mobile view */
        @media screen and (max-width: 750px) {
            .certificate-container.gict-official-cert {
                transform: scale(calc(100vw / 620));
                transform-origin: top center;
            }

            .certificate-wrapper {
                padding: 10px 0;
                height: calc(842px * (100vw / 620));
            }
        }

        @media print {
            @page {
                margin: 0;
                size: A4 portrait;
            }

            header,
            footer,
            nav,
            .header-container,
            .top-links,
            .logo-section,
            .no-print,
            .action-container,
            .page-header {
                display: none !important;
            }

            body,
            html {
                height: auto !important;
                overflow: visible !important;
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }

            .main-content,
            .container,
            .student-corner-container {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: none !important;
            }

            .certificate-wrapper {
                padding: 0 !important;
                background: white !important;
            }

            .certificate-container.gict-official-cert {
                margin: 0 !important;
                box-shadow: none !important;
                width: 210mm !important;
                height: 297mm !important;
                border: none !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .gict-official-cert .border-outer,
            .gict-official-cert .border-inner {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }

        /* Responsive adjustments for viewing */
        @media screen and (max-width: 850px) {
            .certificate-container.gict-official-cert {
                width: 100%;
                min-height: auto;
                aspect-ratio: 794 / 1123;
                transform: none;
            }

            .gict-official-cert .gict-logo-text {
                font-size: 6vw;
            }

            .gict-official-cert .cert-title h2 {
                font-size: 8vw;
            }

            .gict-official-cert .cert-student-name {
                font-size: 3vw;
            }

            .gict-official-cert .student-name-line {
                width: 60%;
            }

            .gict-official-cert .course-line {
                width: 70%;
            }

            .gict-official-cert .cert-course-name {
                font-size: 2.5vw;
            }

            .gict-official-cert .cert-signatures {
                margin: 20px 20px 0 20px;
            }

            .gict-official-cert .signature-block {
                width: 30%;
            }
        }
    </style>

    <div class="container student-corner-container">
        <?php
        $student = null;
        $error = null;
        $show_form = true;

        // Security key for encryption (in a real app, this should be in a config file)
        $encryption_key = "GICT_SECURE_KEY_2026";
        $cipher_method = "aes-256-cbc";

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['enrollment_no'])) {
            $enrollment_no = strtoupper(trim($_POST['enrollment_no']));
            $submitted_dob = $_POST['dob'] ?? null;
        } elseif (isset($_GET['token'])) {
            // Decrypt the token
            $token_data = explode('::', base64_decode($_GET['token']), 2);
            if (count($token_data) === 2) {
                list($encrypted_data, $iv) = $token_data;
                $decrypted = openssl_decrypt($encrypted_data, $cipher_method, $encryption_key, 0, $iv);
                if ($decrypted && strpos($decrypted, '|') !== false) {
                    $parts = explode('|', $decrypted);
                    $enrollment_no = strtoupper(trim($parts[0]));
                    $submitted_dob = $parts[1] ?? null;
                }
            }
        }

        if (isset($enrollment_no) && isset($submitted_dob)) {

            $sql = "
                SELECT 
                    u.id as user_id,
                    u.username as enrollment_no,
                    u.full_name,
                    u.father_name,
                    u.date_of_birth,
                    u.profile_image,
                    se.id as enrollment_id,
                    se.session,
                    se.enrollment_date,
                    se.completion_date,
                    se.marksheet_no,
                    cert.certificate_number,
                    sc.name as sub_course_name,
                    sc.duration as course_duration
                FROM users u
                JOIN student_enrollments se ON u.id = se.user_id
                JOIN sub_courses sc ON se.sub_course_id = sc.id
                LEFT JOIN certificates cert ON se.id = cert.enrollment_id
                WHERE u.username = ? AND u.user_type_id = 2
                ORDER BY se.enrollment_date DESC LIMIT 1
            ";

            $student = getRow($sql, [$enrollment_no]);

            if ($student) {
                if ($submitted_dob && $student['date_of_birth'] === $submitted_dob) {
                    // Fetch marks to calculate overall grade
                    $sql_marks = "SELECT AVG(CASE 
                        WHEN grade IN ('S', 'A+') THEN 95
                        WHEN grade = 'A' THEN 85
                        WHEN grade = 'B+' THEN 78
                        WHEN grade = 'B' THEN 72
                        WHEN grade = 'C' THEN 62
                        WHEN grade = 'D' THEN 52
                        ELSE 40 END) as avg_score
                        FROM student_marks WHERE enrollment_id = ?";
                    $marks_res = getRow($sql_marks, [$student['enrollment_id']]);

                    if (!$marks_res || $marks_res['avg_score'] === null) {
                        $error = "Results for this enrollment have not been uploaded yet.";
                        $student = null;
                    } else {
                        $score = $marks_res['avg_score'];
                        // Store the percentage score
                        $student['percentage'] = round($score, 2);

                        if ($score >= 90)
                            $student['final_grade'] = 'A+';
                        elseif ($score >= 80)
                            $student['final_grade'] = 'A';
                        elseif ($score >= 70)
                            $student['final_grade'] = 'B+';
                        elseif ($score >= 60)
                            $student['final_grade'] = 'B';
                        elseif ($score >= 50)
                            $student['final_grade'] = 'C';
                        elseif ($score >= 40)
                            $student['final_grade'] = 'D';
                        else
                            $student['final_grade'] = 'F';

                        // Fallback for empty session
                        if (empty($student['session']) && !empty($student['enrollment_date'])) {
                            $year = date('Y', strtotime($student['enrollment_date']));
                            $student['session'] = $year . '-' . substr($year + 1, -2);
                        }

                        $show_form = false;
                    }
                } else {
                    $error = "Invalid Date of Birth for the provided Enrollment No.";
                    $student = null;
                }
            } else {
                $error = "No student record found with Enrollment No: " . htmlspecialchars($enrollment_no);
            }
        }
        ?>

        <?php if ($show_form): ?>
            <div class="student-corner-card no-print">
                <h2 class="student-corner-title">View Certificate</h2>
                <form method="POST" action="" class="verification-form">
                    <div class="form-group">
                        <label for="enrollment_no" class="form-label">Enrollment No / Roll No:*</label>
                        <input type="text" id="enrollment_no" name="enrollment_no" class="form-control" required
                            style="text-transform: uppercase;"
                            value="<?php echo isset($_POST['enrollment_no']) ? htmlspecialchars(strtoupper($_POST['enrollment_no'])) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="dob" class="form-label">Date of Birth:*</label>
                        <input type="date" id="dob" name="dob" class="form-control" required
                            value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
                    </div>
                    <button type="submit" class="btn-verify">View Certificate</button>

                    <?php if ($error): ?>
                        <div class="error-message" style="width: 100%; margin-top: 20px;">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        <?php else:
            $atc_code = 'GICT-ATC';
            $atc_name = 'G.I.C.T COMPUTER COLLEGE OF IT & MANAGEMENT JAUNPUR';
            $student_photo = !empty($student['profile_image']) ? $student['profile_image'] : 'assets/images/default-student.png';
            ?>
            <div class="certificate-wrapper"
                style="width: 100%; display: flex; flex-direction: column; align-items: center;">
                <div class="certificate-container gict-official-cert" id="certificate">
                    <!-- Data Overlays - Positioned at exact pixel coordinates on the template background -->

                    <!-- ATC Code -->
                    <div class="cert-overlay overlay-atc-code">
                        <?php echo htmlspecialchars($atc_code); ?>
                    </div>

                    <!-- ATC Name -->
                    <div class="cert-overlay overlay-atc-name">
                        <?php echo htmlspecialchars($atc_name); ?>
                    </div>

                    <!-- Student Photo -->
                    <div class="cert-overlay overlay-photo">
                        <img src="<?php echo htmlspecialchars($student_photo); ?>" alt="Student"
                            onerror="this.src='assets/images/default-student.png'">
                    </div>

                    <!-- Student Name -->
                    <div class="cert-overlay overlay-name">
                        <?php echo htmlspecialchars($student['full_name'] ?? ''); ?>
                    </div>

                    <!-- Letter Grade -->
                    <div class="cert-overlay overlay-grade-letter">
                        <?php echo htmlspecialchars($student['final_grade'] ?? '—'); ?>
                    </div>

                    <!-- Grade (Percentage) -->
                    <div class="cert-overlay overlay-grade-percent">
                        <?php echo htmlspecialchars($student['percentage'] ?? '—') . '%'; ?>
                    </div>

                    <!-- Course Name -->
                    <div class="cert-overlay overlay-course">
                        <?php echo htmlspecialchars($student['sub_course_name'] ?? ''); ?>
                    </div>

                    <!-- Certificate Number -->
                    <div class="cert-overlay overlay-cert-no">
                        <?php echo htmlspecialchars($student['certificate_number'] ?? '—'); ?>
                    </div>

                    <!-- Date of Issue -->
                    <div class="cert-overlay overlay-date-issue">
                        <span class="date-label">Date of Issue:</span>
                        <span class="date-value">
                            <?php
                            $issue_date = !empty($student['completion_date']) ? $student['completion_date'] : date('Y-m-d');
                            echo date('d-m-Y', strtotime($issue_date));
                            ?>
                        </span>
                    </div>
                </div>

                <div class="action-container no-print" style="text-align: center; margin-top: 30px;">
                    <button onclick="window.print()" class="btn-verify" style="background: #3498db; margin-right: 10px;">
                        <i class="fas fa-print"></i> Print Certificate
                    </button>
                    <?php
                    // Encrypt the token using AES-256-CBC
                    $plaintext = ($student['enrollment_no'] ?? '') . '|' . ($student['date_of_birth'] ?? '');
                    $iv_length = openssl_cipher_iv_length($cipher_method);
                    $iv = openssl_random_pseudo_bytes($iv_length);
                    $encrypted = openssl_encrypt($plaintext, $cipher_method, $encryption_key, 0, $iv);
                    $token = base64_encode($encrypted . '::' . $iv);
                    $share_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]?token=" . urlencode($token);
                    ?>
                    <button onclick="copyToClipboard('<?php echo $share_url ?? ''; ?>')" class="btn-verify"
                        style="background: #27ae60;">
                        <i class="fas fa-share-alt"></i> Copy Link
                    </button>
                </div>
            </div>

            <script>
                function copyToClipboard(text) {
                    if (!navigator.clipboard) {
                        // Fallback for non-secure contexts
                        var textArea = document.createElement("textarea");
                        textArea.value = text;
                        document.body.appendChild(textArea);
                        textArea.focus();
                        textArea.select();
                        try {
                            document.execCommand('copy');
                            alert('Secure certificate link copied to clipboard!');
                        } catch (err) {
                            alert('Failed to copy. Please try again.');
                        }
                        document.body.removeChild(textArea);
                        return;
                    }

                    navigator.clipboard.writeText(text).then(() => {
                        alert('Secure certificate link copied successfully!');
                    }).catch(err => {
                        alert('Failed to copy. Please try again.');
                    });
                }
            </script>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>