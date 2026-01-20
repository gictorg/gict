<?php
require_once '../includes/session_manager.php';
require_once '../config/database.php';
require_once '../includes/cloudinary_helper.php';

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

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_profile_image') {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $file = $_FILES['profile_image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png'];
        $max_size = 400 * 1024; // 400KB
        
        if (in_array($file_ext, $allowed_exts) && $file['size'] <= $max_size) {
            // Use consistent filename for profile images - same as admin implementation
            // Add timestamp to ensure unique upload and force Cloudinary to update
            $file_name_prefix = $student['username'] . '_profile_' . time();
            
            // Upload to Cloudinary (same approach as admin/student-details.php)
            $upload_result = smartUpload($file['tmp_name'], $file_name_prefix);
            
            if ($upload_result && $upload_result['success']) {
                $new_image_url = $upload_result['url'];
                
                // Log upload details for debugging (remove in production if needed)
                // The URL should have a new version number if overwrite worked
                // Update users table with new image URL and timestamp
                $update_sql = "UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?";
                $result = updateData($update_sql, [$new_image_url, $user_id]);
                
                // Also update/insert in student_documents table
                $existing_doc = getRow("SELECT id FROM student_documents WHERE user_id = ? AND document_type = 'profile'", [$user_id]);
                if ($existing_doc) {
                    $update_doc_sql = "UPDATE student_documents SET file_url = ?, file_name = ?, file_size = ?, uploaded_at = NOW() WHERE id = ?";
                    updateData($update_doc_sql, [$upload_result['url'], $file['name'], $file['size'], $existing_doc['id']]);
                } else {
                    $insert_doc_sql = "INSERT INTO student_documents (user_id, document_type, file_url, file_name, file_size, uploaded_at) VALUES (?, 'profile', ?, ?, ?, NOW())";
                    insertData($insert_doc_sql, [$user_id, $upload_result['url'], $file['name'], $file['size']]);
                }
                
                if ($result !== false) {
                    // Verify the update was successful by checking the database
                    $verify = getRow("SELECT profile_image, updated_at FROM users WHERE id = ?", [$user_id]);
                    if ($verify && $verify['profile_image'] === $new_image_url) {
                        // Force redirect with cache-busting to ensure new image loads
                        header('Cache-Control: no-cache, no-store, must-revalidate');
                        header('Pragma: no-cache');
                        header('Expires: 0');
                        header('Location: profile.php?img_updated=' . time() . '&nocache=' . microtime(true));
                        exit();
                    } else {
                        $message = 'Image uploaded but database update verification failed. Please refresh the page.';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Failed to update profile image';
                    $message_type = 'error';
                }
            } else {
                // More detailed error message
                $error_detail = isset($upload_result['error']) ? $upload_result['error'] : "Unknown error";
                $public_id_info = isset($upload_result['public_id']) ? " (Public ID: " . $upload_result['public_id'] . ")" : "";
                $message = 'Failed to upload image to Cloudinary. ' . $error_detail . $public_id_info;
                $message_type = 'error';
            }
        } else {
            $message = 'Invalid file format or size. Allowed: JPG, JPEG, PNG (max 400KB)';
            $message_type = 'error';
        }
    } else {
        // More detailed error for debugging
        $upload_error = isset($_FILES['profile_image']) ? $_FILES['profile_image']['error'] : 'FILE_NOT_SET';
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        $error_msg = isset($error_messages[$upload_error]) ? $error_messages[$upload_error] : "Upload error code: $upload_error";
        $message = 'No file uploaded or upload error occurred: ' . $error_msg;
        $message_type = 'error';
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        
        if (empty($full_name) || empty($email)) {
            $message = 'Full name and email are required';
            $message_type = 'error';
        } else {
            try {
                $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?";
                $result = updateData($sql, [$full_name, $email, $phone, $address, $user_id]);
                
                if ($result !== false) {
                    $message = 'Profile updated successfully!';
                    $message_type = 'success';
                    // Refresh student data
                    $student = getRow("SELECT u.*, ut.name as user_type FROM users u JOIN user_types ut ON u.user_type_id = ut.id WHERE u.id = ? AND ut.name = 'student'", [$user_id]);
                } else {
                    $message = 'Failed to update profile';
                    $message_type = 'error';
                }
            } catch (Exception $e) {
                $message = 'Error updating profile';
                $message_type = 'error';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = 'All password fields are required';
            $message_type = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = 'New passwords do not match';
            $message_type = 'error';
        } elseif (strlen($new_password) < 8) {
            $message = 'New password must be at least 8 characters long';
            $message_type = 'error';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $new_password)) {
            $message = 'Password must contain at least one lowercase letter, one uppercase letter, and one number';
            $message_type = 'error';
        } else {
            try {
                // Verify current password
                $user = getRow("SELECT password FROM users WHERE id = ?", [$user_id]);
                if ($user && password_verify($current_password, $user['password'])) {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
                    $result = updateData($sql, [$hashed_password, $user_id]);
                    
                    if ($result !== false) {
                        $message = 'Password changed successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to change password';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Current password is incorrect';
                    $message_type = 'error';
                }
            } catch (Exception $e) {
                $message = 'Error changing password';
                $message_type = 'error';
            }
        }
    }
}

