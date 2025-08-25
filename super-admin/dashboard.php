<?php
require_once '../includes/session_manager.php';

// Check if user is super admin
if (!isSuperAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Get statistics
$total_institutes = getRow("SELECT COUNT(*) as count FROM institutes WHERE status = 'active'")['count'];
$total_students = getRow("SELECT COUNT(*) as count FROM users WHERE user_type = 'student' AND status = 'active'")['count'];
$total_admins = getRow("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin' AND status = 'active'")['count'];
$total_courses = getRow("SELECT COUNT(*) as count FROM courses WHERE status = 'active'")['count'];

// Get recent institutes
$recent_institutes = getRows("
    SELECT * FROM institutes 
    WHERE status = 'active' 
    ORDER BY created_at DESC 
    LIMIT 5
");

// Get institute statistics
$institute_stats = getRows("
    SELECT 
        i.name as institute_name,
        COUNT(DISTINCT u.id) as total_users,
        COUNT(DISTINCT CASE WHEN u.user_type = 'student' THEN u.id END) as students,
        COUNT(DISTINCT CASE WHEN u.user_type = 'admin' THEN u.id END) as admins,
        COUNT(DISTINCT c.id) as courses
    FROM institutes i
    LEFT JOIN users u ON i.id = u.institute_id AND u.status = 'active'
    LEFT JOIN courses c ON i.id = c.institute_id AND c.status = 'active'
    WHERE i.status = 'active'
    GROUP BY i.id, i.name
    ORDER BY total_users DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - GICT Institute</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Super Admin specific overrides */
        .admin-sidebar {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
        }
        
        .admin-topbar {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #1e3a8a;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-weight: 600;
        }
        
        .institute-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .institute-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .institute-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .institute-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            padding: 1.5rem;
        }
        
        .institute-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .institute-meta {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .institute-body {
            padding: 1.5rem;
        }
        
        .institute-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .institute-stat {
            text-align: center;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .institute-stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e3a8a;
        }
        
        .institute-stat-label {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .institute-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #1e3a8a;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1e40af;
        }
        
        .btn-success {
            background: #059669;
            color: white;
        }
        
        .btn-success:hover {
            background: #047857;
        }
        
        .btn-warning {
            background: #d97706;
            color: white;
        }
        
        .btn-warning:hover {
            background: #b45309;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-title i {
            color: #1e3a8a;
        }
    </style>
</head>
<body class="admin-dashboard-body">
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img src="../assets/images/logo.png" alt="logo" />
                <div class="brand-title">SUPER ADMIN</div>
            </div>
            
            <div class="profile-card-mini">
                <img src="../assets/images/default-avatar.png" alt="Profile" />
                <div>
                    <div class="name"><?php echo htmlspecialchars(strtoupper($_SESSION['full_name'])); ?></div>
                    <div class="role">Super Administrator</div>
                </div>
            </div>
            
            <ul class="sidebar-nav">
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="institutes.php"><i class="fas fa-building"></i> Institutes</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> All Users</a></li>
                <li><a href="courses.php"><i class="fas fa-book"></i> All Courses</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="breadcrumbs">
                    <span>Super Admin Dashboard</span>
                </div>
            </div>
            <div class="topbar-right">
                <div class="user-chip">
                    <img src="../assets/images/default-avatar.png" alt="Profile" />
                    <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-content">
            <!-- Welcome Section -->
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-crown"></i> Welcome, Super Administrator</span>
                </div>
                <div class="panel-body">
                    <p>Manage all GICT Institute franchises from this central dashboard. You have complete control over all institutes, users, and courses across the network.</p>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_institutes; ?></div>
                    <div class="stat-label">Active Institutes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_admins; ?></div>
                    <div class="stat-label">Institute Admins</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_courses; ?></div>
                    <div class="stat-label">Total Courses</div>
                </div>
            </div>
            
            <!-- Institute Overview -->
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-building"></i> Institute Overview</span>
                    <a href="institutes.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add Institute
                    </a>
                </div>
                <div class="panel-body">
                    <div class="institute-grid">
                        <?php foreach ($institute_stats as $institute): ?>
                            <div class="institute-card">
                                <div class="institute-header">
                                    <div class="institute-name"><?php echo htmlspecialchars($institute['institute_name']); ?></div>
                                    <div class="institute-meta">
                                        <i class="fas fa-map-marker-alt"></i> Institute Branch
                                    </div>
                                </div>
                                <div class="institute-body">
                                    <div class="institute-stats">
                                        <div class="institute-stat">
                                            <div class="institute-stat-number"><?php echo $institute['students']; ?></div>
                                            <div class="institute-stat-label">Students</div>
                                        </div>
                                        <div class="institute-stat">
                                            <div class="institute-stat-number"><?php echo $institute['admins']; ?></div>
                                            <div class="institute-stat-label">Admins</div>
                                        </div>
                                        <div class="institute-stat">
                                            <div class="institute-stat-number"><?php echo $institute['courses']; ?></div>
                                            <div class="institute-stat-label">Courses</div>
                                        </div>
                                        <div class="institute-stat">
                                            <div class="institute-stat-number"><?php echo $institute['total_users']; ?></div>
                                            <div class="institute-stat-label">Total Users</div>
                                        </div>
                                    </div>
                                    <div class="institute-actions">
                                        <a href="institute-details.php?id=<?php echo $institute['institute_id'] ?? ''; ?>" class="btn btn-primary">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                        <a href="manage-institute.php?id=<?php echo $institute['institute_id'] ?? ''; ?>" class="btn btn-success">
                                            <i class="fas fa-cog"></i> Manage
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-clock"></i> Recent Institutes</span>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Institute Name</th>
                                    <th>Location</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_institutes as $institute): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($institute['name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($institute['address']); ?></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($institute['phone']); ?></div>
                                            <div class="text-muted"><?php echo htmlspecialchars($institute['email']); ?></div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $institute['status']; ?>">
                                                <?php echo ucfirst($institute['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($institute['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="edit-institute.php?id=<?php echo $institute['id']; ?>" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="institute-details.php?id=<?php echo $institute['id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('mobile-open');
        }
    </script>
</body>
</html>
