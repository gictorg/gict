<?php
require_once 'includes/imgbb_helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ImgBB Test - GICT Institute</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
        }
        .content {
            padding: 30px;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .status-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            text-align: center;
        }
        .status-item .icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
        .test-section {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #5a6fd8;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .warning {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-images"></i> ImgBB Integration Test</h1>
            <p>Testing your ImgBB API key: 3acdbb8d9ce98d6f3ff4e61a5902c75a</p>
        </div>
        
        <div class="content">
            <div class="info">
                <h3>🔑 Your ImgBB Configuration</h3>
                <p><strong>API Key:</strong> 3acdbb8d9ce98d6f3ff4e61a5902c75a</p>
                <p><strong>Image Expiration:</strong> 600 seconds (10 minutes)</p>
                <p><strong>Purpose:</strong> Store student and faculty profile images</p>
            </div>
            
            <div class="test-section">
                <h3>📊 Connection Status</h3>
                <?php
                $status = testImgBBConnection();
                ?>
                <div class="status-grid">
                    <div class="status-item">
                        <div class="icon <?php echo $status['curl_available'] ? 'status-ok' : 'status-error'; ?>">
                            <i class="fas fa-<?php echo $status['curl_available'] ? 'check-circle' : 'times-circle'; ?>"></i>
                        </div>
                        <div><strong>cURL Support</strong></div>
                        <div><?php echo $status['curl_available'] ? 'Available' : 'Not Available'; ?></div>
                    </div>
                    
                    <div class="status-item">
                        <div class="icon <?php echo $status['api_key_configured'] ? 'status-ok' : 'status-error'; ?>">
                            <i class="fas fa-<?php echo $status['api_key_configured'] ? 'check-circle' : 'times-circle'; ?>"></i>
                        </div>
                        <div><strong>API Key</strong></div>
                        <div><?php echo $status['api_key_configured'] ? 'Configured' : 'Not Configured'; ?></div>
                    </div>
                    
                    <div class="status-item">
                        <div class="icon <?php echo $status['api_accessible'] ? 'status-ok' : 'status-warning'; ?>">
                            <i class="fas fa-<?php echo $status['api_accessible'] ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                        </div>
                        <div><strong>API Connection</strong></div>
                        <div><?php echo $status['api_accessible'] ? 'Connected' : 'Failed'; ?></div>
                    </div>
                    
                    <div class="status-item">
                        <div class="icon <?php echo $status['ready_for_upload'] ? 'status-ok' : 'status-error'; ?>">
                            <i class="fas fa-<?php echo $status['ready_for_upload'] ? 'check-circle' : 'times-circle'; ?>"></i>
                        </div>
                        <div><strong>Ready for Upload</strong></div>
                        <div><?php echo $status['ready_for_upload'] ? 'Yes' : 'No'; ?></div>
                    </div>
                </div>
                
                <?php if ($status['ready_for_upload']): ?>
                    <div class="success">
                        <h4>✅ All Systems Go!</h4>
                        <p>Your ImgBB integration is ready. You can now upload images through the admin forms.</p>
                    </div>
                <?php else: ?>
                    <div class="warning">
                        <h4>⚠️ Configuration Issues</h4>
                        <p>Please check the status above and resolve any issues before proceeding.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($status['ready_for_upload']): ?>
                <div class="test-section">
                    <h3>🧪 Test Image Upload</h3>
                    <p>Upload a test image to verify everything is working correctly:</p>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="test_file">Select a test image (JPG, PNG, GIF under 100KB):</label>
                            <input type="file" id="test_file" name="test_file" accept=".jpg,.jpeg,.png,.gif" required>
                        </div>
                        <button type="submit" name="test_upload" class="btn">
                            <i class="fas fa-upload"></i> Test Upload to ImgBB
                        </button>
                    </form>
                    
                    <?php
                    if (isset($_POST['test_upload']) && isset($_FILES['test_file'])) {
                        $file = $_FILES['test_file'];
                        if ($file['error'] == 0 && $file['size'] <= 100 * 1024) {
                            $result = uploadToImgBB($file['tmp_name'], 'test_' . time());
                            if ($result && $result['success']) {
                                echo '<div class="success">';
                                echo '<h4>✅ Upload Successful!</h4>';
                                echo '<p><strong>Image URL:</strong> <a href="' . $result['url'] . '" target="_blank">' . $result['url'] . '</a></p>';
                                echo '<p><strong>Display URL:</strong> <a href="' . $result['display_url'] . '" target="_blank">' . $result['display_url'] . '</a></p>';
                                echo '<p><strong>ImgBB ID:</strong> ' . $result['id'] . '</p>';
                                echo '<p><strong>File Size:</strong> ' . formatFileSize($result['size']) . '</p>';
                                echo '<p><strong>Format:</strong> ' . strtoupper($result['format']) . '</p>';
                                echo '<p><strong>Expiration:</strong> ' . $result['expiration'] . ' seconds</p>';
                                if ($result['width'] && $result['height']) {
                                    echo '<p><strong>Dimensions:</strong> ' . $result['width'] . ' x ' . $result['height'] . ' pixels</p>';
                                }
                                echo '</div>';
                            } else {
                                echo '<div class="warning">';
                                echo '<h4>⚠️ Upload Failed</h4>';
                                echo '<p>Check your configuration and try again.</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="warning">';
                            echo '<h4>⚠️ Invalid File</h4>';
                            echo '<p>Please select a valid image file under 100KB.</p>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="test-section">
                <h3>🚀 Next Steps</h3>
                <p>Once ImgBB is working correctly:</p>
                <ol>
                    <li>✅ <strong>Add Students:</strong> Go to <a href="admin/add-student.php">Add Student</a> to upload profile images</li>
                    <li>✅ <strong>Add Faculty:</strong> Go to <a href="admin/staff.php">Staff Management</a> to upload faculty images</li>
                    <li>✅ <strong>View Homepage:</strong> Check <a href="index.php">Homepage</a> to see images from database</li>
                    <li>✅ <strong>Database Storage:</strong> All image URLs are stored in the database for consistency</li>
                </ol>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="admin/add-student.php" class="btn">
                        <i class="fas fa-user-plus"></i> Test Add Student
                    </a>
                    <a href="admin/staff.php" class="btn">
                        <i class="fas fa-users"></i> Test Staff Management
                    </a>
                    <a href="index.php" class="btn">
                        <i class="fas fa-home"></i> View Homepage
                    </a>
                </div>
            </div>
            
            <div class="info">
                <h3>💡 How It Works</h3>
                <ul>
                    <li><strong>Image Upload:</strong> Files are uploaded to ImgBB via API</li>
                    <li><strong>URL Storage:</strong> ImgBB URLs are stored in your database</li>
                    <li><strong>Database Consistency:</strong> No broken links or missing images</li>
                    <li><strong>Automatic Expiration:</strong> Images expire after 10 minutes for security</li>
                    <li><strong>Fast Loading:</strong> Images load from ImgBB's global CDN</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
