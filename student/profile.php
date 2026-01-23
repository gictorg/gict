<?php
require_once '../includes/session_manager.php';
require_once '../config/database.php';
require_once '../includes/cloudinary_helper.php';

if (!isLoggedIn() || !isStudent()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$student = getRow("SELECT u.* FROM users u WHERE u.id = ?", [$user_id]);
if (!$student) {
    header('Location: ../login.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
$message = '';
$message_type = '';

// Processing POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_profile_image') {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $file = $_FILES['profile_image'];
            if ($file['size'] <= 500 * 1024) {
                $upload = smartUpload($file['tmp_name'], $student['username'] . '_avatar_' . time());
                if ($upload && $upload['success']) {
                    updateData("UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?", [$upload['url'], $user_id]);
                    header('Location: profile.php?updated=1');
                    exit;
                } else {
                    $message = "Upload failed.";
                    $message_type = "error";
                }
            } else {
                $message = "File too large (max 500KB).";
                $message_type = "error";
            }
        }
    } elseif ($action === 'update_profile') {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        if (!empty($full_name) && !empty($email)) {
            updateData("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?", [$full_name, $email, $phone, $address, $user_id]);
            $message = "Profile updated successfully!";
            $message_type = "success";
            $student = getRow("SELECT u.* FROM users u WHERE u.id = ?", [$user_id]);
        }
    } elseif ($action === 'change_password') {
        $curr = $_POST['current_password'];
        $new = $_POST['new_password'];
        if (password_verify($curr, $student['password'])) {
            if (strlen($new) >= 8) {
                updateData("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?", [password_hash($new, PASSWORD_DEFAULT), $user_id]);
                $message = "Password updated successfully!";
                $message_type = "success";
            } else {
                $message = "New password must be at least 8 chars.";
                $message_type = "error";
            }
        } else {
            $message = "Incorrect current password.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Student Portal</title>
    <link rel="icon" type="image/png" href="../logo.png">
    <link rel="stylesheet" href="../assets/css/student-portal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="student-portal-body">
    <div class="student-layout">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-container" style="width: 100%; overflow: auto;">
            <!-- Topbar -->
            <header class="admin-topbar">
                <div class="topbar-left">
                    <div class="breadcrumbs">
                        <a href="dashboard.php">Dashboard</a> / <span>Profile</span>
                    </div>
                </div>
            </header>

            <main class="admin-content">
                <?php if ($message): ?>
                    <div
                        style="padding: 15px; border-radius: 12px; margin-bottom: 25px; background: <?php echo $message_type === 'success' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $message_type === 'success' ? '#166534' : '#991b1b'; ?>; border: 1px solid <?php echo $message_type === 'success' ? '#bbf7d0' : '#fecaca'; ?>;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
                    <!-- LEFT COLUMN: Profile Summary -->
                    <div>
                        <div class="panel" style="text-align: center; padding: 40px 20px;">
                            <div style="position: relative; width: 150px; height: 150px; margin: 0 auto 20px;">
                                <img src="<?php echo $student['profile_image'] ?? '../assets/images/default-avatar.png'; ?>"
                                    style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 5px solid #f8fafc; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                                <button onclick="document.getElementById('imgInput').click()"
                                    style="position: absolute; bottom: 5px; right: 5px; width: 40px; height: 40px; border-radius: 50%; background: #667eea; color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                                    <i class="fas fa-camera"></i>
                                </button>
                                <form id="imgForm" method="POST" enctype="multipart/form-data" style="display:none;">
                                    <input type="hidden" name="action" value="upload_profile_image">
                                    <input type="file" id="imgInput" name="profile_image"
                                        onchange="document.getElementById('imgForm').submit()">
                                </form>
                            </div>
                            <h2 style="margin: 0; color: #1e293b;">
                                <?php echo htmlspecialchars($student['full_name']); ?>
                            </h2>
                            <p style="margin: 5px 0 20px; color: #64748b; font-size: 0.9rem;"><strong>Student
                                    ID:</strong> <?php echo htmlspecialchars($student['username']); ?></p>

                            <div
                                style="text-align: left; padding: 20px; background: #f8fafc; border-radius: 15px; margin-top: 20px;">
                                <div style="margin-bottom: 10px; font-size: 0.85rem;">
                                    <span style="color: #64748b; font-weight: 600; display: block;">Email Address</span>
                                    <span
                                        style="color: #1e293b;"><?php echo htmlspecialchars($student['email']); ?></span>
                                </div>
                                <div style="font-size: 0.85rem;">
                                    <span style="color: #64748b; font-weight: 600; display: block;">Registered
                                        Phone</span>
                                    <span
                                        style="color: #1e293b;"><?php echo htmlspecialchars($student['phone'] ?: 'N/A'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: Forms -->
                    <div style="display: grid; gap: 30px;">
                        <!-- Information Form -->
                        <div class="panel">
                            <div class="panel-header">
                                <h1><i class="fas fa-id-badge"></i> Edit Profile</h1>
                            </div>
                            <div class="panel-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    <div
                                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                        <div>
                                            <label
                                                style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px;">Full
                                                Name</label>
                                            <input type="text" name="full_name"
                                                value="<?php echo htmlspecialchars($student['full_name']); ?>" required
                                                style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                                        </div>
                                        <div>
                                            <label
                                                style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px;">Email</label>
                                            <input type="email" name="email"
                                                value="<?php echo htmlspecialchars($student['email']); ?>" required
                                                style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                                        </div>
                                    </div>
                                    <div
                                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                        <div>
                                            <label
                                                style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px;">Phone
                                                Number</label>
                                            <input type="text" name="phone"
                                                value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>"
                                                style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                                        </div>
                                        <div>
                                            <label
                                                style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px;">Address</label>
                                            <input type="text" name="address"
                                                value="<?php echo htmlspecialchars($student['address'] ?? ''); ?>"
                                                style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn-primary">Update Profile</button>
                                </form>
                            </div>
                        </div>

                        <!-- Password Form -->
                        <div class="panel">
                            <div class="panel-header">
                                <h1><i class="fas fa-shield-alt"></i> Security</h1>
                            </div>
                            <div class="panel-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    <div style="margin-bottom: 20px;">
                                        <label
                                            style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px;">Current
                                            Password</label>
                                        <input type="password" name="current_password" required
                                            style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                                    </div>
                                    <div
                                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                                        <div>
                                            <label
                                                style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px;">New
                                                Password</label>
                                            <input type="password" name="new_password" required minlength="8"
                                                style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                                        </div>
                                        <div>
                                            <label
                                                style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px;">Verify
                                                Password</label>
                                            <input type="password" name="confirm_password" required
                                                style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn-primary" style="background: #1e293b;">Change
                                        Security Key</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }
    </script>
</body>

</html>