<?php
require_once '../includes/session_manager.php';
require_once '../config/database.php';

// Check if user is logged in and is faculty
if (!isLoggedIn() || !isFaculty()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle assessment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_assignment') {
    $sub_course_id = intval($_POST['sub_course_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];
    $max_score = intval($_POST['max_score'] ?: 100);

    try {
        if (empty($title))
            throw new Exception("Title is required.");
        if ($sub_course_id <= 0)
            throw new Exception("Please select a course.");

        $sql = "INSERT INTO assignments (sub_course_id, title, description, due_date, max_score, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $result = insertData($sql, [$sub_course_id, $title, $description, $due_date, $max_score, $user_id]);

        if ($result) {
            $success_message = "Assignment created successfully!";
        } else {
            throw new Exception("Failed to create assignment.");
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get assigned courses
$assigned_courses = getRows("
    SELECT sc.id, sc.name as sub_course_name 
    FROM sub_courses sc
    JOIN faculty_courses fc ON sc.id = fc.sub_course_id
    WHERE fc.faculty_id = ? AND fc.status = 'active'
", [$user_id]);

// Get existing assignments
$assignments = getRows("
    SELECT a.*, sc.name as sub_course_name,
           (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id) as submission_count
    FROM assignments a
    JOIN sub_courses sc ON a.sub_course_id = sc.id
    WHERE a.created_by = ?
    ORDER BY a.created_at DESC
", [$user_id]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assignments - Faculty Portal</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .assignments-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .assignment-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            border-left: 4px solid #0f6fb1;
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 20px;
        }

        .assignment-info h3 {
            margin: 0 0 5px 0;
            color: #1e293b;
        }

        .assignment-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #64748b;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .assignment-actions {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-view {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-view:hover {
            background: #e2e8f0;
        }

        .btn-create {
            background: #0f6fb1;
            color: white;
            padding: 10px 20px;
        }

        .btn-create:hover {
            background: #0d5a8f;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
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
                <li><a class="active" href="#"><i class="fas fa-tasks"></i> Assignments</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <div class="assignments-header">
                <h1><i class="fas fa-tasks"></i> Assignment Management</h1>
                <button class="btn-action btn-create" onclick="openModal()">
                    <i class="fas fa-plus"></i> Create New Assignment
                </button>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="assignments-list">
                <?php if (empty($assignments)): ?>
                    <div class="no-data">
                        <i class="fas fa-clipboard-list" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                        <h3>No Assignments Created</h3>
                        <p>You haven't created any assignments yet. Click the button above to start.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <div class="assignment-card">
                            <div class="assignment-info">
                                <h3>
                                    <?php echo htmlspecialchars($assignment['title']); ?>
                                </h3>
                                <div class="assignment-meta">
                                    <span class="meta-item"><i class="fas fa-graduation-cap"></i>
                                        <?php echo htmlspecialchars($assignment['sub_course_name']); ?>
                                    </span>
                                    <span class="meta-item"><i class="fas fa-calendar-alt"></i> Due:
                                        <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?>
                                    </span>
                                    <span class="meta-item"><i class="fas fa-file-alt"></i> Submissions:
                                        <?php echo $assignment['submission_count']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="assignment-actions">
                                <a href="grade-assignment.php?id=<?php echo $assignment['id']; ?>" class="btn-action btn-view">
                                    <i class="fas fa-graduation-cap"></i> Grade Submissions
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Create Assignment Modal -->
    <div id="assignmentModal" class="modal">
        <div class="modal-content">
            <div class="assignments-header">
                <h2>Create New Assignment</h2>
                <i class="fas fa-times" style="cursor: pointer;" onclick="closeModal()"></i>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create_assignment">
                <div class="form-group">
                    <label>Select Course</label>
                    <select name="sub_course_id" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($assigned_courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>">
                                <?php echo htmlspecialchars($course['sub_course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Assignment Title</label>
                    <input type="text" name="title" required placeholder="e.g., Basic PHP Exercises">
                </div>
                <div class="form-group">
                    <label>Description / Instructions</label>
                    <textarea name="description" rows="4"
                        placeholder="Detail the assignment requirements..."></textarea>
                </div>
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="datetime-local" name="due_date" required>
                </div>
                <div class="form-group">
                    <label>Maximum Score</label>
                    <input type="number" name="max_score" value="100" min="1">
                </div>
                <div style="text-align: right;">
                    <button type="button" class="btn-action btn-view" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-action btn-create">Create Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() { document.getElementById('assignmentModal').classList.add('show'); }
        function closeModal() { document.getElementById('assignmentModal').classList.remove('show'); }
    </script>
</body>

</html>