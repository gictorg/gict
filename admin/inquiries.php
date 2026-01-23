<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

$user = getCurrentUser();

// Handle inquiry status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $inquiry_id = intval($_POST['inquiry_id'] ?? 0);

    try {
        switch ($action) {
            case 'update_status':
                $status = $_POST['status'] ?? '';
                if ($inquiry_id && in_array($status, ['new', 'contacted', 'enrolled', 'closed'])) {
                    $sql = "UPDATE inquiries SET status = ?, updated_at = NOW() WHERE id = ?";
                    $result = updateData($sql, [$status, $inquiry_id]);

                    if ($result) {
                        $success_message = "Inquiry status updated successfully!";
                    } else {
                        $error_message = "Failed to update inquiry status.";
                    }
                }
                break;

            case 'delete_inquiry':
                if ($inquiry_id) {
                    $sql = "DELETE FROM inquiries WHERE id = ?";
                    $result = deleteData($sql, [$inquiry_id]);

                    if ($result) {
                        $success_message = "Inquiry deleted successfully!";
                    } else {
                        $error_message = "Failed to delete inquiry.";
                    }
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get inquiries with course information
$inquiries = getRows("
    SELECT i.*, c.name as course_name, sc.name as sub_course_name
    FROM inquiries i
    LEFT JOIN courses c ON i.course_id = c.id
    LEFT JOIN sub_courses sc ON i.sub_course_id = sc.id
    ORDER BY i.created_at DESC
");

// Get statistics
$stats = getRow("
    SELECT 
        COUNT(CASE WHEN status = 'new' THEN 1 END) as new_count,
        COUNT(CASE WHEN status = 'contacted' THEN 1 END) as contacted_count,
        COUNT(CASE WHEN status = 'enrolled' THEN 1 END) as enrolled_count,
        COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_count,
        COUNT(*) as total_count
    FROM inquiries
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiry Management - GICT Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 25px 0;
            border-bottom: 2px solid #e9ecef;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header h1 i {
            color: #667eea;
            font-size: 32px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            border: 1px solid #e9ecef;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .stat-card h3 {
            margin: 0 0 8px 0;
            font-size: 32px;
            font-weight: 800;
            color: #333;
        }

        .stat-card p {
            margin: 0;
            color: #6c757d;
            font-weight: 600;
            letter-spacing: .3px;
            text-transform: uppercase;
            font-size: 12px;
        }

        .table-responsive {
            overflow-x: auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .table th {
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            white-space: nowrap;
        }

        .table td {
            padding: 16px 12px;
            vertical-align: middle;
            color: #374151;
            font-size: 14px;
            border-bottom: 1px solid #f1f5f9;
        }

        .table tbody tr:hover {
            background-color: #f8fafc;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-new {
            background: #fef3c7;
            color: #92400e;
        }

        .status-contacted {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-enrolled {
            background: #d1fae5;
            color: #065f46;
        }

        .status-closed {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: #007bff;
            color: #fff;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-danger {
            background: #dc3545;
            color: #fff;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error,
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }

            .table {
                font-size: 12px;
            }

            .table th,
            .table td {
                padding: 12px 8px;
            }
        }
    </style>
</head>

<body class="admin-dashboard-body">
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img src="../assets/images/logo.png" alt="logo" />
                <div class="brand-title">GICT CONTROL</div>
            </div>
            <div class="profile-card-mini">
                <img src="<?php echo $user['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" alt="Profile"
                    onerror="this.src='../assets/images/default-avatar.png'" />
                <div>
                    <div class="name"><?php echo htmlspecialchars(strtoupper($user['full_name'])); ?></div>
                    <div class="role"><?php echo htmlspecialchars(ucfirst($user['type'])); ?></div>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-user-tie"></i> Staff</a></li>
                <li><a href="courses.php"><i class="fas fa-graduation-cap"></i> Courses</a></li>
                <li><a href="marks-management.php"><i class="fas fa-chart-line"></i> Marks Management</a></li>
                <li><a href="certificate-management.php"><i class="fas fa-certificate"></i> Certificate Management</a>
                </li>
                <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a class="active" href="inquiries.php"><i class="fas fa-question-circle"></i> Course Inquiries</a>
                </li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="menu-toggle"><i class="fas fa-bars"></i></button>
                <div class="breadcrumbs">
                    <a href="../index.php" class="topbar-home-link"><i class="fas fa-home"></i> Home</a>
                    <span style="opacity:.7; margin: 0 6px;">/</span>
                    <a href="../dashboard.php">Dashboard</a>
                    <span style="opacity:.7; margin: 0 6px;">/</span>
                    <span>Course Inquiries</span>
                </div>
            </div>
        </header>

        <main class="admin-content">
            <div class="page-header">
                <h1><i class="fas fa-question-circle"></i> Inquiry Management</h1>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $stats['new_count']; ?></h3>
                    <p>New Inquiries</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['contacted_count']; ?></h3>
                    <p>Contacted</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['enrolled_count']; ?></h3>
                    <p>Enrolled</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['closed_count']; ?></h3>
                    <p>Closed</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Course</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inquiries)): ?>
                            <tr>
                                <td colspan="7" class="text-muted">
                                    <i class="fas fa-inbox" style="font-size: 24px;"></i><br>
                                    No inquiries have been submitted yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inquiries as $inquiry): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($inquiry['name']); ?></td>
                                    <td><?php echo htmlspecialchars($inquiry['mobile']); ?></td>
                                    <td><?php echo htmlspecialchars($inquiry['email'] ?: 'N/A'); ?></td>
                                    <td>
                                        <?php if ($inquiry['sub_course_name']): ?>
                                            <?php echo htmlspecialchars($inquiry['sub_course_name']); ?>
                                            <?php if ($inquiry['course_name']): ?>
                                                <br><small
                                                    class="text-muted"><?php echo htmlspecialchars($inquiry['course_name']); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">No specific course</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span
                                            class="status-badge status-<?php echo $inquiry['status']; ?>"><?php echo ucfirst($inquiry['status']); ?></span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($inquiry['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-primary"
                                                onclick="viewInquiry(<?php echo $inquiry['id']; ?>)"><i class="fas fa-eye"></i>
                                                View</button>
                                            <button class="btn btn-warning"
                                                onclick="updateStatus(<?php echo $inquiry['id']; ?>, '<?php echo $inquiry['status']; ?>')"><i
                                                    class="fas fa-edit"></i> Status</button>
                                            <button class="btn btn-danger"
                                                onclick="deleteInquiry(<?php echo $inquiry['id']; ?>)"><i
                                                    class="fas fa-trash"></i> Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Inquiry Status</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="inquiry_id" id="status_inquiry_id">
                <div class="form-group">
                    <label for="status">New Status</label>
                    <select name="status" id="status" required>
                        <option value="new">New</option>
                        <option value="contacted">Contacted</option>
                        <option value="enrolled">Enrolled</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div style="text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Inquiry Details Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Inquiry Details</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div id="inquiryDetails"></div>
        </div>
    </div>

    <script src="../assets/js/mobile-menu.js"></script>
    <script>
        function updateStatus(inquiryId, currentStatus) {
            document.getElementById('status_inquiry_id').value = inquiryId;
            document.getElementById('status').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }

        function viewInquiry(inquiryId) {
            // In a real implementation, you would fetch inquiry details via AJAX
            // For now, we'll show a simple message
            document.getElementById('inquiryDetails').innerHTML = `
                <p><strong>Inquiry ID:</strong> ${inquiryId}</p>
                <p><strong>Status:</strong> <span class="status-badge status-new">New</span></p>
                <p><strong>Created:</strong> ${new Date().toLocaleString()}</p>
                <p><strong>Message:</strong> This is a sample inquiry message.</p>
            `;
            document.getElementById('viewModal').style.display = 'block';
        }

        function deleteInquiry(inquiryId) {
            if (confirm('Are you sure you want to delete this inquiry?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_inquiry">
                    <input type="hidden" name="inquiry_id" value="${inquiryId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeModal() {
            document.getElementById('statusModal').style.display = 'none';
            document.getElementById('viewModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const statusModal = document.getElementById('statusModal');
            const viewModal = document.getElementById('viewModal');
            if (event.target === statusModal) {
                statusModal.style.display = 'none';
            }
            if (event.target === viewModal) {
                viewModal.style.display = 'none';
            }
        }
    </script>
</body>

</html>