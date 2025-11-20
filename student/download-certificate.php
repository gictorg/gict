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
$course_id = $_GET['course_id'] ?? null;

if (!$course_id) {
    header('Location: dashboard.php');
    exit;
}

// Get student and course information
$enrollment = getRow("
    SELECT se.*, c.name as course_name, c.duration, c.fee, u.full_name, u.username
    FROM student_enrollments se
    JOIN courses c ON se.course_id = c.id
    JOIN users u ON se.user_id = u.id
    WHERE se.user_id = ? AND se.course_id = ? AND se.status = 'completed' AND se.final_marks >= 40
", [$user_id, $course_id]);

if (!$enrollment) {
    header('Location: dashboard.php');
    exit;
}

// Generate QR code for certificate
$qrCode = generateQRCode($student['id'], $enrollment['full_name'] . '_' . $enrollment['course_name'], 80);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Certificate - GICT Institute</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            .certificate { 
                box-shadow: none !important;
                margin: 0 !important;
                page-break-inside: avoid;
            }
        }
        
        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            margin: 0;
        }
        
        .certificate {
            background: white;
            border: 3px solid #667eea;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.2);
            margin-bottom: 30px;
            position: relative;
            min-height: 600px;
        }
        
        .certificate::before {
            content: '';
            position: absolute;
            top: 20px;
            right: 20px;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            opacity: 0.1;
        }
        
        .certificate::after {
            content: '';
            position: absolute;
            bottom: 20px;
            left: 20px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            opacity: 0.1;
        }
        
        .cert-header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 20px;
        }
        
        .institute-name {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .certificate-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .certificate-subtitle {
            font-size: 16px;
            color: #666;
            font-style: italic;
        }
        
        .certificate-body {
            text-align: center;
            margin: 40px 0;
        }
        
        .certificate-text {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .student-name {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin: 30px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .course-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 20px 0;
            text-transform: uppercase;
        }
        
        .course-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 40px 0;
            text-align: left;
        }
        
        .detail-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .detail-label {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }
        
        .detail-value {
            font-size: 18px;
            color: #667eea;
            font-weight: bold;
        }
        
        .completion-date {
            text-align: center;
            margin: 30px 0;
            font-size: 16px;
            color: #666;
        }
        
        .certificate-number {
            text-align: center;
            margin: 20px 0;
            font-size: 14px;
            color: #999;
            font-family: 'Courier New', monospace;
        }
        
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 60px;
            padding-top: 30px;
            border-top: 1px solid #ddd;
        }
        
        .signature-section {
            text-align: center;
        }
        
        .signature-line {
            width: 200px;
            height: 2px;
            background: #333;
            margin: 20px auto;
        }
        
        .signature-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .signature-title {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }
        
        .qr-code {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 80px;
            height: 80px;
            background: #667eea;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            line-height: 1.2;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(102, 126, 234, 0.05);
            font-weight: bold;
            pointer-events: none;
            z-index: 1;
        }
        
        .actions {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin: 0 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-print {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        .btn-back {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        }
        
        .instructions {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .instructions h3 {
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .instructions ul {
            color: #666;
            line-height: 1.6;
            margin: 0;
            padding-left: 20px;
        }
        
        .instructions li {
            margin-bottom: 8px;
        }
        
        @media (max-width: 768px) {
            .course-details {
                grid-template-columns: 1fr;
            }
            
            .signatures {
                grid-template-columns: 1fr;
            }
            
            .actions .btn {
                display: block;
                margin: 10px auto;
                max-width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Course Completion Certificate</h1>
            <p>GICT Institute - Download your course certificate</p>
        </div>
        
        <div class="actions no-print">
            <button onclick="downloadCertificate()" class="btn btn-print">
                <i class="fas fa-download"></i> Download Certificate
            </button>
            <a href="dashboard.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <div class="certificate">
            <div class="watermark">GICT</div>
            <!-- QR Code for Verification -->
            <?php echo $qrCode; ?>
            
            <div class="cert-header">
                <div class="institute-name">GICT Institute</div>
                <div class="certificate-title">Certificate of Completion</div>
                <div class="certificate-subtitle">This is to certify successful completion of the course</div>
            </div>
            
            <div class="certificate-body">
                <div class="certificate-text">
                    This is to certify that
                </div>
                
                <div class="student-name">
                    <?php echo htmlspecialchars($enrollment['full_name']); ?>
                </div>
                
                <div class="certificate-text">
                    has successfully completed the course
                </div>
                
                <div class="course-name">
                    <?php echo htmlspecialchars($enrollment['course_name']); ?>
                </div>
            </div>
            
            <div class="course-details">
                <div class="detail-item">
                    <div class="detail-label">Course Duration</div>
                    <div class="detail-value"><?php echo htmlspecialchars($enrollment['duration']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Final Marks</div>
                    <div class="detail-value"><?php echo $enrollment['final_marks']; ?>%</div>
                </div>
            </div>
            
            <div class="completion-date">
                <strong>Completed on:</strong> <?php echo date('F d, Y', strtotime($enrollment['completion_date'] ?? 'now')); ?>
            </div>
            
            <div class="certificate-number">
                Certificate No: CERT-<?php echo str_pad($enrollment['id'], 6, '0', STR_PAD_LEFT); ?>-<?php echo date('Y'); ?>
            </div>
            
            <div class="signatures">
                <div class="signature-section">
                    <div class="signature-line"></div>
                    <div class="signature-name">GICT Institute</div>
                    <div class="signature-title">Authorized Training Partner</div>
                </div>
                <div class="signature-section">
                    <div class="signature-line"></div>
                    <div class="signature-name">Director</div>
                    <div class="signature-title">GICT Institute</div>
                </div>
            </div>
        </div>
        
        <div class="instructions no-print">
            <h3><i class="fas fa-info-circle"></i> How to Download</h3>
            <ul>
                <li><strong>Print to PDF:</strong> Click the "Print/Save as PDF" button above</li>
                <li><strong>Browser Print:</strong> Use Ctrl+P (Windows) or Cmd+P (Mac) to open print dialog</li>
                <li><strong>Save as PDF:</strong> In the print dialog, select "Save as PDF" as destination</li>
                <li><strong>High Quality:</strong> Ensure "High Quality" is selected for best results</li>
                <li><strong>No Margins:</strong> Set margins to "None" or "Minimum" for best fit</li>
                <li><strong>Landscape:</strong> For best results, select "Landscape" orientation</li>
            </ul>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
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
                            container.innerHTML = '<div style="color: #000; text-align: center; padding: 10px; font-size: 10px; line-height: 1.2; font-weight: bold;">VERIFY<br>CERT</div>';
                        } else {
                            // Clear container and append canvas
                            container.appendChild(canvas);
                        }
                    });
                }
            });
        }
        
        function downloadCertificate() {
            // Generate QR codes first, then trigger print dialog
            generateAllQRCodes();
            setTimeout(() => {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>
