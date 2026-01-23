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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0f6fb1 0%, #2563eb 100%);
            --accent-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --border-color: #e2e8f0;
        }

        body.admin-dashboard-body {
            font-family: 'Outfit', sans-serif !important;
            background: #f8fafc;
        }

        .admin-content {
            padding: 30px;
            background: radial-gradient(circle at top right, rgba(37, 99, 235, 0.03), transparent),
                radial-gradient(circle at bottom left, rgba(99, 102, 241, 0.03), transparent);
        }

        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header h1 i {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-header p {
            color: #64748b;
            font-size: 1.1rem;
            margin-bottom: 0;
        }

        .settings-grid {
            display: flex;
            flex-direction: column;
            gap: 30px;
            margin-top: 35px;
            width: 100%;
        }

        .panel {
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .panel:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            border-color: rgba(37, 99, 235, 0.2);
        }

        .panel-header {
            padding: 24px 30px;
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
        }

        .panel-header h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
        }

        .panel-header h3 i {
            font-size: 1.1rem;
            color: #3b82f6;
        }

        .panel-body {
            padding: 30px;
            flex-grow: 1;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            border: 1px solid var(--border-color);
            background: #f8fafc;
            padding: 14px 18px;
            font-size: 0.95rem;
            border-radius: 12px;
            transition: all 0.2s ease;
            color: #1e293b;
            font-family: inherit;
        }

        .form-group input:focus {
            background: #fff;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.08);
            outline: none;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 16px;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(15, 111, 177, 0.15);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 111, 177, 0.25);
            filter: brightness(1.05);
        }

        .alert {
            padding: 18px 25px;
            border-radius: 16px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            animation: slideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .password-strength-container {
            margin-top: 12px;
        }

        .password-strength-meter {
            height: 6px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.4s ease;
        }

        .strength-text {
            display: block;
            margin-top: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert.fade-out {
            opacity: 0;
            transform: translateY(-10px) scale(0.98);
            transition: all 0.5s ease;
        }

        .panel-header h3 i {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.2rem;
        }

        .profile-card-mini {
            background: rgba(255, 255, 255, 0.08) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 16px !important;
            margin: 15px 0 !important;
        }

        .profile-card-mini img {
            border: 2px solid #3b82f6 !important;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.2);
        }

        @media (max-width: 768px) {
            .admin-content {
                padding: 20px;
            }

            .settings-grid {
                grid-template-columns: 1fr;
            }
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
                    <div class="role"><?php echo htmlspecialchars(ucfirst($user['user_type'] ?? 'admin')); ?></div>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-user-tie"></i> Staff</a></li>
                <li><a href="courses.php"><i class="fas fa-graduation-cap"></i> Courses</a></li>
                <li><a href="marks-management.php"><i class="fas fa-chart-line"></i> Marks Management</a></li>
                <li><a href="certificate-management.php"><i class="fas fa-certificate"></i> Certificate Management</a>
                </li>
                <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="inquiries.php"><i class="fas fa-question-circle"></i> Course Inquiries</a></li>
                <li><a class="active" href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="menu-toggle"><i class="fas fa-bars"></i></button>
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

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="settings-grid">
                <!-- Change Password Card -->
                <div class="panel">
                    <div class="panel-header">
                        <h3><i class="fas fa-key"></i> Change Password</h3>
                    </div>
                    <div class="panel-body">
                        <form method="POST" action="settings.php">
                            <input type="hidden" name="action" value="change_password">

                            <div class="form-group">
                                <label for="current_password">Current Password *</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>

                            <div class="form-group">
                                <label for="new_password">New Password *</label>
                                <input type="password" id="new_password" name="new_password" required minlength="8">
                                <small>Password must be at least 8 characters long and contain lowercase, uppercase, and
                                    number</small>
                                <div class="password-strength-container">
                                    <div class="password-strength-meter">
                                        <div class="password-strength-bar" id="password-strength-bar"></div>
                                    </div>
                                    <span class="strength-text" id="strength-text"></span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required
                                    minlength="8">
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Profile Settings Card -->
                <div class="panel">
                    <div class="panel-header">
                        <h3><i class="fas fa-user-circle"></i> Profile Settings</h3>
                    </div>
                    <div class="panel-body">
                        <form method="POST" action="settings.php">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="form-group">
                                <label for="full_name">Full Name *</label>
                                <input type="text" id="full_name" name="full_name"
                                    value="<?php echo htmlspecialchars($current_user['full_name'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email"
                                    value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone"
                                    value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>">
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- System Settings Card -->
                <div class="panel">
                    <div class="panel-header">
                        <h3><i class="fas fa-university"></i> System Settings</h3>
                    </div>
                    <div class="panel-body">
                        <form method="POST" action="settings.php">
                            <input type="hidden" name="action" value="update_system_settings">

                            <div class="form-group">
                                <label for="institute_name">Institute Name *</label>
                                <input type="text" id="institute_name" name="institute_name"
                                    value="GICT - Global Institute of Computer Technology" required>
                            </div>

                            <div class="form-group">
                                <label for="contact_email">Contact Email *</label>
                                <input type="email" id="contact_email" name="contact_email" value="info@gict.edu"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="contact_phone">Contact Phone</label>
                                <input type="tel" id="contact_phone" name="contact_phone" value="+91-9876543210">
                            </div>

                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address"
                                    rows="3">123 Main Street, City Center, State - 123456</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update System Settings
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Security Settings Card -->
                <div class="panel">
                    <div class="panel-header">
                        <h3><i class="fas fa-shield-alt"></i> Security Settings</h3>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label>Two-Factor Authentication</label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" id="2fa_enabled" disabled>
                                <label for="2fa_enabled" style="margin: 0; font-weight: normal;">Enable 2FA (Coming
                                    Soon)</label>
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
                                <label for="login_notifications" style="margin: 0; font-weight: normal;">Email
                                    notifications for new logins (Coming Soon)</label>
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

    <script src="../assets/js/mobile-menu.js"></script>
    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('open');
        }

        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function () {
            const password = this.value;
            const bar = document.getElementById('password-strength-bar');
            const statusText = document.getElementById('strength-text');

            if (password.length === 0) {
                bar.style.width = '0';
                statusText.textContent = '';
                return;
            }

            let strength = 0;
            if (password.length >= 8) strength += 20;
            if (/[a-z]/.test(password)) strength += 20;
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 20;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;

            bar.style.width = strength + '%';

            if (strength <= 40) {
                bar.style.background = '#ef4444';
                statusText.textContent = 'Weak';
                statusText.style.color = '#ef4444';
            } else if (strength <= 80) {
                bar.style.background = '#f59e0b';
                statusText.textContent = 'Medium';
                statusText.style.color = '#f59e0b';
            } else {
                bar.style.background = '#10b981';
                statusText.textContent = 'Strong';
                statusText.style.color = '#10b981';
            }
        });

        // Confirm password match
        document.getElementById('confirm_password').addEventListener('input', function () {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;

            if (confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Auto-hide alerts after 2 seconds
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.add('fade-out');
                    setTimeout(() => {
                        alert.remove();
                    }, 500); // Wait for transition to finish before removing
                }, 2000);
            });
        });
    </script>
</body>

</html>