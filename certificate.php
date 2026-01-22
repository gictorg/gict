<?php
require_once 'config/database.php';
require_once 'header.php';
require_once 'includes/qr_helper.php';
?>

<div class="main-content">
    <link rel="stylesheet" href="assets/css/student-corner.css">
    <style>
        .certificate-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            border: 15px solid transparent;
            border-image: url('https://www.transparenttextures.com/patterns/dark-matter.png') 30 round;
            position: relative;
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.2);
            font-family: 'Playfair Display', serif;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.95)),
                url('https://www.transparenttextures.com/patterns/old-map.png');
            min-height: 800px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            color: #2c3e50;
            overflow: hidden;
        }

        .certificate-border {
            position: absolute;
            top: 8px;
            left: 8px;
            right: 8px;
            bottom: 8px;
            border: 2px solid #c5a059;
            pointer-events: none;
        }

        .cert-header {
            margin-bottom: 20px;
            width: 100%;
        }

        .cert-logo {
            width: 90px;
            margin-bottom: 10px;
        }

        .cert-institute-name {
            font-size: 32px;
            font-weight: 900;
            color: #1a2a6c;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 0;
            font-family: 'Cinzel', serif;
        }

        .cert-institute-sub {
            font-size: 14px;
            color: #555;
            margin-top: 5px;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .cert-title-box {
            margin: 25px 0;
            position: relative;
            width: 100%;
        }

        .cert-title {
            font-size: 40px;
            color: #c5a059;
            font-family: 'Pinyon Script', cursive;
            margin: 0;
        }

        .cert-subtitle {
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 4px;
            color: #2c3e50;
            margin-top: 5px;
        }

        .cert-content {
            font-size: 18px;
            line-height: 1.6;
            margin: 15px 0;
            max-width: 800px;
        }

        .cert-student-name {
            font-size: 34px;
            font-weight: bold;
            color: #1a2a6c;
            border-bottom: 2px solid #c5a059;
            display: inline-block;
            margin: 5px 0;
            padding: 0 30px;
            font-family: 'Cinzel', serif;
        }

        .cert-course-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }

        .cert-details-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            width: 100%;
            margin-top: 30px;
            border-top: 1px solid #c5a059;
            padding-top: 15px;
        }

        .cert-auth-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-top: 30px;
            padding: 0 40px;
        }

        .cert-qr-box {
            text-align: center;
        }

        .cert-grade-seal-box {
            position: relative;
            width: 120px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cert-ink-seal {
            position: absolute;
            width: 110px;
            height: 110px;
            opacity: 0.7;
            filter: sepia(1) hue-rotate(180deg) saturate(2.5) contrast(1.1);
            transform: rotate(-10deg);
            pointer-events: none;
        }

        .seal-content {
            position: relative;
            z-index: 20;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #1a2a6c;
        }

        .seal-grade-label {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 800;
            margin-bottom: -4px;
        }

        .seal-grade-value {
            font-size: 44px;
            font-weight: 900;
            line-height: 1;
            font-family: 'Cinzel', serif;
        }

        .cert-footer {
            margin-top: 25px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 40px;
        }

        .cert-signature {
            text-align: center;
            width: 180px;
        }

        .sig-line {
            border-top: 2px solid #2c3e50;
            margin-bottom: 8px;
        }

        .sig-name {
            font-weight: bold;
            font-size: 14px;
        }

        .sig-title {
            font-size: 11px;
            color: #7f8c8d;
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
            .action-container {
                display: none !important;
            }

            body,
            html {
                height: auto !important;
                overflow: visible !important;
            }

            .main-content,
            .container,
            .student-corner-container {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: none !important;
                display: block !important;
                box-shadow: none !important;
                border: none !important;
            }

            .certificate-container {
                margin: 10mm auto !important;
                /* Balanced margin for professional look */
                border: none !important;
                box-shadow: none !important;
                width: 190mm !important;
                height: 275mm !important;
                padding: 15mm 15mm !important;
                /* Slightly more top/bottom padding */
                transform: scale(0.92);
                transform-origin: top center;
                box-sizing: border-box;
                page-break-inside: avoid !important;
                page-break-before: avoid !important;
                min-height: auto !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                position: relative !important;
            }

            .cert-header {
                margin-bottom: 10px !important;
            }

            .cert-title-box {
                margin: 15px 0 !important;
            }

            .cert-content {
                margin: 10px 0 !important;
                font-size: 16px !important;
            }

            .cert-student-name {
                font-size: 30px !important;
            }

            .cert-course-name {
                font-size: 20px !important;
            }

            .cert-details-grid {
                margin-top: 15px !important;
                padding-top: 10px !important;
            }

            .cert-auth-row {
                margin-top: 15px !important;
            }

            .cert-footer {
                margin-top: 20px !important;
            }

            .certificate-border {
                border-width: 3px !important;
                top: 3mm;
                left: 3mm;
                right: 3mm;
                bottom: 3mm;
            }
        }

        /* External Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&family=Pinyon+Script&display=swap');
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
                    se.id as enrollment_id,
                    se.session,
                    se.enrollment_date,
                    se.completion_date,
                    sc.name as sub_course_name,
                    sc.duration as course_duration
                FROM users u
                JOIN student_enrollments se ON u.id = se.user_id
                JOIN sub_courses sc ON se.sub_course_id = sc.id
                WHERE u.username = ? AND u.user_type_id = 2
                ORDER BY se.enrollment_date DESC LIMIT 1
            ";

            $student = getRow($sql, [$enrollment_no]);

            if ($student) {
                if ($submitted_dob && $student['date_of_birth'] === $submitted_dob) {
                    // Fetch marks to calculate overall grade
                    $sql_marks = "SELECT AVG(CASE 
                        WHEN grade = 'S' THEN 95
                        WHEN grade = 'A' THEN 85
                        WHEN grade = 'B' THEN 75
                        WHEN grade = 'C' THEN 65
                        WHEN grade = 'D' THEN 55
                        ELSE 40 END) as avg_score
                        FROM student_marks WHERE enrollment_id = ?";
                    $marks_res = getRow($sql_marks, [$student['enrollment_id']]);

                    if (!$marks_res || $marks_res['avg_score'] === null) {
                        $error = "Results for this enrollment have not been uploaded yet.";
                        $student = null;
                    } else {
                        $score = $marks_res['avg_score'];
                        if ($score >= 90)
                            $student['final_grade'] = 'S';
                        elseif ($score >= 80)
                            $student['final_grade'] = 'A';
                        elseif ($score >= 70)
                            $student['final_grade'] = 'B';
                        elseif ($score >= 60)
                            $student['final_grade'] = 'C';
                        elseif ($score >= 50)
                            $student['final_grade'] = 'D';
                        else
                            $student['final_grade'] = 'E';

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
                            placeholder="e.g. SA2026001" style="text-transform: uppercase;"
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
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        <?php else: ?>
            <div class="certificate-wrapper"
                style="width: 100%; display: flex; flex-direction: column; align-items: center;">
                <div class="certificate-container" id="certificate">
                    <div class="certificate-border"></div>

                    <div class="cert-header">
                        <img src="logo.png" alt="GICT Logo" class="cert-logo">
                        <h1 class="cert-institute-name">Global Institute of Compute Technology</h1>
                        <div class="cert-institute-sub">AN ISO 9001:2015 CERTIFIED INSTITUTION</div>
                    </div>

                    <div class="cert-title-box">
                        <h2 class="cert-title">Diploma / Certificate</h2>
                        <div class="cert-subtitle">OF COMPLETION</div>
                    </div>

                    <div class="cert-content">
                        This is to certify that <strong>Mr./Ms.</strong><br>
                        <div class="cert-student-name">
                            <?php echo htmlspecialchars($student['full_name'] ?? ''); ?>
                        </div><br>
                        <strong>Son / Daughter of</strong>
                        <?php echo htmlspecialchars($student['father_name'] ?? ''); ?><br>
                        has successfully completed the course in
                        <div class="cert-course-name">
                            <?php echo htmlspecialchars($student['sub_course_name'] ?? ''); ?>
                        </div>
                        with merit during the academic session
                        <strong><?php echo htmlspecialchars($student['session'] ?? '2025-26'); ?></strong>.
                    </div>

                    <div class="cert-details-grid">
                        <div class="cert-detail-item">
                            <span class="cert-detail-label">Enrollment No.</span>
                            <span class="cert-detail-value">
                                <?php echo htmlspecialchars(strtoupper($student['enrollment_no'] ?? '')); ?>
                            </span>
                        </div>
                        <div class="cert-detail-item">
                            <span class="cert-detail-label">Duration</span>
                            <span class="cert-detail-value">
                                <?php echo htmlspecialchars($student['course_duration'] ?? ''); ?>
                            </span>
                        </div>
                        <div class="cert-detail-item">
                            <span class="cert-detail-label">Issued Date</span>
                            <span class="cert-detail-value">
                                <?php echo date('d-m-Y', strtotime($student['completion_date'] ?: 'now')); ?>
                            </span>
                        </div>
                    </div>

                    <div class="cert-auth-row">
                        <div class="cert-qr-box">
                            <?php echo generateCertificateQRCode($student['enrollment_no'], $student['full_name'], $student['sub_course_name'], 85); ?>
                            <p style="font-size: 10px; margin-top: 5px; font-weight: bold;">VERIFY</p>
                        </div>
                        <div class="cert-grade-seal-box">
                            <img src="assets/images/logo bgremove.png" class="cert-ink-seal" alt="GICT Seal">
                            <div class="seal-content">
                                <span class="seal-grade-label">Grade</span>
                                <span class="seal-grade-value"><?php echo $student['final_grade'] ?? ''; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="cert-footer">
                        <div class="cert-signature">
                            <div class="sig-line"></div>
                            <div class="sig-name">Prepared By</div>
                            <div class="sig-title">Examination Dept.</div>
                        </div>
                        <div class="cert-signature">
                            <div class="sig-line"></div>
                            <div class="sig-name">Director</div>
                            <div class="sig-title">GICT Institute</div>
                        </div>
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