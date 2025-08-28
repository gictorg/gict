<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

$user = getCurrentUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve_payment':
                $payment_id = $_POST['payment_id'] ?? 0;
                if ($payment_id) {
                    try {
                        $sql = "UPDATE payments SET status = 'completed', updated_at = NOW() WHERE id = ? AND status = 'pending'";
                        $result = updateData($sql, [$payment_id]);
                        if ($result) {
                            $success_message = "Payment approved successfully!";
                        } else {
                            $error_message = "Failed to approve payment or payment already processed.";
                        }
                    } catch (Exception $e) {
                        $error_message = "Error: " . $e->getMessage();
                    }
                }
                break;
                
            case 'reject_payment':
                $payment_id = $_POST['payment_id'] ?? 0;
                $rejection_reason = $_POST['rejection_reason'] ?? 'Payment rejected by admin';
                if ($payment_id) {
                    try {
                        $sql = "UPDATE payments SET status = 'failed', updated_at = NOW() WHERE id = ? AND status = 'pending'";
                        $result = updateData($sql, [$payment_id]);
                        if ($result) {
                            $success_message = "Payment rejected successfully!";
                        } else {
                            $error_message = "Failed to reject payment or payment already processed.";
                        }
                    } catch (Exception $e) {
                        $error_message = "Error: " . $e->getMessage();
                    }
                }
                break;
                
            case 'approve_enrollment':
                $enrollment_id = $_POST['enrollment_id'] ?? 0;
                if ($enrollment_id) {
                    try {
                        $sql = "UPDATE student_enrollments SET status = 'enrolled', updated_at = NOW() WHERE id = ? AND status = 'pending'";
                        $result = updateData($sql, [$enrollment_id]);
                        if ($result) {
                            $success_message = "Enrollment approved successfully!";
                        } else {
                            $error_message = "Failed to approve enrollment or enrollment already processed.";
                        }
                    } catch (Exception $e) {
                        $error_message = "Error: " . $e->getMessage();
                    }
                }
                break;
                
            case 'reject_enrollment':
                $enrollment_id = $_POST['enrollment_id'] ?? 0;
                $rejection_reason = $_POST['rejection_reason'] ?? 'Enrollment rejected by admin';
                if ($enrollment_id) {
                    try {
                        $sql = "UPDATE student_enrollments SET status = 'dropped', updated_at = NOW() WHERE id = ? AND status = 'pending'";
                        $result = updateData($sql, [$enrollment_id]);
                        if ($result) {
                            $success_message = "Enrollment rejected successfully!";
                        } else {
                            $error_message = "Failed to reject enrollment or enrollment already processed.";
                        }
                    } catch (Exception $e) {
                        $error_message = "Error: " . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get pending payments
$pending_payments = [];
try {
    $sql = "SELECT 
                p.id, p.amount, p.payment_date, p.payment_method, p.transaction_id, p.status, p.created_at,
                u.username, u.full_name, u.email, u.phone,
                sc.name as course_name, c.name as main_course
            FROM payments p
            JOIN users u ON p.user_id = u.id
            JOIN sub_courses sc ON p.sub_course_id = sc.id
            JOIN courses c ON sc.course_id = c.id
            WHERE p.status = 'pending'
            ORDER BY p.created_at ASC";
    $pending_payments = getRows($sql);
} catch (Exception $e) {
    $error_message = "Error loading pending payments: " . $e->getMessage();
}

// Get pending enrollments
$pending_enrollments = [];
try {
    $sql = "SELECT 
                se.id, se.enrollment_date, se.status, se.created_at,
                u.username, u.full_name, u.email, u.phone,
                sc.name as course_name, sc.fee, c.name as main_course
            FROM student_enrollments se
            JOIN users u ON se.user_id = u.id
            JOIN sub_courses sc ON se.sub_course_id = sc.id
            JOIN courses c ON sc.course_id = c.id
            WHERE se.status = 'pending'
            ORDER BY se.created_at ASC";
    $pending_enrollments = getRows($sql);
} catch (Exception $e) {
    $error_message = "Error loading pending enrollments: " . $e->getMessage();
}

// Get statistics
$stats = [];
try {
    // Total pending payments
    $sql = "SELECT COUNT(*) as count, SUM(amount) as total_amount FROM payments WHERE status = 'pending'";
    $payment_stats = getRow($sql);
    $stats['pending_payments'] = $payment_stats['count'] ?? 0;
    $stats['pending_amount'] = $payment_stats['total_amount'] ?? 0;
    
    // Total pending enrollments
    $sql = "SELECT COUNT(*) as count FROM student_enrollments WHERE status = 'pending'";
    $enrollment_stats = getRow($sql);
    $stats['pending_enrollments'] = $enrollment_stats['count'] ?? 0;
    
} catch (Exception $e) {
    error_log("Error loading statistics: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approvals - GICT Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-content {
            padding: 20px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
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
        
        .approval-section {
            background: white;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .section-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .section-header h2 {
            margin: 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-content {
            padding: 20px;
        }
        .approval-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }
        .approval-item:last-child {
            margin-bottom: 0;
        }
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .item-title {
            font-weight: bold;
            color: #333;
            font-size: 16px;
        }
        .item-date {
            color: #666;
            font-size: 14px;
        }
        .item-details {
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
        
        .no-pending {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .no-pending i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #ddd;
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="page-header">
                <h1><i class="fas fa-clock"></i> Pending Approvals</h1>
                <div class="header-actions">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <h3><?php echo $stats['pending_payments'] ?? 0; ?></h3>
                    <p>Pending Payments</p>
                </div>
                <div class="stat-card warning">
                    <h3>₹<?php echo number_format($stats['pending_amount'] ?? 0, 2); ?></h3>
                    <p>Pending Amount</p>
                </div>
                <div class="stat-card success">
                    <h3><?php echo $stats['pending_enrollments'] ?? 0; ?></h3>
                    <p>Pending Enrollments</p>
                </div>
            </div>
            
            <!-- Pending Payments Section -->
            <div class="approval-section">
                <div class="section-header">
                    <h2><i class="fas fa-credit-card"></i> Pending Payment Approvals</h2>
                </div>
                <div class="section-content">
                    <?php if (empty($pending_payments)): ?>
                        <div class="no-pending">
                            <i class="fas fa-check-circle"></i>
                            <h3>No Pending Payments</h3>
                            <p>All payments have been processed.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pending_payments as $payment): ?>
                            <div class="approval-item">
                                <div class="item-header">
                                    <div class="item-title">
                                        Payment for <?php echo htmlspecialchars($payment['course_name']); ?>
                                    </div>
                                    <div class="item-date">
                                        <?php echo date('M d, Y H:i', strtotime($payment['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="item-details">
                                    <div class="detail-group">
                                        <div class="detail-label">Student</div>
                                        <div class="detail-value">
                                            <?php echo htmlspecialchars($payment['full_name']); ?> 
                                            (<?php echo htmlspecialchars($payment['username']); ?>)
                                        </div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label">Course</div>
                                        <div class="detail-value">
                                            <?php echo htmlspecialchars($payment['main_course']); ?> - 
                                            <?php echo htmlspecialchars($payment['course_name']); ?>
                                        </div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label">Amount</div>
                                        <div class="detail-value">₹<?php echo number_format($payment['amount'], 2); ?></div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label">Payment Method</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($payment['payment_method'] ?? 'Not specified'); ?></div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label">Transaction ID</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($payment['transaction_id'] ?? 'Not provided'); ?></div>
                                    </div>
                                </div>
                                <div class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="approve_payment">
                                        <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                        <button type="submit" class="btn-approve" onclick="return confirm('Approve this payment?')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="reject_payment">
                                        <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                        <button type="submit" class="btn-reject" onclick="return confirm('Reject this payment?')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Pending Enrollments Section -->
            <div class="approval-section">
                <div class="section-header">
                    <h2><i class="fas fa-user-graduate"></i> Pending Enrollment Approvals</h2>
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
                            <div class="approval-item">
                                <div class="item-header">
                                    <div class="item-title">
                                        Enrollment in <?php echo htmlspecialchars($enrollment['course_name']); ?>
                                    </div>
                                    <div class="item-date">
                                        <?php echo date('M d, Y H:i', strtotime($enrollment['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="item-details">
                                    <div class="detail-group">
                                        <div class="detail-label">Student</div>
                                        <div class="detail-value">
                                            <?php echo htmlspecialchars($enrollment['full_name']); ?> 
                                            (<?php echo htmlspecialchars($enrollment['username']); ?>)
                                        </div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label">Course</div>
                                        <div class="detail-value">
                                            <?php echo htmlspecialchars($enrollment['main_course']); ?> - 
                                            <?php echo htmlspecialchars($enrollment['course_name']); ?>
                                        </div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label">Course Fee</div>
                                        <div class="detail-value">₹<?php echo number_format($enrollment['fee'], 2); ?></div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label">Enrollment Date</div>
                                        <div class="detail-value"><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></div>
                                    </div>
                                </div>
                                <div class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="approve_enrollment">
                                        <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['id']; ?>">
                                        <button type="submit" class="btn-approve" onclick="return confirm('Approve this enrollment?')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="reject_enrollment">
                                        <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['id']; ?>">
                                        <button type="submit" class="btn-reject" onclick="return confirm('Reject this enrollment?')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../footer.php'; ?>
    
    <script>
        // Auto-refresh page every 30 seconds to show latest pending items
        setTimeout(function() {
            location.reload();
        }, 30000);
        
        // Show confirmation for actions
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const action = this.querySelector('input[name="action"]').value;
                    const isApprove = action.includes('approve');
                    const message = isApprove ? 
                        'Are you sure you want to approve this item?' : 
                        'Are you sure you want to reject this item?';
                    
                    if (!confirm(message)) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
