<?php
// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<aside class="admin-sidebar">
    <div class="admin-brand">
        <img src="../assets/images/logo.png" alt="logo" />
        <div class="brand-title">STUDENT PORTAL</div>
    </div>
    <div class="profile-card-mini">
        <?php if (!empty($student['profile_image']) && filter_var($student['profile_image'], FILTER_VALIDATE_URL)): ?>
            <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile" />
        <?php else: ?>
            <div class="profile-placeholder">
                <i class="fas fa-user"></i>
            </div>
        <?php endif; ?>
        <div>
            <div class="name"><?php echo htmlspecialchars(strtoupper($student['full_name'])); ?></div>
            <div class="role">Student</div>
        </div>
    </div>
    <ul class="sidebar-nav">
        <li>
            <a href="../index.php"><i class="fas fa-home"></i> Home</a>
        </li>
        <li class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        </li>
        <li class="<?php echo $current_page === 'courses' ? 'active' : ''; ?>">
            <a href="courses.php"><i class="fas fa-book"></i> My Courses</a>
        </li>
        <li class="<?php echo $current_page === 'documents' ? 'active' : ''; ?>">
            <a href="documents.php"><i class="fas fa-file-alt"></i> Documents</a>
        </li>
        <li class="<?php echo $current_page === 'payments' ? 'active' : ''; ?>">
            <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
        </li>
        <li class="<?php echo $current_page === 'profile' ? 'active' : ''; ?>">
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        </li>
        <li class="<?php echo $current_page === 'view-id' ? 'active' : ''; ?>">
            <a href="view-id.php"><i class="fas fa-id-card"></i> Digital ID</a>
        </li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</aside>
