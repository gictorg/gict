<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

// Include Cloudinary helper for image uploads
require_once '../includes/cloudinary_helper.php';

// Include User ID Generator helper
require_once '../includes/user_id_generator.php';

$user = getCurrentUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_staff':
                $email = $_POST['email'] ?? '';
                $full_name = $_POST['full_name'] ?? '';
                $phone = $_POST['phone'] ?? '';
                $address = $_POST['address'] ?? '';
                $date_of_birth = $_POST['date_of_birth'] ?? '';
                $gender = trim($_POST['gender'] ?? '');
                $qualification = $_POST['qualification'] ?? '';
                $experience_years = $_POST['experience_years'] ?? 0;
                $joining_date = $_POST['joining_date'] ?? '';
                $previous_institute = $_POST['previous_institute'] ?? '';
                
                // Validate gender - required field, must be one of the allowed ENUM values
                $allowed_genders = ['male', 'female', 'other'];
                if (empty($gender)) {
                    $_SESSION['error_message'] = "Please select a gender.";
                    header('Location: staff.php');
                    exit;
                } elseif (!in_array($gender, $allowed_genders)) {
                    $_SESSION['error_message'] = "Invalid gender value selected.";
                    header('Location: staff.php');
                    exit;
                }
                
                // Validate date of birth - cannot be in the future
                if (!empty($date_of_birth) && $date_of_birth > date('Y-m-d')) {
                    $_SESSION['error_message'] = "Date of birth cannot be in the future.";
                    header('Location: staff.php');
                    exit;
                }
                
                // Handle profile image upload to Cloudinary
                $profile_image_url = '';
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                    $profile_file = $_FILES['profile_image'];
                    $profile_ext = strtolower(pathinfo($profile_file['name'], PATHINFO_EXTENSION));
                    $allowed_image_exts = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($profile_ext, $allowed_image_exts) && $profile_file['size'] <= 400 * 1024) {
                        // We'll upload with a temporary name first, then rename after user ID generation
                        $temp_name = 'temp_faculty_' . time();
                        $upload_result = smartUpload(
                            $profile_file['tmp_name'], 
                            $temp_name
                        );
                        
                        if ($upload_result && $upload_result['success']) {
                            $profile_image_url = $upload_result['url'];
                        } else {
                            $error_message = "Failed to upload profile image to Cloudinary.";
                        }
                    } else {
                        $error_message = "Profile image must be JPG, PNG, or GIF and under 400KB.";
                    }
                }
                
                if (empty($error_message)) {
                    // Generate unique user ID for faculty
                    $generated_user_id = generateUniqueUserId('faculty');
                    if (!$generated_user_id) {
                        $error_message = "Failed to generate unique user ID for faculty member.";
                    } else {
                        // Generate default password (generated_user_id + 123)
                        $default_password = $generated_user_id . '123';
                        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
                        
                        try {
                            $sql = "INSERT INTO users (username, password, email, full_name, user_type_id, phone, address, date_of_birth, gender, qualification, experience_years, joining_date, profile_image) VALUES (?, ?, ?, ?, 3, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $result = insertData($sql, [$generated_user_id, $hashed_password, $email, $full_name, $phone, $address, $date_of_birth, $gender, $qualification, $experience_years, $joining_date, $profile_image_url]);
                            
                            if ($result) {
                                $success_message = "Staff member '$full_name' added successfully!<br>";
                                $success_message .= "<strong>User ID:</strong> $generated_user_id<br>";
                                $success_message .= "<strong>Default Password:</strong> $default_password";
                                
                                if (!empty($profile_image_url)) {
                                    $success_message .= "<br><strong>Profile Image:</strong> <a href='$profile_image_url' target='_blank'>View Image</a>";
                                }
                            } else {
                                $error_message = "Failed to add staff member.";
                            }
                        } catch (Exception $e) {
                            $error_message = "Error: " . $e->getMessage();
                        }
                    }
                }
                break;
                
            case 'delete_staff':
                $staff_id = $_POST['staff_id'] ?? 0;
                if ($staff_id) {
                    try {
                        $sql = "DELETE FROM users WHERE id = ? AND user_type_id = 3";
                        $result = deleteData($sql, [$staff_id]);
                        if ($result) {
                            $success_message = "Staff member deleted successfully!";
                        } else {
                            $error_message = "Failed to delete staff member.";
                        }
                    } catch (Exception $e) {
                        $error_message = "Error: " . $e->getMessage();
                    }
                }
                break;
                
            case 'toggle_status':
                $staff_id = $_POST['staff_id'] ?? 0;
                $new_status = $_POST['new_status'] ?? '';
                if ($staff_id) {
                    try {
                        $sql = "UPDATE users SET status = ? WHERE id = ? AND user_type_id = 3";
                        $result = updateData($sql, [$new_status, $staff_id]);
                        if ($result) {
                            $success_message = "Staff status updated successfully!";
                        } else {
                            $error_message = "Failed to update staff status.";
                        }
                    } catch (Exception $e) {
                        $error_message = "Error: " . $e->getMessage();
                    }
                }
                break;
                
            case 'update_password':
                $staff_id = $_POST['staff_id'] ?? 0;
                $new_password = $_POST['staff_password'] ?? '';
                if ($staff_id && !empty($new_password)) {
                    try {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ? AND user_type_id = 3";
                        $result = updateData($sql, [$hashed_password, $staff_id]);
                        if ($result) {
                            $success_message = "Staff password updated successfully!";
                        } else {
                            $error_message = "Failed to update staff password.";
                        }
                    } catch (Exception $e) {
                        $error_message = "Error: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Please provide a new password.";
                }
                break;
        }
    }
}

