<?php
require_once '../includes/session_manager.php';
require_once '../config/database.php';

// Check if user is logged in and is student
if (!isLoggedIn() || !isStudent()) {
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

// Get student information for sidebar
$student = getRow("SELECT u.* FROM users u WHERE u.id = ?", [$user_id]);

// Get assignment details
$assignment = getRow("
    SELECT a.*, sc.name as sub_course_name 
    FROM assignments a
    JOIN sub_courses sc ON a.sub_course_id = sc.id
    JOIN student_enrollments se ON sc.id = se.sub_course_id
    WHERE a.id = ? AND se.user_id = ? AND se.status = 'enrolled' AND a.status = 'active'
", [$assignment_id, $user_id]);

if (!$assignment) {
    header('Location: assignments.php');
    exit;
}

// Check if already submitted
$enrollment = getRow("SELECT id FROM student_enrollments WHERE user_id = ? AND sub_course_id = ?", [$user_id, $assignment['sub_course_id']]);
$enrollment_id = $enrollment['id'];

$submission = getRow("SELECT * FROM assignment_submissions WHERE assignment_id = ? AND enrollment_id = ?", [$assignment_id, $enrollment_id]);
if ($submission) {
    header('Location: assignments.php');
    exit;
}

$current_page = 'assignments.php';

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_text = trim($_POST['submission_text'] ?? '');
    try {
        if (strtotime($assignment['due_date']) < time())
            throw new Exception("Deadline passed.");
        if (empty($submission_text) && empty($_FILES['assignment_file']['name']))
            throw new Exception("Please provide content.");

        $file_url = '';
        if (!empty($_FILES['assignment_file']['name'])) {
            $upload_dir = '../uploads/assignments/';
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0755, true);
            $fn = 'sub_' . $user_id . '_' . $assignment_id . '_' . time() . '.' . pathinfo($_FILES['assignment_file']['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $upload_dir . $fn))
                $file_url = 'uploads/assignments/' . $fn;
        }

        insertData("INSERT INTO assignment_submissions (assignment_id, enrollment_id, submission_text, file_url, status) VALUES (?, ?, ?, ?, 'submitted')", [$assignment_id, $enrollment_id, $submission_text, $file_url]);
        $success_message = "Assignment submitted successfully!";
        header("Refresh: 2; URL=assignments.php");
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit - Student Portal</title>
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
                    <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                    <div class="breadcrumbs">
                        <a href="assignments.php">Assignments</a> / <span>Submission Panel</span>
                    </div>
                </div>
            </header>

            <main class="admin-content">
                <div class="panel" style="max-width: 800px; margin: 0 auto;">
                    <div class="panel-header"
                        style="display: flex; justify-content: space-between; align-items: center;">
                        <h1><i class="fas fa-file-upload"></i> Submitting:
                            <?php echo htmlspecialchars($assignment['title']); ?></h1>
                    </div>
                    <div class="panel-body">
                        <?php if ($success_message): ?>
                            <div
                                style="padding: 20px; border-radius: 12px; margin-bottom: 20px; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; text-align: center;">
                                <i class="fas fa-check-circle"
                                    style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                <?php echo $success_message; ?>
                            </div>
                        <?php elseif ($error_message): ?>
                            <div
                                style="padding: 15px; border-radius: 12px; margin-bottom: 20px; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!$success_message): ?>
                            <p style="color: #ef4444; font-size: 0.85rem; font-weight: 700; margin-bottom: 25px;">
                                <i class="fas fa-clock"></i> Deadline:
                                <?php echo date('M d, Y h:i A', strtotime($assignment['due_date'])); ?>
                            </p>

                            <form method="POST" enctype="multipart/form-data">
                                <div style="margin-bottom: 20px;">
                                    <label
                                        style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px;">Response
                                        / Theory</label>
                                    <textarea name="submission_text"
                                        placeholder="Type your answer or any notes for the instructor..."
                                        style="width: 100%; min-height: 200px; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; font-family: inherit; resize: vertical;"></textarea>
                                </div>

                                <div style="margin-bottom: 30px;">
                                    <label
                                        style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px;">Supporting
                                        Document (Optional)</label>
                                    <input type="file" name="assignment_file"
                                        style="width: 100%; padding: 15px; border: 2px dashed #cbd5e1; border-radius: 12px; cursor: pointer; background: #f8fafc;">
                                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 8px;">Supported formats: PDF,
                                        DOCX, ZIP, JPG, PNG (Max 5MB)</p>
                                </div>

                                <div style="display: flex; gap: 15px;">
                                    <button type="submit" class="btn-primary"
                                        style="flex: 1; justify-content: center; height: 50px;">Finalize Submission</button>
                                    <a href="assignments.php" class="btn-primary"
                                        style="background: #f1f5f9; color: #475569; box-shadow: none; border: 1px solid #e2e8f0; height: 50px; justify-content: center;">Discard</a>
                                </div>
                            </form>
                        <?php endif; ?>
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