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
$student = getRow("SELECT u.*, ut.name as user_type FROM users u JOIN user_types ut ON u.user_type_id = ut.id WHERE u.id = ? AND ut.name = 'student'", [$user_id]);
if (!$student) {
    header('Location: ../login.php');
    exit;
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'make_payment':
                $sub_course_id = intval($_POST['sub_course_id']);
                $amount = floatval($_POST['amount']);
                $payment_method = trim($_POST['payment_method']);
                $payment_type = trim($_POST['payment_type']);
                
                if (empty($payment_method)) {
                    throw new Exception("Please select a payment method.");
                }
                
                if ($amount <= 0) {
                    throw new Exception("Invalid payment amount.");
                }
                
                // Get course details
                $course = getRow("
                    SELECT sc.*, c.name as course_name, cc.name as category_name
                    FROM sub_courses sc
                    JOIN courses c ON sc.course_id = c.id
                    JOIN course_categories cc ON c.category_id = cc.id
                    WHERE sc.id = ? AND sc.status = 'active'
                ", [$sub_course_id]);
                
                if (!$course) {
                    throw new Exception("Course not found.");
                }
                
                // Check if student is already enrolled
                $existing_enrollment = getRow("
                    SELECT * FROM student_enrollments 
                    WHERE user_id = ? AND sub_course_id = ?
                ", [$user_id, $sub_course_id]);
                
                // Check existing payments for this course
                $existing_payments = getRows("
                    SELECT * FROM payments 
                    WHERE user_id = ? AND sub_course_id = ?
                    ORDER BY created_at DESC
                ", [$user_id, $sub_course_id]);
                
                $total_paid = array_sum(array_column($existing_payments, 'amount'));
                $remaining_amount = $course['fee'] - $total_paid;
                
                if ($amount > $remaining_amount) {
                    throw new Exception("Payment amount exceeds remaining fee. Remaining: ₹{$remaining_amount}");
                }
                
                // Create payment record
                $payment_sql = "INSERT INTO payments (user_id, sub_course_id, amount, total_fee, remaining_amount, payment_date, payment_method, payment_type, status, created_at) VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?, 'pending', NOW())";
                $payment_result = insertData($payment_sql, [
                    $user_id, $sub_course_id, $amount, $course['fee'], 
                    $remaining_amount - $amount, $payment_method, $payment_type
                ]);
                
                if (!$payment_result) {
                    throw new Exception("Failed to create payment record.");
                }
                
                // If this is the first payment, create enrollment record
                if (!$existing_enrollment) {
                    $enrollment_sql = "INSERT INTO student_enrollments (user_id, sub_course_id, enrollment_date, status, created_at) VALUES (?, ?, CURDATE(), 'pending', NOW())";
                    $enrollment_result = insertData($enrollment_sql, [$user_id, $sub_course_id]);
                    
                    if (!$enrollment_result) {
                        throw new Exception("Failed to create enrollment record.");
                    }
                }
                
                $success_message = "Payment submitted successfully! Amount: ₹{$amount}. Your payment is pending approval.";
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get student's payments
$payments = getRows("
    SELECT p.*, sc.name as sub_course_name, c.name as course_name, cc.name as category_name
    FROM payments p
    JOIN sub_courses sc ON p.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    JOIN course_categories cc ON c.category_id = cc.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
", [$user_id]);

// Get available courses for payment
$available_courses = getRows("
    SELECT sc.id, sc.name as sub_course_name, c.name as course_name, cc.name as category_name,
           sc.fee, sc.duration, sc.description
    FROM sub_courses sc
    JOIN courses c ON sc.course_id = c.id
    JOIN course_categories cc ON c.category_id = cc.id
    WHERE sc.status = 'active'
    ORDER BY c.name, sc.name
", []);

// Calculate payment statistics
$total_paid = array_sum(array_column($payments, 'amount'));
$pending_payments = array_filter($payments, fn($p) => $p['status'] === 'pending');
$completed_payments = array_filter($payments, fn($p) => $p['status'] === 'completed');
$pending_amount = array_sum(array_column($pending_payments, 'amount'));
$completed_amount = array_sum(array_column($completed_payments, 'amount'));
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
        .stat-card.total { border-left: 4px solid #667eea; }
        .stat-card.pending { border-left: 4px solid #ffc107; }
        .stat-card.completed { border-left: 4px solid #28a745; }
        .stat-value {
            font-size: 1.5rem;
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
        .payment-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .course-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
        }
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .course-fee {
            font-size: 1.25rem;
            font-weight: bold;
            color: #667eea;
        }
        .payment-form {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.875rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5a67d8; }
        .btn-success { background: #28a745; color: white; }
        .btn-info { background: #17a2b8; color: white; }
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
                    <span>Payments</span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="page-header">
                <h1><i class="fas fa-credit-card"></i> Payment Management</h1>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <!-- Payment Statistics -->
            <div class="payment-stats">
                <div class="stat-card total">
                    <div class="stat-value">₹<?php echo number_format($total_paid, 2); ?></div>
                    <div class="stat-label">Total Paid</div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-value">₹<?php echo number_format($pending_amount, 2); ?></div>
                    <div class="stat-label">Pending Approval</div>
                </div>
                <div class="stat-card completed">
                    <div class="stat-value">₹<?php echo number_format($completed_amount, 2); ?></div>
                    <div class="stat-label">Approved Payments</div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-history"></i> Payment History</span>
                </div>
                <div class="panel-body">
                    <?php if (empty($payments)): ?>
                        <p class="text-muted">No payment history available.</p>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <div class="payment-card">
                                <div class="payment-header">
                                    <div>
                                        <h4><?php echo htmlspecialchars($payment['course_name']); ?> - <?php echo htmlspecialchars($payment['sub_course_name']); ?></h4>
                                        <p class="text-muted"><?php echo htmlspecialchars($payment['category_name']); ?></p>
                                    </div>
                                    <div class="payment-amount">
                                        ₹<?php echo number_format($payment['amount'], 2); ?>
                                        <div class="payment-status status-<?php echo $payment['status']; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="payment-details">
                                    <div class="detail-item">
                                        <i class="fas fa-calendar"></i>
                                        <span>Payment Date: <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-credit-card"></i>
                                        <span>Method: <?php echo htmlspecialchars($payment['payment_method'] ?? 'Not specified'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-tag"></i>
                                        <span>Type: <?php echo ucfirst($payment['payment_type'] ?? 'full'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Total Fee: ₹<?php echo number_format($payment['total_fee'], 2); ?></span>
                                    </div>
                                    <?php if ($payment['remaining_amount'] > 0): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <span>Remaining: ₹<?php echo number_format($payment['remaining_amount'], 2); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($payment['notes']): ?>
                                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-top: 1rem;">
                                        <strong>Note:</strong> <?php echo htmlspecialchars($payment['notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Make New Payment -->
            <div class="panel" style="margin-top: 2rem;">
                <div class="panel-header">
                    <span><i class="fas fa-plus-circle"></i> Make New Payment</span>
                </div>
                <div class="panel-body">
                    <?php if (empty($available_courses)): ?>
                        <p class="text-muted">No courses available for payment.</p>
                    <?php else: ?>
                        <?php foreach ($available_courses as $course): ?>
                            <div class="course-card">
                                <div class="course-header">
                                    <div>
                                        <h4><?php echo htmlspecialchars($course['course_name']); ?> - <?php echo htmlspecialchars($course['sub_course_name']); ?></h4>
                                        <p class="text-muted"><?php echo htmlspecialchars($course['category_name']); ?></p>
                                    </div>
                                    <div class="course-fee">
                                        ₹<?php echo number_format($course['fee'], 2); ?>
                                    </div>
                                </div>
                                
                                <div class="payment-details">
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <span>Duration: <?php echo $course['duration']; ?> months</span>
                                    </div>
                                    <?php if ($course['description']): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-info-circle"></i>
                                            <span><?php echo htmlspecialchars($course['description']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="payment-form">
                                    <form method="POST" onsubmit="return validatePaymentForm(this, <?php echo $course['fee']; ?>)">
                                        <input type="hidden" name="action" value="make_payment">
                                        <input type="hidden" name="sub_course_id" value="<?php echo $course['id']; ?>">
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="amount_<?php echo $course['id']; ?>">Payment Amount (₹)</label>
                                                <input type="number" name="amount" id="amount_<?php echo $course['id']; ?>" 
                                                       min="100" max="<?php echo $course['fee']; ?>" step="100" 
                                                       value="<?php echo $course['fee']; ?>" required>
                                                <small class="text-muted">You can pay partial amount (min ₹100)</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="payment_method_<?php echo $course['id']; ?>">Payment Method</label>
                                                <select name="payment_method" id="payment_method_<?php echo $course['id']; ?>" required>
                                                    <option value="">Select Method</option>
                                                    <option value="UPI">UPI</option>
                                                    <option value="Online Banking">Online Banking</option>
                                                    <option value="Credit Card">Credit Card</option>
                                                    <option value="Debit Card">Debit Card</option>
                                                    <option value="Cash">Cash</option>
                                                    <option value="Cheque">Cheque</option>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="payment_type_<?php echo $course['id']; ?>">Payment Type</label>
                                                <select name="payment_type" id="payment_type_<?php echo $course['id']; ?>" required>
                                                    <option value="full">Full Payment</option>
                                                    <option value="partial">Partial Payment</option>
                                                    <option value="installment">Installment</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-credit-card"></i> Submit Payment
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('mobile-open');
        }
        
        function validatePaymentForm(form, totalFee) {
            const amount = parseFloat(form.amount.value);
            const paymentType = form.payment_type.value;
            
            if (amount <= 0) {
                alert('Payment amount must be greater than 0.');
                return false;
            }
            
            if (amount > totalFee) {
                alert('Payment amount cannot exceed the total course fee.');
                return false;
            }
            
            if (paymentType === 'full' && amount < totalFee) {
                if (!confirm('You selected "Full Payment" but the amount is less than the total fee. Do you want to continue with partial payment?')) {
                    return false;
                }
            }
            
            return true;
        }
        
        // Update payment type based on amount
        document.querySelectorAll('input[name="amount"]').forEach(input => {
            input.addEventListener('input', function() {
                const amount = parseFloat(this.value);
                const totalFee = parseFloat(this.max);
                const paymentTypeSelect = this.closest('form').querySelector('select[name="payment_type"]');
                
                if (amount >= totalFee) {
                    paymentTypeSelect.value = 'full';
                } else if (amount > 0) {
                    paymentTypeSelect.value = 'partial';
                }
            });
        });
    </script>
</body>
</html>
