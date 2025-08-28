<header class="admin-topbar">
    <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h2 class="page-title"><?php echo ucfirst($current_page ?? 'Dashboard'); ?></h2>
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
