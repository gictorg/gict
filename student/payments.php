<?php
require_once '../includes/session_manager.php';
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get student information
$student = getRow("SELECT * FROM users WHERE id = ? AND user_type = 'student'", [$user_id]);
if (!$student) {
    header('Location: ../login.php');
    exit;
}

// Get payment history
$payments = getRows("
    SELECT sp.*, c.name as course_name
    FROM student_payments sp
    JOIN courses c ON sp.course_id = c.id
    WHERE sp.user_id = ?
    ORDER BY sp.payment_date DESC
", [$user_id]);

// Get pending payments
$pending_payments = getRows("
    SELECT sp.*, c.name as course_name, c.fee as course_fee
    FROM student_payments sp
    JOIN courses c ON sp.course_id = c.id
    WHERE sp.user_id = ? AND sp.status = 'pending'
    ORDER BY sp.payment_date DESC
", [$user_id]);

$total_paid = array_sum(array_column(array_filter($payments, fn($p) => $p['status'] === 'completed'), 'amount'));
$total_pending = array_sum(array_column($pending_payments, 'amount'));
$total_courses = count(array_unique(array_column($payments, 'course_id')));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Student Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Student-specific overrides to match admin dashboard exactly */
        .admin-sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .admin-topbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        /* Digital ID Badge */
        .digital-id-badge {
            position: absolute;
            bottom: -5px;
            right: -5px;
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: 2px solid #fff;
        }
        
        .digital-id-badge:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .digital-id-badge i {
            color: white;
            font-size: 14px;
        }
        
        .profile-card-mini {
            position: relative;
        }
        
        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        /* Payment cards */
        .payment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .payment-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
        }
        
        .payment-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .payment-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        
        .payment-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .payment-details {
            margin-bottom: 1rem;
        }
        
        .payment-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .payment-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-success {
            background: #16a34a;
            color: white;
        }
        
        .btn-success:hover {
            background: #15803d;
        }
        
        .btn-info {
            background: #0891b2;
            color: white;
        }
        
        .btn-info:hover {
            background: #0e7490;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }
        
        .text-muted {
            color: #6b7280;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .amount {
            font-weight: 600;
        }
        
        .amount.completed {
            color: #16a34a;
        }
        
        .amount.pending {
            color: #f59e0b;
        }
        
        .amount.failed {
            color: #dc2626;
        }
    </style>
</head>
<body class="admin-dashboard-body">
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img src="../assets/images/logo.png" alt="logo" />
                <div class="brand-title">STUDENT PORTAL</div>
            </div>
            
            <div class="profile-card-mini">
                <div style="position: relative;">
                    <img src="<?php echo $student['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" alt="Profile" onerror="this.src='../assets/images/default-avatar.png'" />
                    <div class="digital-id-badge" onclick="viewID()" title="View Digital ID">
                        <i class="fas fa-id-card"></i>
                    </div>
                </div>
                <div>
                    <div class="name"><?php echo htmlspecialchars(strtoupper($student['full_name'])); ?></div>
                    <div class="role">Student</div>
                </div>
            </div>
            
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="courses.php"><i class="fas fa-book"></i> My Courses</a></li>
                <li><a href="documents.php"><i class="fas fa-file-upload"></i> Documents</a></li>
                <li><a href="payments.php" class="active"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
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
                    <span>Payment Management</span>
                </div>
            </div>
            <div class="topbar-right">
                <div class="user-chip">
                    <img src="<?php echo $student['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" alt="Profile" onerror="this.src='../assets/images/default-avatar.png'" />
                    <span><?php echo htmlspecialchars($student['full_name']); ?></span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-content">
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($total_paid, 2); ?></div>
                    <div class="stat-label">Total Paid</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($total_pending, 2); ?></div>
                    <div class="stat-label">Pending Amount</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_courses; ?></div>
                    <div class="stat-label">Courses Enrolled</div>
                </div>
            </div>
            
            <!-- Pending Payments -->
            <?php if (!empty($pending_payments)): ?>
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-clock"></i> Pending Payments</span>
                </div>
                <div class="panel-body">
                    <div class="payment-grid">
                        <?php foreach ($pending_payments as $payment): ?>
                            <div class="payment-card">
                                <div class="payment-header">
                                    <h6 class="payment-title"><?php echo htmlspecialchars($payment['course_name']); ?></h6>
                                    <span class="payment-status status-pending">Pending</span>
                                </div>
                                <div class="payment-details">
                                    <div class="payment-detail">
                                        <i class="fas fa-dollar-sign"></i>
                                        <span>Amount: $<?php echo number_format($payment['amount'], 2); ?></span>
                                    </div>
                                    <div class="payment-detail">
                                        <i class="fas fa-calendar"></i>
                                        <span>Due: <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></span>
                                    </div>
                                </div>
                                <div class="payment-actions">
                                    <button class="btn btn-primary btn-sm">
                                        <i class="fas fa-credit-card"></i> Pay Now
                                    </button>
                                    <button class="btn btn-info btn-sm">
                                        <i class="fas fa-info-circle"></i> View Details
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Payment History -->
            <div class="panel" style="margin-top: 1.5rem;">
                <div class="panel-header">
                    <span><i class="fas fa-history"></i> Payment History</span>
                </div>
                <div class="panel-body">
                    <?php if (empty($payments)): ?>
                        <p class="text-muted">No payment history available.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Amount</th>
                                        <th>Payment Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($payment['course_name']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="amount <?php echo $payment['status']; ?>">
                                                    $<?php echo number_format($payment['amount'], 2); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                            <td>
                                                <span class="payment-status status-<?php echo $payment['status']; ?>">
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($payment['status'] === 'completed'): ?>
                                                    <button class="btn btn-success btn-sm">
                                                        <i class="fas fa-download"></i> Download Receipt
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('mobile-open');
        }
    </script>
</body>
</html>
