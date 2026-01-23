<?php
// --- Library Check ---
$libraryPath = 'phpqrcode/qrlib.php';
if (!file_exists($libraryPath)) {
    die("<strong>Error:</strong> QR code library not found. Ensure 'phpqrcode' is in the same directory.");
}
require_once $libraryPath;

// Include database connection
require_once 'config/database.php';

// --- Configuration ---
$showIdCard = false;
$qrCodeDataUri = '';
$studentImageDataUri = '';
$studentName = '';
$fatherName = '';
$courseName = '';
$studentId = '';
$batch = '';
$expiryDate = '';

// --- Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get student ID from POST request
    $studentId = isset($_POST['student_id']) ? trim(htmlspecialchars($_POST['student_id'])) : '';
    $isDownload = isset($_POST['download']) && $_POST['download'] === 'true';

    if ($studentId) {
        // Fetch student information from database
        $student = getRow("
            SELECT u.*, ut.name as user_type, sc.name as course_name 
            FROM users u 
            JOIN user_types ut ON u.user_type_id = ut.id 
            JOIN student_enrollments se ON u.id = se.user_id
            JOIN sub_courses sc ON se.sub_course_id = sc.id
            WHERE u.id = ? AND ut.name = 'student'
            ORDER BY se.enrollment_date DESC LIMIT 1
        ", [$studentId]);

        if ($student) {
            $studentName = $student['full_name'];
            $fatherName = $student['father_name'];
            $courseName = $student['course_name'] ?? 'General';
            $batch = date('Y'); // Current year as batch
            $expiryDate = date('Y-m-d', strtotime('+1 year')); // 1 year from now

            // Handle student image from database
            if (!empty($student['profile_image']) && filter_var($student['profile_image'], FILTER_VALIDATE_URL)) {
                $studentImageDataUri = $student['profile_image'];
            } else {
                // Create a placeholder image
                $studentImageDataUri = 'data:image/svg+xml;base64,' . base64_encode('<svg width="120" height="150" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="150" fill="#667eea"/><text x="60" y="75" font-family="Arial" font-size="48" fill="white" text-anchor="middle">👤</text></svg>');
            }

            // ✅ Instead of embedding JSON, encode a verification URL
            $dataToEncode = "http://yourdomain.com/verify.php?studentId=" . urlencode($studentId) . "&expiryDate=" . urlencode($expiryDate);

            // Generate QR code in temp file
            $tempFile = tempnam(sys_get_temp_dir(), 'qr') . '.png';
            QRcode::png($dataToEncode, $tempFile, QR_ECLEVEL_H, 4, 2);

            $pngData = file_get_contents($tempFile);
            unlink($tempFile);

            $qrCodeDataUri = 'data:image/png;base64,' . base64_encode($pngData);
            $showIdCard = true;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if student_id is provided via GET for direct lookup
    if (isset($_GET['student_id'])) {
        $studentId = trim(htmlspecialchars($_GET['student_id']));

        // Fetch student information from database (same logic as POST)
        $student = getRow("
            SELECT u.*, ut.name as user_type, sc.name as course_name 
            FROM users u 
            JOIN user_types ut ON u.user_type_id = ut.id 
            JOIN student_enrollments se ON u.id = se.user_id
            JOIN sub_courses sc ON se.sub_course_id = sc.id
            WHERE u.id = ? AND ut.name = 'student'
            ORDER BY se.enrollment_date DESC LIMIT 1
        ", [$studentId]);

        if ($student) {
            $studentName = $student['full_name'];
            $fatherName = $student['father_name'];
            $courseName = $student['course_name'] ?? 'General';
            $batch = date('Y');
            $expiryDate = date('Y-m-d', strtotime('+1 year'));

            if (!empty($student['profile_image']) && filter_var($student['profile_image'], FILTER_VALIDATE_URL)) {
                $studentImageDataUri = $student['profile_image'];
            } else {
                $studentImageDataUri = 'data:image/svg+xml;base64,' . base64_encode('<svg width="120" height="150" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="150" fill="#667eea"/><text x="60" y="75" font-family="Arial" font-size="48" fill="white" text-anchor="middle">👤</text></svg>');
            }

            $dataToEncode = "https://gict.org.in/verify.php?studentId=" . urlencode($studentId) . "&expiryDate=" . urlencode($expiryDate);
            $tempFile = tempnam(sys_get_temp_dir(), 'qr') . '.png';
            QRcode::png($dataToEncode, $tempFile, QR_ECLEVEL_H, 4, 2);
            $pngData = file_get_contents($tempFile);
            unlink($tempFile);
            $qrCodeDataUri = 'data:image/png;base64,' . base64_encode($pngData);
            $showIdCard = true;
        }
    }
    // Original form logic for manual input
    elseif (isset($_GET['student_name'])) {
        $studentName = isset($_GET['student_name']) ? trim(htmlspecialchars($_GET['student_name'])) : '';
        $fatherName = isset($_GET['father_name']) ? trim(htmlspecialchars($_GET['father_name'])) : '';
        $courseName = isset($_GET['course_name']) ? trim(htmlspecialchars($_GET['course_name'])) : '';
        $studentId = isset($_GET['student_id']) ? trim(htmlspecialchars($_GET['student_id'])) : '';
        $batch = isset($_GET['batch']) ? trim(htmlspecialchars($_GET['batch'])) : '';
        $expiryDate = isset($_GET['expiry_date']) ? trim(htmlspecialchars($_GET['expiry_date'])) : '';

        // Validate file upload
        if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] === UPLOAD_ERR_OK) {
            $imageTmpPath = $_FILES['student_image']['tmp_name'];
            $imageMimeType = mime_content_type($imageTmpPath);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

            if (in_array($imageMimeType, $allowedTypes)) {
                $imageData = file_get_contents($imageTmpPath);
                $studentImageDataUri = 'data:' . $imageMimeType . ';base64,' . base64_encode($imageData);
            }
        }

        // ✅ Instead of embedding JSON, encode a verification URL
        $dataToEncode = "http://yourdomain.com/verify.php?studentId=" . urlencode($studentId) . "&expiryDate=" . urlencode($expiryDate);

        if ($studentName && $studentId && $batch && $expiryDate && $studentImageDataUri) {
            // Generate QR code in temp file
            $tempFile = tempnam(sys_get_temp_dir(), 'qr') . '.png';
            QRcode::png($dataToEncode, $tempFile, QR_ECLEVEL_H, 4, 2);

            $pngData = file_get_contents($tempFile);
            unlink($tempFile);

            $qrCodeDataUri = 'data:image/png;base64,' . base64_encode($pngData);
            $showIdCard = true;
        }
    }
}

// If this is a download request, show only the ID card without the form
if ($showIdCard && isset($_POST['download']) && $_POST['download'] === 'true') {
    // Show only the ID card for download
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Student ID Card - Download</title>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

            body {
                font-family: 'Inter', sans-serif;
                background-color: #f0f4f9;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 2rem;
                margin: 0;
            }

            #idCard {
                width: 340px;
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
                overflow: hidden;
                border: 1px solid #e5e7eb;
                padding-bottom: 1rem;
            }

            .id-card-header {
                display: flex;
                align-items: center;
                background: #1d4ed8;
                color: #fff;
                padding: 1rem 1.5rem;
                border-radius: 8px 8px 0 0;
            }

            .id-card-logo {
                height: 60px;
                width: 60px;
                border-radius: 50%;
                object-fit: cover;
                background: white;
                margin-right: 1rem;
                border: 2px solid #fff;
            }

            .id-card-header-text h2 {
                font-size: 1.4rem;
                margin: 0;
                font-weight: 700;
                line-height: 1.2;
            }

            .id-card-header-text p {
                font-size: 0.8rem;
                margin: 0.1rem 0 0;
                opacity: 0.9;
            }

            .id-card-body {
                padding: 1rem;
                text-align: center;
            }

            .id-card-photo {
                width: 100px;
                height: 100px;
                border-radius: 4px;
                object-fit: cover;
                margin-bottom: 0.5rem;
                border: 2px solid #1d4ed8;
            }

            .id-card-name {
                font-size: 1.2rem;
                font-weight: 700;
                margin: 0.3rem 0;
            }

            .id-card-studentid {
                font-size: 0.9rem;
                color: #374151;
                margin-bottom: 1rem;
            }

            .id-card-row {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-top: 0.8rem;
            }

            .id-card-info {
                text-align: left;
                font-size: 0.9rem;
            }

            .id-card-info p {
                margin: 0.4rem 0;
            }

            .label {
                font-weight: 600;
                color: #1f2937;
            }

            .id-card-qr {
                width: 90px;
                height: 90px;
            }

            .id-card-footer {
                margin-top: 0.5rem;
                font-size: 0.7rem;
                text-align: center;
                color: #6b7280;
                border-top: 1px solid #e5e7eb;
                padding-top: 0.5rem;
            }

            .download-button {
                margin-top: 1rem;
                background: #16a34a;
                color: #fff;
                border: none;
                padding: 0.7rem 1.5rem;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 600;
            }

            .download-button:hover {
                background: #15803d;
            }
        </style>
    </head>

    <body>
        <div>
            <h2 style="text-align:center; margin-bottom:1rem;">Student ID Card</h2>
            <div id="idCard"
                style="width: 340px; background: #fff; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); overflow: hidden; border: 1px solid #e5e7eb; padding-bottom: 1rem; margin: 0 auto; line-height: 1.4; position: relative; text-align: left;">
                <div class="id-card-header"
                    style="display: flex; align-items: center; background: #1d4ed8; color: #fff; padding: 15px 20px; border-radius: 8px 8px 0 0;">
                    <img src="/logo.png" alt="Institute Logo" class="id-card-logo"
                        style="height: 55px; width: 55px; border-radius: 50%; object-fit: cover; background: white; margin-right: 12px; border: 2px solid #fff;">
                    <div class="id-card-header-text" style="flex: 1;">
                        <h2
                            style="font-size: 1.1rem; margin: 0; font-weight: 700; line-height: 1.2; text-transform: uppercase;">
                            GICT COMPUTER INSTITUTE</h2>
                        <p style="font-size: 0.75rem; margin: 2px 0 0; opacity: 0.9;">Student Identification Card</p>
                    </div>
                </div>
                <div class="id-card-body" style="padding: 15px; text-align: center;">
                    <img src="<?php echo $studentImageDataUri; ?>" class="id-card-photo" alt="Student Photo"
                        style="width: 100px; height: 100px; border-radius: 8px; object-fit: cover; margin-bottom: 10px; border: 3px solid #1d4ed8; display: block; margin-left: auto; margin-right: auto;">
                    <p class="id-card-name" style="font-size: 1.2rem; font-weight: 700; margin: 5px 0; color: #1a202c;">
                        <?php echo $studentName; ?>
                    </p>
                    <div class="id-card-info"
                        style="margin: 10px 0; border-top: 1px solid #f3f4f6; padding-top: 10px; text-align: left;">
                        <p style="margin: 3px 0; font-size: 0.85rem;"><span class="label"
                                style="font-weight: 700; color: #4a5568; min-width: 80px; display: inline-block;">F/Name:</span>
                            <span style="color: #1a202c;"><?php echo $fatherName; ?></span>
                        </p>
                        <p style="margin: 3px 0; font-size: 0.85rem;"><span class="label"
                                style="font-weight: 700; color: #4a5568; min-width: 80px; display: inline-block;">Course:</span>
                            <span style="color: #1a202c;"><?php echo $courseName; ?></span>
                        </p>
                        <p style="margin: 3px 0; font-size: 0.85rem;"><span class="label"
                                style="font-weight: 700; color: #4a5568; min-width: 80px; display: inline-block;">Student
                                ID:</span> <span style="color: #1a202c;"><?php echo $studentId; ?></span></p>
                    </div>
                    <div class="id-card-row"
                        style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 10px;">
                        <div class="id-card-info" style="text-align: left; font-size: 0.8rem;">
                            <p style="margin: 2px 0;"><span class="label"
                                    style="font-weight: 700; color: #4a5568;">Batch:</span> <span
                                    style="color: #1a202c;"><?php echo $batch; ?></span></p>
                            <p style="margin: 2px 0;"><span class="label"
                                    style="font-weight: 700; color: #4a5568;">Expires:</span> <span
                                    style="color: #1a202c;"><?php echo date("m/Y", strtotime($expiryDate)); ?></span></p>
                        </div>
                        <img src="<?php echo $qrCodeDataUri; ?>" class="id-card-qr" alt="QR Code"
                            style="width: 70px; height: 70px; border: 1px solid #e2e8f0; padding: 2px; border-radius: 4px;">
                    </div>
                </div>
                <div class="id-card-footer"
                    style="margin-top: 5px; font-size: 0.65rem; text-align: center; color: #718096; border-top: 1px solid #edf2f7; padding: 10px 15px 0;">
                    If found, please return to the institute admin office.</div>
            </div>
            <button id="downloadBtn" class="download-button">Download ID Card</button>
        </div>

        <script>
            const downloadBtn = document.getElementById('downloadBtn');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', () => {
                    const { jsPDF } = window.jspdf;
                    const idCardElement = document.getElementById('idCard');
                    html2canvas(idCardElement, { scale: 4, useCORS: true }).then(canvas => {
                        const imgData = canvas.toDataURL('image/png');
                        const pdf = new jsPDF({
                            orientation: 'portrait',
                            unit: 'px',
                            format: [idCardElement.offsetWidth, idCardElement.offsetHeight]
                        });
                        pdf.addImage(imgData, 'PNG', 0, 0, idCardElement.offsetWidth, idCardElement.offsetHeight);
                        pdf.save('student-id-<?php echo $studentId; ?>.pdf');
                    });
                });
            }
        </script>
    </body>

    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>University Student ID Card Generator</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f4f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            margin: 0;
        }

        .form-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 600px;
            width: 100%;
            margin-bottom: 2rem;
        }

        .form-container h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #1f2937;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #374151;
        }

        input[type="text"],
        input[type="date"],
        input[type="file"] {
            width: 100%;
            padding: 0.6rem;
            margin-top: 0.3rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }

        .submit-button {
            display: block;
            width: 100%;
            background: #1d4ed8;
            color: white;
            padding: 0.8rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .submit-button:hover {
            background: #1e40af;
        }

        #idCard {
            width: 340px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid #e5e7eb;
            padding-bottom: 1rem;
        }

        .id-card-header {
            display: flex;
            align-items: center;
            background: #1d4ed8;
            color: #fff;
            padding: 1rem 1.5rem;
            border-radius: 8px 8px 0 0;
        }

        .id-card-logo {
            height: 60px;
            width: 60px;
            border-radius: 50%;
            object-fit: cover;
            background: white;
            margin-right: 1rem;
            border: 2px solid #fff;
        }

        .id-card-header-text h2 {
            font-size: 1.4rem;
            margin: 0;
            font-weight: 700;
            line-height: 1.2;
        }

        .id-card-header-text p {
            font-size: 0.9rem;
            margin: 0.2rem 0 0;
            opacity: 0.9;
        }

        .id-card-body {
            padding: 1rem;
            text-align: center;
        }

        .id-card-photo {
            width: 120px;
            height: auto;
            border-radius: 2px;
            object-fit: cover;
            margin-bottom: 0.5rem;
            border: 3px solid #1d4ed8;
        }

        .id-card-name {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0.3rem 0;
        }

        .id-card-studentid {
            font-size: 0.9rem;
            color: #374151;
            margin-bottom: 1rem;
        }

        .id-card-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 0.8rem;
        }

        .id-card-info {
            text-align: left;
            font-size: 0.9rem;
        }

        .id-card-info p {
            margin: 0.4rem 0;
        }

        .label {
            font-weight: 600;
            color: #1f2937;
        }

        .id-card-qr {
            width: 90px;
            height: 90px;
        }

        .id-card-footer {
            margin-top: 1rem;
            font-size: 0.75rem;
            text-align: center;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 0.5rem;
        }

        .download-button {
            margin-top: 1rem;
            background: #16a34a;
            color: #fff;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .download-button:hover {
            background: #15803d;
        }
    </style>
