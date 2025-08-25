<?php
require_once '../includes/session_manager.php';

// Check if user is super admin
if (!isSuperAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Get system-wide statistics
$total_institutes = getRow("SELECT COUNT(*) as count FROM institutes WHERE status = 'active'")['count'];
$total_users = getRow("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'];
$total_students = getRow("SELECT COUNT(*) as count FROM users WHERE user_type = 'student' AND status = 'active'")['count'];
$total_admins = getRow("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin' AND status = 'active'")['count'];
$total_faculty = getRow("SELECT COUNT(*) as count FROM users WHERE user_type = 'faculty' AND status = 'active'")['count'];
$total_courses = getRow("SELECT COUNT(*) as count FROM courses WHERE status = 'active'")['count'];
$total_sub_courses = getRow("SELECT COUNT(*) as count FROM sub_courses WHERE status = 'active'")['count'];
$total_enrollments = getRow("SELECT COUNT(*) as count FROM student_enrollments")['count'];
$total_payments = getRow("SELECT COUNT(*) as count FROM payments")['count'];
$total_revenue = getRow("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'")['total'] ?? 0;

// Get recent activity
$recent_institutes = getRows("SELECT * FROM institutes ORDER BY created_at DESC LIMIT 5");
$recent_users = getRows("
    SELECT u.*, i.name as institute_name 
    FROM users u 
    LEFT JOIN institutes i ON u.institute_id = i.id 
    ORDER BY u.created_at DESC 
    LIMIT 10
");
$recent_enrollments = getRows("
    SELECT se.*, u.full_name as student_name, sc.name as sub_course_name, i.name as institute_name
    FROM student_enrollments se
    JOIN users u ON se.user_id = u.id
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    JOIN institutes i ON c.institute_id = i.id
    ORDER BY se.created_at DESC
    LIMIT 10
");

// Get institute-wise statistics
$institute_stats = getRows("
    SELECT 
        i.name as institute_name,
        i.slug as institute_slug,
        COUNT(DISTINCT u.id) as total_users,
        COUNT(DISTINCT CASE WHEN u.user_type = 'student' THEN u.id END) as students,
        COUNT(DISTINCT CASE WHEN u.user_type = 'admin' THEN u.id END) as admins,
        COUNT(DISTINCT c.id) as courses,
        COUNT(DISTINCT sc.id) as sub_courses,
        COUNT(DISTINCT se.id) as enrollments,
        SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END) as revenue
    FROM institutes i
    LEFT JOIN users u ON i.id = u.institute_id AND u.status = 'active'
    LEFT JOIN courses c ON i.id = c.institute_id AND c.status = 'active'
    LEFT JOIN sub_courses sc ON c.id = sc.course_id AND sc.status = 'active'
    LEFT JOIN student_enrollments se ON sc.id = se.sub_course_id
    LEFT JOIN payments p ON se.id = p.enrollment_id
    GROUP BY i.id, i.name, i.slug
    ORDER BY revenue DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Super Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
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
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .revenue-card {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
        }
        
        .revenue-card .stat-icon,
        .revenue-card .stat-number,
        .revenue-card .stat-label {
            color: white;
        }
        
        .reports-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .report-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .report-header {
            background: #f8fafc;
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .report-body {
            padding: 1.5rem;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #6b7280;
        }
        
        .activity-content h4 {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: #111827;
        }
        
        .activity-content p {
            margin: 0.25rem 0 0 0;
            font-size: 0.8rem;
            color: #6b7280;
        }
        
        .activity-time {
            font-size: 0.75rem;
            color: #9ca3af;
        }
        
        .institute-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .institute-table th,
        .institute-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .institute-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }
        
        .institute-table td {
            font-size: 0.9rem;
        }
        
        .institute-name {
            font-weight: 600;
            color: #111827;
        }
        
        .institute-slug {
            font-size: 0.8rem;
            color: #6b7280;
        }
        
        .revenue-amount {
            font-weight: 600;
            color: #059669;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .reports-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
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
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="institutes.php"><i class="fas fa-building"></i> Institutes</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> All Users</a></li>
                <li><a href="courses.php"><i class="fas fa-book"></i> All Courses</a></li>
                <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a></li>
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
                    <span>System Reports</span>
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
            <!-- Statistics Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🏢</div>
                    <div class="stat-number"><?php echo $total_institutes; ?></div>
                    <div class="stat-label">Active Institutes</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🎓</div>
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="stat-label">Students</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👨‍💼</div>
                    <div class="stat-number"><?php echo $total_admins; ?></div>
                    <div class="stat-label">Admins</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👨‍🏫</div>
                    <div class="stat-number"><?php echo $total_faculty; ?></div>
                    <div class="stat-label">Faculty</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-number"><?php echo $total_courses; ?></div>
                    <div class="stat-label">Main Courses</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📖</div>
                    <div class="stat-number"><?php echo $total_sub_courses; ?></div>
                    <div class="stat-label">Sub Courses</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-number"><?php echo $total_enrollments; ?></div>
                    <div class="stat-label">Enrollments</div>
                </div>
                
                <div class="stat-card revenue-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-number">₹<?php echo number_format($total_revenue); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            
            <!-- Reports Grid -->
            <div class="reports-grid">
                <!-- Recent Activity -->
                <div class="report-card">
                    <div class="report-header">
                        <i class="fas fa-clock"></i>
                        Recent Activity
                    </div>
                    <div class="report-body">
                        <?php if (empty($recent_users) && empty($recent_enrollments)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No recent activity</p>
                            </div>
                        <?php else: ?>
                            <?php 
                            $activities = [];
                            foreach ($recent_users as $user) {
                                $activities[] = [
                                    'type' => 'user',
                                    'data' => $user,
                                    'time' => $user['created_at']
                                ];
                            }
                            foreach ($recent_enrollments as $enrollment) {
                                $activities[] = [
                                    'type' => 'enrollment',
                                    'data' => $enrollment,
                                    'time' => $enrollment['created_at']
                                ];
                            }
                            
                            // Sort by time
                            usort($activities, function($a, $b) {
                                return strtotime($b['time']) - strtotime($a['time']);
                            });
                            
                            $activities = array_slice($activities, 0, 10);
                            ?>
                            
                            <?php foreach ($activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-avatar">
                                        <?php if ($activity['type'] === 'user'): ?>
                                            <i class="fas fa-user"></i>
                                        <?php else: ?>
                                            <i class="fas fa-graduation-cap"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-content">
                                        <?php if ($activity['type'] === 'user'): ?>
                                            <h4><?php echo htmlspecialchars($activity['data']['full_name']); ?></h4>
                                            <p>Joined <?php echo htmlspecialchars($activity['data']['institute_name'] ?? 'System'); ?></p>
                                        <?php else: ?>
                                            <h4><?php echo htmlspecialchars($activity['data']['student_name']); ?></h4>
                                            <p>Enrolled in <?php echo htmlspecialchars($activity['data']['sub_course_name']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('M d, H:i', strtotime($activity['time'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Institute Performance -->
                <div class="report-card">
                    <div class="report-header">
                        <i class="fas fa-chart-line"></i>
                        Institute Performance
                    </div>
                    <div class="report-body">
                        <?php if (empty($institute_stats)): ?>
                            <div class="empty-state">
                                <i class="fas fa-chart-bar"></i>
                                <p>No institute data available</p>
                            </div>
                        <?php else: ?>
                            <table class="institute-table">
                                <thead>
                                    <tr>
                                        <th>Institute</th>
                                        <th>Users</th>
                                        <th>Courses</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($institute_stats as $institute): ?>
                                        <tr>
                                            <td>
                                                <div class="institute-name"><?php echo htmlspecialchars($institute['institute_name']); ?></div>
                                                <div class="institute-slug"><?php echo htmlspecialchars($institute['institute_slug']); ?></div>
                                            </td>
                                            <td><?php echo $institute['total_users']; ?></td>
                                            <td><?php echo $institute['courses']; ?></td>
                                            <td class="revenue-amount">₹<?php echo number_format($institute['revenue']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-bolt"></i> Quick Actions</span>
                </div>
                <div class="panel-body">
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <a href="institutes.php" class="btn btn-primary">
                            <i class="fas fa-building"></i> Manage Institutes
                        </a>
                        <a href="users.php" class="btn btn-success">
                            <i class="fas fa-users"></i> View All Users
                        </a>
                        <a href="courses.php" class="btn btn-warning">
                            <i class="fas fa-book"></i> Manage Courses
                        </a>
                        <a href="../index.php" class="btn btn-info">
                            <i class="fas fa-home"></i> Go to Homepage
                        </a>
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
