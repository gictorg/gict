<header class="admin-topbar">
    <div class="topbar-left">
        <button class="menu-toggle"><i class="fas fa-bars"></i></button>
        <div class="breadcrumbs">
            <a href="../index.php" class="topbar-home-link"><i class="fas fa-home"></i> Home</a>
            <span style="opacity:.7; margin: 0 6px;">/</span>
            <span><?php echo $page_title ?? 'Student Dashboard'; ?></span>
        </div>
    </div>
    <div class="topbar-right">
        <div class="user-chip">
            <?php if (!empty($student['profile_image']) && filter_var($student['profile_image'], FILTER_VALIDATE_URL)): ?>
                <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile" />
            <?php else: ?>
                <div class="profile-placeholder">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
            <?php echo htmlspecialchars($student['full_name']); ?>
        </div>
    </div>
</header>
