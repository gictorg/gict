<?php
// Get current page for active tab highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-title">STUDENT PORTAL</div>
    </div>
    <div class="sidebar-header">
        <div class="user-info">
            <div class="user-avatar">
                <img src="<?php echo $student['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" alt="Student Photo" onerror="this.src='../assets/images/default-avatar.png'">
                <div class="digital-id-badge" onclick="viewID()" title="View Digital ID">
                    <i class="fas fa-id-card"></i>
                </div>
            </div>
            <div class="user-details">
                <h6><?php echo htmlspecialchars($student['full_name']); ?></h6>
                <span class="user-role">Student</span>
                <div class="digital-id-link">
                    <a href="#" onclick="viewID(); return false;" class="digital-id-btn">
                        <i class="fas fa-id-card"></i> Digital ID
                    </a>
                </div>
            </div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'courses') ? 'active' : ''; ?>">
                <a href="courses.php" class="nav-link">
                    <i class="fas fa-book"></i>
                    <span>My Courses</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'documents') ? 'active' : ''; ?>">
                <a href="documents.php" class="nav-link">
                    <i class="fas fa-file-upload"></i>
                    <span>Documents</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'payments') ? 'active' : ''; ?>">
                <a href="payments.php" class="nav-link">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'profile') ? 'active' : ''; ?>">
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="../index.php" class="nav-link">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>
