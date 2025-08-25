<?php
require_once '../includes/session_manager.php';

// Check if user is student
if (!isStudent()) {
    header('Location: ../login.php');
    exit;
}

$student_id = $_SESSION['user_id'];
$institute_id = getCurrentInstituteId();
$institute = getCurrentInstitute();

// Get student data
$student = getUserById($student_id);

// Get enrolled courses with sub-course details
$enrolled_courses = getStudentEnrollments($student_id);

// Get student payments
$payments = getStudentPayments($student_id);

// Calculate statistics
$total_enrolled = count($enrolled_courses);
$active_courses = count(array_filter($enrolled_courses, function($course) {
    return $course['status'] === 'active';
}));
$completed_courses = count(array_filter($enrolled_courses, function($course) {
    return $course['status'] === 'completed';
}));

// Get student documents
$documents = getRows("
    SELECT * FROM student_documents 
    WHERE user_id = ? 
    ORDER BY uploaded_at DESC
", [$student_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo htmlspecialchars($institute['name']); ?></title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <style>
        /* Student-specific overrides */
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-weight: 600;
        }
        
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .course-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .course-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .course-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
        }
        
        .course-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .course-meta {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .course-body {
            padding: 1.5rem;
        }
        
        .course-details {
            margin-bottom: 1rem;
        }
        
        .course-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .course-detail:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #6b7280;
            font-weight: 600;
        }
        
        .detail-value {
            color: #374151;
            font-weight: 600;
        }
        
        .course-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-success {
            background: #059669;
            color: white;
        }
        
        .btn-success:hover {
            background: #047857;
        }
        
        .btn-warning {
            background: #d97706;
            color: white;
        }
        
        .btn-warning:hover {
            background: #b45309;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }
        
        .institute-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .institute-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .institute-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .institute-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .institute-detail i {
            width: 20px;
            opacity: 0.8;
        }
        
        /* Modal styles */
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
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
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
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
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
            <div class="topbar-right">
                <div class="user-chip">
                    <img src="<?php echo $student['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" alt="Profile" onerror="this.src='../assets/images/default-avatar.png'" />
                    <span><?php echo htmlspecialchars($student['full_name']); ?></span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-content">
            <!-- Institute Info -->
            <div class="institute-info">
                <div class="institute-name">
                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($institute['name']); ?>
                </div>
                <div class="institute-details">
                    <div class="institute-detail">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($institute['address']); ?></span>
                    </div>
                    <div class="institute-detail">
                        <i class="fas fa-phone"></i>
                        <span><?php echo htmlspecialchars($institute['phone']); ?></span>
                    </div>
                    <div class="institute-detail">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($institute['email']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
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
                    <span><i class="fas fa-book"></i> My Enrolled Courses</span>
                </div>
                <div class="panel-body">
                    <?php if (empty($enrolled_courses)): ?>
                        <div style="text-align: center; padding: 3rem 1rem;">
                            <i class="fas fa-book-open" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                            <p class="text-muted" style="font-size: 1.1rem; margin: 0;">No courses enrolled yet</p>
                            <p class="text-muted" style="margin: 0.5rem 0 0 0;">Contact your institute admin to enroll in courses</p>
                        </div>
                    <?php else: ?>
                        <div class="course-grid">
                            <?php foreach ($enrolled_courses as $course): ?>
                                <div class="course-card">
                                    <div class="course-header">
                                        <div class="course-name"><?php echo htmlspecialchars($course['sub_course_name']); ?></div>
                                        <div class="course-meta">
                                            <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($course['course_name']); ?>
                                        </div>
                                    </div>
                                    <div class="course-body">
                                        <div class="course-details">
                                            <div class="course-detail">
                                                <span class="detail-label">Course Category:</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($course['category']); ?></span>
                                            </div>
                                            <div class="course-detail">
                                                <span class="detail-label">Enrollment Date:</span>
                                                <span class="detail-value"><?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?></span>
                                            </div>
                                            <div class="course-detail">
                                                <span class="detail-label">Status:</span>
                                                <span class="detail-value">
                                                    <span class="status-badge status-<?php echo $course['status']; ?>">
                                                        <?php echo ucfirst($course['status']); ?>
                                                    </span>
                                                </span>
                                            </div>
                                            <?php if ($course['final_marks']): ?>
                                                <div class="course-detail">
                                                    <span class="detail-label">Final Marks:</span>
                                                    <span class="detail-value"><?php echo $course['final_marks']; ?>%</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="course-actions">
                                            <?php if ($course['status'] === 'active'): ?>
                                                <button class="btn btn-primary">
                                                    <i class="fas fa-play"></i> Continue Learning
                                                </button>
                                            <?php elseif ($course['status'] === 'completed' && $course['final_marks'] >= 60): ?>
                                                <button class="btn btn-success">
                                                    <i class="fas fa-certificate"></i> View Certificate
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Digital ID & Certificates -->
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-id-card"></i> Digital ID & Certificates</span>
                </div>
                <div class="panel-body">
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <button class="btn btn-primary" onclick="viewID()">
                            <i class="fas fa-eye"></i> View Digital ID
                        </button>
                        <button class="btn btn-success">
                            <i class="fas fa-download"></i> Download Certificate
                        </button>
                        <button class="btn btn-warning">
                            <i class="fas fa-list"></i> View Records
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- ID Card Modal -->
    <div id="idModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Digital ID Card</div>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
                    <p>Loading ID card...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="downloadFromModal()">
                    <i class="fas fa-download"></i> Download PDF
                </button>
                <button class="btn btn-warning" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('mobile-open');
        }
        
        function viewID() {
            const modal = document.getElementById('idModal');
            const modalBody = document.getElementById('modalBody');
            
            modal.style.display = 'block';
            modalBody.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i><p>Loading ID card...</p></div>';
            
            // Send request to id.php
            const formData = new FormData();
            formData.append('student_id', '<?php echo $student_id; ?>');
            
            fetch('../id.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Parse the HTML and extract the ID card content
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const idCard = doc.getElementById('idCard');
                
                if (idCard) {
                    // Extract styles and content
                    const styles = idCard.getAttribute('style') || '';
                    const content = idCard.innerHTML;
                    
                    modalBody.innerHTML = `
                        <div id="idCardContent" style="${styles}">
                            ${content}
                        </div>
                    `;
                } else {
                    modalBody.innerHTML = '<div style="text-align: center; padding: 2rem; color: #dc2626;"><i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i><p>Error loading ID card</p></div>';
                }
            })
            .catch(error => {
                modalBody.innerHTML = '<div style="text-align: center; padding: 2rem; color: #dc2626;"><i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i><p>Error loading ID card</p></div>';
            });
        }
        
        function closeModal() {
            document.getElementById('idModal').style.display = 'none';
        }
        
        function downloadFromModal() {
            const idCardContent = document.getElementById('idCardContent');
            if (!idCardContent) return;
            
            html2canvas(idCardContent, {
                scale: 2,
                useCORS: true,
                allowTaint: true
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jspdf.jsPDF();
                const imgWidth = 210;
                const pageHeight = 295;
                const imgHeight = canvas.height * imgWidth / canvas.width;
                let heightLeft = imgHeight;
                
                let position = 0;
                
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
                
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }
                
                pdf.save('student-id-card.pdf');
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('idModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
