<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

$user = getCurrentUser();

// Handle enrollment approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'approve_enrollment':
                $enrollment_id = intval($_POST['enrollment_id']);
                $payment_id = intval($_POST['payment_id']);
                
                // Verify enrollment exists
                $enrollment = getRow("
                    SELECT se.*, sc.name as sub_course_name, u.full_name as student_name
                    FROM student_enrollments se
                    JOIN sub_courses sc ON se.sub_course_id = sc.id
                    JOIN courses c ON sc.course_id = c.id
                    JOIN users u ON se.user_id = u.id
                    WHERE se.id = ? AND se.status = 'pending'
                ", [$enrollment_id]);
                
                if (!$enrollment) {
                    throw new Exception("Invalid enrollment selected.");
                }
                
                // Update enrollment status
                $enrollment_sql = "UPDATE student_enrollments SET status = 'enrolled', updated_at = NOW() WHERE id = ?";
                $enrollment_result = updateData($enrollment_sql, [$enrollment_id]);
                
                if (!$enrollment_result) {
                    throw new Exception("Failed to approve enrollment. Please try again.");
                }
                
                // Update payment status
                $payment_sql = "UPDATE payments SET status = 'completed', updated_at = NOW() WHERE id = ?";
                $payment_result = updateData($payment_sql, [$payment_id]);
                
                if (!$payment_result) {
                    throw new Exception("Failed to update payment status. Please try again.");
                }
                
                $success_message = "Enrollment approved successfully! Student {$enrollment['student_name']} is now enrolled in {$enrollment['sub_course_name']}.";
                break;
                
            case 'reject_enrollment':
                $enrollment_id = intval($_POST['enrollment_id']);
                $payment_id = intval($_POST['payment_id']);
                $rejection_reason = trim($_POST['rejection_reason']);
                
                if (empty($rejection_reason)) {
                    throw new Exception("Please provide a reason for rejection.");
                }
                
                // Verify enrollment exists
                $enrollment = getRow("
                    SELECT se.*, sc.name as sub_course_name, u.full_name as student_name
                    FROM student_enrollments se
                    JOIN sub_courses sc ON se.sub_course_id = sc.id
                    JOIN courses c ON sc.course_id = c.id
                    JOIN users u ON se.user_id = u.id
                    WHERE se.id = ? AND se.status = 'pending'
                ", [$enrollment_id]);
                
                if (!$enrollment) {
                    throw new Exception("Invalid enrollment selected.");
                }
                
                // Update enrollment status
                $enrollment_sql = "UPDATE student_enrollments SET status = 'rejected', updated_at = NOW() WHERE id = ?";
                $enrollment_result = updateData($enrollment_sql, [$enrollment_id]);
                
                if (!$enrollment_result) {
                    throw new Exception("Failed to reject enrollment. Please try again.");
                }
                
                // Update payment status
                $payment_sql = "UPDATE payments SET status = 'failed', updated_at = NOW() WHERE id = ?";
                $payment_result = updateData($payment_sql, [$payment_id]);
                
                if (!$payment_result) {
                    throw new Exception("Failed to update payment status. Please try again.");
                }
                
                $success_message = "Enrollment rejected successfully.";
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get pending enrollments
$pending_enrollments = getRows("
    SELECT se.id as enrollment_id, se.enrollment_date, se.status as enrollment_status,
           sc.name as sub_course_name, sc.fee, c.name as course_name, cc.name as category_name,
           u.full_name as student_name, u.email as student_email, u.phone as student_phone,
           p.id as payment_id, p.amount, p.payment_method, p.status as payment_status, p.payment_date
    FROM student_enrollments se
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    JOIN course_categories cc ON c.category_id = cc.id
    JOIN users u ON se.user_id = u.id
    JOIN payments p ON se.user_id = p.user_id AND se.sub_course_id = p.sub_course_id
    WHERE se.status = 'pending'
    ORDER BY se.enrollment_date DESC
");

// Get approved enrollments
$approved_enrollments = getRows("
    SELECT se.id as enrollment_id, se.enrollment_date, se.status as enrollment_status,
           sc.name as sub_course_name, sc.fee, c.name as course_name, cc.name as category_name,
           u.full_name as student_name, u.email as student_email, u.phone as student_phone,
           p.id as payment_id, p.amount, p.payment_method, p.status as payment_status, p.payment_date
    FROM student_enrollments se
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    JOIN course_categories cc ON c.category_id = cc.id
    JOIN users u ON se.user_id = u.id
    JOIN payments p ON se.user_id = p.user_id AND se.sub_course_id = p.sub_course_id
    WHERE se.status = 'enrolled'
    ORDER BY se.enrollment_date DESC
    LIMIT 20
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Enrollments - GICT Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .enrollment-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .enrollment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        .enrollment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .student-info {
            flex: 1;
        }
        .student-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .student-details {
            color: #6c757d;
            font-size: 14px;
        }
        .course-info {
            text-align: right;
        }
        .course-name {
            font-size: 16px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 5px;
        }
        .sub-course-name {
            color: #6c757d;
            font-size: 14px;
        }
        .enrollment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        .detail-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .detail-value {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .btn-approve {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }
        .btn-reject {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
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
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
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
                <a href="students.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Students</span>
                </a>
                <a href="staff.php" class="nav-item">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Staff</span>
                </a>
                <a href="courses.php" class="nav-item">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Courses</span>
                </a>
                <a href="pending-enrollments.php" class="nav-item active">
                    <i class="fas fa-clock"></i>
                    <span>Pending Enrollments</span>
                </a>
                <a href="admissions.php" class="nav-item">
                    <i class="fas fa-user-plus"></i>
                    <span>Admissions</span>
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
                <h1><i class="fas fa-clock"></i> Pending Enrollments</h1>
                <p>Review and approve student enrollments and payments</p>
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

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Pending Enrollments</h3>
                    <div class="value"><?php echo count($pending_enrollments); ?></div>
                    <div class="label">Awaiting Approval</div>
                </div>
                <div class="stat-card">
                    <h3>Approved Today</h3>
                    <div class="value">
                        <?php 
                        $today_approved = array_filter($approved_enrollments, function($e) {
                            return date('Y-m-d', strtotime($e['enrollment_date'])) === date('Y-m-d');
                        });
                        echo count($today_approved);
                        ?>
                    </div>
                    <div class="label">Today's Approvals</div>
                </div>
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <div class="value">₹<?php echo number_format(array_sum(array_column($approved_enrollments, 'amount'))); ?></div>
                    <div class="label">From Approved Enrollments</div>
                </div>
            </div>

            <!-- Pending Enrollments Section -->
            <div class="section-header">
                <h2><i class="fas fa-clock"></i> Pending Enrollments</h2>
                <p>Review and approve student enrollment requests</p>
            </div>

            <?php if (empty($pending_enrollments)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>No Pending Enrollments</h3>
                    <p>All enrollment requests have been processed.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pending_enrollments as $enrollment): ?>
                    <div class="enrollment-card">
                        <div class="enrollment-header">
                            <div class="student-info">
                                <div class="student-name"><?php echo htmlspecialchars($enrollment['student_name']); ?></div>
                                <div class="student-details">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($enrollment['student_email']); ?> |
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($enrollment['student_phone']); ?>
                                </div>
                            </div>
                            <div class="course-info">
                                <div class="course-name"><?php echo htmlspecialchars($enrollment['course_name']); ?></div>
                                <div class="sub-course-name"><?php echo htmlspecialchars($enrollment['sub_course_name']); ?></div>
                            </div>
                        </div>
                        
                        <div class="enrollment-details">
                            <div class="detail-item">
                                <div class="detail-label">Enrollment Date</div>
                                <div class="detail-value"><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Payment Method</div>
                                <div class="detail-value"><?php echo ucfirst($enrollment['payment_method']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Amount</div>
                                <div class="detail-value">₹<?php echo number_format($enrollment['amount']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Status</div>
                                <div class="detail-value">
                                    <span class="status-badge status-<?php echo $enrollment['enrollment_status']; ?>">
                                        <?php echo ucfirst($enrollment['enrollment_status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button class="btn-approve" onclick="approveEnrollment(<?php echo $enrollment['enrollment_id']; ?>, <?php echo $enrollment['payment_id']; ?>)">
                                <i class="fas fa-check"></i> Approve Enrollment
                            </button>
                            <button class="btn-reject" onclick="rejectEnrollment(<?php echo $enrollment['enrollment_id']; ?>, <?php echo $enrollment['payment_id']; ?>)">
                                <i class="fas fa-times"></i> Reject Enrollment
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Recent Approved Enrollments Section -->
            <div class="section-header" style="margin-top: 40px;">
                <h2><i class="fas fa-check-circle"></i> Recent Approved Enrollments</h2>
                <p>Recently approved student enrollments</p>
            </div>

            <?php if (empty($approved_enrollments)): ?>
                <div class="empty-state">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>No Approved Enrollments</h3>
                    <p>No enrollments have been approved yet.</p>
                </div>
            <?php else: ?>
                <div class="courses-table">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Sub-Course</th>
                                    <th>Enrollment Date</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($approved_enrollments as $enrollment): ?>
                                    <tr>
                                        <td>
                                            <div class="student-name"><?php echo htmlspecialchars($enrollment['student_name']); ?></div>
                                            <small><?php echo htmlspecialchars($enrollment['student_email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($enrollment['course_name']); ?></td>
                                        <td class="course-name"><?php echo htmlspecialchars($enrollment['sub_course_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                        <td>₹<?php echo number_format($enrollment['amount']); ?></td>
                                        <td><?php echo ucfirst($enrollment['payment_method']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-times"></i> Reject Enrollment</h2>
                <span class="close" onclick="closeRejectionModal()">&times;</span>
            </div>
            
            <form method="POST" action="pending-enrollments.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject_enrollment">
                    <input type="hidden" id="reject_enrollment_id" name="enrollment_id">
                    <input type="hidden" id="reject_payment_id" name="payment_id">
                    
                    <div class="form-group">
                        <label for="rejection_reason">Reason for Rejection *</label>
                        <textarea id="rejection_reason" name="rejection_reason" required rows="4" 
                                  placeholder="Please provide a reason for rejecting this enrollment..."></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeRejectionModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Reject Enrollment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function approveEnrollment(enrollmentId, paymentId) {
            if (confirm('Are you sure you want to approve this enrollment? This will enroll the student in the course.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'pending-enrollments.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'approve_enrollment';
                
                const enrollmentInput = document.createElement('input');
                enrollmentInput.type = 'hidden';
                enrollmentInput.name = 'enrollment_id';
                enrollmentInput.value = enrollmentId;
                
                const paymentInput = document.createElement('input');
                paymentInput.type = 'hidden';
                paymentInput.name = 'payment_id';
                paymentInput.value = paymentId;
                
                form.appendChild(actionInput);
                form.appendChild(enrollmentInput);
                form.appendChild(paymentInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function rejectEnrollment(enrollmentId, paymentId) {
            document.getElementById('reject_enrollment_id').value = enrollmentId;
            document.getElementById('reject_payment_id').value = paymentId;
            document.getElementById('rejectionModal').style.display = 'block';
        }
        
        function closeRejectionModal() {
            document.getElementById('rejectionModal').style.display = 'none';
            document.getElementById('rejection_reason').value = '';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('rejectionModal');
            if (event.target === modal) {
                closeRejectionModal();
            }
        }
    </script>
</body>
</html>
