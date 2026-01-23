<?php
require_once '../includes/session_manager.php';
require_once '../config/database.php';

// Check if user is logged in and is faculty
if (!isLoggedIn() || !isFaculty()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$assignment_id = intval($_GET['id'] ?? 0);
$success_message = '';
$error_message = '';

if (!$assignment_id) {
    header('Location: assignments.php');
    exit;
}

// Get assignment details
$assignment = getRow("
    SELECT a.*, sc.name as sub_course_name 
    FROM assignments a
    JOIN sub_courses sc ON a.sub_course_id = sc.id
    WHERE a.id = ? AND a.created_by = ?
", [$assignment_id, $user_id]);

if (!$assignment) {
    header('Location: assignments.php');
    exit;
}

// Handle grading submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'grade_submission') {
    $submission_id = intval($_POST['submission_id']);
    $score = intval($_POST['score']);
    $feedback = trim($_POST['feedback']);

    try {
        if ($score < 0 || $score > $assignment['max_score']) {
            throw new Exception("Score must be between 0 and " . $assignment['max_score']);
        }

        $sql = "UPDATE assignment_submissions SET score = ?, feedback = ?, status = 'graded', graded_by = ?, updated_at = NOW() WHERE id = ?";
        updateData($sql, [$score, $feedback, $user_id, $submission_id]);
        $success_message = "Submission graded successfully!";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get submissions for this assignment
$submissions = getRows("
    SELECT asub.*, u.full_name, u.username, se.id as enrollment_id
    FROM student_enrollments se
    JOIN users u ON se.user_id = u.id
    LEFT JOIN assignment_submissions asub ON se.id = asub.enrollment_id AND asub.assignment_id = ?
    WHERE se.sub_course_id = ? AND se.status = 'enrolled'
    ORDER BY u.full_name
", [$assignment_id, $assignment['sub_course_id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Submissions - Faculty Portal</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .submission-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .submission-table th { background: #f8fafc; padding: 15px; text-align: left; }
        .submission-table td { padding: 15px; border-bottom: 1px solid #f1f5f9; }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-graded { background: #dcfce7; color: #166534; }
        .status-submitted { background: #dbeafe; color: #1e40af; }
        .status-pending { background: #f3f4f6; color: #4b5563; }
        
        .grade-input {
            width: 70px;
            padding: 5px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            text-align: center;
        }
        
        .feedback-area {
            width: 100%;
            padding: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 13px;
        }
        
        .btn-grade {
            background: #0f6fb1;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
    </style>
</head>
<body class="admin-dashboard-body">
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img src="../assets/images/logo.png" alt="logo" />
                <div class="brand-title">FACULTY PORTAL</div>
            </div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                <li><a class="active" href="assignments.php"><i class="fas fa-tasks"></i> Assignments</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <div class="page-header">
                <h1><i class="fas fa-graduation-cap"></i> Grading: <?php echo htmlspecialchars($assignment['title']); ?></h1>
                <p>Course: <?php echo htmlspecialchars($assignment['sub_course_name']); ?> | Max Score: <?php echo $assignment['max_score']; ?></p>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="submission-list">
                <table class="submission-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Status</th>
                            <th>Submission</th>
                            <th>Score / <?php echo $assignment['max_score']; ?></th>
                            <th>Feedback</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $sub): ?>
                            <tr>
                                <td>
                                    <div class="student-name"><?php echo htmlspecialchars($sub['full_name']); ?></div>
                                    <div style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($sub['username']); ?></div>
                                </td>
                                <td>
                                    <?php if (!$sub['id']): ?>
                                        <span class="status-badge status-pending">Not Submitted</span>
                                    <?php else: ?>
                                        <span class="status-badge status-<?php echo $sub['status']; ?>"><?php echo ucfirst($sub['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sub['id']): ?>
                                        <div style="font-size: 12px; max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars(substr($sub['submission_text'], 0, 50)); ?>...
                                        </div>
                                        <?php if ($sub['file_url']): ?>
                                            <a href="<?php echo $sub['file_url']; ?>" target="_blank" style="font-size: 12px;"><i class="fas fa-file-download"></i> View File</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="grade_submission">
                                    <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                    <td>
                                        <input type="number" name="score" class="grade-input" 
                                               value="<?php echo $sub['score']; ?>" 
                                               max="<?php echo $assignment['max_score']; ?>" min="0" 
                                               <?php echo !$sub['id'] ? 'disabled' : ''; ?>>
                                    </td>
                                    <td>
                                        <textarea name="feedback" class="feedback-area" rows="1" 
                                                  <?php echo !$sub['id'] ? 'disabled' : ''; ?>><?php echo htmlspecialchars($sub['feedback'] ?? ''); ?></textarea>
                                    </td>
                                    <td>
                                        <?php if ($sub['id']): ?>
                                            <button type="submit" class="btn-grade">Save Grade</button>
                                        <?php else: ?>
                                            <button type="button" class="btn-grade" disabled style="opacity: 0.5;">Save Grade</button>
                                        <?php endif; ?>
                                    </td>
                                </form>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
