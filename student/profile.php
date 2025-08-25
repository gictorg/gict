<?php
require_once '../includes/session_manager.php';
require_once '../config/database.php';

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
                    $student = getRow("SELECT * FROM users WHERE id = ? AND user_type = 'student'", [$user_id]);
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
        } elseif (strlen($new_password) < 6) {
            $message = 'New password must be at least 6 characters long';
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
            <div class="topbar-right">
                <div class="user-chip">
                    <img src="<?php echo $student['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" alt="Profile" onerror="this.src='../assets/images/default-avatar.png'" />
                    <span><?php echo htmlspecialchars($student['full_name']); ?></span>
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
                <img src="<?php echo $student['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" 
                     alt="Profile Photo" class="profile-avatar" 
                     onerror="this.src='../assets/images/default-avatar.png'">
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($student['full_name']); ?></h2>
                    <p><?php echo htmlspecialchars($student['email']); ?></p>
                    <div class="student-id">
                        <i class="fas fa-id-card"></i> Student ID: <?php echo $student['id']; ?>
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
                                       placeholder="Enter your new password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                       placeholder="Confirm your new password" required>
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
    </script>
</body>
</html>
