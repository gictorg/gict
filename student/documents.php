<?php
require_once '../includes/session_manager.php';
require_once '../config/database.php';
require_once '../includes/cloudinary_helper.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get student information
$student = getRow("SELECT u.* FROM users u WHERE u.id = ?", [$user_id]);
if (!$student) {
    header('Location: ../login.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

// Document types available for upload
$document_types = [
    'marksheet' => 'Educational Marksheet',
    'aadhaar' => 'Aadhaar Card',
    'pan' => 'PAN Card',
    'driving_license' => 'Driving License',
    'passport' => 'Passport',
    'visa' => 'Visa Document',
    'certificate' => 'Professional Certificate',
    'id_proof' => 'Government ID Proof',
    'address_proof' => 'Address Proof Document',
    'other' => 'Other Document'
];

$message = '';
$message_type = '';

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'upload_document') {
        $document_type = trim($_POST['document_type'] ?? '');
        $file = $_FILES['document_file'] ?? null;

        if (empty($document_type) || !isset($document_types[$document_type])) {
            $message = 'Invalid document type selected';
            $message_type = 'error';
        } elseif (!$file || $file['error'] !== 0) {
            $message = 'File upload error. Please try again.';
            $message_type = 'error';
        } else {
            if ($file['size'] > 1024 * 1024 * 2) { // 2MB limit
                $message = 'File size must be less than 2MB';
                $message_type = 'error';
            } else {
                $upload_result = smartUpload($file['tmp_name'], $user_id . '_' . $document_type);

                if ($upload_result && isset($upload_result['url'])) {
                    $existing = getRow("SELECT id FROM student_documents WHERE user_id = ? AND document_type = ?", [$user_id, $document_type]);

                    if ($existing) {
                        updateData("
                            UPDATE student_documents 
                            SET file_name = ?, file_url = ?, file_size = ?, uploaded_at = CURRENT_TIMESTAMP
                            WHERE user_id = ? AND document_type = ?
                        ", [$file['name'], $upload_result['url'], $file['size'], $user_id, $document_type]);
                        $message = 'Document updated successfully!';
                    } else {
                        insertData("
                            INSERT INTO student_documents (user_id, document_type, file_url, file_name, file_size)
                            VALUES (?, ?, ?, ?, ?)
                        ", [$user_id, $document_type, $upload_result['url'], $file['name'], $file['size']]);
                        $message = 'Document uploaded successfully!';
                    }
                    $message_type = 'success';
                } else {
                    $message = 'Cloudinary upload failed.';
                    $message_type = 'error';
                }
            }
        }
    }
}

$documents = getRows("SELECT * FROM student_documents WHERE user_id = ? ORDER BY uploaded_at DESC", [$user_id]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents - Student Portal</title>
    <link rel="icon" type="image/png" href="../logo.png">
    <link rel="stylesheet" href="../assets/css/student-portal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="student-portal-body">
    <div class="student-layout">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-container" style="width: 100%; overflow: auto;">
            <!-- Topbar -->
            <header class="admin-topbar">
                <div class="topbar-left">
                    <div class="breadcrumbs">
                        <a href="dashboard.php">Dashboard</a> / <span>Documents</span>
                    </div>
                </div>
            </header>

            <main class="admin-content">
                <div style="display: grid; grid-template-columns: 1.2fr 2fr; gap: 30px;">
                    <!-- Upload Section -->
                    <div class="panel">
                        <div class="panel-header">
                            <h1><i class="fas fa-file-upload"></i> New Upload</h1>
                        </div>
                        <div class="panel-body">
                            <?php if ($message): ?>
                                <div
                                    style="padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; background: <?php echo $message_type === 'success' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $message_type === 'success' ? '#166534' : '#991b1b'; ?>; border: 1px solid <?php echo $message_type === 'success' ? '#bbf7d0' : '#fecaca'; ?>;">
                                    <?php echo htmlspecialchars($message); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="upload_document">

                                <div style="margin-bottom: 20px;">
                                    <label
                                        style="display: block; margin-bottom: 8px; font-weight: 600; color: #1e293b;">Select
                                        Document Type</label>
                                    <select name="document_type" required
                                        style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0; font-family: inherit;">
                                        <option value="">-- Choose Type --</option>
                                        <?php foreach ($document_types as $key => $label): ?>
                                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div style="margin-bottom: 25px;">
                                    <label
                                        style="display: block; margin-bottom: 8px; font-weight: 600; color: #1e293b;">Choose
                                        File</label>
                                    <input type="file" name="document_file" required accept=".pdf,.jpg,.jpeg,.png"
                                        style="width: 100%; padding: 10px; border: 2px dashed #e2e8f0; border-radius: 10px; cursor: pointer;">
                                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 8px;">Allowed: PDF, JPG,
                                        PNG (Max 2MB)</p>
                                </div>

                                <button type="submit" class="btn-primary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-cloud-upload-alt"></i> Upload to Vault
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Documents List -->
                    <div class="panel">
                        <div class="panel-header">
                            <h1><i class="fas fa-folder-open"></i> Vault Documents</h1>
                        </div>
                        <div class="panel-body">
                            <?php if (empty($documents)): ?>
                                <div style="text-align: center; padding: 40px; color: #64748b;">
                                    <i class="fas fa-box-open"
                                        style="font-size: 3rem; margin-bottom: 15px; opacity: 0.2;"></i>
                                    <p>Your document vault is currently empty.</p>
                                </div>
                            <?php else: ?>
                                <div style="display: grid; gap: 15px;">
                                    <?php foreach ($documents as $doc): ?>
                                        <div style="padding: 15px 20px; border: 1px solid #f1f5f9; border-radius: 15px; display: flex; justify-content: space-between; align-items: center; transition: background 0.2s;"
                                            onmouseover="this.style.background='#f8fafc'"
                                            onmouseout="this.style.background='white'">
                                            <div style="display: flex; align-items: center; gap: 15px;">
                                                <div
                                                    style="width: 45px; height: 45px; background: #e0e7ff; color: #4338ca; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                                    <i
                                                        class="fas <?php echo strpos($doc['file_name'] ?? '', '.pdf') !== false ? 'fa-file-pdf' : 'fa-file-image'; ?>"></i>
                                                </div>
                                                <div>
                                                    <h4 style="margin: 0; font-size: 0.95rem; color: #1e293b;">
                                                        <?php echo htmlspecialchars($document_types[$doc['document_type']] ?? $doc['document_type']); ?>
                                                    </h4>
                                                    <p
                                                        style="margin: 3px 0 0; font-size: 0.75rem; color: #64748b; font-family: monospace;">
                                                        <?php echo htmlspecialchars($doc['file_name']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <span
                                                    style="font-size: 0.8rem; color: #94a3b8; margin-right: 10px;"><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></span>
                                                <a href="<?php echo htmlspecialchars($doc['file_url']); ?>" target="_blank"
                                                    class="btn-primary"
                                                    style="padding: 8px 15px; font-size: 0.8rem; background: #f1f5f9; color: #1e293b; box-shadow: none; border: 1px solid #e2e8f0;">
                                                    View
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }
    </script>
</body>

</html>