<?php
require_once '../includes/session_manager.php';
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get student information - use session data instead of joining with user_types
$student = getRow("SELECT u.* FROM users u WHERE u.id = ?", [$user_id]);
if (!$student) {
    header('Location: ../login.php');
    exit;
}

// Get student enrollment information
$enrolled_courses = getRows("
    SELECT sc.name as sub_course_name, c.name as course_name, se.status as enrollment_status, se.enrollment_date, se.completion_date
    FROM student_enrollments se
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses c ON sc.course_id = c.id
    WHERE se.user_id = ?
    ORDER BY se.enrollment_date DESC
", [$user_id]);

// Get certificates for completed courses
$certificates = getRows("
    SELECT c.*, se.sub_course_id, sc.name as sub_course_name, co.name as course_name, cc.name as category_name
    FROM certificates c
    JOIN student_enrollments se ON c.enrollment_id = se.id
    JOIN sub_courses sc ON se.sub_course_id = sc.id
    JOIN courses co ON sc.course_id = co.id
    JOIN course_categories cc ON co.category_id = cc.id
    WHERE se.user_id = ?
    ORDER BY c.generated_at DESC
", [$user_id]);

$total_enrolled = count($enrolled_courses);
$active_courses = count(array_filter($enrolled_courses, fn($e) => $e['enrollment_status'] === 'enrolled'));
$completed_courses = count(array_filter($enrolled_courses, fn($e) => $e['enrollment_status'] === 'completed'));
$certificates_count = count($certificates);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - GICT Institute</title>
    <link rel="icon" type="image/png" href="../logo.png">
    <link rel="shortcut icon" type="image/png" href="../logo.png">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- html2canvas and jsPDF for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <style>
        /* Student-specific overrides to match admin dashboard exactly */
        .admin-sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .admin-topbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        /* Digital ID Badge */
        .digital-id-badge {
            position: absolute;
            bottom: -5px;
            right: -5px;
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: 2px solid #fff;
        }
        
        .digital-id-badge:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .digital-id-badge i {
            color: white;
            font-size: 14px;
        }
        
        .profile-card-mini {
            position: relative;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-weight: 600;
            font-size: 1.2rem;
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }
        
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .modal-body {
            padding: 2rem;
            text-align: center;
        }
        
        .modal-actions {
            padding: 1rem 2rem 2rem;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        
        .btn-success {
            background: #16a34a;
            color: white;
        }
        
        .btn-success:hover {
            background: #15803d;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }
        
        /* Feature items */
        .feature-item {
            text-align: center;
            padding: 1.5rem;
            border-radius: 8px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        
        .feature-item i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .feature-item h6 {
            margin: 0 0 0.5rem 0;
            font-weight: 600;
            color: #374151;
        }
        
        .feature-item p {
            margin: 0 0 1rem 0;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        /* Welcome section */
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .welcome-section h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .welcome-section p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        /* Course cards */
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .course-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
        }
        
        .course-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .course-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        
        .course-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-completed {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .course-details {
            margin-bottom: 1rem;
        }
        
        .course-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .course-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-info {
            background: #0891b2;
            color: white;
        }
        
        .btn-info:hover {
            background: #0e7490;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
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
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="courses.php"><i class="fas fa-book"></i> My Courses</a></li>
                <li><a href="documents.php"><i class="fas fa-file-upload"></i> Documents</a></li>
                <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
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
                    <span>Student Dashboard</span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1><i class="fas fa-user-graduate"></i> Welcome, <?php echo htmlspecialchars($student['full_name']); ?>!</h1>
                <p>This is your student dashboard. Here you can view your courses, documents, and manage your profile.</p>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_enrolled; ?></div>
                    <div class="stat-label">Enrolled Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $active_courses; ?></div>
                    <div class="stat-label">Active Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $completed_courses; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>

            <!-- Enrolled Courses -->
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-book"></i> Enrolled Courses</span>
                </div>
                <div class="panel-body">
                    <?php if (empty($enrolled_courses)): ?>
                        <p class="text-muted">You haven't enrolled in any courses yet.</p>
                    <?php else: ?>
                        <div class="course-grid">
                            <?php foreach ($enrolled_courses as $course): ?>
                                <div class="course-card">
                                    <div class="course-header">
                                        <h6 class="course-title"><?php echo htmlspecialchars($course['sub_course_name']); ?></h6>
                                        <span class="course-status status-<?php echo $course['enrollment_status']; ?>">
                                            <?php echo ucfirst($course['enrollment_status']); ?>
                                        </span>
                                    </div>
                                    <div class="course-details">
                                        <div class="course-detail">
                                            <i class="fas fa-book"></i>
                                            <span>Course: <?php echo htmlspecialchars($course['course_name']); ?></span>
                                        </div>
                                        <div class="course-detail">
                                            <i class="fas fa-calendar"></i>
                                            <span>Enrolled: <?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?></span>
                                        </div>
                                        <?php if ($course['completion_date']): ?>
                                            <div class="course-detail">
                                                <i class="fas fa-trophy"></i>
                                                <span>Completed: <?php echo date('M d, Y', strtotime($course['completion_date'])); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="course-actions">
                                        <button class="btn btn-info btn-sm">
                                            <i class="fas fa-play"></i> Continue Learning
                                        </button>
                                        <?php if ($course['enrollment_status'] === 'completed'): ?>
                                            <button class="btn btn-success btn-sm">
                                                <i class="fas fa-certificate"></i> View Certificate
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- My Certificates -->
            <div class="panel" style="margin-top: 1.5rem;">
                <div class="panel-header">
                    <span><i class="fas fa-certificate"></i> My Certificates (<?php echo $certificates_count; ?>)</span>
                </div>
                <div class="panel-body">
                    <?php if (empty($certificates)): ?>
                        <div class="text-center" style="padding: 2rem;">
                            <i class="fas fa-certificate" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                            <h5>No Certificates Yet</h5>
                            <p class="text-muted">Complete your courses to earn certificates!</p>
                        </div>
                    <?php else: ?>
                        <div class="certificates-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                            <?php foreach ($certificates as $cert): ?>
                                <div class="certificate-card" style="background: white; border: 1px solid #e9ecef; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    <div class="certificate-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                        <div>
                                            <h6 style="margin: 0; color: #333; font-weight: 600;"><?php echo htmlspecialchars($cert['sub_course_name']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($cert['course_name']); ?></small>
                                        </div>
                                        <span class="badge badge-success" style="background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px;">
                                            <?php echo ucfirst($cert['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="certificate-details" style="margin-bottom: 1rem;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                            <small class="text-muted">Certificate No:</small>
                                            <small style="font-weight: 600;"><?php echo htmlspecialchars($cert['certificate_number']); ?></small>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                            <small class="text-muted">Generated:</small>
                                            <small><?php echo date('M d, Y', strtotime($cert['generated_at'])); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="certificate-actions" style="display: flex; gap: 0.5rem;">
                                        <a href="../<?php echo $cert['certificate_url']; ?>" target="_blank" class="btn btn-success btn-sm" style="flex: 1; text-align: center; padding: 8px 12px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; font-size: 12px;">
                                            <i class="fas fa-certificate"></i> Certificate
                                        </a>
                                        <a href="../<?php echo $cert['marksheet_url']; ?>" target="_blank" class="btn btn-info btn-sm" style="flex: 1; text-align: center; padding: 8px 12px; background: #17a2b8; color: white; text-decoration: none; border-radius: 4px; font-size: 12px;">
                                            <i class="fas fa-file-alt"></i> Marksheet
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Digital ID Card -->
            <div class="panel" style="margin-top: 1.5rem;">
                <div class="panel-header">
                    <span><i class="fas fa-id-card"></i> Digital ID Card</span>
                </div>
                <div class="panel-body">
                    <div class="feature-item" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-id-card text-primary" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h6>Student ID Card</h6>
                        <p>Generate and download your digital student ID card</p>
                        <button onclick="generateIdCard()" class="btn btn-primary">
                            <i class="fas fa-id-card"></i> Generate ID Card
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- ID Card Modal -->
    <div id="idCardModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Digital ID Card</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="idCardContainer"></div>
            </div>
            <div class="modal-actions">
                <button id="modalDownloadBtn" onclick="downloadFromModal()" class="btn btn-success">
                    <i class="fas fa-download"></i> Download ID Card
                </button>
            </div>
        </div>
    </div>

    <script>
        // View ID function - shows modal with id.php content
        function viewID() {
            const modal = document.getElementById('idCardModal');
            const container = document.getElementById('idCardContainer');
            
            // Show the modal
            modal.classList.add('show');
            
            // Show loading
            container.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #667eea;"></i><p style="margin-top: 10px;">Loading ID Card...</p></div>';
            
            // Create form data - only send student ID
            const formData = new FormData();
            formData.append('student_id', '<?php echo $student['id']; ?>');
            
            // Fetch the content from id.php
            fetch('../id.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Create a temporary div to parse the HTML
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                // Find the ID card element
                const idCard = tempDiv.querySelector('#idCard');
                
                if (idCard) {
                    // Create the styled ID card
                    const styledCard = `
                        <div id="idCard" style="width: 340px; background: #fff; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); overflow: hidden; border: 1px solid #e5e7eb; padding-bottom: 1rem; margin: 0 auto;">
                            <div class="id-card-header" style="display: flex; align-items: center; background: #1d4ed8; color: #fff; padding: 1rem 1.5rem; border-radius: 8px 8px 0 0;">
                                <img src="../assets/images/logo.png" alt="Institute Logo" class="id-card-logo" style="height: 60px; width: 60px; border-radius: 50%; object-fit: cover; background: white; margin-right: 1rem; border: 2px solid #fff;" onerror="this.style.display='none'">
                                <div class="id-card-header-text">
                                    <h2 style="font-size: 1.4rem; margin: 0; font-weight: 700; line-height: 1.2;">GICT COMPUTER INSTITUTE</h2>
                                    <p style="font-size: 0.9rem; margin: 0.2rem 0 0; opacity: 0.9;">Student Identification Card</p>
                                </div>
                            </div>
                            <div class="id-card-body" style="padding: 1rem; text-align: center;">
                                <img src="${idCard.querySelector('.id-card-photo')?.src || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTIwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iIzY2N2VlYSIvPjx0ZXh0IHg9IjYwIiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjQ4IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+8J+RqDwvdGV4dD48L3N2Zz4='}" class="id-card-photo" alt="Student Photo" style="width: 120px; height: auto; border-radius: 2px; object-fit: cover; margin-bottom: 0.5rem; border: 3px solid #1d4ed8;">
                                <p class="id-card-name" style="font-size: 1.2rem; font-weight: 700; margin: 0.3rem 0;">${idCard.querySelector('.id-card-name')?.textContent || '<?php echo htmlspecialchars($student['full_name']); ?>'}</p>
                                <p class="id-card-studentid" style="font-size: 0.9rem; color: #374151; margin-bottom: 1rem;">STUDENT ID: ${idCard.querySelector('.id-card-studentid')?.textContent?.replace('STUDENT ID: ', '') || '<?php echo $student['id']; ?>'}</p>
                                <div class="id-card-row" style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 0.8rem;">
                                    <div class="id-card-info" style="text-align: left; font-size: 0.9rem;">
                                        <p style="margin: 0.4rem 0;"><span class="label" style="font-weight: 600; color: #1f2937;">Batch:</span> ${idCard.querySelector('.id-card-info .label')?.nextSibling?.textContent?.trim() || '<?php echo date('Y'); ?>'}</p>
                                        <p style="margin: 0.4rem 0;"><span class="label" style="font-weight: 600; color: #1f2937;">Expires:</span> ${idCard.querySelector('.id-card-info .label:last-child')?.nextSibling?.textContent?.trim() || '<?php echo date("m/Y", strtotime('+1 year')); ?>'}</p>
                                    </div>
                                    <img src="${idCard.querySelector('.id-card-qr')?.src || ''}" class="id-card-qr" alt="QR Code" style="width: 90px; height: 90px;">
                                </div>
                            </div>
                            <div class="id-card-footer" style="margin-top: 1rem; font-size: 0.75rem; text-align: center; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 0.5rem;">If found, please return to the university admin office.</div>
                        </div>
                    `;
                    
                    container.innerHTML = styledCard;
                } else {
                    container.innerHTML = '<p style="text-align: center; color: #666;">Error loading ID card</p>';
                }
            })
            .catch(error => {
                console.error('Error loading ID card:', error);
                container.innerHTML = '<p style="text-align: center; color: #666;">Error loading ID card</p>';
            });
        }
        
        // Close modal function
        function closeModal() {
            const modal = document.getElementById('idCardModal');
            modal.classList.remove('show');
        }
        
        // Download from modal function - directly generate PDF
        function downloadFromModal() {
            // Get the ID card element from the modal
            const idCardElement = document.getElementById('idCard');
            if (!idCardElement) {
                alert('ID card not found');
                return;
            }
            
            // Show loading
            const downloadBtn = document.querySelector('#modalDownloadBtn');
            if (downloadBtn) {
                downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
                downloadBtn.disabled = true;
            }
            
            // Generate PDF using html2canvas and jsPDF
            html2canvas(idCardElement, { 
                scale: 4, 
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                
                // Create PDF
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'px',
                    format: [idCardElement.offsetWidth, idCardElement.offsetHeight]
                });
                
                pdf.addImage(imgData, 'PNG', 0, 0, idCardElement.offsetWidth, idCardElement.offsetHeight);
                pdf.save('student-id-<?php echo $student['id']; ?>.pdf');
                
                // Reset button
                if (downloadBtn) {
                    downloadBtn.innerHTML = '<i class="fas fa-download"></i> Download ID Card';
                    downloadBtn.disabled = false;
                }
            }).catch(error => {
                console.error('Error generating PDF:', error);
                alert('Error generating PDF. Please try again.');
                
                // Reset button
                if (downloadBtn) {
                    downloadBtn.innerHTML = '<i class="fas fa-download"></i> Download ID Card';
                    downloadBtn.disabled = false;
                }
            });
        }
        
        // Close modal when clicking outside
        document.getElementById('idCardModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('mobile-open');
        }
    </script>
</body>
</html>
