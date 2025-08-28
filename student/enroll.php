<?php
require_once '../auth.php';
requireLogin();
requireRole('student');

$user = getCurrentUser();

// Handle enrollment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'enroll':
                $sub_course_id = intval($_POST['sub_course_id']);
                $payment_method = $_POST['payment_method'];
                $payment_amount = floatval($_POST['payment_amount']);
                
                // Verify sub-course exists and is active
                $sub_course = getRow("
                    SELECT sc.*, c.name as course_name, c.institute_id 
                    FROM sub_courses sc 
                    JOIN courses c ON sc.course_id = c.id 
                    WHERE sc.id = ? AND sc.status = 'active'
                ", [$sub_course_id]);
                
                if (!$sub_course) {
                    throw new Exception("Invalid sub-course selected.");
                }
                
                // Check if student is already enrolled
                $existing_enrollment = getRow("
                    SELECT id FROM student_enrollments 
                    WHERE user_id = ? AND sub_course_id = ?
                ", [$user['id'], $sub_course_id]);
                
                if ($existing_enrollment) {
                    throw new Exception("You are already enrolled in this sub-course.");
                }
                
                // Create enrollment with pending status
                $enrollment_sql = "INSERT INTO student_enrollments (user_id, sub_course_id, enrollment_date, status) VALUES (?, ?, CURDATE(), 'pending')";
                $enrollment_id = insertData($enrollment_sql, [$user['id'], $sub_course_id]);
                
                if (!$enrollment_id) {
                    throw new Exception("Failed to create enrollment. Please try again.");
                }
                
                // Create payment record
                $payment_sql = "INSERT INTO payments (user_id, sub_course_id, amount, payment_date, payment_method, status) VALUES (?, ?, ?, CURDATE(), ?, 'pending')";
                $payment_result = insertData($payment_sql, [$user['id'], $sub_course_id, $payment_amount, $payment_method]);
                
                if (!$payment_result) {
                    throw new Exception("Failed to create payment record. Please try again.");
                }
                
                $success_message = "Enrollment submitted successfully! Your enrollment is pending admin approval.";
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get available sub-courses
$sub_courses = getRows("
    SELECT sc.*, c.name as course_name, cc.name as category_name,
           COUNT(DISTINCT se.user_id) as enrolled_students
    FROM sub_courses sc
    JOIN courses c ON sc.course_id = c.id
    JOIN course_categories cc ON c.category_id = cc.id
    LEFT JOIN student_enrollments se ON sc.id = se.sub_course_id AND se.status = 'enrolled'
    WHERE sc.status = 'active'
    GROUP BY sc.id, sc.name, sc.description, sc.fee, sc.duration, sc.status, c.name, cc.name
    ORDER BY c.name, sc.name
");

// Get student's current enrollments
$my_enrollments = getRows("
    SELECT se.*, sc.name as sub_course_name, sc.fee, c.name as course_name, cc.name as category_name,
           p.status as payment_status, p.payment_method, p.amount
    FROM student_enrollments se
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    JOIN course_categories cc ON c.category_id = cc.id
    LEFT JOIN payments p ON se.user_id = p.user_id AND se.sub_course_id = p.sub_course_id
    WHERE se.user_id = ?
    ORDER BY se.enrollment_date DESC
", [$user['id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Enrollment - GICT Student</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .enrollment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .course-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .course-name {
            font-size: 18px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 5px;
        }
        .course-category {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 500;
        }
        .course-fee {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }
        .course-description {
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .course-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 14px;
            color: #495057;
        }
        .enroll-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .enroll-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .enroll-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        .enrollment-status {
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
                    <div class="user-role">Student</div>
                </div>
            </div>
            
            <nav class="admin-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="enroll.php" class="nav-item active">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Enroll in Courses</span>
                </a>
                <a href="courses.php" class="nav-item">
                    <i class="fas fa-book"></i>
                    <span>My Courses</span>
                </a>
                <a href="payments.php" class="nav-item">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
                <a href="documents.php" class="nav-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Documents</span>
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
                <h1><i class="fas fa-graduation-cap"></i> Course Enrollment</h1>
                <p>Browse and enroll in available courses at your institute</p>
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

            <!-- My Enrollments Section -->
            <div class="section-header">
                <h2><i class="fas fa-list"></i> My Enrollments</h2>
                <p>Track your current and pending enrollments</p>
            </div>

            <?php if (empty($my_enrollments)): ?>
                <div class="empty-state">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>No Enrollments Yet</h3>
                    <p>You haven't enrolled in any courses yet. Browse the available courses below to get started.</p>
                </div>
            <?php else: ?>
                <div class="courses-table">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Sub-Course</th>
                                    <th>Enrollment Date</th>
                                    <th>Status</th>
                                    <th>Payment Status</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_enrollments as $enrollment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($enrollment['course_name']); ?></td>
                                        <td class="course-name"><?php echo htmlspecialchars($enrollment['sub_course_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                        <td>
                                            <span class="enrollment-status status-<?php echo $enrollment['status']; ?>">
                                                <?php echo ucfirst($enrollment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="payment-status payment-<?php echo $enrollment['payment_status'] ?? 'pending'; ?>">
                                                <?php echo ucfirst($enrollment['payment_status'] ?? 'pending'); ?>
                                            </span>
                                        </td>
                                        <td>₹<?php echo number_format($enrollment['amount'] ?? $enrollment['fee']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Available Courses Section -->
            <div class="section-header" style="margin-top: 40px;">
                <h2><i class="fas fa-book"></i> Available Courses</h2>
                <p>Browse and enroll in courses offered by your institute</p>
            </div>

            <?php if (empty($sub_courses)): ?>
                <div class="empty-state">
                    <i class="fas fa-book"></i>
                    <h3>No Courses Available</h3>
                    <p>There are currently no courses available for enrollment. Please check back later.</p>
                </div>
            <?php else: ?>
                <div class="enrollment-grid">
                    <?php foreach ($sub_courses as $sub_course): ?>
                        <div class="course-card">
                            <div class="course-header">
                                <div>
                                    <div class="course-name"><?php echo htmlspecialchars($sub_course['name']); ?></div>
                                    <div class="course-category"><?php echo htmlspecialchars($sub_course['category']); ?></div>
                                </div>
                                <div class="course-fee">₹<?php echo number_format($sub_course['fee']); ?></div>
                            </div>
                            
                            <div class="course-description">
                                <?php echo htmlspecialchars($sub_course['description']); ?>
                            </div>
                            
                            <div class="course-details">
                                <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($sub_course['duration']); ?></span>
                                <span><i class="fas fa-users"></i> <?php echo $sub_course['enrolled_students']; ?> enrolled</span>
                            </div>
                            
                            <button class="enroll-btn" onclick="enrollInCourse(<?php echo $sub_course['id']; ?>, '<?php echo htmlspecialchars($sub_course['name']); ?>', <?php echo $sub_course['fee']; ?>)">
                                <i class="fas fa-plus"></i> Enroll Now
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Enrollment Modal -->
    <div id="enrollmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-graduation-cap"></i> Enroll in Course</h2>
                <span class="close" onclick="closeEnrollmentModal()">&times;</span>
            </div>
            
            <form method="POST" action="enroll.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="enroll">
                    <input type="hidden" id="sub_course_id" name="sub_course_id">
                    <input type="hidden" id="payment_amount" name="payment_amount">
                    
                    <div class="form-group">
                        <label>Course Name</label>
                        <input type="text" id="course_name_display" readonly style="background: #f8f9fa;">
                    </div>
                    
                    <div class="form-group">
                        <label>Course Fee</label>
                        <input type="text" id="course_fee_display" readonly style="background: #f8f9fa;">
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">Payment Method *</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="online">Online Payment (UPI/Card)</option>
                            <option value="offline">Offline Payment (Cash/Cheque)</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Important:</strong> Your enrollment will be pending until the admin approves your payment. 
                        You will receive a notification once your enrollment is approved.
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEnrollmentModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Submit Enrollment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function enrollInCourse(subCourseId, courseName, fee) {
            document.getElementById('sub_course_id').value = subCourseId;
            document.getElementById('course_name_display').value = courseName;
            document.getElementById('course_fee_display').value = '₹' + fee.toLocaleString();
            document.getElementById('payment_amount').value = fee;
            document.getElementById('enrollmentModal').style.display = 'block';
        }
        
        function closeEnrollmentModal() {
            document.getElementById('enrollmentModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('enrollmentModal');
            if (event.target === modal) {
                closeEnrollmentModal();
            }
        }
    </script>
</body>
</html>
