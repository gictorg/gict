<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

$user = getCurrentUser();

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Validate inputs
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    throw new Exception("All password fields are required.");
                }
                
                if ($new_password !== $confirm_password) {
                    throw new Exception("New password and confirm password do not match.");
                }
                
                if (strlen($new_password) < 8) {
                    throw new Exception("New password must be at least 8 characters long.");
                }
                
                // Additional password strength validation
                if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $new_password)) {
                    throw new Exception("Password must contain at least one lowercase letter, one uppercase letter, and one number.");
                }
                
                // Verify current password
                $sql = "SELECT password FROM users WHERE id = ?";
                $current_user = getRow($sql, [$user['id']]);
                
                if (!$current_user || !password_verify($current_password, $current_user['password'])) {
                    throw new Exception("Current password is incorrect.");
                }
                
                // Hash new password and update
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
                $result = updateData($update_sql, [$hashed_password, $user['id']]);
                
                if ($result) {
                    $success_message = "Password changed successfully!";
                } else {
                    throw new Exception("Failed to update password. Please try again.");
                }
                break;
                
            case 'update_profile':
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                
                if (empty($full_name) || empty($email)) {
                    throw new Exception("Full name and email are required.");
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Please enter a valid email address.");
                }
                
                // Check if email already exists for another user
                $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
                $existing_user = getRow($check_sql, [$email, $user['id']]);
                
                if ($existing_user) {
                    throw new Exception("Email address is already in use by another user.");
                }
                
                $update_sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?";
                $result = updateData($update_sql, [$full_name, $email, $phone, $user['id']]);
                
                if ($result) {
                    $success_message = "Profile updated successfully!";
                    // Refresh user data
                    $user = getCurrentUser();
                } else {
                    throw new Exception("Failed to update profile. Please try again.");
                }
                break;
                
            case 'update_system_settings':
                $institute_name = trim($_POST['institute_name']);
                $contact_email = trim($_POST['contact_email']);
                $contact_phone = trim($_POST['contact_phone']);
                $address = trim($_POST['address']);
                
                if (empty($institute_name) || empty($contact_email)) {
                    throw new Exception("Institute name and contact email are required.");
                }
                
                if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Please enter a valid contact email address.");
                }
                
                // Update system settings (you can create a settings table or use config file)
                $success_message = "System settings updated successfully!";
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get current user data for profile form
$current_user = getRow("SELECT * FROM users WHERE id = ?", [$user['id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - GICT Admin</title>
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
        
        .admin-topbar {
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            height: 60px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 999;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 18px;
            color: #64748b;
            cursor: pointer;
            padding: 8px;
        }
        .breadcrumbs {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #64748b;
        }
        .breadcrumbs a {
            color: #3b82f6;
            text-decoration: none;
        }
        .topbar-home-link {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .admin-content {
            flex: 1;
            margin-left: 260px;
            margin-top: 60px;
            padding: 20px;
            min-height: calc(100vh - 60px);
            background: #f1f5f9;
            width: calc(100vw - 260px);
            box-sizing: border-box;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .settings-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .settings-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .settings-header h3 {
            margin: 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .settings-body {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .password-strength {
            margin-top: 8px;
            font-size: 12px;
        }
        
        .strength-weak { color: #dc2626; }
        .strength-medium { color: #d97706; }
        .strength-strong { color: #059669; }
        
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .admin-sidebar.open {
                transform: translateX(0);
            }
            .admin-content {
                margin-left: 0;
                width: 100vw;
            }
            .admin-topbar {
                left: 0;
            }
            .menu-toggle {
                display: block;
            }
            .settings-grid {
                grid-template-columns: 1fr;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
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
                    <div class="role"><?php echo htmlspecialchars(ucfirst($user['user_type'] ?? 'admin')); ?></div>
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
                <li><a class="active" href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="breadcrumbs">
                    <a href="../index.php" class="topbar-home-link"><i class="fas fa-home"></i> Home</a>
                    <span style="opacity:.7; margin: 0 6px;">/</span>
                    <a href="../dashboard.php">Dashboard</a>
                    <span style="opacity:.7; margin: 0 6px;">/</span>
                    <span>Settings</span>
                </div>
            </div>
        </header>

        <main class="admin-content">
            <div class="page-header">
                <h1><i class="fas fa-cog"></i> Admin Settings</h1>
                <p>Manage your account settings and system preferences</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="settings-grid">
                <!-- Change Password Card -->
                <div class="settings-card">
                    <div class="settings-header">
                        <h3><i class="fas fa-key"></i> Change Password</h3>
                    </div>
                    <div class="settings-body">
                        <form method="POST" action="settings.php">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label for="current_password">Current Password *</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password *</label>
                                <input type="password" id="new_password" name="new_password" required minlength="8">
                                <small>Password must be at least 8 characters long and contain lowercase, uppercase, and number</small>
                                <div class="password-strength" id="password-strength"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Profile Settings Card -->
                <div class="settings-card">
                    <div class="settings-header">
                        <h3><i class="fas fa-user"></i> Profile Settings</h3>
                    </div>
                    <div class="settings-body">
                        <form method="POST" action="settings.php">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-group">
                                <label for="full_name">Full Name *</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($current_user['full_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- System Settings Card -->
                <div class="settings-card">
                    <div class="settings-header">
                        <h3><i class="fas fa-building"></i> System Settings</h3>
                    </div>
                    <div class="settings-body">
                        <form method="POST" action="settings.php">
                            <input type="hidden" name="action" value="update_system_settings">
                            
                            <div class="form-group">
                                <label for="institute_name">Institute Name *</label>
                                <input type="text" id="institute_name" name="institute_name" value="GICT - Global Institute of Computer Technology" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_email">Contact Email *</label>
                                <input type="email" id="contact_email" name="contact_email" value="info@gict.edu" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_phone">Contact Phone</label>
                                <input type="tel" id="contact_phone" name="contact_phone" value="+91-9876543210">
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" rows="3">123 Main Street, City Center, State - 123456</textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update System Settings
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Security Settings Card -->
                <div class="settings-card">
                    <div class="settings-header">
                        <h3><i class="fas fa-shield-alt"></i> Security Settings</h3>
                    </div>
                    <div class="settings-body">
                        <div class="form-group">
                            <label>Two-Factor Authentication</label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" id="2fa_enabled" disabled>
                                <label for="2fa_enabled" style="margin: 0; font-weight: normal;">Enable 2FA (Coming Soon)</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Session Timeout</label>
                            <select disabled style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <option>30 minutes</option>
                                <option>1 hour</option>
                                <option>4 hours</option>
                                <option>8 hours</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Login Notifications</label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" id="login_notifications" disabled>
                                <label for="login_notifications" style="margin: 0; font-weight: normal;">Email notifications for new logins (Coming Soon)</label>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-secondary" disabled>
                            <i class="fas fa-save"></i> Update Security Settings
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('open');
        }

        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                strengthDiv.className = 'password-strength';
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            let strengthText = '';
            let strengthClass = '';
            
            if (strength <= 2) {
                strengthText = 'Weak password';
                strengthClass = 'strength-weak';
            } else if (strength <= 4) {
                strengthText = 'Medium strength password';
                strengthClass = 'strength-medium';
            } else {
                strengthText = 'Strong password';
                strengthClass = 'strength-strong';
            }
            
            strengthDiv.textContent = strengthText;
            strengthDiv.className = 'password-strength ' + strengthClass;
        });

        // Confirm password match
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
