<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

$user = getCurrentUser();

// Handle enrollment approval/rejection and payment verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $enrollment_id = intval($_POST['enrollment_id'] ?? 0);
    $payment_id = intval($_POST['payment_id'] ?? 0);
    
    try {
        switch ($action) {
            case 'verify_payment':
                if ($payment_id) {
                    // Verify payment
                    $payment_sql = "UPDATE payments SET status = 'completed', payment_verified_by = ?, payment_verified_at = NOW() WHERE id = ? AND status = 'pending'";
                    $payment_result = updateData($payment_sql, [$user['id'], $payment_id]);
                    
                    if ($payment_result) {
                        // Update enrollment status to enrolled
                        $enrollment_sql = "UPDATE student_enrollments SET status = 'enrolled', updated_at = NOW() WHERE id = ? AND status = 'payment_pending'";
                        $enrollment_result = updateData($enrollment_sql, [$enrollment_id]);
                        
                        if ($enrollment_result) {
                            $success_message = "Payment verified and enrollment approved successfully!";
                        } else {
                            $error_message = "Payment verified but failed to update enrollment status.";
                        }
                    } else {
                        $error_message = "Failed to verify payment or payment already processed.";
                    }
                }
                break;
                
            case 'reject_payment':
                if ($payment_id) {
                    $rejection_reason = $_POST['rejection_reason'] ?? 'Payment rejected by admin';
                    
                    // Reject payment
                    $payment_sql = "UPDATE payments SET status = 'failed', payment_verified_by = ?, payment_verified_at = NOW(), payment_notes = ? WHERE id = ? AND status = 'pending'";
                    $payment_result = updateData($payment_sql, [$user['id'], $rejection_reason, $payment_id]);
                    
                    if ($payment_result) {
                        // Update enrollment status to rejected
                        $enrollment_sql = "UPDATE student_enrollments SET status = 'rejected', updated_at = NOW() WHERE id = ? AND status = 'payment_pending'";
                        $enrollment_result = updateData($enrollment_sql, [$enrollment_id]);
                        
                        if ($enrollment_result) {
                            $success_message = "Payment rejected and enrollment cancelled successfully!";
                        } else {
                            $error_message = "Payment rejected but failed to update enrollment status.";
                        }
                    } else {
                        $error_message = "Failed to reject payment or payment already processed.";
                    }
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get pending enrollments with payment details
$pending_enrollments = getRows("
    SELECT se.*, sc.name as sub_course_name, c.name as course_name, cc.name as category_name,
           u.full_name as student_name, u.email as student_email, u.phone as student_phone,
           p.id as payment_id, p.amount, p.payment_method, p.status as payment_status, 
           p.transaction_id, p.payment_notes, p.payment_date
    FROM student_enrollments se
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    JOIN course_categories cc ON c.category_id = cc.id
    JOIN users u ON se.user_id = u.id
    LEFT JOIN payments p ON se.user_id = p.user_id AND se.sub_course_id = p.sub_course_id
    WHERE se.status = 'payment_pending'
    ORDER BY se.enrollment_date DESC
");

// Get enrolled students
$enrolled_students = getRows("
    SELECT se.*, sc.name as sub_course_name, c.name as course_name, cc.name as category_name,
           u.full_name as student_name, u.email as student_email, u.phone as student_phone,
           p.amount, p.payment_method, p.status as payment_status
    FROM student_enrollments se
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    JOIN course_categories cc ON c.category_id = cc.id
    JOIN users u ON se.user_id = u.id
    LEFT JOIN payments p ON se.user_id = p.user_id AND se.sub_course_id = p.sub_course_id
    WHERE se.status = 'enrolled'
    ORDER BY se.enrollment_date DESC
");

// Get statistics
$stats = getRow("
    SELECT 
        COUNT(CASE WHEN status = 'payment_pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'enrolled' THEN 1 END) as enrolled_count,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count
    FROM student_enrollments
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Approvals - GICT Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 24px;
        }
        .stat-card p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        .stat-card.primary { border-left: 4px solid #007bff; }
        .stat-card.success { border-left: 4px solid #28a745; }
        .stat-card.warning { border-left: 4px solid #ffc107; }
        .stat-card.danger { border-left: 4px solid #dc3545; }
        
        .enrollment-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .enrollment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .enrollment-title {
            font-weight: bold;
            color: #333;
            font-size: 16px;
        }
        .enrollment-date {
            color: #666;
            font-size: 14px;
        }
        .enrollment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .detail-group {
            display: flex;
            flex-direction: column;
        }
        .detail-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .detail-value {
            font-weight: 500;
            color: #333;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .btn-approve {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-reject {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-approve:hover { background: #218838; }
        .btn-reject:hover { background: #c82333; }
        
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
        
        .payment-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .payment-pending {
            background: #fff3cd;
            color: #856404;
        }
        .payment-completed {
            background: #d4edda;
            color: #155724;
        }
    </style>
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
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="enrollment-approvals.php" class="nav-item active">
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
                <h1><i class="fas fa-user-graduate"></i> Enrollment Approvals</h1>
                <p>Review and approve student enrollments</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Section -->
            <div class="stats-grid">
                <div class="stat-card warning">
                    <h3><?php echo $stats['pending_count']; ?></h3>
                    <p>Pending Approvals</p>
                </div>
                <div class="stat-card success">
                    <h3><?php echo $stats['enrolled_count']; ?></h3>
                    <p>Enrolled Students</p>
                </div>
                <div class="stat-card primary">
                    <h3><?php echo $stats['completed_count']; ?></h3>
                    <p>Completed Courses</p>
                </div>
                <div class="stat-card danger">
                    <h3><?php echo $stats['rejected_count']; ?></h3>
                    <p>Rejected Enrollments</p>
                </div>
            </div>

            <!-- Pending Enrollments Section -->
            <div class="approval-section">
                <div class="section-header">
                    <h2><i class="fas fa-credit-card"></i> Payment Verification & Enrollment Approvals</h2>
                    <p>Review payments and approve student enrollments</p>
                </div>
                <div class="section-content">
                    <?php if (empty($pending_enrollments)): ?>
                        <div class="no-pending">
                            <i class="fas fa-check-circle"></i>
                            <h3>No Pending Enrollments</h3>
                            <p>All enrollments have been processed.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pending_enrollments as $enrollment): ?>
                            <div class="enrollment-item">
                                <div class="enrollment-header">
                                    <div class="enrollment-title">
                                        <?php echo htmlspecialchars($enrollment['student_name']); ?> - 
                                        <?php echo htmlspecialchars($enrollment['sub_course_name']); ?>
                                    </div>
                                    <div class="enrollment-date">
                                        <?php echo date('M d, Y H:i', strtotime($enrollment['enrollment_date'])); ?>
                                    </div>
                                </div>
                                <div class="enrollment-details">
                                    <div class="detail-group">
                                        <div class="detail-label">Course</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($enrollment['course_name']); ?></div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label">Student Email</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($enrollment['student_email']); ?></div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label">Student Phone</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($enrollment['student_phone']); ?></div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label">Payment Status</div>
                                        <div class="detail-value">
                                            <span class="payment-status payment-<?php echo $enrollment['payment_status'] ?? 'pending'; ?>">
                                                <?php echo ucfirst($enrollment['payment_status'] ?? 'pending'); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label">Amount</div>
                                        <div class="detail-value">₹<?php echo number_format($enrollment['amount'] ?? 0); ?></div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label">Payment Method</div>
                                        <div class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $enrollment['payment_method'] ?? 'N/A')); ?></div>
                                    </div>
                                    <?php if ($enrollment['transaction_id']): ?>
                                    <div class="detail-group">
                                        <div class="detail-label">Transaction ID</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($enrollment['transaction_id']); ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($enrollment['payment_notes']): ?>
                                    <div class="detail-group">
                                        <div class="detail-label">Payment Notes</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($enrollment['payment_notes']); ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="verify_payment">
                                        <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['id']; ?>">
                                        <input type="hidden" name="payment_id" value="<?php echo $enrollment['payment_id']; ?>">
                                        <button type="submit" class="btn-approve" onclick="return confirm('Are you sure you want to verify this payment and approve enrollment?')">
                                            <i class="fas fa-check"></i> Verify Payment & Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="reject_payment">
                                        <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['id']; ?>">
                                        <input type="hidden" name="payment_id" value="<?php echo $enrollment['payment_id']; ?>">
                                        <input type="hidden" name="rejection_reason" value="Payment rejected by admin">
                                        <button type="submit" class="btn-reject" onclick="return confirm('Are you sure you want to reject this payment?')">
                                            <i class="fas fa-times"></i> Reject Payment
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Enrolled Students Section -->
            <div class="approval-section">
                <div class="section-header">
                    <h2><i class="fas fa-users"></i> Enrolled Students</h2>
                    <p>Students currently enrolled in courses</p>
                </div>
                <div class="section-content">
                    <?php if (empty($enrolled_students)): ?>
                        <div class="no-pending">
                            <i class="fas fa-user-graduate"></i>
                            <h3>No Enrolled Students</h3>
                            <p>No students are currently enrolled in courses.</p>
                        </div>
                    <?php else: ?>
                        <div class="courses-table">
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Course</th>
                                            <th>Sub-Course</th>
                                            <th>Enrollment Date</th>
                                            <th>Status</th>
                                            <th>Payment Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($enrolled_students as $enrollment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($enrollment['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($enrollment['course_name']); ?></td>
                                                <td><?php echo htmlspecialchars($enrollment['sub_course_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $enrollment['status']; ?>">
                                                        <?php echo ucfirst($enrollment['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="payment-status payment-<?php echo $enrollment['payment_status'] ?? 'pending'; ?>">
                                                        <?php echo ucfirst($enrollment['payment_status'] ?? 'pending'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="marks-management.php?enrollment_id=<?php echo $enrollment['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-chart-line"></i> Manage Marks
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