// Get student documents
$documents = getRows("
    SELECT * FROM student_documents 
    WHERE user_id = ? 
    ORDER BY uploaded_at DESC
", [$user_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Student Dashboard</title>
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
        
        /* Profile header section */
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.3);
            margin: 0 auto 1rem;
            display: block;
            position: relative;
            z-index: 1;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .profile-info h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.8rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }
        
        .profile-info p {
            margin: 0.25rem 0;
            opacity: 0.9;
            font-size: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .student-id {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-block;
            margin-top: 0.5rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        /* Form styles */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.2s;
            background: #fff;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: #fff;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .btn {
            padding: 0.875rem 1.75rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            font-size: 0.9rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
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
        
        .btn-danger {
            background: #dc2626;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b91c1c;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left-color: #16a34a;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left-color: #dc2626;
        }
        
        .text-muted {
            color: #6b7280;
        }
        
        /* Document grid */
        .document-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .document-item {
            background: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .document-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .document-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .document-type {
            font-weight: 600;
            color: #374151;
            font-size: 1rem;
        }
        
        .document-status {
            padding: 0.375rem 0.75rem;
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
        
        .document-actions {
            margin-top: 1rem;
        }
        
        .document-meta {
            color: #6b7280;
            font-size: 0.875rem;
            margin: 0.5rem 0;
        }
        
        /* Section headers */
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-title i {
            color: #667eea;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .document-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-header {
                padding: 1.5rem;
            }
            
            .profile-avatar {
                width: 100px;
                height: 100px;
            }
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
                    <img src="<?php 
                        $imgUrl = $student['profile_image'] ?? '../assets/images/default-avatar.png';
                        // Add cache-busting parameter
                        $separator = (strpos($imgUrl, '?') !== false) ? '&' : '?';
                        echo $imgUrl . $separator . 'v=' . time() . '&t=' . ($student['updated_at'] ?? time());
                    ?>" alt="Profile" onerror="this.src='../assets/images/default-avatar.png'" />
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
                <li><a href="documents.php"><i class="fas fa-file-upload"></i> Documents</a></li>
                <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
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
                    <span>My Profile</span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-content">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Profile Header -->
            <div class="profile-header">
                <div style="position: relative; display: inline-block;">
                    <img src="<?php 
                        $imgUrl = $student['profile_image'] ?? '../assets/images/default-avatar.png';
                        // Add cache-busting parameter
                        $separator = (strpos($imgUrl, '?') !== false) ? '&' : '?';
                        echo $imgUrl . $separator . 'v=' . time() . '&t=' . ($student['updated_at'] ?? time());
                    ?>" 
                         alt="Profile Photo" 
                         class="profile-avatar" 
                         id="profile-image-preview"
                         onerror="this.src='../assets/images/default-avatar.png'">
                    <button onclick="document.getElementById('profile-image-upload').click()" 
                            class="btn btn-primary" 
                            style="position: absolute; bottom: 0; right: 0; border-radius: 50%; width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3); background: #667eea; border: none; cursor: pointer;"
                            title="Update Profile Image">
                        <i class="fas fa-camera" style="color: white;"></i>
                    </button>
                    <form method="POST" enctype="multipart/form-data" id="profile-image-form" style="display: none;">
                        <input type="hidden" name="action" value="upload_profile_image">
                        <input type="file" 
                               id="profile-image-upload" 
                               name="profile_image" 
                               accept="image/*" 
                               onchange="uploadProfileImage(this)">
                    </form>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($student['full_name']); ?></h2>
                    <p><?php echo htmlspecialchars($student['email']); ?></p>
                    <div class="student-id">
                        <i class="fas fa-id-card"></i> Student ID: <?php echo htmlspecialchars($student['username']); ?>
                    </div>
                </div>
            </div>
            
            <!-- Profile Information -->
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-user-edit"></i> Personal Information</span>
                </div>
                <div class="panel-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" 
                                       placeholder="Enter your phone number">
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <input type="text" id="address" name="address" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['address'] ?? ''); ?>" 
                                       placeholder="Enter your address">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="panel" style="margin-top: 1.5rem;">
                <div class="panel-header">
                    <span><i class="fas fa-lock"></i> Security Settings</span>
                </div>
                <div class="panel-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" 
                                   placeholder="Enter your current password" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" 
                                       placeholder="Enter your new password" required minlength="8">
                                <small>Password must be at least 8 characters long and contain lowercase, uppercase, and number</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                       placeholder="Confirm your new password" required minlength="8">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- My Documents -->
            <div class="panel" style="margin-top: 1.5rem;">
                <div class="panel-header">
                    <span><i class="fas fa-file-alt"></i> My Documents</span>
                </div>
                <div class="panel-body">
                    <?php if (empty($documents)): ?>
                        <div style="text-align: center; padding: 3rem 1rem;">
                            <i class="fas fa-file-upload" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                            <p class="text-muted" style="font-size: 1.1rem; margin: 0;">No documents uploaded yet</p>
                            <p class="text-muted" style="margin: 0.5rem 0 0 0;">Upload your documents from the Documents page</p>
                        </div>
                    <?php else: ?>
                        <div class="document-grid">
                            <?php foreach ($documents as $doc): ?>
                                <div class="document-item">
                                    <div class="document-header">
                                        <span class="document-type">
                                            <i class="fas fa-file-alt"></i> 
                                            <?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?>
                                        </span>
                                        <span class="document-status status-<?php echo $doc['status']; ?>">
                                            <?php echo ucfirst($doc['status']); ?>
                                        </span>
                                    </div>
                                    <div class="document-meta">
                                        <div><strong>File:</strong> <?php echo htmlspecialchars($doc['document_name']); ?></div>
                                        <div><strong>Uploaded:</strong> <?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></div>
                                    </div>
                                    <div class="document-actions">
                                        <a href="<?php echo htmlspecialchars($doc['imgbb_url']); ?>" target="_blank" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View Document
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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
        
        // Upload profile image function
        function uploadProfileImage(input) {
            if (!input.files || !input.files[0]) {
                return;
            }
            
            const file = input.files[0];
            const fileSize = file.size / 1024; // KB
            
            // Validate file size
            if (fileSize > 400) {
                alert('File size must be less than 400KB. Current size: ' + fileSize.toFixed(1) + 'KB');
                input.value = '';
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                alert('Invalid file type. Please upload JPG, JPEG, or PNG image.');
                input.value = '';
                return;
            }
            
            // Show preview immediately
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewImg = document.getElementById('profile-image-preview');
                if (previewImg) {
                    previewImg.src = e.target.result;
                }
                // Also update sidebar image
                const sidebarImg = document.querySelector('.profile-card-mini img');
                if (sidebarImg) {
                    sidebarImg.src = e.target.result;
                }
            };
            reader.readAsDataURL(file);
            
            // Submit the form
            const form = document.getElementById('profile-image-form');
            if (form) {
                // Ensure form is visible (temporarily) for submission
                form.style.display = 'block';
                form.style.position = 'absolute';
                form.style.left = '-9999px';
                
                // Submit the form
                form.submit();
            } else {
                console.error('Profile image form not found');
                alert('Error: Form not found. Please refresh the page and try again.');
            }
        }
        
        // Force image refresh on page load if updated parameter is present
        if (window.location.search.includes('img_updated=')) {
            // Remove query parameters from URL to clean it up
            const cleanUrl = window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
            
            // Force reload all profile images with new cache-busting
            const images = document.querySelectorAll('img[src*="profile_image"], img.profile-avatar, .profile-card-mini img, img[alt="Profile"]');
            images.forEach(img => {
                if (img.src && !img.src.includes('default-avatar')) {
                    // Remove existing cache parameters and add new ones
                    const url = new URL(img.src, window.location.origin);
                    url.searchParams.delete('v');
                    url.searchParams.delete('t');
                    url.searchParams.set('v', Date.now());
                    url.searchParams.set('t', Date.now());
                    img.src = url.toString();
                    // Force reload
                    img.onload = function() {
                        this.style.opacity = '1';
                    };
                }
            });
            
            // Also try to reload the image by creating a new image element
            setTimeout(function() {
                const previewImg = document.getElementById('profile-image-preview');
                const sidebarImg = document.querySelector('.profile-card-mini img');
                
                function reloadImage(img) {
                    if (!img || !img.src || img.src.includes('default-avatar')) return;
                    
                    const url = new URL(img.src);
                    url.searchParams.delete('v');
                    url.searchParams.delete('t');
                    url.searchParams.set('v', Date.now());
                    url.searchParams.set('t', Date.now());
                    
                    // Create new image to force reload
                    const newImg = new Image();
                    newImg.crossOrigin = 'anonymous';
                    newImg.onload = function() {
                        img.src = this.src;
                        img.style.opacity = '0';
                        setTimeout(() => { img.style.opacity = '1'; }, 50);
                    };
                    newImg.onerror = function() {
                        // If error, try without cache-busting (might be Cloudinary issue)
                        const baseUrl = url.origin + url.pathname;
                        newImg.src = baseUrl + '?v=' + Date.now();
                    };
                    newImg.src = url.toString();
                }
                
                reloadImage(previewImg);
                reloadImage(sidebarImg);
            }, 100);
        }
    </script>
</body>
</html>
