<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

$user = getCurrentUser();

// Get statistics
$stats = getRow("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE user_type = 'student' AND status = 'active') as total_students,
        (SELECT COUNT(*) FROM student_enrollments WHERE status = 'enrolled') as active_enrollments,
        (SELECT COUNT(*) FROM student_enrollments WHERE status = 'pending') as pending_enrollments,
        (SELECT COUNT(*) FROM student_enrollments WHERE status = 'completed') as completed_courses,
        (SELECT COUNT(*) FROM certificates) as certificates_generated,
        (SELECT COUNT(*) FROM payments WHERE status = 'completed') as total_payments,
        (SELECT COALESCE(SUM(paid_fees), 0) FROM student_enrollments WHERE paid_fees > 0) as total_collections,
        (SELECT COUNT(*) FROM inquiries WHERE status = 'new') as new_inquiries,
        (SELECT COUNT(*) FROM inquiries) as total_inquiries
");

// Get recent enrollments
$recent_enrollments = getRows("
    SELECT se.*, sc.name as sub_course_name, c.name as course_name, u.full_name as student_name
    FROM student_enrollments se
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    JOIN users u ON se.user_id = u.id
    ORDER BY se.created_at DESC
    LIMIT 5
");

// Get recent payments
$recent_payments = getRows("
    SELECT p.*, u.full_name as student_name, sc.name as sub_course_name
    FROM payments p
    JOIN users u ON p.user_id = u.id
    JOIN sub_courses sc ON p.sub_course_id = sc.id
    ORDER BY p.created_at DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GICT Institute</title>
    <link rel="icon" type="image/png" href="../logo.png">
    <link rel="shortcut icon" type="image/png" href="../logo.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img src="../assets/images/logo.png" alt="GICT Logo">
                <span class="brand-title">GICT Institute</span>
            </div>
            
            <div class="profile-card-mini">
                <img src="<?php echo $user['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" alt="Profile">
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                    <div class="user-role">Admin</div>
                </div>
            </div>
            
            <nav class="admin-nav">
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="enrollment-approvals.php" class="nav-item">
                    <i class="fas fa-user-graduate"></i>
                    <span>Enrollment Approvals</span>
                </a>
                <a href="marks-management.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Marks Management</span>
                </a>
                <a href="certificate-management.php" class="nav-item">
                    <i class="fas fa-certificate"></i>
                    <span>Certificate Management</span>
                </a>
                <a href="students.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Students</span>
                </a>
                <a href="courses.php" class="nav-item">
                    <i class="fas fa-book"></i>
                    <span>Courses</span>
                </a>
                <a href="payments.php" class="nav-item">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
                <a href="inquiries.php" class="nav-item">
                    <i class="fas fa-question-circle"></i>
                    <span>Course Inquiries</span>
                </a>
                <a href="../PRD_Visualization_Dashboard.html" target="_blank" class="nav-item">
                    <i class="fas fa-project-diagram"></i>
                    <span>System Overview</span>
                </a>
                <a href="profile.php" class="nav-item">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="../logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="page-header">
                <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>! Here's an overview of your institute.</p>
                <div style="margin-top: 15px;">
                    <a href="../PRD_Visualization_Dashboard.html" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 500; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);">
                        <i class="fas fa-project-diagram"></i>
                        <span>View System Architecture & Requirements</span>
                        <i class="fas fa-external-link-alt" style="font-size: 0.85em; opacity: 0.8;"></i>
                    </a>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_students']; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['active_enrollments']; ?></h3>
                        <p>Active Enrollments</p>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['pending_enrollments']; ?></h3>
                        <p>Pending Approvals</p>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['completed_courses']; ?></h3>
                        <p>Completed Courses</p>
                    </div>
                </div>
                
                <div class="stat-card secondary">
                    <div class="stat-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['certificates_generated']; ?></h3>
                        <p>Certificates Generated</p>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stat-content">
                        <h3>₹<?php echo number_format($stats['total_collections'] ?? 0, 0); ?></h3>
                        <p>Total Collections</p>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['new_inquiries']; ?></h3>
                        <p>New Inquiries</p>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_inquiries']; ?></h3>
                        <p>Total Inquiries</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="action-card" onclick="window.location.href='enrollment-approvals.php'">
                    <i class="fas fa-user-graduate"></i>
                    <h3>Enrollment Approvals</h3>
                    <p>Review and approve student enrollments</p>
                </div>
                <div class="action-card" onclick="window.location.href='marks-management.php'">
                    <i class="fas fa-chart-line"></i>
                    <h3>Marks Management</h3>
                    <p>Enter and manage student marks</p>
                </div>
                <div class="action-card" onclick="window.location.href='certificate-management.php'">
                    <i class="fas fa-certificate"></i>
                    <h3>Certificate Management</h3>
                    <p>Generate certificates for completed courses</p>
                </div>
                <div class="action-card" onclick="window.location.href='students.php'">
                    <i class="fas fa-users"></i>
                    <h3>Student Management</h3>
                    <p>Manage student accounts and information</p>
                </div>
                <div class="action-card" onclick="window.location.href='inquiries.php'">
                    <i class="fas fa-question-circle"></i>
                    <h3>Course Inquiries</h3>
                    <p>View and manage student course inquiries</p>
                </div>
                <div class="action-card" onclick="window.open('../PRD_Visualization_Dashboard.html', '_blank')" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                    <i class="fas fa-project-diagram"></i>
                    <h3 style="color: white;">System Overview</h3>
                    <p style="color: rgba(255,255,255,0.9);">Explore comprehensive system architecture, requirements, and feature documentation</p>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="dashboard-sections">
                <div class="panel">
                    <div class="panel-header">
                        <h2><i class="fas fa-user-graduate"></i> Recent Enrollments</h2>
                        <a href="enrollment-approvals.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($recent_enrollments)): ?>
                            <div class="no-data">
                                <i class="fas fa-user-graduate"></i>
                                <h3>No Recent Enrollments</h3>
                                <p>No student enrollments found.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Course</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_enrollments as $enrollment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($enrollment['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($enrollment['sub_course_name']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $enrollment['status']; ?>">
                                                        <?php echo ucfirst($enrollment['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <h2><i class="fas fa-credit-card"></i> Recent Payments</h2>
                        <a href="payments.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($recent_payments)): ?>
                            <div class="no-data">
                                <i class="fas fa-credit-card"></i>
                                <h3>No Recent Payments</h3>
                                <p>No payment records found.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Course</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_payments as $payment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['sub_course_name']); ?></td>
                                                <td>₹<?php echo number_format($payment['amount']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $payment['status']; ?>">
                                                        <?php echo ucfirst($payment['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .stat-card.primary .stat-icon { background: #007bff; }
        .stat-card.success .stat-icon { background: #28a745; }
        .stat-card.warning .stat-icon { background: #ffc107; }
        .stat-card.info .stat-icon { background: #17a2b8; }
        .stat-card.secondary .stat-icon { background: #6c757d; }
        
        .stat-content h3 {
            margin: 0 0 5px 0;
            font-size: 24px;
            color: #333;
        }
        
        .stat-content p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .action-card:hover {
            transform: translateY(-2px);
            border-color: #007bff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .action-card i {
            font-size: 32px;
            color: #007bff;
            margin-bottom: 15px;
        }
        
        .action-card h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 18px;
        }
        
        .action-card p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .dashboard-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-enrolled {
            background: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .dashboard-sections {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
