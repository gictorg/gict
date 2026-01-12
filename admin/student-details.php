<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

// Include Cloudinary helper for file uploads
require_once '../includes/cloudinary_helper.php';

// Initialize session for flash messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle document upload
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_document') {
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    $document_type = isset($_POST['document_type']) ? $_POST['document_type'] : '';
    
    // Validate document type
    $allowed_document_types = ['profile', 'marksheet', 'aadhaar', 'pan', 'driving_license', 'passport', 'visa', 'certificate', 'id_proof', 'address_proof', 'other'];
    
    if (!$student_id || !in_array($document_type, $allowed_document_types)) {
        $_SESSION['error_message'] = "Invalid request.";
        header('Location: student-details.php?id=' . $student_id);
        exit;
    }
    
    // Check if file was uploaded
    $file_key = 'document_file';
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
        $file = $_FILES[$file_key];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Determine allowed extensions based on document type
        if (in_array($document_type, ['profile', 'aadhaar', 'id_proof', 'address_proof'])) {
            $allowed_exts = ['jpg', 'jpeg', 'png'];
            $max_size = 400 * 1024; // 400KB
        } else {
            $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];
            $max_size = 400 * 1024; // 400KB
        }
        
        if (in_array($file_ext, $allowed_exts) && $file['size'] <= $max_size) {
            // Get student username for file naming
            $student = getRow("SELECT username FROM users WHERE id = ?", [$student_id]);
            $file_name_prefix = $student ? $student['username'] . '_' . $document_type : $student_id . '_' . $document_type;
            
            // Upload to Cloudinary
            $upload_result = smartUpload($file['tmp_name'], $file_name_prefix);
            
            if ($upload_result && $upload_result['success']) {
                // Check if document already exists for this type
                $existing_doc = getRow("SELECT id FROM student_documents WHERE user_id = ? AND document_type = ?", [$student_id, $document_type]);
                
                if ($existing_doc) {
                    // Update existing document
                    $update_sql = "UPDATE student_documents SET file_url = ?, file_name = ?, file_size = ?, uploaded_at = NOW() WHERE id = ?";
                    $result = updateData($update_sql, [
                        $upload_result['url'],
                        $file['name'],
                        $file['size'],
                        $existing_doc['id']
                    ]);
                } else {
                    // Insert new document
                    $insert_sql = "INSERT INTO student_documents (user_id, document_type, file_url, file_name, file_size, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())";
                    $result = insertData($insert_sql, [
                        $student_id,
                        $document_type,
                        $upload_result['url'],
                        $file['name'],
                        $file['size']
                    ]);
                }
                
                if ($result) {
                    // If it's a profile image, also update the users table
                    if ($document_type === 'profile') {
                        $update_user_sql = "UPDATE users SET profile_image = ? WHERE id = ?";
                        updateData($update_user_sql, [$upload_result['url'], $student_id]);
                    }
                    $_SESSION['success_message'] = ucfirst(str_replace('_', ' ', $document_type)) . " uploaded successfully!";
                    header('Location: student-details.php?id=' . $student_id);
                    exit;
                } else {
                    $_SESSION['error_message'] = "Failed to save document record.";
                }
            } else {
                $error_detail = isset($upload_result['error']) ? $upload_result['error'] : "Unknown error";
                $_SESSION['error_message'] = "Failed to upload document to Cloudinary. " . $error_detail;
            }
        } else {
            $_SESSION['error_message'] = "Invalid file format or size. Allowed: " . implode(', ', $allowed_exts) . " (max " . ($max_size / 1024) . "KB)";
        }
    } else {
        $_SESSION['error_message'] = "No file uploaded or upload error occurred.";
    }
    
    header('Location: student-details.php?id=' . $student_id);
    exit;
}

// Get student ID from query parameter
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$student_id) {
    header('Location: students.php');
    exit;
}