</head>

<body>

    <?php if (!$showIdCard): ?>
        <div class="form-container">
            <h1>Generate Student ID Card</h1>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group"><label>Full Name:</label><input type="text" name="student_name" required></div>
                <div class="form-group"><label>Father's Name:</label><input type="text" name="father_name" required></div>
                <div class="form-group"><label>Course Name:</label><input type="text" name="course_name" required></div>
                <div class="form-group"><label>Student ID:</label><input type="text" name="student_id" required></div>
                <div class="form-group"><label>Batch:</label><input type="text" name="batch" required></div>
                <div class="form-group"><label>Expiry Date:</label><input type="date" name="expiry_date" required></div>
                <div class="form-group"><label>Student Photo:</label><input type="file" name="student_image"
                        accept="image/*" required></div>
                <button type="submit" class="submit-button">Generate ID Card</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($showIdCard): ?>
        <div>
            <h2 style="text-align:center; margin-bottom:1rem;">Generated ID Card</h2>
            <div id="idCard"
                style="width: 340px; background: #fff; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); overflow: hidden; border: 1px solid #e5e7eb; padding-bottom: 1rem; margin: 0 auto; line-height: 1.4; position: relative; text-align: left;">
                <div class="id-card-header"
                    style="display: flex; align-items: center; background: #1d4ed8; color: #fff; padding: 15px 20px; border-radius: 8px 8px 0 0;">
                    <img src="/logo.png" alt="Institute Logo" class="id-card-logo"
                        style="height: 55px; width: 55px; border-radius: 50%; object-fit: cover; background: white; margin-right: 12px; border: 2px solid #fff;">
                    <div class="id-card-header-text" style="flex: 1;">
                        <h2
                            style="font-size: 1.1rem; margin: 0; font-weight: 700; line-height: 1.2; text-transform: uppercase;">
                            GICT COMPUTER INSTITUTE</h2>
                        <p style="font-size: 0.75rem; margin: 2px 0 0; opacity: 0.9;">Student Identification Card</p>
                    </div>
                </div>
                <div class="id-card-body" style="padding: 15px; text-align: center;">
                    <img src="<?php echo $studentImageDataUri; ?>" class="id-card-photo" alt="Student Photo"
                        style="width: 100px; height: 100px; border-radius: 8px; object-fit: cover; margin-bottom: 10px; border: 3px solid #1d4ed8; display: block; margin-left: auto; margin-right: auto;">
                    <p class="id-card-name" style="font-size: 1.2rem; font-weight: 700; margin: 5px 0; color: #1a202c;">
                        <?php echo $studentName; ?>
                    </p>
                    <div class="id-card-info"
                        style="margin: 10px 0; border-top: 1px solid #f3f4f6; padding-top: 10px; text-align: left;">
                        <p style="margin: 3px 0; font-size: 0.85rem;"><span class="label"
                                style="font-weight: 700; color: #4a5568; min-width: 80px; display: inline-block;">F/Name:</span>
                            <span style="color: #1a202c;"><?php echo $fatherName; ?></span>
                        </p>
                        <p style=" margin: 3px 0; font-size: 0.85rem;"><span class="label"
                                style="font-weight: 700; color: #4a5568; min-width: 80px; display: inline-block;">Course:</span>
                            <span style="color: #1a202c;"><?php echo $courseName; ?></span>
                        </p>
                        <p style="margin: 3px 0; font-size: 0.85rem;"><span class="label"
                                style="font-weight: 700; color: #4a5568; min-width: 80px; display: inline-block;">Student
                                ID:</span> <span style="color: #1a202c;"><?php echo $studentId; ?></span></p>
                    </div>
                    <div class=" id-card-row"
                        style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 10px;">
                        <div class="id-card-info" style="text-align: left; font-size: 0.8rem;">
                            <p style="margin: 2px 0;"><span class="label"
                                    style="font-weight: 700; color: #4a5568;">Batch:</span> <span
                                    style="color: #1a202c;"><?php echo $batch; ?></span></p>
                            <p style=" margin: 2px 0;"><span class="label"
                                    style="font-weight: 700; color: #4a5568;">Expires:</span> <span
                                    style="color: #1a202c;"><?php echo date("m/Y", strtotime($expiryDate)); ?></span></p>
                        </div>
                        <img src=" <?php echo $qrCodeDataUri; ?>" class="id-card-qr" alt="QR Code"
                            style="width: 70px; height: 70px; border: 1px solid #e2e8f0; padding: 2px; border-radius: 4px;">
                    </div>
                </div>
                <div class="id-card-footer"
                    style="margin-top: 5px; font-size: 0.65rem; text-align: center; color: #718096; border-top: 1px solid #edf2f7; padding: 10px 15px 0;">
                    If found, please return to the institute admin office.</div>
            </div>
            <button id="downloadBtn" class="download-button">Download ID Card</button>
        </div>
    <?php endif; ?>

    <script>
        const downloadBtn = document.getElementById('downloadBtn');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', () => {
                const { jsPDF } = window.jspdf;
                const idCardElement = document.getElementById('idCard');
                html2canvas(idCardElement, { scale: 4, useCORS: true }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF({
                        orientation: 'portrait',
                        unit: 'px',
                        format: [idCardElement.offsetWidth, idCardElement.offsetHeight]
                    });
                    pdf.addImage(imgData, 'PNG', 0, 0, idCardElement.offsetWidth, idCardElement.offsetHeight);
                    pdf.save('student-id-<?php echo $studentId; ?>.pdf');
                });
            });
        }
    </script>

</body>

</html>