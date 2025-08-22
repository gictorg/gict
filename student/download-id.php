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
if (!$student) {
    header('Location: ../login.php');
    exit;
}

// Generate QR code
$qrCode = generateQRCode($student['id'], $student['full_name'], 80);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Student ID - GICT Institute</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            .id-card { 
                box-shadow: none !important;
                margin: 0 !important;
                page-break-inside: avoid;
            }
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
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
        
        .actions {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        
        .btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .btn-print {
            background: #28a745;
        }
        
        .btn-print:hover {
            background: #218838;
        }
        
        .btn-back {
            background: #6c757d;
        }
        
        .btn-back:hover {
            background: #5a6268;
        }
        
        .id-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
            margin-bottom: 30px;
        }
        
        /* Official Institute Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            font-weight: 900;
            color: rgba(102, 126, 234, 0.08);
            z-index: 1;
            pointer-events: none;
            user-select: none;
        }
        
        /* QR Code */
        .qr-code {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 2;
        }
        
        .id-content {
            padding: 40px;
            position: relative;
            z-index: 3;
        }
        
        .id-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }
        
        .id-header h2 {
            color: #667eea;
            margin: 0 0 10px 0;
            font-size: 32px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .id-header p {
            color: #666;
            margin: 0;
            font-size: 16px;
            font-weight: 500;
        }
        
        .id-body {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }
        
        .id-photo {
            width: 120px;
            height: 150px;
            border-radius: 15px;
            overflow: hidden;
            border: 4px solid #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            flex-shrink: 0;
        }
        
        .id-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .id-photo-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }
        
        .id-details {
            flex: 1;
        }
        
        .id-detail-row {
            display: flex;
            margin-bottom: 15px;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .id-detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            width: 140px;
            font-weight: 700;
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            flex: 1;
            color: #555;
            font-size: 16px;
            font-weight: 500;
        }
        
        .id-footer {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            text-align: center;
            position: relative;
            z-index: 3;
        }
        
        .validity {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }
        
        /* Security Features Section */
        .security-features {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
            border-left: 5px solid #28a745;
        }
        
        .security-features h3 {
            color: #333;
            margin: 0 0 20px 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .security-features h3 i {
            color: #28a745;
        }
        
        .security-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .security-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #555;
            font-size: 14px;
        }
        
        .security-item i {
            color: #28a745;
            width: 16px;
        }
        
        .instructions {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }
        
        .instructions h3 {
            color: #333;
            margin: 0 0 20px 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .instructions h3 i {
            color: #667eea;
        }
        
        .instructions ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .instructions li {
            margin-bottom: 10px;
            color: #555;
            line-height: 1.5;
        }
        
        .instructions strong {
            color: #333;
        }
        
        @media (max-width: 768px) {
            .id-body {
                flex-direction: column;
                text-align: center;
            }
            
            .id-photo {
                margin: 0 auto;
            }
            
            .id-detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .detail-label {
                width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Student ID Card</h1>
            <p>GICT Institute - Download your digital student ID</p>
        </div>
        
        <div class="actions no-print">
            <button onclick="downloadID()" class="btn btn-print">
                <i class="fas fa-download"></i> Download ID Card
            </button>
            <a href="dashboard.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <div class="id-card">
            <!-- Official Institute Watermark -->
            <div class="watermark">GICT</div>
            
            <!-- QR Code for Verification -->
            <?php echo $qrCode; ?>
            
            <div class="id-content">
                <div class="id-header">
                    <h2>GICT INSTITUTE</h2>
                    <p>Student Identity Card</p>
                </div>
                
                <div class="id-body">
                    <div class="id-photo">
                        <?php if (!empty($student['profile_image']) && filter_var($student['profile_image'], FILTER_VALIDATE_URL)): ?>
                            <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Student Photo">
                        <?php else: ?>
                            <div class="id-photo-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="id-details">
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
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($student['email']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="id-footer">
                <div class="validity">
                    Valid until: <?php echo date('M Y', strtotime('+1 year')); ?>
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
        
        <div class="instructions no-print">
            <h3><i class="fas fa-info-circle"></i> How to Download</h3>
            <ul>
                <li><strong>Direct Download:</strong> Click the "Download ID Card" button above</li>
                <li><strong>Browser Print:</strong> Use Ctrl+P (Windows) or Cmd+P (Mac) to open print dialog</li>
                <li><strong>Save as PDF:</strong> In the print dialog, select "Save as PDF" as destination</li>
                <li><strong>High Quality:</strong> Ensure "High Quality" is selected for best results</li>
                <li><strong>No Margins:</strong> Set margins to "None" or "Minimum" for best fit</li>
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
                            container.innerHTML = '<div style="color: #000; text-align: center; padding: 10px; font-size: 10px; line-height: 1.2; font-weight: bold;">VERIFY<br>QR</div>';
                        } else {
                            // Clear container and append canvas
                            container.innerHTML = '';
                            container.appendChild(canvas);
                        }
                    });
                }
            });
        }
        
        function downloadID() {
            // Generate QR codes first, then trigger print dialog
            generateAllQRCodes();
            setTimeout(() => {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>
