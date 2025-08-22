<?php
require_once '../includes/session_manager.php';
require_once '../config/database.php';
require_once '../includes/qr_helper.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get student information
$student = getRow("SELECT * FROM users WHERE id = ? AND user_type = 'student'", [$user_id]);

// Generate QR code
$qrCode = generateQRCode($student['id'], $student['full_name'], 80);
if (!$student) {
    header('Location: ../login.php');
    exit;
}

// Get student enrollment information
$enrollments = getRows("
    SELECT c.name as course_name, se.status, se.enrollment_date
    FROM student_enrollments se
    JOIN courses c ON se.course_id = c.id
    WHERE se.user_id = ?
    ORDER BY se.enrollment_date DESC
", [$user_id]);

$total_courses = count($enrollments);
$active_courses = count(array_filter($enrollments, fn($e) => $e['status'] === 'active'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Digital ID - Student Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .id-viewer {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .digital-id-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4);
            position: relative;
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .id-display-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
        }
        
        .id-display-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
            font-weight: 700;
        }
        
        .id-display-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }
        
        .id-display-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            align-items: center;
        }
        
        .id-photo-section {
            text-align: center;
        }
        
        .id-photo-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            overflow: hidden;
            border: 4px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .id-photo-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .id-photo-placeholder-large {
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }
        
        .id-details-large {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
        }
        
        .id-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .id-detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .detail-label {
            font-weight: 600;
            opacity: 0.9;
        }
        
        .detail-value {
            font-weight: 700;
            font-size: 18px;
        }
        
        .id-actions-large {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            justify-content: center;
        }
        
        .btn-large {
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .btn-large:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .id-info-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .id-info-section h3 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        
        .info-card h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
        }
        
        .info-card .number {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .info-card .label {
            color: #666;
            font-size: 14px;
        }
        
        .security-features {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
        }
        
        .security-features h3 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .security-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .security-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .security-item i {
            color: #28a745;
            font-size: 18px;
        }
        
        .security-item span {
            color: #333;
            font-size: 14px;
        }
        
        /* QR Code Section */
        .qr-code-section {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
            border: 2px solid #e9ecef;
        }
        
        .qr-code-section h4 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }
        
        .qr-code-section .qr-code {
            margin: 0 auto 15px auto;
        }
        
        .qr-info {
            margin: 0;
            color: #666;
            font-size: 14px;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .id-display-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .id-actions-large {
                flex-direction: column;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php 
        $page_title = 'Digital ID Card';
        include 'includes/sidebar.php'; 
        ?>
        
        <?php include 'includes/topbar.php'; ?>
        
        <!-- Main Content -->
        <main class="admin-content">
            <div class="id-viewer">
                <!-- Digital ID Display -->
                <div class="digital-id-display">
                    <div class="id-display-header">
                        <h1><i class="fas fa-id-card"></i> Digital Student ID Card</h1>
                        <p>GICT Institute - Official Student Identification</p>
                    </div>
                    
                                               <div class="id-display-content">
                               <div class="id-photo-section">
                                   <?php if (!empty($student['profile_image']) && filter_var($student['profile_image'], FILTER_VALIDATE_URL)): ?>
                                       <div class="id-photo-large">
                                           <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Student Photo">
                                       </div>
                                   <?php else: ?>
                                       <div class="id-photo-large">
                                           <div class="id-photo-placeholder-large">
                                               <i class="fas fa-user"></i>
                                           </div>
                                       </div>
                                   <?php endif; ?>
                               </div>
                               
                               <!-- QR Code Display -->
                               <div class="qr-code-section">
                                   <h4>Verification QR Code</h4>
                                   <?php echo $qrCode; ?>
                                   <p class="qr-info">Scan to verify student identity</p>
                               </div>
                        
                        <div class="id-details-large">
                            <div class="id-detail-row">
                                <span class="detail-label">Full Name:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($student['full_name']); ?></span>
                            </div>
                            <div class="id-detail-row">
                                <span class="detail-label">Student ID:</span>
                                <span class="detail-value"><?php echo $student['id']; ?></span>
                            </div>
                            <div class="id-detail-row">
                                <span class="detail-label">Username:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($student['username']); ?></span>
                            </div>
                            <div class="id-detail-row">
                                <span class="detail-label">Status:</span>
                                <span class="detail-value">Active Student</span>
                            </div>
                            <div class="id-detail-row">
                                <span class="detail-label">Valid Until:</span>
                                <span class="detail-value"><?php echo date('M Y', strtotime('+1 year')); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="id-actions-large">
                        <button onclick="downloadID()" class="btn-large">
                            <i class="fas fa-download"></i>
                            Download PDF
                        </button>
                        <a href="dashboard.php" class="btn-large">
                            <i class="fas fa-arrow-left"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
                
                <!-- Student Information -->
                <div class="id-info-section">
                    <h3><i class="fas fa-info-circle"></i> Student Information</h3>
                    <div class="info-grid">
                        <div class="info-card">
                            <h4>Total Courses</h4>
                            <div class="number"><?php echo $total_courses; ?></div>
                            <div class="label">Enrolled</div>
                        </div>
                        <div class="info-card">
                            <h4>Active Courses</h4>
                            <div class="number"><?php echo $active_courses; ?></div>
                            <div class="label">Currently Studying</div>
                        </div>
                        <div class="info-card">
                            <h4>Student Since</h4>
                            <div class="number"><?php echo date('M Y', strtotime($student['created_at'] ?? 'now')); ?></div>
                            <div class="label">Enrollment Date</div>
                        </div>
                        <div class="info-card">
                            <h4>Contact</h4>
                            <div class="number"><?php echo htmlspecialchars($student['email']); ?></div>
                            <div class="label">Email Address</div>
                        </div>
                    </div>
                </div>
                
                <!-- Security Features -->
                <div class="security-features">
                    <h3><i class="fas fa-shield-alt"></i> Security Features</h3>
                    <div class="security-list">
                        <div class="security-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Unique Student ID Number</span>
                        </div>
                        <div class="security-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Digital Verification QR Code</span>
                        </div>
                        <div class="security-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Official Institute Watermark</span>
                        </div>
                        <div class="security-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Secure PDF Generation</span>
                        </div>
                        <div class="security-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Valid Until Date</span>
                        </div>
                        <div class="security-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Institute Authentication</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/mobile-menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script>
        // Generate QR codes when page loads
        document.addEventListener('DOMContentLoaded', function() {
            generateAllQRCodes();
        });
        
        function generateAllQRCodes() {
            const qrContainers = document.querySelectorAll('.qr-code-container');
            qrContainers.forEach(container => {
                const data = container.getAttribute('data-qr');
                if (data) {
                    // Clear the container first
                    container.innerHTML = '';
                    
                    QRCode.toCanvas(container, data, {
                        width: container.offsetWidth - 4, // Account for border
                        height: container.offsetHeight - 4,
                        margin: 2,
                        color: {
                            dark: '#000000',
                            light: '#FFFFFF'
                        },
                        errorCorrectionLevel: 'M'
                    }, function(error, canvas) {
                        if (error) {
                            console.error('QR Code generation failed:', error);
                            // Fallback to text display
                            container.innerHTML = '<div style="color: #000; text-align: center; padding: 10px; font-size: 10px; line-height: 1.2; font-weight: bold;">VERIFY<br>QR</div>';
                        } else {
                            // Clear container and append canvas
                            container.appendChild(canvas);
                        }
                    });
                }
            });
        }
        
        function downloadID() {
            // Open download page in new tab for printing
            window.open('download-id.php', '_blank');
        }
    </script>
</body>
</html>
