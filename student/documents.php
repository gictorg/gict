<?php
require_once '../includes/session_manager.php';
require_once '../config/database.php';
require_once '../includes/imgbb_helper.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    header('Location: ../login.php');
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
            // Check file size (200KB limit)
            if ($file['size'] > 204800) {
                $message = 'File size must be less than 200KB';
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
        /* Custom styles for documents page */
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
        
        /* Form Styling */
        .upload-form {
            max-width: 800px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group select,
        .form-group input[type="file"] {
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-group select:focus,
        .form-group input[type="file"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
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
        
        /* Document Items */
        .document-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        
        .document-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .document-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .document-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .document-details h4 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }
        
        .document-details p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .document-meta {
            display: flex;
            gap: 20px;
            margin-top: 8px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 12px;
        }
        
        .meta-item i {
            color: #667eea;
            width: 14px;
        }
        
        .document-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-approved {
            background: #e8f5e8;
            color: #28a745;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .document-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
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
        <?php 
        $page_title = 'Documents';
        include 'includes/sidebar.php'; 
        ?>

        <?php include 'includes/topbar.php'; ?>

        <!-- Mobile Overlay -->
        <div class="mobile-overlay" onclick="toggleMobileMenu()"></div>
        
        <!-- Main Content -->
        <main class="admin-content">
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
        </main>
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
