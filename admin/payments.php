<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

// Initialize session for flash messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get flash messages from session and clear them
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$user = getCurrentUser();

// Get recent completed payments
$completed_payments = getRows("
    SELECT p.*, u.full_name as student_name, sc.name as sub_course_name, c.name as course_name
    FROM payments p
    JOIN users u ON p.user_id = u.id
    JOIN sub_courses sc ON p.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    WHERE p.status = 'completed'
    ORDER BY p.updated_at DESC
    LIMIT 20
");

// Calculate accurate statistics
$stats = getRow("
    SELECT 
        COALESCE(SUM(remaining_fees), 0) as total_outstanding,
        (SELECT COUNT(*) FROM student_enrollments WHERE remaining_fees > 0) as outstanding_count,
        (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed') as total_collected,
        (SELECT COUNT(*) FROM payments WHERE status = 'completed') as total_completed_count
    FROM student_enrollments
");

$total_pending = $stats['total_outstanding'];
$total_pending_count = $stats['outstanding_count'];
$total_completed = $stats['total_collected'];
$total_completed_count = $stats['total_completed_count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - GICT Admin</title>
    <link rel="icon" type="image/png" href="../logo.png">
    <link rel="shortcut icon" type="image/png" href="../logo.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card.pending {
            border-left: 4px solid #ffc107;
        }

        .stat-card.completed {
            border-left: 4px solid #28a745;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .payment-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
        }

        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .payment-amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
        }

        .payment-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .payment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-item i {
            color: #667eea;
            width: 20px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
        }

        .modal-header {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
        }

        /* Table Styling */
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .table th {
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }

        .table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: #f8fafc;
        }

        .table tbody tr:last-child {
            border-bottom: none;
        }

        .table td {
            padding: 16px 12px;
            vertical-align: middle;
            color: #374151;
            font-size: 14px;
        }

        .table td:first-child {
            font-weight: 600;
            color: #1f2937;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .text-muted {
            color: #6b7280;
            font-style: italic;
            text-align: center;
            padding: 2rem;
        }

        border-bottom: 1px solid #e9ecef;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>

<body class="admin-dashboard-body">
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img src="../assets/images/logo.png" alt="logo" />
                <div class="brand-title">GICT CONTROL</div>
            </div>

            <div class="profile-card-mini">
                <img src="<?php echo $user['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" alt="Profile"
                    onerror="this.src='../assets/images/default-avatar.png'" />
                <div>
                    <div class="name"><?php echo htmlspecialchars(strtoupper($user['full_name'])); ?></div>
                    <div class="role"><?php echo htmlspecialchars(ucfirst($user['type'])); ?></div>
                </div>
            </div>

            <ul class="sidebar-nav">
                <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-user-tie"></i> Staff</a></li>
                <li><a href="courses.php"><i class="fas fa-graduation-cap"></i> Courses</a></li>
                <li><a href="marks-management.php"><i class="fas fa-chart-line"></i> Marks Management</a></li>
                <li><a href="certificate-management.php"><i class="fas fa-certificate"></i> Certificate Management</a>
                </li>
                <li><a class="active" href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="inquiries.php"><i class="fas fa-question-circle"></i> Course Inquiries</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="menu-toggle"><i class="fas fa-bars"></i></button>
                <div class="breadcrumbs">
                    <a href="../index.php" class="topbar-home-link"><i class="fas fa-home"></i> Home</a>
                    <span style="opacity:.7; margin: 0 6px;">/</span>
                    <a href="../dashboard.php">Dashboard</a>
                    <span style="opacity:.7; margin: 0 6px;">/</span>
                    <span>Payments</span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="page-header">
                <h1><i class="fas fa-credit-card"></i> Payment Management</h1>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" id="successAlert"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" id="errorAlert"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <!-- Payment Statistics -->
            <div class="payment-stats">
                <div class="stat-card pending">
                    <div class="stat-value">₹<?php echo number_format($total_pending, 2); ?></div>
                    <div class="stat-label">Outstanding Fees</div>
                    <div class="stat-count"><?php echo $total_pending_count; ?> students</div>
                </div>
                <div class="stat-card completed">
                    <div class="stat-value">₹<?php echo number_format($total_completed, 2); ?></div>
                    <div class="stat-label">Total Collected</div>
                    <div class="stat-count"><?php echo $total_completed_count; ?> payments</div>
                </div>
            </div>



            <!-- Recent Completed Payments -->
            <div class="panel" style="margin-top: 2rem;">
                <div class="panel-header">
                    <span><i class="fas fa-check-circle"></i> Recent Completed Payments</span>
                </div>
                <div class="panel-body">
                    <?php if (empty($completed_payments)): ?>
                        <p class="text-muted">No completed payments to display.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th>Amount</th>
                                        <th>Payment Date</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completed_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['course_name']); ?> -
                                                <?php echo htmlspecialchars($payment['sub_course_name']); ?>
                                            </td>
                                            <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'Not specified'); ?>
                                            </td>
                                            <td><span class="badge badge-success">Completed</span></td>
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



    <script src="../assets/js/mobile-menu.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('mobile-open');
        }

        // Auto-dismiss alerts after 5 seconds
        const successAlert = document.getElementById('successAlert');
        const errorAlert = document.getElementById('errorAlert');

        if (successAlert) {
            setTimeout(function () {
                successAlert.style.transition = 'opacity 0.5s ease-out';
                successAlert.style.opacity = '0';
                setTimeout(function () {
                    successAlert.remove();
                }, 500);
            }, 5000);
        }

        if (errorAlert) {
            setTimeout(function () {
                errorAlert.style.transition = 'opacity 0.5s ease-out';
                errorAlert.style.opacity = '0';
                setTimeout(function () {
                    errorAlert.remove();
                }, 500);
            }, 5000);
        }
    </script>
</body>

</html>