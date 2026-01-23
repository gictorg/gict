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

<!-- Digital ID Card Modal -->
<div id="idCardModal" class="id-modal">
    <div class="id-card-container">
        <div class="id-card-header-actions" style="position: absolute; right: 20px; top: 20px; z-index: 10;">
            <button onclick="closeID()"
                style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;"><i
                    class="fas fa-times"></i></button>
        </div>
        <div id="idCardContent" class="id-card-inner-body">
            <!-- ID Card will be loaded here from id.php -->
            <div style="text-align: center; padding: 50px;">
                <i class="fas fa-spinner fa-spin fa-2x" style="color: #0f6fb1;"></i>
                <p style="margin-top: 15px; color: #64748b;">Loading ID Card...</p>
            </div>
        </div>
    </div>
</div>

<style>
    .id-modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(10px);
        z-index: 5000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .id-card-container {
        width: 100%;
        max-width: 400px;
        background: white;
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.6);
        position: relative;
        animation: modalScaleUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes modalScaleUp {
        from {
            opacity: 0;
            transform: scale(0.9);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .id-card-inner-body {
        max-height: 80vh;
        overflow-y: auto;
    }
</style>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) sidebar.classList.toggle('mobile-open');
    }

    function viewID() {
        const modal = document.getElementById('idCardModal');
        const content = document.getElementById('idCardContent');
        modal.style.display = 'flex';

        // Use Fetch to get the content from id.php
        const formData = new FormData();
        formData.append('student_id', '<?php echo $student['id']; ?>');

        // Hide the scroll of body
        document.body.style.overflow = 'hidden';

        fetch('../id.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const idCard = doc.querySelector('#idCard');
                if (idCard) {
                    // Adjust styles for modal view
                    idCard.style.margin = '0 auto';
                    idCard.style.boxShadow = 'none';
                    idCard.style.border = 'none';
                    content.innerHTML = '<div style="padding: 20px;">' + idCard.outerHTML + '</div>';
                } else {
                    content.innerHTML = '<div style="padding: 50px; text-align : center; color: #ef4444;"><i class="fas fa-exclamation-circle fa-2x"></i><p>Error loading ID card content.</p></div>';
                }
            })
            .catch(err => {
                console.error(err);
                content.innerHTML = '<div style="padding: 50px; text-align : center; color: #ef4444;"><i class="fas fa-wifi fa-2x"></i><p>Network error.</p></div>';
            });
    }

    function closeID() {
        document.getElementById('idCardModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    // Close modal on outside click
    window.onclick = function (event) {
        const modal = document.getElementById('idCardModal');
        if (event.target == modal) {
            closeID();
        }
    }
</script>