// Get student information
$student = getRow("
    SELECT u.*, 
           DATE_FORMAT(u.date_of_birth, '%Y-%m-%d') as dob_formatted,
           DATE_FORMAT(u.joining_date, '%Y-%m-%d') as joining_date_formatted
    FROM users u 
    WHERE u.id = ? AND u.user_type_id = 2
", [$student_id]);

if (!$student) {
    $_SESSION['error_message'] = "Student not found.";
    header('Location: students.php');
    exit;
}

// Get enrollment information
$enrollments = getRows("
    SELECT se.*, 
           c.name as course_name, 
           cc.name as category_name,
           sc.name as sub_course_name,
           sc.fee as course_fee,
           sc.duration as course_duration,
           DATE_FORMAT(se.enrollment_date, '%d %M %Y') as enrollment_date_formatted,
           DATE_FORMAT(se.completion_date, '%d %M %Y') as completion_date_formatted
    FROM student_enrollments se
    LEFT JOIN sub_courses sc ON se.sub_course_id = sc.id
    LEFT JOIN courses c ON sc.course_id = c.id
    LEFT JOIN course_categories cc ON c.category_id = cc.id
    WHERE se.user_id = ?
    ORDER BY se.enrollment_date DESC
", [$student_id]);

// Get certificates
$certificates = getRows("
    SELECT cert.*,
           se.sub_course_id,
           sc.name as sub_course_name,
           c.name as course_name,
           cc.name as category_name,
           DATE_FORMAT(cert.generated_at, '%d %M %Y') as generated_date_formatted
    FROM certificates cert
    JOIN student_enrollments se ON cert.enrollment_id = se.id
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    LEFT JOIN course_categories cc ON c.category_id = cc.id
    WHERE se.user_id = ?
    ORDER BY cert.generated_at DESC
", [$student_id]);

// Get marks for all enrollments
$marks_data = [];
foreach ($enrollments as $enrollment) {
    if (!empty($enrollment['id'])) {
        $marks = getRows("
            SELECT subject_name, marks_obtained, max_marks, grade, remarks
            FROM student_marks
            WHERE enrollment_id = ?
            ORDER BY subject_name
        ", [$enrollment['id']]);
        $marks_data[$enrollment['id']] = $marks;
    }
}

// Get student documents
$documents = getRows("
    SELECT document_type, file_url, file_name, file_size,
           DATE_FORMAT(uploaded_at, '%d %M %Y') as uploaded_date_formatted
    FROM student_documents
    WHERE user_id = ?
    ORDER BY uploaded_at DESC
", [$student_id]);

// Define all available document types (excluding profile - it's handled separately)
$all_document_types = [
    'aadhaar' => 'Aadhaar Card',
    'pan' => 'PAN Card',
    'driving_license' => 'Driving License',
    'passport' => 'Passport',
    'visa' => 'Visa',
    'id_proof' => 'ID Proof',
    'address_proof' => 'Address Proof',
    'marksheet' => 'Marksheet',
    'certificate' => 'Certificate',
    'other' => 'Other Document'
];

// Create a map of existing documents by type (excluding profile)
$existing_documents = [];
foreach ($documents as $doc) {
    // Skip profile documents - they're handled separately in the header
    if ($doc['document_type'] !== 'profile') {
        $existing_documents[$doc['document_type']] = $doc;
    }
}

// Determine missing documents (commonly required ones, excluding profile)
$commonly_required = ['aadhaar', 'pan'];
$missing_documents = [];
foreach ($commonly_required as $doc_type) {
    if (!isset($existing_documents[$doc_type])) {
        $missing_documents[$doc_type] = $all_document_types[$doc_type];
    }
}

// Get payment history
$payments = getRows("
    SELECT p.*,
           sc.name as sub_course_name,
           c.name as course_name,
           DATE_FORMAT(p.payment_date, '%d %M %Y') as payment_date_formatted
    FROM payments p
    LEFT JOIN sub_courses sc ON p.sub_course_id = sc.id
    LEFT JOIN courses c ON sc.course_id = c.id
    WHERE p.user_id = ?
    ORDER BY p.payment_date DESC, p.created_at DESC
", [$student_id]);

$user = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details - GICT Admin</title>
    <link rel="icon" type="image/png" href="../logo.png">
    <link rel="shortcut icon" type="image/png" href="../logo.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 260px;
            background: #1f2d3d;
            color: #e9eef3;
            padding: 18px 14px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        .admin-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }
        .admin-brand img {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            object-fit: cover;
        }
        .brand-title {
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .profile-card-mini {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 14px;
            display: grid;
            grid-template-columns: 56px 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .profile-card-mini img {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.25);
        }
        .profile-card-mini .name {
            font-weight: 600;
        }
        .profile-card-mini .role {
            color: #cbd5e1;
            font-size: 12px;
            margin-top: 2px;
        }
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 8px 0 0 0;
        }
        .sidebar-nav li {
            margin: 4px 0;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            text-decoration: none;
            color: #e9eef3;
            border-radius: 8px;
            transition: background 0.2s ease;
        }
        .sidebar-nav a i {
            width: 18px;
            text-align: center;
        }
        .sidebar-nav a.active,
        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.09);
        }
        .admin-content {
            flex: 1;
            margin-left: 260px;
            margin-top: 60px;
            padding: 20px;
        }
        .admin-topbar {
            background: #0f6fb1;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 18px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            position: fixed;
            top: 0;
            right: 0;
            left: 260px;
            height: 60px;
            z-index: 999;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .breadcrumbs {
            font-size: 13px;
            opacity: 0.9;
        }
        .topbar-home-link {
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
        }
        .topbar-home-link:hover {
            text-decoration: underline;
        }
        .student-detail-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
        }
        .student-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .student-header-info h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }
        .student-header-info p {
            margin: 5px 0;
            opacity: 0.9;
        }
        .detail-section {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
        }
        .detail-section h2 {
            color: #667eea;
            margin: 0 0 20px 0;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .info-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        .enrollment-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .enrollment-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        .enrollment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        .enrollment-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-enrolled {
            background: #d4edda;
            color: #155724;
        }
        .status-payment_pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }
        .fees-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 15px 0;
        }
        .fee-item {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
        }
        .fee-item.total {
            background: #e3f2fd;
        }
        .fee-item.paid {
            background: #e8f5e9;
        }
        .fee-item.remaining {
            background: #ffebee;
        }
        .fee-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        .fee-value {
            font-size: 20px;
            font-weight: 700;
        }
        .certificate-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
        }
        .certificate-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .certificate-number {
            font-weight: 600;
            color: #667eea;
        }
        .certificate-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background: #138496;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .marks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .marks-table th,
        .marks-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .marks-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        .document-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .payment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .back-button {
            margin-bottom: 20px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h2 {
            margin: 0;
            color: #667eea;
        }
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #000;
        }
        .modal-body {
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
            cursor: pointer;
        }
        .form-group input[type="file"]:hover {
            border-color: #667eea;
            background: #f0f0ff;
        }
        .form-text {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #6c757d;
        }
        #upload_file_preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img src="../assets/images/logo.png" alt="logo" />
                <div class="brand-title">GICT CONTROL</div>
            </div>
            <div class="profile-card-mini">
                <img src="../assets/images/brijendra.jpeg" alt="Profile" />
                <div>
                    <div class="name"><?php echo htmlspecialchars(strtoupper($user['full_name'])); ?></div>
                    <div class="role"><?php echo htmlspecialchars(ucfirst($user['type'])); ?></div>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-user-tie"></i> Staff</a></li>
                <li><a href="courses.php"><i class="fas fa-graduation-cap"></i> Courses</a></li>
                <li><a href="pending-approvals.php"><i class="fas fa-clock"></i> Pending Approvals</a></li>
                <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="inquiries.php"><i class="fas fa-question-circle"></i> Course Inquiries</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <header class="admin-topbar">
            <div class="topbar-left">
                <div class="breadcrumbs">
                    <a href="../index.php" class="topbar-home-link"><i class="fas fa-home"></i> Home</a>
                    <span style="opacity:.7; margin: 0 6px;">/</span>
                    <a href="students.php">Students</a>
                    <span style="opacity:.7; margin: 0 6px;">/</span>
                    <span>Student Details</span>
                </div>
            </div>
        </header>

        <main class="admin-content">
            <div class="back-button">
                <a href="students.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Students
                </a>
            </div>

            <!-- Flash Messages -->
            <?php if ($success_message): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Student Header -->
            <div class="student-detail-header">
                <div style="position: relative; display: inline-block;">
                    <img src="<?php echo !empty($student['profile_image']) ? htmlspecialchars($student['profile_image']) : '../assets/images/default-student.png'; ?>" 
                         alt="Profile" 
                         class="student-avatar-large"
                         id="profile-image-preview"
                         onerror="this.src='../assets/images/default-student.png'">
                    <button onclick="document.getElementById('profile-image-upload').click()" 
                            class="btn btn-primary" 
                            style="position: absolute; bottom: 0; right: 0; border-radius: 50%; width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"
                            title="Update Profile Image">
                        <i class="fas fa-camera"></i>
                    </button>
                    <input type="file" 
                           id="profile-image-upload" 
                           name="document_file" 
                           accept="image/*" 
                           style="display: none;"
                           onchange="showUploadForm('profile', this)">
                </div>
                <div class="student-header-info">
                    <h1><?php echo htmlspecialchars($student['full_name']); ?></h1>
                    <p><i class="fas fa-id-card"></i> Student ID: <?php echo htmlspecialchars($student['username']); ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email']); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></p>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="detail-section">
                <h2><i class="fas fa-user"></i> Personal Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['full_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Date of Birth</div>
                        <div class="info-value"><?php echo $student['date_of_birth'] ? date('d M Y', strtotime($student['date_of_birth'])) : 'N/A'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Gender</div>
                        <div class="info-value"><?php echo htmlspecialchars(ucfirst($student['gender'] ?? 'N/A')); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Joining Date</div>
                        <div class="info-value"><?php echo $student['joining_date'] ? date('d M Y', strtotime($student['joining_date'])) : 'N/A'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Qualification</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['qualification'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status-badge status-<?php echo $student['status']; ?>">
                                <?php echo htmlspecialchars(ucfirst($student['status'])); ?>
                            </span>
                        </div>
                    </div>
                    <?php if (!empty($student['address'])): ?>
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <div class="info-label">Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['address']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Course Enrollments -->
            <div class="detail-section">
                <h2><i class="fas fa-graduation-cap"></i> Course Enrollments</h2>
                <?php if (empty($enrollments)): ?>
                    <p style="text-align: center; color: #6c757d; padding: 40px;">No course enrollments found.</p>
                <?php else: ?>
                    <?php foreach ($enrollments as $enrollment): ?>
                        <div class="enrollment-card">
                            <div class="enrollment-header">
                                <div>
                                    <h3 class="enrollment-title"><?php echo htmlspecialchars($enrollment['course_name'] ?? 'N/A'); ?></h3>
                                    <?php if (!empty($enrollment['sub_course_name'])): ?>
                                        <p style="color: #666; margin: 5px 0;"><i class="fas fa-book"></i> <?php echo htmlspecialchars($enrollment['sub_course_name']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($enrollment['category_name'])): ?>
                                        <p style="color: #666; margin: 5px 0;"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($enrollment['category_name']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <span class="status-badge status-<?php echo $enrollment['enrollment_status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $enrollment['enrollment_status'])); ?>
                                </span>
                            </div>
                            
                            <div class="info-grid" style="margin-top: 15px;">
                                <div class="info-item">
                                    <div class="info-label">Enrollment Date</div>
                                    <div class="info-value"><?php echo $enrollment['enrollment_date_formatted'] ?? 'N/A'; ?></div>
                                </div>
                                <?php if (!empty($enrollment['completion_date'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Completion Date</div>
                                    <div class="info-value"><?php echo $enrollment['completion_date_formatted']; ?></div>
                                </div>
                                <?php endif; ?>
                                <div class="info-item">
                                    <div class="info-label">Duration</div>
                                    <div class="info-value"><?php echo htmlspecialchars($enrollment['course_duration'] ?? 'N/A'); ?></div>
                                </div>
                            </div>

                            <!-- Fees Summary -->
                            <?php if (!empty($enrollment['total_fee'])): ?>
                            <div class="fees-summary">
                                <div class="fee-item total">
                                    <div class="fee-label">Total Fee</div>
                                    <div class="fee-value">₹<?php echo number_format($enrollment['total_fee'], 2); ?></div>
                                </div>
                                <div class="fee-item paid">
                                    <div class="fee-label">Paid</div>
                                    <div class="fee-value" style="color: #28a745;">₹<?php echo number_format($enrollment['paid_fees'] ?? 0, 2); ?></div>
                                </div>
                                <div class="fee-item remaining">
                                    <div class="fee-label">Remaining</div>
                                    <div class="fee-value" style="color: <?php echo ($enrollment['remaining_fees'] ?? 0) > 0 ? '#dc3545' : '#28a745'; ?>;">
                                        ₹<?php echo number_format($enrollment['remaining_fees'] ?? 0, 2); ?>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap;">
                                <button class="btn btn-primary" onclick="editFees(<?php echo $enrollment['id']; ?>, <?php echo $enrollment['total_fee']; ?>, <?php echo $enrollment['paid_fees'] ?? 0; ?>)">
                                    <i class="fas fa-edit"></i> Edit Fees
                                </button>
                                <button class="btn btn-info" onclick="editStatus(<?php echo $enrollment['id']; ?>, '<?php echo $enrollment['enrollment_status']; ?>')">
                                    <i class="fas fa-exchange-alt"></i> Change Status
                                </button>
                                <?php if ($enrollment['enrollment_status'] !== 'completed' && ($enrollment['remaining_fees'] ?? 0) <= 0): ?>
                                <button class="btn btn-success" onclick="markCompleted(<?php echo $enrollment['id']; ?>)">
                                    <i class="fas fa-check-circle"></i> Mark Completed
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Marks -->
                            <?php if (!empty($marks_data[$enrollment['id']])): ?>
                            <div style="margin-top: 20px;">
                                <h4 style="margin-bottom: 10px; color: #667eea;"><i class="fas fa-chart-line"></i> Marks</h4>
                                <table class="marks-table">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Marks Obtained</th>
                                            <th>Max Marks</th>
                                            <th>Grade</th>
                                            <?php if (!empty($marks_data[$enrollment['id']][0]['remarks'])): ?>
                                            <th>Remarks</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($marks_data[$enrollment['id']] as $mark): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                                            <td><?php echo $mark['marks_obtained']; ?></td>
                                            <td><?php echo $mark['max_marks']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($mark['grade']); ?></strong></td>
                                            <?php if (!empty($mark['remarks'])): ?>
                                            <td><?php echo htmlspecialchars($mark['remarks']); ?></td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Certificates -->
            <?php if (!empty($certificates)): ?>
            <div class="detail-section">
                <h2><i class="fas fa-certificate"></i> Certificates</h2>
                <?php foreach ($certificates as $cert): ?>
                    <div class="certificate-card">
                        <div class="certificate-header">
                            <div>
                                <h3 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($cert['course_name']); ?></h3>
                                <p style="color: #666; margin: 0;"><?php echo htmlspecialchars($cert['sub_course_name']); ?></p>
                            </div>
                            <div>
                                <div class="certificate-number"><?php echo htmlspecialchars($cert['certificate_number']); ?></div>
                                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                    Generated: <?php echo $cert['generated_date_formatted']; ?>
                                </div>
                            </div>
                        </div>
                        <div class="certificate-actions">
                            <?php if (!empty($cert['certificate_url'])): ?>
                            <a href="../<?php echo htmlspecialchars($cert['certificate_url']); ?>" target="_blank" class="btn btn-success">
                                <i class="fas fa-certificate"></i> View Certificate
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($cert['marksheet_url'])): ?>
                            <a href="../<?php echo htmlspecialchars($cert['marksheet_url']); ?>" target="_blank" class="btn btn-info">
                                <i class="fas fa-file-alt"></i> View Marksheet
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Documents -->
            <div class="detail-section">
                <h2><i class="fas fa-file-upload"></i> Documents</h2>
                
                <!-- Existing Documents -->
                <?php 
                // Filter out profile documents - they're handled separately in the header
                $display_documents = array_filter($documents, function($doc) {
                    return $doc['document_type'] !== 'profile';
                });
                ?>
                <?php if (!empty($display_documents)): ?>
                    <div style="margin-bottom: 30px;">
                        <h3 style="color: #495057; font-size: 18px; margin-bottom: 15px;"><i class="fas fa-check-circle" style="color: #28a745;"></i> Uploaded Documents</h3>
                        <?php foreach ($display_documents as $doc): ?>
                            <div class="document-item">
                                <div>
                                    <strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $doc['document_type']))); ?></strong>
                                    <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                        Uploaded: <?php echo $doc['uploaded_date_formatted']; ?>
                                        <?php if (!empty($doc['file_size'])): ?>
                                            | Size: <?php echo number_format($doc['file_size'] / 1024, 2); ?> KB
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <a href="<?php echo htmlspecialchars($doc['file_url']); ?>" target="_blank" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <button onclick="showUploadForm('<?php echo $doc['document_type']; ?>')" class="btn btn-secondary">
                                        <i class="fas fa-edit"></i> Replace
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Missing Documents -->
                <?php if (!empty($missing_documents)): ?>
                    <div>
                        <h3 style="color: #495057; font-size: 18px; margin-bottom: 15px;"><i class="fas fa-exclamation-circle" style="color: #ffc107;"></i> Missing Documents</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
                            <?php 
                            // Show missing commonly required documents
                            foreach ($missing_documents as $doc_type => $doc_name): 
                            ?>
                                <div style="border: 2px dashed #dee2e6; border-radius: 8px; padding: 20px; text-align: center; background: #f8f9fa;">
                                    <i class="fas fa-file-upload" style="font-size: 32px; color: #6c757d; margin-bottom: 10px;"></i>
                                    <div style="font-weight: 600; margin-bottom: 10px; color: #495057;">
                                        <?php echo htmlspecialchars($doc_name); ?>
                                    </div>
                                    <button onclick="showUploadForm('<?php echo $doc_type; ?>')" class="btn btn-primary" style="width: 100%;">
                                        <i class="fas fa-upload"></i> Upload
                                    </button>
                                </div>
                            <?php 
                            endforeach; 
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Upload Other Documents -->
                <div style="margin-top: 30px;">
                    <h3 style="color: #495057; font-size: 18px; margin-bottom: 15px;"><i class="fas fa-plus-circle"></i> Upload Other Documents</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
                        <?php foreach ($all_document_types as $doc_type => $doc_name): ?>
                            <?php if (!isset($existing_documents[$doc_type]) && !in_array($doc_type, ['aadhaar', 'pan'])): ?>
                                <button onclick="showUploadForm('<?php echo $doc_type; ?>')" class="btn btn-secondary" style="width: 100%;">
                                    <i class="fas fa-file"></i> <?php echo htmlspecialchars($doc_name); ?>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Upload Document Modal -->
            <div id="uploadDocumentModal" class="modal" style="display: none;">
                <div class="modal-content" style="max-width: 500px;">
                    <div class="modal-header">
                        <h2><i class="fas fa-upload"></i> Upload Document</h2>
                        <span class="close" onclick="closeUploadModal()">&times;</span>
                    </div>
                    <form method="POST" action="student-details.php" enctype="multipart/form-data" id="uploadDocumentForm">
                        <input type="hidden" name="action" value="upload_document">
                        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                        <input type="hidden" name="document_type" id="upload_document_type">
                        <div class="modal-body">
                            <div class="form-group">
                                <label id="upload_document_label">Select Document</label>
                                <input type="file" 
                                       id="upload_document_file" 
                                       name="document_file" 
                                       required 
                                       accept="image/*,.pdf"
                                       onchange="previewUploadFile(this)">
                                <small class="form-text text-muted" id="upload_file_help">
                                    Max size: 400KB. Formats: JPG, JPEG, PNG, PDF
                                </small>
                                <div id="upload_file_preview" style="margin-top: 10px;"></div>
                            </div>
                        </div>
                        <div class="form-actions" style="display: flex; gap: 10px; justify-content: flex-end; padding: 20px; border-top: 1px solid #e9ecef;">
                            <button type="button" class="btn btn-secondary" onclick="closeUploadModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payment History -->
            <?php if (!empty($payments)): ?>
            <div class="detail-section">
                <h2><i class="fas fa-credit-card"></i> Payment History</h2>
                <?php foreach ($payments as $payment): ?>
                    <div class="payment-item">
                        <div>
                            <strong><?php echo htmlspecialchars($payment['course_name'] ?? 'N/A'); ?></strong>
                            <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                Date: <?php echo $payment['payment_date_formatted']; ?> | 
                                Method: <?php echo htmlspecialchars($payment['payment_method'] ?? 'N/A'); ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 18px; font-weight: 600; color: #28a745;">
                                ₹<?php echo number_format($payment['amount'], 2); ?>
                            </div>
                            <span class="status-badge status-<?php echo $payment['status']; ?>" style="margin-top: 5px; display: inline-block;">
                                <?php echo ucfirst($payment['status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        function editFees(enrollmentId, totalFee, paidFees) {
            // Redirect to students.php with parameters to open edit fees modal
            window.location.href = 'students.php?edit_fees=' + enrollmentId + '&total_fee=' + totalFee + '&paid_fees=' + paidFees;
        }
        
        function editStatus(enrollmentId, currentStatus) {
            // Redirect to students.php with parameters to open edit status modal
            window.location.href = 'students.php?edit_status=' + enrollmentId + '&current_status=' + currentStatus;
        }
        
        function markCompleted(enrollmentId) {
            if (confirm('Are you sure you want to mark this enrollment as completed?')) {
                // Create a form to submit the action
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'students.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'mark_completed';
                form.appendChild(actionInput);
                
                const enrollmentInput = document.createElement('input');
                enrollmentInput.type = 'hidden';
                enrollmentInput.name = 'enrollment_id';
                enrollmentInput.value = enrollmentId;
                form.appendChild(enrollmentInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function showUploadForm(documentType, fileInputElement) {
            const modal = document.getElementById('uploadDocumentModal');
            const documentTypeInput = document.getElementById('upload_document_type');
            const documentLabel = document.getElementById('upload_document_label');
            const fileInput = document.getElementById('upload_document_file');
            const fileHelp = document.getElementById('upload_file_help');
            const preview = document.getElementById('upload_file_preview');
            
            // Set document type
            documentTypeInput.value = documentType;
            
            // Update label
            const docNames = {
                'profile': 'Profile Image',
                'aadhaar': 'Aadhaar Card',
                'pan': 'PAN Card',
                'driving_license': 'Driving License',
                'passport': 'Passport',
                'visa': 'Visa',
                'id_proof': 'ID Proof',
                'address_proof': 'Address Proof',
                'marksheet': 'Marksheet',
                'certificate': 'Certificate',
                'other': 'Other Document'
            };
            documentLabel.textContent = 'Select ' + (docNames[documentType] || documentType);
            
            // Set accept attribute based on document type
            if (['profile', 'aadhaar', 'id_proof', 'address_proof'].includes(documentType)) {
                fileInput.accept = 'image/*';
                fileHelp.textContent = 'Max size: 400KB. Formats: JPG, JPEG, PNG';
            } else {
                fileInput.accept = 'image/*,.pdf';
                fileHelp.textContent = 'Max size: 400KB. Formats: JPG, JPEG, PNG, PDF';
            }
            
            // Clear preview
            preview.innerHTML = '';
            fileInput.value = '';
            
            // If file was already selected (from profile image button), trigger preview
            if (fileInputElement && fileInputElement.files && fileInputElement.files[0]) {
                // Create a new FileList-like object and set it
                const dt = new DataTransfer();
                dt.items.add(fileInputElement.files[0]);
                fileInput.files = dt.files;
                previewUploadFile(fileInput);
            }
            
            // Show modal
            modal.style.display = 'block';
        }
        
        function closeUploadModal() {
            document.getElementById('uploadDocumentModal').style.display = 'none';
            document.getElementById('upload_document_file').value = '';
            document.getElementById('upload_file_preview').innerHTML = '';
        }
        
        function previewUploadFile(input) {
            const preview = document.getElementById('upload_file_preview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.maxWidth = '100%';
                        img.style.maxHeight = '200px';
                        img.style.borderRadius = '8px';
                        img.style.marginTop = '10px';
                        preview.appendChild(img);
                    } else {
                        const div = document.createElement('div');
                        div.innerHTML = '<i class="fas fa-file-pdf" style="font-size: 48px; color: #dc3545;"></i><br><strong>' + file.name + '</strong>';
                        div.style.textAlign = 'center';
                        div.style.padding = '20px';
                        preview.appendChild(div);
                    }
                };
                
                reader.readAsDataURL(file);
            }
        }
        
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('uploadDocumentModal');
            if (event.target == modal) {
                closeUploadModal();
            }
        }
    </script>
</body>
</html>
