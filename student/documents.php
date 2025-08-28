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
$student = getRow("SELECT u.*, ut.name as user_type FROM users u JOIN user_types ut ON u.user_type_id = ut.id WHERE u.id = ? AND ut.name = 'student'", [$user_id]);
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
    <title>Document Management - Student Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Student-specific overrides to match admin dashboard exactly */
        .admin-sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .admin-topbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        /* Digital ID Badge */
        .digital-id-badge {
            position: absolute;
            bottom: -5px;
            right: -5px;
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: 2px solid #fff;
        }
        
        .digital-id-badge:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .digital-id-badge i {
            color: white;
            font-size: 14px;
        }
        
        .profile-card-mini {
            position: relative;
        }
        
        /* Form styles */
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-success {
            background: #16a34a;
            color: white;
        }
        
        .btn-success:hover {
            background: #15803d;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .text-muted {
            color: #6b7280;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body class="admin-dashboard-body">
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img src="../assets/images/logo.png" alt="logo" />
                <div class="brand-title">STUDENT PORTAL</div>
            </div>
            
            <div class="profile-card-mini">
                <div style="position: relative;">
                    <img src="<?php echo $student['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" alt="Profile" onerror="this.src='../assets/images/default-avatar.png'" />
                    <div class="digital-id-badge" onclick="viewID()" title="View Digital ID">
                        <i class="fas fa-id-card"></i>
                    </div>
                </div>
                <div>
                    <div class="name"><?php echo htmlspecialchars(strtoupper($student['full_name'])); ?></div>
                    <div class="role">Student</div>
                </div>
            </div>
            
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="courses.php"><i class="fas fa-book"></i> My Courses</a></li>
                <li><a href="documents.php" class="active"><i class="fas fa-file-upload"></i> Documents</a></li>
                <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="breadcrumbs">
                    <span>Document Management</span>
                </div>
            </div>
            <div class="topbar-right">
                <div class="user-chip">
                    <img src="<?php echo $student['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" alt="Profile" onerror="this.src='../assets/images/default-avatar.png'" />
                    <span><?php echo htmlspecialchars($student['full_name']); ?></span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-upload"></i> Upload Documents</span>
                </div>
                <div class="panel-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                        <input type="hidden" name="action" value="upload_document">
                        
                        <div class="form-group">
                            <label for="document_type">Document Type</label>
                            <select name="document_type" id="document_type" class="form-control" required>
                                <option value="">Select Document Type</option>
                                <?php foreach ($document_types as $key => $label): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="document_file">Document File</label>
                            <input type="file" name="document_file" id="document_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                            <small class="text-muted">Maximum file size: 200KB. Supported formats: JPG, JPEG, PNG, PDF</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Document
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="panel" style="margin-top: 1.5rem;">
                <div class="panel-header">
                    <span><i class="fas fa-file-alt"></i> My Documents</span>
                </div>
                <div class="panel-body">
                    <?php if (empty($documents)): ?>
                        <p class="text-muted">No documents uploaded yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Document Type</th>
                                        <th>File Name</th>
                                        <th>Upload Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($document_types[$doc['document_type']] ?? ucfirst($doc['document_type'])); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $doc['status']; ?>">
                                                    <?php echo ucfirst($doc['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars($doc['imgbb_url']); ?>" target="_blank" class="btn btn-success btn-sm">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('mobile-open');
        }
    </script>
</body>
</html>
