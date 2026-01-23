<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

$user = getCurrentUser();

// Handle certificate generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'generate_certificate':
                $encryption_key = "GICT_SECURE_KEY_2026";
                $cipher_method = "aes-256-cbc";
                $enrollment_id = intval($_POST['enrollment_id']);

                // Get enrollment details
                $enrollment = getRow("
                    SELECT se.*, sc.name as sub_course_name, c.name as course_name, u.full_name as student_name,
                           u.email as student_email, u.phone as student_phone
                    FROM student_enrollments se
                    JOIN sub_courses sc ON se.sub_course_id = sc.id
                    JOIN courses c ON sc.course_id = c.id
                    JOIN users u ON se.user_id = u.id
                    WHERE se.id = ? AND se.status = 'completed'
                ", [$enrollment_id]);

                if (!$enrollment) {
                    throw new Exception("Enrollment not found or not eligible for certificate generation.");
                }

                // Check if certificate already exists
                $existing_certificate = getRow("
                    SELECT id FROM certificates WHERE enrollment_id = ?
                ", [$enrollment_id]);

                if ($existing_certificate) {
                    throw new Exception("Certificate already generated for this enrollment.");
                }

                // Generate certificate number (12-digit random alphanumeric)
                $certificate_number = generateUniqueNumber(12);

                // Get student marks for certificate
                $marks = getRows("
                    SELECT sm.*, cs.subject_name, cs.max_marks
                    FROM student_marks sm
                    JOIN course_subjects cs ON sm.subject_id = cs.id
                    WHERE sm.enrollment_id = ?
                    ORDER BY cs.id
                ", [$enrollment_id]);

                // Calculate total marks and percentage
                $total_marks = 0;
                $obtained_marks = 0;
                foreach ($marks as $mark) {
                    $total_marks += $mark['max_marks'];
                    $obtained_marks += $mark['total_marks'];
                }
                $percentage = $total_marks > 0 ? round(($obtained_marks / $total_marks) * 100, 2) : 0;

                // Generate certificate and marksheet
                $certificate_data = [
                    'student_name' => $enrollment['student_name'],
                    'course_name' => $enrollment['sub_course_name'],
                    'main_course' => $enrollment['course_name'],
                    'certificate_number' => $certificate_number,
                    'completion_date' => $enrollment['completion_date'],
                    'percentage' => $percentage,
                    'marks' => $marks,
                    'total_marks' => $total_marks,
                    'obtained_marks' => $obtained_marks
                ];

                // Generate certificate file (placeholder - you can implement actual PDF generation)
                $certificate_url = generateCertificate($certificate_data);
                $marksheet_url = generateMarksheet($certificate_data);

                // Save certificate record (Removed URL columns)
                $certificate_sql = "INSERT INTO certificates (enrollment_id, certificate_number, generated_by, status, generated_at) VALUES (?, ?, ?, 'generated', CURRENT_TIMESTAMP)";
                $result = insertData($certificate_sql, [
                    $enrollment_id,
                    $certificate_number,
                    $user['id']
                ]);

                if ($result) {
                    $success_message = "Certificate generated successfully! Certificate Number: {$certificate_number}";
                } else {
                    $error_message = "Failed to save certificate record.";
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Handle Search
$search = trim($_GET['search'] ?? '');
$params = [];
$where_clause = "WHERE se.status = 'completed'";

if (!empty($search)) {
    $where_clause .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR cert.certificate_number LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Get completed enrollments
$completed_enrollments = getRows("
    SELECT se.*, sc.name as sub_course_name, c.name as course_name, cc.name as category_name,
           u.full_name as student_name, u.username as enrollment_no, u.date_of_birth,
           u.email as student_email, u.phone as student_phone,
           cert.certificate_number, cert.generated_at, cert.status as cert_status
    FROM student_enrollments se
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    JOIN course_categories cc ON c.category_id = cc.id
    JOIN users u ON se.user_id = u.id
    LEFT JOIN certificates cert ON se.id = cert.enrollment_id
    $where_clause
    ORDER BY se.completion_date DESC
", $params);

// Get statistics
$stats = getRow("
    SELECT 
        COUNT(*) as total_completed,
        COUNT(cert.id) as certificates_generated,
        COUNT(*) - COUNT(cert.id) as pending_certificates
    FROM student_enrollments se
    LEFT JOIN certificates cert ON se.id = cert.enrollment_id
    WHERE se.status = 'completed'
");

$encryption_key = "GICT_SECURE_KEY_2026";
$cipher_method = "aes-256-cbc";
?>

<?php
// Certificate generation functions (placeholder implementations)
function generateCertificate($data)
{
    // This is a placeholder function
    // In a real implementation, you would generate a PDF certificate
    $filename = 'certificate_' . $data['certificate_number'] . '.pdf';
    $filepath = '../assets/generated_certificates/' . $filename;

    // Create directory if it doesn't exist
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // For now, just create a placeholder file
    file_put_contents($filepath, "Certificate placeholder for " . $data['student_name']);

    return 'assets/generated_certificates/' . $filename;
}

function generateMarksheet($data)
{
    // This is a placeholder function
    // In a real implementation, you would generate a PDF marksheet
    $filename = 'marksheet_' . $data['certificate_number'] . '.pdf';
    $filepath = '../assets/generated_marksheets/' . $filename;

    // Create directory if it doesn't exist
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // For now, just create a placeholder file
    file_put_contents($filepath, "Marksheet placeholder for " . $data['student_name']);

    return 'assets/generated_marksheets/' . $filename;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Management - GICT Admin</title>
    <link rel="icon" type="image/png" href="../logo.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 24px;
        }

        .stat-card p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .stat-card.primary {
            border-left: 4px solid #007bff;
        }

        .stat-card.success {
            border-left: 4px solid #28a745;
        }

        .stat-card.warning {
            border-left: 4px solid #ffc107;
        }

        .certificate-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .certificate-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .certificate-title {
            font-weight: bold;
            color: #333;
            font-size: 16px;
        }

        .certificate-date {
            color: #666;
            font-size: 14px;
        }

        .certificate-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            align-items: flex-end;
            margin-bottom: 0;
        }

        .detail-group {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .detail-value {
            font-weight: 500;
            color: #333;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn {
            padding: 8px 16px;
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

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-generated {
            background: #d4edda;
            color: #155724;
        }

        .status-issued {
            background: #cce5ff;
            color: #004085;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .no-certificates {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-certificates i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #ddd;
        }
    </style>
</head>

<body class="admin-dashboard-body">
    <div class="admin-layout">
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

            <ul class="sidebar-nav">
                <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-user-tie"></i> Staff</a></li>
                <li><a href="courses.php"><i class="fas fa-graduation-cap"></i> Courses</a></li>
                <li><a href="marks-management.php"><i class="fas fa-chart-line"></i> Marks Management</a></li>
                <li><a class="active" href="certificate-management.php"><i class="fas fa-certificate"></i> Certificate
                        Management</a></li>
                <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="inquiries.php"><i class="fas fa-question-circle"></i> Course Inquiries</a></li>
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
                    <span>Certificate Management</span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="page-header">
                <h1><i class="fas fa-certificate"></i> Certificate Management</h1>
                <p style="color: #64748b; margin-top: 5px;">Generate and manage certificates for completed courses</p>
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

            <!-- Statistics Section -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <h3><?php echo $stats['total_completed']; ?></h3>
                    <p>Completed Courses</p>
                </div>
                <div class="stat-card success">
                    <h3><?php echo $stats['certificates_generated']; ?></h3>
                    <p>Certificates Generated</p>
                </div>
                <div class="stat-card warning">
                    <h3><?php echo $stats['pending_certificates']; ?></h3>
                    <p>Pending Certificates</p>
                </div>
            </div>

            <!-- Certificates Section -->
            <div class="approval-section">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                    <h2
                        style="font-size: 18px; color: #1e293b; display: flex; align-items: center; gap: 10px; margin: 0;">
                        <i class="fas fa-list-ul" style="color: #0f6fb1;"></i> Course Completions
                        <?php if (!empty($search)): ?>
                            <span
                                style="font-size: 12px; font-weight: normal; color: #64748b; background: #f1f5f9; padding: 2px 10px; border-radius: 12px; margin-left: 10px;">Results
                                for: "<?php echo htmlspecialchars($search); ?>"</span>
                        <?php endif; ?>
                    </h2>

                    <form method="GET" style="display: flex; gap: 8px; width: 320px;">
                        <div style="position: relative; flex: 1;">
                            <i class="fas fa-search"
                                style="position: absolute; left: 10px; top: 10px; color: #94a3b8; font-size: 13px;"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Search Name or Reg No..."
                                style="width: 100%; padding: 8px 12px 8px 30px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 13px; background: #fff;">
                        </div>
                        <button type="submit" class="btn btn-primary"
                            style="height: 34px; padding: 0 15px; font-size: 13px; display: flex; align-items: center;">Search</button>
                        <?php if (!empty($search)): ?>
                            <a href="certificate-management.php" class="btn"
                                style="background: #f1f5f9; color: #64748b; height: 34px; padding: 0 12px; display: flex; align-items: center; font-size: 13px;"><i
                                    class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="section-content">
                    <?php if (empty($completed_enrollments)): ?>
                        <div class="no-certificates">
                            <i class="fas fa-certificate"></i>
                            <h3>No Completed Courses</h3>
                            <p>No students have completed courses yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($completed_enrollments as $enrollment): ?>
                            <div class="certificate-item">
                                <div class="certificate-header">
                                    <div class="certificate-title">
                                        <?php echo htmlspecialchars($enrollment['student_name']); ?> -
                                        <?php echo htmlspecialchars($enrollment['sub_course_name']); ?>
                                    </div>
                                    <div class="certificate-date">
                                        Completed: <?php echo date('M d, Y', strtotime($enrollment['completion_date'])); ?>
                                    </div>
                                </div>
                                <div class="certificate-details">
                                    <div class="detail-group">
                                        <div class="detail-label">Course</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($enrollment['course_name']); ?>
                                        </div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label">Registration No.</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($enrollment['enrollment_no']); ?>
                                        </div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label">Certificate Number</div>
                                        <div class="detail-value" style="color: #c5a059; font-weight: bold;">
                                            <?php if ($enrollment['certificate_number']): ?>
                                                <?php echo htmlspecialchars($enrollment['certificate_number']); ?>
                                            <?php else: ?>
                                                <span style="color: #666;">Not generated</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label">Status</div>
                                        <div class="detail-value">
                                            <?php if ($enrollment['cert_status']): ?>
                                                <span class="status-badge status-<?php echo $enrollment['cert_status']; ?>">
                                                    <?php echo ucfirst($enrollment['cert_status']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">Pending</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label">Generated Date</div>
                                        <div class="detail-value">
                                            <?php if ($enrollment['generated_at']): ?>
                                                <?php echo date('M d, Y H:i', strtotime($enrollment['generated_at'])); ?>
                                            <?php else: ?>
                                                <span style="color: #666;">Not generated</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="detail-group">
                                        <div class="detail-label" style="height: 18px;"></div>
                                        <div class="detail-value">
                                            <?php if ($enrollment['certificate_number']): ?>
                                                <?php
                                                $plaintext = $enrollment['enrollment_no'] . '|' . $enrollment['date_of_birth'];
                                                $iv_length = openssl_cipher_iv_length($cipher_method);
                                                $iv = openssl_random_pseudo_bytes($iv_length);
                                                $encrypted = openssl_encrypt($plaintext, $cipher_method, $encryption_key, 0, $iv);
                                                $cert_token = urlencode(base64_encode($encrypted . '::' . $iv));
                                                ?>
                                                <a href="../certificate.php?token=<?php echo $cert_token; ?>" target="_blank"
                                                    class="btn btn-info"
                                                    style="width: 100%; justify-content: center; height: 35px; white-space: nowrap;">
                                                    <i class="fas fa-certificate"></i> View Certificate
                                                </a>
                                            <?php else: ?>
                                                <form method="POST" style="display: block;">
                                                    <input type="hidden" name="action" value="generate_certificate">
                                                    <input type="hidden" name="enrollment_id"
                                                        value="<?php echo $enrollment['id']; ?>">
                                                    <button type="submit" class="btn btn-success"
                                                        style="width: 100%; justify-content: center; height: 35px; white-space: nowrap;"
                                                        onclick="return confirm('Are you sure you want to generate certificate for this student?')">
                                                        <i class="fas fa-certificate"></i> Generate
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/mobile-menu.js"></script>
</body>

</html>