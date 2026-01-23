<?php
// Get current page for active tab highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar" id="sidebar">
    <div class="admin-brand">
        <div class="brand-title">STUDENT PORTAL</div>
    </div>

    <div class="profile-card-mini">
        <div style="position: relative;">
            <img src="<?php echo $student['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" alt="Profile"
                onerror="this.src='../assets/images/default-avatar.png'" />
            <div class="digital-id-badge" onclick="viewID()" title="View Digital ID">
                <i class="fas fa-id-card"></i>
            </div>
        </div>
        <div>
            <div class="name"><?php echo htmlspecialchars(strtoupper($student['full_name'] ?? 'STUDENT')); ?></div>
            <div class="role">ID: <?php echo htmlspecialchars($student['username'] ?? ''); ?></div>
        </div>
    </div>

    <ul class="sidebar-nav">
        <li>
            <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="courses.php" class="<?php echo ($current_page == 'courses.php') ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> <span>My Courses</span>
            </a>
        </li>
        <li>
            <a href="assignments.php" class="<?php echo ($current_page == 'assignments.php') ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i> <span>Assignments</span>
            </a>
        </li>
        <li>
            <a href="attendance.php" class="<?php echo ($current_page == 'attendance.php') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> <span>Attendance</span>
            </a>
        </li>
        <li>
            <a href="documents.php" class="<?php echo ($current_page == 'documents.php') ? 'active' : ''; ?>">
                <i class="fas fa-file-upload"></i> <span>Documents</span>
            </a>
        </li>
        <li>
            <a href="payments.php" class="<?php echo ($current_page == 'payments.php') ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i> <span>Payments</span>
            </a>
        </li>
        <li>
            <a href="profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> <span>Profile</span>
            </a>
        </li>
        <li style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <a href="../index.php">
                <i class="fas fa-home"></i> <span>Home Page</span>
            </a>
        </li>
        <li>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>

<script>
    function viewID() {
        // Logic to view digital ID
        alert('Digital ID Feature Coming Soon');
    }

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('mobile-open');
    }
</script>