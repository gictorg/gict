<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

$user = getCurrentUser();

// Get enrollment ID from URL
$enrollment_id = intval($_GET['enrollment_id'] ?? 0);

// Handle marks submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'submit_marks':
                $enrollment_id = intval($_POST['enrollment_id']);
                $marks_data = $_POST['marks'] ?? [];
                
                // Get enrollment details
                $enrollment = getRow("
                    SELECT se.*, sc.name as sub_course_name, c.name as course_name, u.full_name as student_name
                    FROM student_enrollments se
                    JOIN sub_courses sc ON se.sub_course_id = sc.id
                    JOIN courses c ON sc.course_id = c.id
                    JOIN users u ON se.user_id = u.id
                    WHERE se.id = ? AND se.status = 'enrolled'
                ", [$enrollment_id]);
                
                if (!$enrollment) {
                    throw new Exception("Enrollment not found or not eligible for marks entry.");
                }
                
                // Process marks for each subject
                foreach ($marks_data as $subject_id => $marks_info) {
                    $marks_obtained = intval($marks_info['marks']);
                    $remarks = $marks_info['remarks'] ?? '';
                    
                    // Calculate grade based on marks
                    $grade = '';
                    if ($marks_obtained >= 90) $grade = 'A+';
                    elseif ($marks_obtained >= 80) $grade = 'A';
                    elseif ($marks_obtained >= 70) $grade = 'B+';
                    elseif ($marks_obtained >= 60) $grade = 'B';
                    elseif ($marks_obtained >= 50) $grade = 'C';
                    elseif ($marks_obtained >= 40) $grade = 'D';
                    else $grade = 'F';
                    
                    // Insert or update marks
                    $marks_sql = "INSERT INTO student_marks (enrollment_id, subject_id, marks_obtained, grade, remarks) 
                                 VALUES (?, ?, ?, ?, ?) 
                                 ON DUPLICATE KEY UPDATE 
                                 marks_obtained = VALUES(marks_obtained), 
                                 grade = VALUES(grade), 
                                 remarks = VALUES(remarks),
                                 updated_at = NOW()";
                    
                    $result = updateData($marks_sql, [$enrollment_id, $subject_id, $marks_obtained, $grade, $remarks]);
                    
                    if (!$result) {
                        throw new Exception("Failed to save marks for subject ID: $subject_id");
                    }
                }
                
                $success_message = "Marks submitted successfully for {$enrollment['student_name']} in {$enrollment['sub_course_name']}.";
                break;
                
            case 'complete_course':
                $enrollment_id = intval($_POST['enrollment_id']);
                
                // Get enrollment details
                $enrollment = getRow("
                    SELECT se.*, sc.name as sub_course_name, c.name as course_name, u.full_name as student_name
                    FROM student_enrollments se
                    JOIN sub_courses sc ON se.sub_course_id = sc.id
                    JOIN courses c ON sc.course_id = c.id
                    JOIN users u ON se.user_id = u.id
                    WHERE se.id = ? AND se.status = 'enrolled'
                ", [$enrollment_id]);
                
                if (!$enrollment) {
                    throw new Exception("Enrollment not found or not eligible for completion.");
                }
                
                // Check if all subjects have marks
                $subjects_count = getRow("
                    SELECT COUNT(*) as count FROM course_subjects WHERE sub_course_id = ?
                ", [$enrollment['sub_course_id']])['count'];
                
                $marks_count = getRow("
                    SELECT COUNT(*) as count FROM student_marks WHERE enrollment_id = ?
                ", [$enrollment_id])['count'];
                
                if ($marks_count < $subjects_count) {
                    throw new Exception("Cannot complete course. Please enter marks for all subjects first.");
                }
                
                // Mark course as completed
                $completion_sql = "UPDATE student_enrollments SET status = 'completed', completion_date = CURDATE(), updated_at = NOW() WHERE id = ?";
                $result = updateData($completion_sql, [$enrollment_id]);
                
                if ($result) {
                    $success_message = "Course completed successfully! {$enrollment['student_name']} has completed {$enrollment['sub_course_name']}.";
                } else {
                    $error_message = "Failed to complete course.";
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get enrolled students
$enrolled_students = getRows("
    SELECT se.*, sc.name as sub_course_name, c.name as course_name, cc.name as category_name,
           u.full_name as student_name, u.email as student_email, u.phone as student_phone,
           COUNT(sm.id) as marks_entered,
           COUNT(cs.id) as total_subjects
    FROM student_enrollments se
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    JOIN course_categories cc ON c.category_id = cc.id
    JOIN users u ON se.user_id = u.id
    LEFT JOIN course_subjects cs ON sc.id = cs.sub_course_id
    LEFT JOIN student_marks sm ON se.id = sm.enrollment_id
    WHERE se.status = 'enrolled'
    GROUP BY se.id, se.user_id, se.sub_course_id, se.enrollment_date, se.completion_date, se.status, se.created_at, se.updated_at, sc.name, c.name, cc.name, u.full_name, u.email, u.phone
    ORDER BY se.enrollment_date DESC
");

// If specific enrollment is selected, get its details
$selected_enrollment = null;
$subjects = [];
$existing_marks = [];

if ($enrollment_id) {
    $selected_enrollment = getRow("
        SELECT se.*, sc.name as sub_course_name, c.name as course_name, cc.name as category_name,
               u.full_name as student_name, u.email as student_email, u.phone as student_phone
        FROM student_enrollments se
        JOIN sub_courses sc ON se.sub_course_id = sc.id
        JOIN courses c ON sc.course_id = c.id
        JOIN course_categories cc ON c.category_id = cc.id
        JOIN users u ON se.user_id = u.id
        WHERE se.id = ? AND se.status = 'enrolled'
    ", [$enrollment_id]);
    
    if ($selected_enrollment) {
        // Get subjects for this course
        $subjects = getRows("
            SELECT * FROM course_subjects WHERE sub_course_id = ? ORDER BY id
        ", [$selected_enrollment['sub_course_id']]);
        
        // Get existing marks
        $existing_marks = getRows("
            SELECT sm.*, cs.subject_name, cs.max_marks
            FROM student_marks sm
            JOIN course_subjects cs ON sm.subject_id = cs.id
            WHERE sm.enrollment_id = ?
        ", [$enrollment_id]);
        
        // Convert to associative array for easy lookup
        $marks_lookup = [];
        foreach ($existing_marks as $mark) {
            $marks_lookup[$mark['subject_id']] = $mark;
        }
        $existing_marks = $marks_lookup;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marks Management - GICT Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .marks-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        
        .students-list {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-height: 600px;
            overflow-y: auto;
        }
        
        .student-item {
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .student-item:hover {
            background: #f8f9fa;
            border-color: #007bff;
        }
        
        .student-item.active {
            background: #e3f2fd;
            border-color: #007bff;
        }
        
        .student-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .student-course {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .marks-progress {
            font-size: 12px;
            color: #28a745;
        }
        
        .marks-form {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 60px;
            resize: vertical;
        }
        
        .marks-grid {
            display: grid;
            grid-template-columns: 1fr 100px 1fr;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .subject-name {
            font-weight: 600;
            color: #333;
        }
        
        .max-marks {
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        
        .marks-input {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .marks-input input {
            width: 80px;
            text-align: center;
        }
        
        .grade-display {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
            text-align: center;
            min-width: 30px;
        }
        
        .grade-A { background: #d4edda; color: #155724; }
        .grade-B { background: #cce5ff; color: #004085; }
        .grade-C { background: #fff3cd; color: #856404; }
        .grade-D { background: #f8d7da; color: #721c24; }
        .grade-F { background: #f5c6cb; color: #721c24; }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .completion-section {
            background: #e8f5e8;
            border: 1px solid #28a745;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .completion-section h4 {
            color: #155724;
            margin: 0 0 10px 0;
        }
        
        .completion-section p {
            color: #155724;
            margin: 0;
            font-size: 14px;
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
                <a href="enrollment-approvals.php" class="nav-item">
                    <i class="fas fa-user-graduate"></i>
                    <span>Enrollment Approvals</span>
                </a>
                <a href="marks-management.php" class="nav-item active">
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
                <h1><i class="fas fa-chart-line"></i> Marks Management</h1>
                <p>Enter and manage student marks for courses</p>
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

            <div class="marks-container">
                <!-- Students List -->
                <div class="students-list">
                    <h3><i class="fas fa-users"></i> Enrolled Students</h3>
                    <?php if (empty($enrolled_students)): ?>
                        <div class="no-pending">
                            <i class="fas fa-user-graduate"></i>
                            <h4>No Enrolled Students</h4>
                            <p>No students are currently enrolled in courses.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($enrolled_students as $student): ?>
                            <div class="student-item <?php echo $enrollment_id == $student['id'] ? 'active' : ''; ?>" 
                                 onclick="selectStudent(<?php echo $student['id']; ?>)">
                                <div class="student-name"><?php echo htmlspecialchars($student['student_name']); ?></div>
                                <div class="student-course"><?php echo htmlspecialchars($student['sub_course_name']); ?></div>
                                <div class="marks-progress">
                                    <i class="fas fa-chart-line"></i> 
                                    <?php echo $student['marks_entered']; ?>/<?php echo $student['total_subjects']; ?> subjects marked
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Marks Form -->
                <div class="marks-form">
                    <?php if ($selected_enrollment): ?>
                        <h3><i class="fas fa-edit"></i> Enter Marks</h3>
                        <div class="student-info">
                            <h4><?php echo htmlspecialchars($selected_enrollment['student_name']); ?></h4>
                            <p><?php echo htmlspecialchars($selected_enrollment['sub_course_name']); ?> - <?php echo htmlspecialchars($selected_enrollment['course_name']); ?></p>
                        </div>

                        <form method="POST" action="marks-management.php">
                            <input type="hidden" name="action" value="submit_marks">
                            <input type="hidden" name="enrollment_id" value="<?php echo $selected_enrollment['id']; ?>">
                            
                            <?php foreach ($subjects as $subject): ?>
                                <div class="marks-grid">
                                    <div class="subject-name"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                                    <div class="max-marks">Max: <?php echo $subject['max_marks']; ?></div>
                                    <div class="marks-input">
                                        <input type="number" 
                                               name="marks[<?php echo $subject['id']; ?>][marks]" 
                                               value="<?php echo $existing_marks[$subject['id']]['marks_obtained'] ?? ''; ?>"
                                               min="0" 
                                               max="<?php echo $subject['max_marks']; ?>"
                                               onchange="calculateGrade(this, <?php echo $subject['max_marks']; ?>)"
                                               required>
                                        <div class="grade-display" id="grade-<?php echo $subject['id']; ?>">
                                            <?php echo $existing_marks[$subject['id']]['grade'] ?? ''; ?>
                                        </div>
                                        <input type="hidden" name="marks[<?php echo $subject['id']; ?>][remarks]" 
                                               value="<?php echo htmlspecialchars($existing_marks[$subject['id']]['remarks'] ?? ''); ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="action-buttons">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Marks
                                </button>
                            </div>
                        </form>

                        <!-- Course Completion Section -->
                        <?php if (count($existing_marks) >= count($subjects)): ?>
                            <div class="completion-section">
                                <h4><i class="fas fa-check-circle"></i> Course Ready for Completion</h4>
                                <p>All subjects have been marked. You can now complete this course for the student.</p>
                                <form method="POST" action="marks-management.php" style="margin-top: 10px;">
                                    <input type="hidden" name="action" value="complete_course">
                                    <input type="hidden" name="enrollment_id" value="<?php echo $selected_enrollment['id']; ?>">
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to complete this course? This will make the student eligible for certificate generation.')">
                                        <i class="fas fa-graduation-cap"></i> Complete Course
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-pending">
                            <i class="fas fa-mouse-pointer"></i>
                            <h4>Select a Student</h4>
                            <p>Click on a student from the list to enter their marks.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function selectStudent(enrollmentId) {
            window.location.href = 'marks-management.php?enrollment_id=' + enrollmentId;
        }
        
        function calculateGrade(input, maxMarks) {
            const marks = parseInt(input.value) || 0;
            const percentage = (marks / maxMarks) * 100;
            let grade = '';
            let gradeClass = '';
            
            if (percentage >= 90) { grade = 'A+'; gradeClass = 'grade-A'; }
            else if (percentage >= 80) { grade = 'A'; gradeClass = 'grade-A'; }
            else if (percentage >= 70) { grade = 'B+'; gradeClass = 'grade-B'; }
            else if (percentage >= 60) { grade = 'B'; gradeClass = 'grade-B'; }
            else if (percentage >= 50) { grade = 'C'; gradeClass = 'grade-C'; }
            else if (percentage >= 40) { grade = 'D'; gradeClass = 'grade-D'; }
            else { grade = 'F'; gradeClass = 'grade-F'; }
            
            const gradeDisplay = input.parentElement.querySelector('.grade-display');
            gradeDisplay.textContent = grade;
            gradeDisplay.className = 'grade-display ' + gradeClass;
        }
    </script>
</body>
</html>
