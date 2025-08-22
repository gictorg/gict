<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

// Include ImgBB helper
require_once '../includes/imgbb_helper.php';

$user = getCurrentUser();
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $qualification = trim($_POST['qualification'] ?? '');
    $joining_date = $_POST['joining_date'] ?? '';
    
    // Validation
    if (empty($username) || empty($full_name) || empty($phone)) {
        $error_message = "Username, Full Name, and Phone are required fields.";
    } else {
        try {
            // Check if username already exists
            $checkSql = "SELECT id FROM users WHERE username = ?";
            $existingUser = getRow($checkSql, [$username]);
            
            if ($existingUser) {
                $error_message = "Username '$username' already exists. Please choose a different username.";
            } else {
                // Handle file uploads to ImgBB
                $profile_image_url = '';
                $marksheet_url = '';
                $aadhaar_card_url = '';
                $uploaded_files = [];
                
                // Profile Image Upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                    $profile_file = $_FILES['profile_image'];
                    $profile_ext = strtolower(pathinfo($profile_file['name'], PATHINFO_EXTENSION));
                    $allowed_image_exts = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($profile_ext, $allowed_image_exts) && $profile_file['size'] <= 100 * 1024) {
                                                       $imgbb_result = smartUpload(
                                   $profile_file['tmp_name'], 
                                   $username . '_profile'
                               );
                        
                        if ($imgbb_result && $imgbb_result['success']) {
                            $profile_image_url = $imgbb_result['url'];
                            $uploaded_files[] = [
                                'type' => 'profile_image',
                                'url' => $imgbb_result['url'],
                                'display_url' => $imgbb_result['display_url'],
                                'imgbb_id' => $imgbb_result['id'],
                                'size' => $profile_file['size']
                            ];
                        } else {
                            $error_message = "Failed to upload profile image to ImgBB.";
                        }
                    } else {
                        $error_message = "Profile image must be JPG, PNG, or GIF and under 100KB.";
                    }
                }
                
                // Marksheet Upload
                if (isset($_FILES['marksheet']) && $_FILES['marksheet']['error'] == 0) {
                    $marksheet_file = $_FILES['marksheet'];
                    $marksheet_ext = strtolower(pathinfo($marksheet_file['name'], PATHINFO_EXTENSION));
                    $allowed_doc_exts = ['pdf', 'jpg', 'jpeg', 'png'];
                    
                    if (in_array($marksheet_ext, $allowed_doc_exts) && $marksheet_file['size'] <= 100 * 1024) {
                                                       $imgbb_result = smartUpload(
                                   $marksheet_file['tmp_name'], 
                                   $username . '_marksheet'
                               );
                        
                        if ($imgbb_result && $imgbb_result['success']) {
                            $marksheet_url = $imgbb_result['url'];
                            $uploaded_files[] = [
                                'type' => 'marksheet',
                                'url' => $imgbb_result['url'],
                                'display_url' => $imgbb_result['display_url'],
                                'imgbb_id' => $imgbb_result['id'],
                                'size' => $marksheet_file['size']
                            ];
                        } else {
                            $error_message = "Failed to upload marksheet to ImgBB.";
                        }
                    } else {
                        $error_message = "Marksheet must be PDF, JPG, PNG, or JPEG and under 100KB.";
                    }
                }
                
                // Aadhaar Card Upload
                if (isset($_FILES['aadhaar_card']) && $_FILES['aadhaar_card']['error'] == 0) {
                    $aadhaar_file = $_FILES['aadhaar_card'];
                    $aadhaar_ext = strtolower(pathinfo($aadhaar_file['name'], PATHINFO_EXTENSION));
                    $allowed_doc_exts = ['pdf', 'jpg', 'jpeg', 'png'];
                    
                    if (in_array($aadhaar_ext, $allowed_doc_exts) && $aadhaar_file['size'] <= 100 * 1024) {
                                                       $imgbb_result = smartUpload(
                                   $aadhaar_file['tmp_name'], 
                                   $username . '_aadhaar'
                               );
                        
                        if ($imgbb_result && $imgbb_result['success']) {
                            $aadhaar_card_url = $imgbb_result['url'];
                            $uploaded_files[] = [
                                'type' => 'aadhaar_card',
                                'url' => $imgbb_result['url'],
                                'display_url' => $imgbb_result['display_url'],
                                'imgbb_id' => $imgbb_result['id'],
                                'size' => $aadhaar_file['size']
                            ];
                        } else {
                            $error_message = "Failed to upload Aadhaar card to ImgBB.";
                        }
                    } else {
                        $error_message = "Aadhaar card must be PDF, JPG, PNG, or JPEG and under 100KB.";
                    }
                }
                
                if (empty($error_message)) {
                    // Generate default password (username + 123)
                    $default_password = $username . '123';
                    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
                    
                    // Insert student into database
                    $sql = "INSERT INTO users (username, password, email, full_name, user_type, phone, address, date_of_birth, gender, qualification, joining_date, profile_image) VALUES (?, ?, ?, ?, 'student', ?, ?, ?, ?, ?, ?, ?)";
                    $result = insertData($sql, [$username, $hashed_password, $email, $full_name, $phone, $address, $date_of_birth, $gender, $qualification, $joining_date, $profile_image_url]);
                    
                    if ($result) {
                        // Insert document records with ImgBB URLs
                        foreach ($uploaded_files as $file) {
                            $docSql = "INSERT INTO student_documents (user_id, document_type, file_path, imgbb_id, original_filename, file_size, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                            insertData($docSql, [
                                $result, 
                                $file['type'], 
                                $file['url'], 
                                $file['imgbb_id'],
                                basename($_FILES[$file['type']]['name']),
                                $file['size']
                            ]);
                        }
                        
                        $success_message = "Student '$full_name' added successfully!<br>";
                        $success_message .= "<strong>Username:</strong> $username<br>";
                        $success_message .= "<strong>Default Password:</strong> $default_password<br>";
                        $success_message .= "<strong>Documents Uploaded:</strong> " . 
                            (empty($profile_image_url) ? 'None' : 'Profile Image') . ", " .
                            (empty($marksheet_url) ? 'None' : 'Marksheet') . ", " .
                            (empty($aadhaar_card_url) ? 'None' : 'Aadhaar Card');
                        
                        if (!empty($uploaded_files)) {
                            $success_message .= "<br><br><strong>ImgBB URLs (Stored in Database):</strong><br>";
                            foreach ($uploaded_files as $file) {
                                $success_message .= "• " . ucfirst(str_replace('_', ' ', $file['type'])) . ": <a href='" . $file['url'] . "' target='_blank'>View File</a> (" . round($file['size'] / 1024, 2) . " KB)<br>";
                            }
                        }
                        
                        // Clear form data
                        $_POST = array();
                    } else {
                        $error_message = "Failed to add student to database.";
                    }
                }
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - GICT Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-content {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .back-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .back-btn:hover {
            background: #5a6268;
        }
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-section {
            margin-bottom: 30px;
        }
        .form-section h3 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .file-upload {
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        .file-upload:hover {
            border-color: #5a6fd8;
            background: #e9ecef;
        }
        .file-upload input[type="file"] {
            display: none;
        }
        .file-upload label {
            cursor: pointer;
            color: #667eea;
            font-weight: 500;
        }
        .file-upload .icon {
            font-size: 24px;
            margin-bottom: 10px;
            color: #667eea;
        }
        .file-info {
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
        .submit-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
            width: 100%;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .required {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include '../header.php'; ?>
        
        <div class="admin-content">
            <div class="page-header">
                <h1><i class="fas fa-user-plus"></i> Add New Student</h1>
                <a href="students.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Students
                </a>
            </div>
            
            <?php if ($success_message): ?>
                <div class="message success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="message error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <!-- Basic Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Basic Information</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username <span class="required">*</span></label>
                                <input type="text" id="username" name="username" required 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                       placeholder="Enter unique username">
                            </div>
                            
                            <div class="form-group">
                                <label for="full_name">Full Name <span class="required">*</span></label>
                                <input type="text" id="full_name" name="full_name" required 
                                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                                       placeholder="Enter full name">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="Enter email address">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number <span class="required">*</span></label>
                                <input type="tel" id="phone" name="phone" required 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                       placeholder="Enter phone number">
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3" 
                                      placeholder="Enter address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Personal Details -->
                    <div class="form-section">
                        <h3><i class="fas fa-id-card"></i> Personal Details</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" 
                                       value="<?php echo $_POST['date_of_birth'] ?? ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo ($_POST['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($_POST['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($_POST['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="qualification">Highest Qualification</label>
                                <input type="text" id="qualification" name="qualification" 
                                       value="<?php echo htmlspecialchars($_POST['qualification'] ?? ''); ?>"
                                       placeholder="e.g., 12th Standard, B.Tech, etc.">
                            </div>
                            
                            <div class="form-group">
                                <label for="joining_date">Joining Date</label>
                                <input type="date" id="joining_date" name="joining_date" 
                                       value="<?php echo $_POST['joining_date'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Document Uploads -->
                    <div class="form-section">
                        <h3><i class="fas fa-upload"></i> Document Uploads</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Profile Image (Optional)</label>
                                <div class="file-upload">
                                    <input type="file" id="profile_image" name="profile_image" accept="image/*">
                                    <label for="profile_image">
                                        <div class="icon"><i class="fas fa-camera"></i></div>
                                        <div>Click to upload profile image</div>
                                        <div class="file-info">Max size: 100KB | Formats: JPG, PNG, GIF</div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Marksheet (Optional)</label>
                                <div class="file-upload">
                                    <input type="file" id="marksheet" name="marksheet" accept=".pdf,.jpg,.jpeg,.png">
                                    <label for="marksheet">
                                        <div class="icon"><i class="fas fa-file-alt"></i></div>
                                        <div>Click to upload marksheet</div>
                                        <div class="file-info">Max size: 100KB | Formats: PDF, JPG, PNG</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Aadhaar Card (Optional)</label>
                            <div class="file-upload">
                                <input type="file" id="aadhaar_card" name="aadhaar_card" accept=".pdf,.jpg,.jpeg,.png">
                                <label for="aadhaar_card">
                                    <div class="icon"><i class="fas fa-id-card"></i></div>
                                    <div>Click to upload Aadhaar card</div>
                                    <div class="file-info">Max size: 100KB | Formats: PDF, JPG, PNG</div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i> Add Student
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // File upload preview and validation
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const fileSize = file.size;
                    const maxSize = 100 * 1024; // 100KB
                    
                    if (fileSize > maxSize) {
                        alert('File size must be under 100KB. Current size: ' + Math.round(fileSize/1024) + 'KB');
                        this.value = '';
                        return;
                    }
                    
                    // Update label to show selected file
                    const label = this.parentElement.querySelector('label div:nth-child(2)');
                    label.textContent = 'Selected: ' + file.name;
                    label.style.color = '#28a745';
                }
            });
        });
    </script>
</body>
</html>
