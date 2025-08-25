<header class="admin-topbar">
    <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h2 class="page-title"><?php echo ucfirst($current_page ?? 'Dashboard'); ?></h2>
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
            <span><?php echo htmlspecialchars($student['full_name']); ?></span>
        </div>
    </div>
</header>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const adminContent = document.querySelector('.admin-content');
    
    if (sidebar.classList.contains('collapsed')) {
        sidebar.classList.remove('collapsed');
        adminContent.style.marginLeft = '280px';
    } else {
        sidebar.classList.add('collapsed');
        adminContent.style.marginLeft = '0';
    }
}
</script>