// Get all faculty/staff members
$staff = [];
try {
            $sql = "SELECT 
                u.id, u.username, u.email, u.full_name, u.phone, u.address, 
                u.date_of_birth, u.gender, u.qualification, u.experience_years, 
                u.joining_date, u.status, u.created_at, u.profile_image
                          FROM users u 
              WHERE u.user_type_id = 3 
              ORDER BY u.created_at DESC";
    $staff = getRows($sql);
} catch (Exception $e) {
    $error_message = "Error loading staff: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - GICT Admin</title>
    <link rel="icon" type="image/png" href="../logo.png">
    <link rel="shortcut icon" type="image/png" href="../logo.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-content {
            padding: 20px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .add-staff-btn {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .add-staff-btn:hover {
            transform: translateY(-2px);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid #ffc107;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }
        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .stat-card .label {
            color: #999;
            font-size: 12px;
        }
        .staff-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .staff-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .staff-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ffc107, #fd7e14, #e83e8c);
        }
        .staff-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        .staff-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }
        
        .staff-avatar {
            flex-shrink: 0;
        }
        
        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #667eea;
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        
        .default-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            border: 3px solid #667eea;
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        .staff-details {
            flex: 1;
        }
        .staff-name {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 4px;
        }
        .staff-username {
            font-size: 14px;
            color: #667eea;
            font-weight: 500;
        }
        .staff-status {
            background: #f8f9fa;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .staff-status.active { background: #d4edda; color: #155724; }
        .staff-status.inactive { background: #f8d7da; color: #721c24; }
        
        /* Search Section Styling */
        .search-section {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 18px;
        }
        .search-box input {
            width: 100%;
            padding: 15px 50px 15px 45px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .clear-search {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .clear-search:hover {
            background: #c82333;
            transform: translateY(-50%) scale(1.1);
        }
        .search-filters {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .search-filters select {
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .search-filters select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }
        .search-filters select:hover {
            border-color: #667eea;
        }
        
        .section-header {
            margin-bottom: 25px;
            text-align: center;
        }
        .section-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .section-header h2 i {
            color: #667eea;
        }
        .section-header p {
            color: #6c757d;
            font-size: 16px;
            margin: 0;
        }
        .staff-info {
            margin-bottom: 15px;
            font-size: 14px;
        }
        .staff-info strong {
            color: #666;
            min-width: 100px;
            display: inline-block;
        }
        .qualification-section {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #ffc107;
        }
        .qualification-section h4 {
            margin: 0 0 10px 0;
            color: #856404;
            font-size: 14px;
        }
        .staff-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
        }
        .btn-edit { background: #007bff; color: white; }
        .btn-password { background: #6f42c1; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-toggle { background: #6c757d; color: white; }
        .btn:hover { opacity: 0.8; }
        .status-active { color: #28a745; }
        .status-inactive { color: #dc3545; }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        .close:hover { color: #000; }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        .btn-primary { background: #007bff; color: white; padding: 12px 24px; }
        .btn-secondary { background: #6c757d; color: white; padding: 12px 24px; }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Enhanced Modal Styles */
        .form-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }
        
        .form-section h3 {
            margin: 0 0 20px 0;
            color: #495057;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-section h3 i {
            color: #667eea;
            font-size: 20px;
        }
        
        .file-upload-wrapper {
            position: relative;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #fff;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-upload-wrapper:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .file-upload-wrapper input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-upload-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            color: #6c757d;
        }
        
        .file-upload-info i {
            font-size: 24px;
            color: #667eea;
        }
        
        .file-upload-info span {
            font-size: 14px;
            font-weight: 500;
        }
        
        .image-preview {
            margin-top: 15px;
            text-align: center;
        }
        
        .image-preview img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .image-preview .file-info {
            margin-top: 10px;
            padding: 8px 12px;
            background: #e9ecef;
            border-radius: 6px;
            font-size: 12px;
            color: #495057;
        }
        
        .form-text {
            margin-top: 8px;
            font-size: 12px;
            color: #6c757d;
        }
        
        .form-text i {
            margin-right: 5px;
            color: #667eea;
        }
    </style>
</head>
<body class="admin-dashboard-body">
    <div class="admin-layout">
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
                <li><a class="active" href="staff.php"><i class="fas fa-user-tie"></i> Staff</a></li>
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
                <button class="menu-toggle"><i class="fas fa-bars"></i></button>
                <div class="breadcrumbs">
                    <a href="../index.php" class="home-link">Home</a> / 
                    <a href="../dashboard.php">Dashboard</a> / Staff
                </div>
            </div>
        </header>

        <main class="admin-content">
            <div class="page-header">
                <h1><i class="fas fa-user-tie"></i> Staff Management</h1>
                <button class="add-staff-btn" onclick="openAddStaffModal()">
                    <i class="fas fa-plus"></i> Add New Staff
                </button>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" id="successAlert"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" id="errorAlert"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Staff</h3>
                    <div class="value"><?php echo count($staff); ?></div>
                    <div class="label">Faculty Members</div>
                </div>
                <div class="stat-card">
                    <h3>Active Staff</h3>
                    <div class="value"><?php echo count(array_filter($staff, function($s) { return $s['status'] === 'active'; })); ?></div>
                    <div class="label">Currently Active</div>
                </div>
                <div class="stat-card">
                    <h3>Avg Experience</h3>
                    <div class="value">
                        <?php 
                        $active_staff = array_filter($staff, function($s) { return $s['status'] === 'active'; });
                        if (count($active_staff) > 0) {
                            $avg_exp = array_sum(array_column($active_staff, 'experience_years')) / count($active_staff);
                            echo number_format($avg_exp, 1);
                        } else {
                            echo '0';
                        }
                        ?>
                    </div>
                    <div class="label">Years</div>
                </div>
                <div class="stat-card">
                    <h3>PhD Holders</h3>
                    <div class="value">
                        <?php 
                        $phd_count = count(array_filter($staff, function($s) { 
                            return $s['status'] === 'active' && 
                                   stripos($s['qualification'], 'ph.d') !== false; 
                        }));
                        echo $phd_count;
                        ?>
                    </div>
                    <div class="label">Doctorate Degree</div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="search-section">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="staffSearch" placeholder="Search staff by name, user ID, email, or phone..." onkeyup="searchStaff()">
                    <button class="clear-search" onclick="clearStaffSearch()" id="clearStaffSearchBtn" style="display: none;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="search-filters">
                    <select id="statusFilter" onchange="filterStaff()">
                        <option value="active">Active Only</option>
                        <option value="">All Status</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                    <select id="sortBy" onchange="sortStaff()">
                        <option value="created_at">Sort by: Join Date</option>
                        <option value="full_name">Sort by: Name</option>
                        <option value="username">Sort by: User ID</option>
                        <option value="experience_years">Sort by: Experience</option>
                    </select>
                </div>
            </div>

            <!-- Staff Grid -->
            <div class="section-header">
                <h2><i class="fas fa-users"></i> All Staff Members</h2>
                <p>Manage faculty accounts, view profiles, and track qualifications</p>
            </div>
            <div class="staff-grid" id="staffGrid">
                <?php foreach ($staff as $member): ?>
                    <div class="staff-card">
                        <div class="staff-header">
                            <div class="staff-avatar">
                                <?php if (!empty($member['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($member['profile_image']); ?>" alt="Profile" class="profile-img">
                                <?php else: ?>
                                    <div class="default-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="staff-details">
                                <div class="staff-name"><?php echo htmlspecialchars($member['full_name']); ?></div>
                                <div class="staff-username"><?php echo htmlspecialchars($member['username']); ?></div>
                            </div>
                            <div class="staff-status <?php echo $member['status']; ?>">
                                <?php echo htmlspecialchars(ucfirst($member['status'])); ?>
                            </div>
                        </div>
                        
                        <div class="staff-info">
                            <strong>User ID:</strong> <?php echo htmlspecialchars($member['username']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?><br>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($member['phone']); ?><br>
                            <strong>Gender:</strong> <?php echo htmlspecialchars(ucfirst($member['gender'] ?? 'N/A')); ?><br>
                            <strong>Joined:</strong> <?php echo date('M d, Y', strtotime($member['created_at'])); ?>
                        </div>

                        <div class="qualification-section">
                            <h4><i class="fas fa-graduation-cap"></i> Professional Details</h4>
                            <div class="staff-info">
                                <strong>Qualification:</strong> <?php echo htmlspecialchars($member['qualification'] ?? 'N/A'); ?><br>
                                <strong>Experience:</strong> <?php echo $member['experience_years']; ?> years<br>
                                <strong>Joining Date:</strong> <?php echo $member['joining_date'] ? date('M d, Y', strtotime($member['joining_date'])) : 'N/A'; ?>
                            </div>
                        </div>

                        <div class="staff-actions">
                            <button class="btn btn-edit" onclick="editStaff(<?php echo $member['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-password" onclick="openPasswordModal(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['username']); ?>')">
                                <i class="fas fa-key"></i> Password
                            </button>
                            <button class="btn btn-toggle" onclick="toggleStatus(<?php echo $member['id']; ?>, '<?php echo $member['status'] === 'active' ? 'inactive' : 'active'; ?>')">
                                <i class="fas fa-toggle-on"></i> 
                                <?php echo $member['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                            </button>
                            <button class="btn btn-delete" onclick="deleteStaff(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['full_name']); ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Add Staff Modal -->
    <div id="addStaffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Add New Staff Member</h2>
                <span class="close" onclick="closeAddStaffModal()">&times;</span>
            </div>
            
            <form method="POST" action="staff.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_staff">
                
                
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" required placeholder="Enter staff member's full name">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required placeholder="staff@example.com">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="+91-9876543210">
                        </div>
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="gender">Gender *</label>
                            <select id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3" placeholder="Enter complete address"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Professional Information Section -->
                <div class="form-section">
                    <h3><i class="fas fa-briefcase"></i> Professional Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="qualification">Qualification *</label>
                            <select id="qualification" name="qualification" required>
                                <option value="">Select Qualification</option>
                                <option value="B.Tech">B.Tech</option>
                                <option value="M.Tech">M.Tech</option>
                                <option value="B.Sc">B.Sc</option>
                                <option value="M.Sc">M.Sc</option>
                                <option value="B.Com">B.Com</option>
                                <option value="M.Com">M.Com</option>
                                <option value="BBA">BBA</option>
                                <option value="MBA">MBA</option>
                                <option value="Ph.D">Ph.D</option>
                                <option value="Diploma">Diploma</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="experience_years">Years of Experience *</label>
                            <input type="number" id="experience_years" name="experience_years" min="0" max="50" required placeholder="0">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="joining_date">Joining Date</label>
                            <input type="date" id="joining_date" name="joining_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="previous_institute">Previous Institute (Optional)</label>
                            <input type="text" id="previous_institute" name="previous_institute" placeholder="Name of previous institute">
                        </div>
                    </div>
                </div>
                
                <!-- Profile Image Section -->
                <div class="form-section">
                    <h3><i class="fas fa-camera"></i> Profile Image</h3>
                    
                    <div class="form-group">
                        <label for="profile_image">Profile Image (Optional)</label>
                        <div class="file-upload-wrapper">
                            <input type="file" id="profile_image" name="profile_image" accept="image/*" onchange="previewImage(this, 'staff-profile-preview')">
                            <div class="file-upload-info">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Click to upload or drag & drop</span>
                            </div>
                        </div>
                        <div id="staff-profile-preview" class="image-preview"></div>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Max size: 400KB. Formats: JPG, PNG, GIF
                        </small>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddStaffModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add Staff Member
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Update Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-key"></i> Update Password</h2>
                <span class="close" onclick="closePasswordModal()">&times;</span>
            </div>
            
            <form method="POST" action="staff.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_password">
                <input type="hidden" id="password_staff_id" name="staff_id">
                
                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input type="password" id="new_password" name="staff_password" required 
                           minlength="6" placeholder="Enter new password (min 6 characters)">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           minlength="6" placeholder="Confirm new password">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mobile Menu JavaScript -->
    <script src="../assets/js/mobile-menu.js"></script>
    
    <script>
        // Image preview functionality
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            
            if (file) {
                // Clear previous preview
                preview.innerHTML = '';
                
                // Check file size (400KB limit for staff)
                const fileSize = file.size / 1024; // Convert to KB
                if (fileSize > 400) {
                    alert('File size must be less than 400KB. Current size: ' + fileSize.toFixed(1) + 'KB');
                    input.value = '';
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid file type: JPG, JPEG, PNG, or GIF');
                    input.value = '';
                    return;
                }
                
                // Show image preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Preview';
                    preview.appendChild(img);
                    
                    // Add file info
                    const fileInfo = document.createElement('div');
                    fileInfo.className = 'file-info';
                    fileInfo.innerHTML = `
                        <strong>${file.name}</strong><br>
                        Size: ${fileSize.toFixed(1)}KB<br>
                        Type: ${file.type}
                    `;
                    preview.appendChild(fileInfo);
                };
                reader.readAsDataURL(file);
            }
        }
        
        // Form validation
        function validateStaffForm() {
            const form = document.querySelector('form[action="staff.php"]');
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    field.style.borderColor = '#e1e5e9';
                }
            });
            
            if (!isValid) {
                alert('Please fill in all required fields marked with *');
            }
            
            return isValid;
        }
        
        // Enhanced form submission
        document.querySelector('form[action="staff.php"]').addEventListener('submit', function(e) {
            if (!validateStaffForm()) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Staff...';
            submitBtn.disabled = true;
            
            // Re-enable after a delay (in case of error)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
        });
        
        function openAddStaffModal() {
            document.getElementById('addStaffModal').style.display = 'block';
        }
        
        function closeAddStaffModal() {
            document.getElementById('addStaffModal').style.display = 'none';
        }
        
        function openPasswordModal(staffId, username) {
            document.getElementById('password_staff_id').value = staffId;
            document.getElementById('passwordModal').style.display = 'block';
            document.getElementById('new_password').focus();
        }
        
        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
        }
        
        function deleteStaff(staffId, staffName) {
            if (confirm(`Are you sure you want to delete staff member "${staffName}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'staff.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_staff';
                
                const staffIdInput = document.createElement('input');
                staffIdInput.type = 'hidden';
                staffIdInput.name = 'staff_id';
                staffIdInput.value = staffId;
                
                form.appendChild(actionInput);
                form.appendChild(staffIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function toggleStatus(staffId, newStatus) {
            const action = newStatus === 'active' ? 'activate' : 'deactivate';
            if (confirm(`Are you sure you want to ${action} this staff member?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'staff.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'toggle_status';
                
                const staffIdInput = document.createElement('input');
                staffIdInput.type = 'hidden';
                staffIdInput.name = 'staff_id';
                staffIdInput.value = staffId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'new_status';
                statusInput.value = newStatus;
                
                form.appendChild(actionInput);
                form.appendChild(staffIdInput);
                form.appendChild(statusInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addStaffModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
        
        // Search, Filter, and Sort Functions for Staff
        let allStaff = [];
        
        function searchStaff() {
            const searchTerm = document.getElementById('staffSearch').value.toLowerCase();
            const clearBtn = document.getElementById('clearStaffSearchBtn');
            
            if (searchTerm.length > 0) {
                clearBtn.style.display = 'block';
            } else {
                clearBtn.style.display = 'none';
            }
            
            // Apply both search and status filter
            filterStaff();
        }
        
        function clearStaffSearch() {
            document.getElementById('staffSearch').value = '';
            document.getElementById('clearStaffSearchBtn').style.display = 'none';
            
            // Re-apply status filter after clearing search
            filterStaff();
        }
        
        function filterStaff() {
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const searchTerm = document.getElementById('staffSearch').value.toLowerCase();
            
            allStaff.forEach(staff => {
                let show = true;
                
                // Apply status filter
                if (statusFilter && staff.status !== statusFilter) {
                    show = false;
                }
                
                // Apply search filter
                if (searchTerm && !(staff.name.includes(searchTerm) || 
                                   staff.userId.includes(searchTerm) || 
                                   staff.email.includes(searchTerm))) {
                    show = false;
                }
                
                staff.element.style.display = show ? 'block' : 'none';
            });
            
            updateStaffCount();
        }
        
        function sortStaff() {
            const sortBy = document.getElementById('sortBy').value;
            const staffGrid = document.getElementById('staffGrid');
            const visibleStaff = allStaff.filter(staff => 
                staff.element.style.display !== 'none'
            );
            
            // Sort the visible staff
            visibleStaff.sort((a, b) => {
                switch(sortBy) {
                    case 'full_name':
                        return a.name.localeCompare(b.name);
                    case 'username':
                        return a.userId.localeCompare(b.userId);
                    case 'experience_years':
                        return b.experience - a.experience;
                    case 'created_at':
                    default:
                        return b.created_at - a.created_at;
                }
            });
            
            // Reorder the DOM elements
            visibleStaff.forEach(staff => {
                staffGrid.appendChild(staff.element);
            });
        }
        
        function updateStaffCount() {
            const visibleCount = allStaff.filter(staff => 
                staff.element.style.display !== 'none'
            ).length;
            
            const totalCount = allStaff.length;
            const sectionHeader = document.querySelector('.section-header h2');
            sectionHeader.innerHTML = `<i class="fas fa-users"></i> All Staff Members (${visibleCount}/${totalCount})`;
        }
        
        // Apply default filter on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Store all staff data for search/filter operations
            const staffCards = document.querySelectorAll('.staff-card');
            allStaff = Array.from(staffCards).map(card => ({
                element: card,
                name: card.querySelector('.staff-name').textContent.toLowerCase(),
                userId: card.querySelector('.staff-username').textContent.toLowerCase(),
                email: card.querySelector('.staff-info').textContent.toLowerCase(),
                status: card.querySelector('.staff-status').textContent.toLowerCase().trim(),
                experience: parseInt(card.querySelector('.staff-info').textContent.match(/experience:\s*(\d+)/i)?.[1] || 0),
                created_at: card.querySelector('.staff-info').textContent.includes('Joined:') ? 
                    new Date(card.querySelector('.staff-info').textContent.split('Joined:')[1].trim()) : new Date(0)
            }));
            

            
            // Apply default filter to show only active staff
            filterStaff();
        });
        
        // Auto-dismiss alerts after 5 seconds
        const successAlert = document.getElementById('successAlert');
        const errorAlert = document.getElementById('errorAlert');
        
        if (successAlert) {
            setTimeout(function() {
                successAlert.style.transition = 'opacity 0.5s ease-out';
                successAlert.style.opacity = '0';
                setTimeout(function() {
                    successAlert.remove();
                }, 500);
            }, 5000);
        }
        
        if (errorAlert) {
            setTimeout(function() {
                errorAlert.style.transition = 'opacity 0.5s ease-out';
                errorAlert.style.opacity = '0';
                setTimeout(function() {
                    errorAlert.remove();
                }, 500);
            }, 5000);
        }
    </script>
</body>
</html>
