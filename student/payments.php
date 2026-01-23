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
$student = getRow("SELECT u.* FROM users u WHERE u.id = ?", [$user_id]);
if (!$student) {
    header('Location: ../login.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'make_payment') {
            $sub_course_id = intval($_POST['sub_course_id']);
            $amount = floatval($_POST['amount']);
            $payment_method = trim($_POST['payment_method']);
            $payment_type = trim($_POST['payment_type']);

            if (empty($payment_method))
                throw new Exception("Please select a payment method.");
            if ($amount <= 0)
                throw new Exception("Invalid payment amount.");

            $course = getRow("SELECT * FROM sub_courses WHERE id = ? AND status = 'active'", [$sub_course_id]);
            if (!$course)
                throw new Exception("Course not found.");

            $prev_payments = getRow("SELECT SUM(amount) as paid FROM payments WHERE user_id = ? AND sub_course_id = ? AND status = 'completed'", [$user_id, $sub_course_id]);
            $total_paid_already = $prev_payments['paid'] ?? 0;
            $remaining = $course['fee'] - $total_paid_already;

            if ($amount > $remaining + 0.01)
                throw new Exception("Payment exceeds remaining fee (₹" . number_format($remaining, 2) . ")");

            insertData("INSERT INTO payments (user_id, sub_course_id, amount, total_fee, remaining_amount, payment_date, payment_method, payment_type, status, created_at) VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?, 'pending', NOW())", [
                $user_id,
                $sub_course_id,
                $amount,
                $course['fee'],
                $remaining - $amount,
                $payment_method,
                $payment_type
            ]);

            $success_message = "Payment request submitted successfully! Pending approval.";
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

$payments = getRows("SELECT p.*, sc.name as sub_course_name, c.name as course_name FROM payments p JOIN sub_courses sc ON p.sub_course_id = sc.id JOIN courses c ON sc.course_id = c.id WHERE p.user_id = ? ORDER BY p.created_at DESC", [$user_id]);
$available_courses = getRows("SELECT sc.*, sc.name as sub_course_name, c.name as course_name FROM sub_courses sc JOIN courses c ON sc.course_id = c.id WHERE sc.status = 'active'", []);

$total_paid = array_sum(array_map(fn($p) => $p['status'] === 'completed' ? $p['amount'] : 0, $payments));
$total_pending = array_sum(array_map(fn($p) => $p['status'] === 'pending' ? $p['amount'] : 0, $payments));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Student Portal</title>
    <link rel="icon" type="image/png" href="../logo.png">
    <link rel="stylesheet" href="../assets/css/student-portal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="student-portal-body">
    <div class="student-layout">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-container" style="width: 100%; overflow: auto;">
            <!-- Topbar -->
            <header class="admin-topbar">
                <div class="topbar-left">
                    <div class="breadcrumbs">
                        <a href="dashboard.php">Dashboard</a> / <span>Payments</span>
                    </div>
                </div>
            </header>

            <main class="admin-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">₹<?php echo number_format($total_paid, 2); ?></div>
                        <div class="stat-label">Total Paid</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #f59e0b;">
                            ₹<?php echo number_format($total_pending, 2); ?></div>
                        <div class="stat-label">Pending Approval</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1.3fr; gap: 30px;">
                    <!-- Payment History -->
                    <div class="panel">
                        <div class="panel-header">
                            <h1><i class="fas fa-history"></i> Payment History</h1>
                        </div>
                        <div class="panel-body">
                            <?php if (empty($payments)): ?>
                                <p style="color: #64748b; text-align: center; padding: 30px;">No payments recorded yet.</p>
                            <?php else: ?>
                                <div style="display: grid; gap: 15px;">
                                    <?php foreach ($payments as $p): ?>
                                        <div
                                            style="padding: 20px; border: 1px solid #f1f5f9; border-radius: 15px; display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <h4 style="margin: 0; color: #1e293b;">
                                                    <?php echo htmlspecialchars($p['sub_course_name']); ?>
                                                </h4>
                                                <div style="font-size: 0.8rem; color: #64748b; margin-top: 5px;">
                                                    <span><i class="fas fa-calendar"></i>
                                                        <?php echo date('M d, Y', strtotime($p['created_at'])); ?></span> •
                                                    <span><i class="fas fa-money-check"></i>
                                                        <?php echo $p['payment_method']; ?></span>
                                                </div>
                                            </div>
                                            <div style="text-align: right;">
                                                <div style="font-weight: 800; color: #1e293b; font-size: 1.1rem;">
                                                    ₹<?php echo number_format($p['amount'], 2); ?></div>
                                                <span
                                                    style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: <?php echo $p['status'] === 'completed' ? '#10b981' : ($p['status'] === 'pending' ? '#f59e0b' : '#ef4444'); ?>;">
                                                    <?php echo $p['status']; ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- New Payment -->
                    <div class="panel">
                        <div class="panel-header">
                            <h1><i class="fas fa-plus-circle"></i> New Payment</h1>
                        </div>
                        <div class="panel-body">
                            <?php if (isset($success_message)): ?>
                                <div
                                    style="padding: 10px; background: #dcfce7; color: #166534; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; border: 1px solid #bbf7d0;">
                                    <?php echo $success_message; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($error_message)): ?>
                                <div
                                    style="padding: 10px; background: #fee2e2; color: #991b1b; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; border: 1px solid #fecaca;">
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <input type="hidden" name="action" value="make_payment">
                                <div class="form-group">
                                    <label>Select Course</label>
                                    <select name="sub_course_id" required class="form-control">
                                        <?php foreach ($available_courses as $c): ?>
                                            <option value="<?php echo $c['id']; ?>">
                                                <?php echo htmlspecialchars($c['sub_course_name']); ?>
                                                (₹<?php echo number_format($c['fee'], 2); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Amount (₹)</label>
                                    <input type="number" name="amount" required class="form-control">
                                </div>

                                <div class="form-group">
                                    <label>Payment Method</label>
                                    <select name="payment_method" required class="form-control">
                                        <option value="UPI">UPI / QR Scan</option>
                                        <option value="Card">Debit / Credit Card</option>
                                        <option value="Net Banking">Net Banking</option>
                                        <option value="Cash">Offline Cash</option>
                                    </select>
                                </div>

                                <div class="form-group" style="margin-bottom: 30px;">
                                    <label>Payment Type</label>
                                    <select name="payment_type" required class="form-control">
                                        <option value="partial">Partial / Installment</option>
                                        <option value="full">Full Course Fee</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn-primary" style="width: 100%; justify-content: center;">
                                    Submit Request
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }
    </script>
</body>

</html>