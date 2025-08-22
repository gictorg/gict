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
    <title>Payments - Student Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .payment-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
        }
        
        .payment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.15);
        }
        
        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .payment-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        
        .payment-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-completed {
            background: #e8f5e8;
            color: #28a745;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .payment-details {
            margin-bottom: 20px;
        }
        
        .payment-detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .payment-detail:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #666;
            font-size: 14px;
        }
        
        .detail-value {
            font-weight: 600;
            color: #333;
        }
        
        .payment-amount {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            text-align: center;
            margin: 20px 0;
        }
        
        .payment-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            justify-content: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .upi-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .upi-section h3 {
            margin-bottom: 15px;
            font-size: 24px;
        }
        
        .upi-section p {
            margin-bottom: 20px;
            opacity: 0.9;
        }
        
        .upi-button {
            background: white;
            color: #667eea;
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .upi-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php 
        $page_title = 'Payments';
        include 'includes/sidebar.php'; 
        ?>
        
        <?php include 'includes/topbar.php'; ?>
        
        <!-- Main Content -->
        <main class="admin-content">
            <!-- UPI Payment Section -->
            <div class="upi-section">
                <h3><i class="fas fa-mobile-alt"></i> UPI Payment</h3>
                <p>Pay your course fees instantly using UPI. Fast, secure, and convenient!</p>
                <a href="#" class="upi-button">
                    <i class="fas fa-qrcode"></i>
                    Pay via UPI
                </a>
            </div>
            
            <!-- Statistics -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-number">₹<?php echo number_format($total_paid, 2); ?></div>
                    <div class="stat-label">Total Paid</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">₹<?php echo number_format($total_pending, 2); ?></div>
                    <div class="stat-label">Pending Amount</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_courses; ?></div>
                    <div class="stat-label">Courses Enrolled</div>
                </div>
            </div>
            
            <!-- Pending Payments -->
            <?php if (!empty($pending_payments)): ?>
            <div class="section">
                <div class="section-header">
                    <h2><i class="fas fa-clock"></i> Pending Payments</h2>
                </div>
                <div class="section-body">
                    <div class="payment-grid">
                        <?php foreach ($pending_payments as $payment): ?>
                            <div class="payment-card">
                                <div class="payment-header">
                                    <h3 class="payment-title"><?php echo htmlspecialchars($payment['course_name']); ?></h3>
                                    <span class="payment-status status-pending">Pending</span>
                                </div>
                                <div class="payment-amount">₹<?php echo number_format($payment['amount'], 2); ?></div>
                                <div class="payment-details">
                                    <div class="payment-detail">
                                        <span class="detail-label">Course Fee:</span>
                                        <span class="detail-value">₹<?php echo number_format($payment['course_fee'], 2); ?></span>
                                    </div>
                                    <div class="payment-detail">
                                        <span class="detail-label">Due Date:</span>
                                        <span class="detail-value"><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></span>
                                    </div>
                                    <div class="payment-detail">
                                        <span class="detail-label">Payment ID:</span>
                                        <span class="detail-value">#<?php echo $payment['id']; ?></span>
                                    </div>
                                </div>
                                <div class="payment-actions">
                                    <a href="#" class="btn btn-primary">Pay Now</a>
                                    <a href="#" class="btn btn-warning">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Payment History -->
            <div class="section">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Payment History</h2>
                </div>
                <div class="section-body">
                    <?php if (empty($payments)): ?>
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <i class="fas fa-credit-card" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                            <h3>No Payment History</h3>
                            <p>You haven't made any payments yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="payment-grid">
                            <?php foreach ($payments as $payment): ?>
                                <div class="payment-card">
                                    <div class="payment-header">
                                        <h3 class="payment-title"><?php echo htmlspecialchars($payment['course_name']); ?></h3>
                                        <span class="payment-status status-<?php echo $payment['status']; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </div>
                                    <div class="payment-amount">₹<?php echo number_format($payment['amount'], 2); ?></div>
                                    <div class="payment-details">
                                        <div class="payment-detail">
                                            <span class="detail-label">Payment Date:</span>
                                            <span class="detail-value"><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></span>
                                        </div>
                                        <div class="payment-detail">
                                            <span class="detail-label">Payment Method:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($payment['payment_method'] ?? 'UPI'); ?></span>
                                        </div>
                                        <div class="payment-detail">
                                            <span class="detail-label">Transaction ID:</span>
                                            <span class="detail-value">#<?php echo $payment['id']; ?></span>
                                        </div>
                                    </div>
                                    <div class="payment-actions">
                                        <?php if ($payment['status'] === 'completed'): ?>
                                            <a href="#" class="btn btn-success">Download Receipt</a>
                                        <?php else: ?>
                                            <a href="#" class="btn btn-warning">View Details</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/mobile-menu.js"></script>
</body>
</html>
