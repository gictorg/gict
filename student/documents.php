<?php
session_start();
require_once '../config/database.php';
require_once '../includes/imgbb_helper.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get student information
$student = getRow("SELECT * FROM users WHERE id = ? AND user_type = 'student'", [$user_id]);
if (!$student) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$message_type = '';

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'upload_document') {
        $document_type = $_POST['document_type'];
        $file = $_FILES['document_file'];
        
        if ($file['error'] === 0) {
            // Check file size (100KB limit)
            if ($file['size'] > 102400) {
                $message = 'File size must be less than 100KB';
                $message_type = 'error';
            } else {
                // Upload to ImgBB with proper naming convention
                $imgbb_result = smartUpload($file['tmp_name'], $user_id . '_' . $document_type);
                
                if ($imgbb_result && isset($imgbb_result['url'])) {
                    // Get file extension
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    
                    // Check if document already exists for this type
                    $existing = getRow("SELECT id FROM student_documents WHERE user_id = ? AND document_type = ?", [$user_id, $document_type]);
                    
                    if ($existing) {
                        // Update existing document
                        $result = updateData("
                            UPDATE student_documents 
                            SET document_name = ?, imgbb_url = ?, file_extension = ?, upload_date = CURRENT_TIMESTAMP, status = 'pending'
                            WHERE user_id = ? AND document_type = ?
                        ", [$file['name'], $imgbb_result['url'], $extension, $user_id, $document_type]);
                        
                        if ($result !== false) {
                            $message = 'Document updated successfully!';
                            $message_type = 'success';
                        } else {
                            $message = 'Failed to update document';
                            $message_type = 'error';
                        }
                    } else {
                        // Insert new document
                        $result = insertData("
                            INSERT INTO student_documents (user_id, document_type, document_name, imgbb_url, file_extension, status)
                            VALUES (?, ?, ?, ?, ?, 'pending')
                        ", [$user_id, $document_type, $file['name'], $imgbb_result['url'], $extension]);
                        
                        if ($result !== false) {
                            $message = 'Document uploaded successfully!';
                            $message_type = 'success';
                        } else {
                            $message = 'Failed to upload document';
                            $message_type = 'error';
                        }
                    }
                } else {
                    $message = 'Failed to upload document to ImgBB';
                    $message_type = 'error';
                }
            }
        } else {
            $message = 'Error uploading file';
            $message_type = 'error';
        }
    }
}

// Get all documents for this student
$documents = getRows("
    SELECT * FROM student_documents 
    WHERE user_id = ? 
    ORDER BY uploaded_at DESC
", [$user_id]);

// Document types available for upload
$document_types = [
    'marksheet' => 'Educational Marksheet',
    'aadhaar' => 'Aadhaar Card',
    'pan' => 'PAN Card',
    'driving_license' => 'Driving License',
    'passport' => 'Passport',
    'visa' => 'Visa Document',
    'certificate' => 'Professional Certificate',
    'id_proof' => 'Government ID Proof',
    'address_proof' => 'Address Proof Document',
    'other' => 'Other Document'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents - Student Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Main Layout - Using CSS Grid like admin dashboard */
        .admin-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            grid-template-rows: 60px calc(100vh - 60px);
            grid-template-areas:
                "sidebar topbar"
                "sidebar content";
            height: 100vh;
        }
        
        .student-content {
            grid-area: content;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            overflow-y: auto;
        }
        
        /* Enhanced Sidebar Styling */
        .admin-sidebar {
            grid-area: sidebar;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            padding: 18px 14px;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header {
            background: var(--primary);
            padding: 20px 16px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .sidebar-nav li a {
            color: var(--sidebar-text);
            padding: 15px 20px;
            border-radius: 0;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .sidebar-nav li a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: translateX(5px);
            border-left-color: #f39c12;
            color: white;
        }
        
        .sidebar-nav li.active a {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            border-left-color: #f39c12;
            color: white;
        }
        
        .sidebar-nav li a i {
            width: 20px;
            margin-right: 12px;
            font-size: 16px;
        }
        
        /* Enhanced Topbar */
        .admin-topbar {
            grid-area: topbar;
            background: var(--primary);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 18px;
            box-shadow: var(--shadow);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .menu-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .menu-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }
        
        .breadcrumb {
            color: white;
            font-size: 18px;
            font-weight: 600;
        }
        
        .user-chip {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 8px 16px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
        }
        
        .user-chip:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.4);
        }
        
        .user-chip span {
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        .user-avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 35px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(102, 126, 234, 0.05);
        }
        
        .section:hover {
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.12);
            transform: translateY(-3px);
            border-color: rgba(102, 126, 234, 0.15);
        }
        
        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .section-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .section-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 18px;
            position: relative;
            z-index: 2;
            line-height: 1.3;
        }
        
        .section-header h2 i {
            font-size: 30px;
            opacity: 0.9;
        }
        
        .section-body {
            padding: 35px;
        }
        
        .upload-form {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(102, 126, 234, 0.1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #495057;
            font-weight: 700;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: white;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .document-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 16px;
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .document-item:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .document-info {
            display: flex;
            align-items: center;
            gap: 25px;
        }
        
        .document-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .document-details h4 {
            margin: 0 0 12px 0;
            color: #495057;
            font-size: 22px;
            font-weight: 700;
            line-height: 1.3;
        }
        
        .document-details p {
            margin: 0;
            color: #6c757d;
            font-size: 16px;
            line-height: 1.4;
        }
        
        .document-meta {
            display: flex;
            gap: 25px;
            margin-top: 12px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6c757d;
            font-size: 15px;
            font-weight: 500;
        }
        
        .status-badge {
            padding: 8px 18px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #495057;
        }
        
        .empty-state p {
            margin: 0;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .admin-layout {
                grid-template-columns: 1fr;
                grid-template-rows: 60px 1fr;
                grid-template-areas:
                    "topbar"
                    "content";
            }
            
            .admin-sidebar {
                position: fixed;
                left: -260px;
                top: 0;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease;
                background: var(--sidebar-bg);
            }
            
            .admin-sidebar.open {
                left: 0;
            }
            
            .student-content {
                padding: 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .document-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .document-actions {
                width: 100%;
                justify-content: flex-start;
            }
            
            .section-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            /* Mobile Menu Toggle Button */
            .menu-toggle {
                display: block !important;
            }
            
            /* Mobile Overlay */
            .mobile-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }
            
            .mobile-overlay.active {
                display: block;
            }
        }
        
        /* Hide menu toggle on desktop */
        @media (min-width: 769px) {
            .menu-toggle {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="GICT Logo" class="logo" style="width: 36px; height: 36px; border-radius: 6px; object-fit: cover;">
                <h3 style="margin: 10px 0 0 0; font-size: 18px; font-weight: 600;">Student Portal</h3>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="courses.php"><i class="fas fa-book"></i> My Courses</a></li>
                    <li class="active"><a href="documents.php"><i class="fas fa-file-alt"></i> Documents</a></li>
                    <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <!-- Topbar -->
        <div class="admin-topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="breadcrumb">
                    <span>Documents</span>
                </div>
            </div>
            <div class="topbar-right">
                <div class="user-chip">
                    <?php if (!empty($student['profile_image']) && filter_var($student['profile_image'], FILTER_VALIDATE_URL)): ?>
                        <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile" class="user-avatar">
                    <?php else: ?>
                        <div class="user-avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($student['full_name']); ?></span>
                </div>
            </div>
        </div>

        <!-- Mobile Overlay -->
        <div class="mobile-overlay" onclick="toggleMobileMenu()"></div>
        
        <!-- Main Content -->
        <div class="student-content">
        <!-- Upload Document Section -->
        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-upload"></i> Upload New Document</h2>
            </div>
            <div class="section-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <input type="hidden" name="action" value="upload_document">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="document_type">Document Type *</label>
                            <select name="document_type" id="document_type" required>
                                <option value="">Select Document Type</option>
                                <?php foreach ($document_types as $type => $label): ?>
                                    <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="document_file">Document File *</label>
                            <input type="file" name="document_file" id="document_file" accept="image/*,.pdf" required>
                            <small style="color: #6c757d; margin-top: 5px; display: block;">
                                Maximum file size: 100KB. Supported formats: JPG, PNG, PDF
                            </small>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Document
                    </button>
                </form>
            </div>
        </div>

        <!-- Documents List Section -->
        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-file-alt"></i> My Documents</h2>
            </div>
            <div class="section-body">
                <?php if (empty($documents)): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <h3>No Documents Uploaded</h3>
                        <p>You haven't uploaded any documents yet. Use the form above to upload your first document.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($documents as $doc): ?>
                        <div class="document-item">
                            <div class="document-info">
                                <div class="document-icon">
                                    <i class="fas fa-file-<?php echo $doc['document_type'] === 'marksheet' ? 'alt' : 'image'; ?>"></i>
                                </div>
                                <div class="document-details">
                                    <h4><?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?></h4>
                                    <p><?php echo htmlspecialchars($doc['original_filename']); ?></p>
                                    <div class="document-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <span><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-file"></i>
                                            <span><?php echo strtoupper(pathinfo($doc['original_filename'], PATHINFO_EXTENSION)); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-file"></i>
                                            <span><?php echo number_format($doc['file_size'] / 1024, 2); ?> KB</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="document-actions">
                                <span class="status-badge status-<?php echo $doc['status']; ?>">
                                    <?php echo ucfirst($doc['status']); ?>
                                </span>
                                
                                <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                
                                <?php if ($doc['status'] === 'pending'): ?>
                                    <button class="btn btn-danger" onclick="deleteDocument(<?php echo $doc['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>

    <script>
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
        }
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.admin-sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                sidebar.classList.remove('open');
                document.querySelector('.mobile-overlay').classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        // Close menu on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const sidebar = document.querySelector('.admin-sidebar');
                const overlay = document.querySelector('.mobile-overlay');
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        function deleteDocument(documentId) {
            if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete-document.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'document_id';
                input.value = documentId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
