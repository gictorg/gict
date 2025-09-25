<?php
require_once '../auth.php';
requireLogin();
requireRole('admin');

// Include ImgBB helper for image uploads
require_once '../includes/imgbb_helper.php';

// Include User ID Generator helper
require_once '../includes/user_id_generator.php';



$user = getCurrentUser();

// Handle AJAX requests for enrollment data
if (isset($_GET['action']) && $_GET['action'] === 'get_enrollments') {
    header('Content-Type: application/json');
    
    $student_id = intval($_GET['student_id'] ?? 0);
    if ($student_id <= 0) {
        echo json_encode(['error' => 'Invalid student ID']);
        exit;
    }
    
    try {
        $enrollment_sql = "SELECT 
            se.id,
            se.enrollment_date,
            se.status as enrollment_status,
            se.created_at,
            sc.name as sub_course_name,
            sc.fee as sub_course_fee,
            c.name as course_name,
            c.duration as course_duration,
            cc.name as category_name
        FROM student_enrollments se
        JOIN sub_courses sc ON se.sub_course_id = sc.id
        JOIN courses c ON sc.course_id = c.id
        LEFT JOIN course_categories cc ON c.category_id = cc.id
        WHERE se.user_id = ?
        ORDER BY se.created_at DESC";
        
        $enrollments = getRows($enrollment_sql, [$student_id]);
        
        // Format the data for display
        $formatted_enrollments = [];
        foreach ($enrollments as $enrollment) {
            $formatted_enrollments[] = [
                'id' => $enrollment['id'],
                'course_name' => $enrollment['course_name'],
                'sub_course_name' => $enrollment['sub_course_name'],
                'category' => $enrollment['category_name'] ?? 'N/A',
                'enrollment_date' => date('M d, Y', strtotime($enrollment['enrollment_date'])),
                'status' => ucfirst($enrollment['enrollment_status']),
                'fee' => '₹' . number_format($enrollment['sub_course_fee'], 2),
                'duration' => $enrollment['course_duration'] ?? 'N/A'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'enrollments' => $formatted_enrollments,
            'total' => count($formatted_enrollments)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'error' => 'Failed to fetch enrollment data: ' . $e->getMessage()
        ]);
    }
    exit;
}



// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_student':
                $email = $_POST['email'] ?? '';
                $full_name = $_POST['full_name'] ?? '';
                $phone = $_POST['phone'] ?? '';
                $address = $_POST['address'] ?? '';
                $date_of_birth = $_POST['date_of_birth'] ?? '';
                $gender = $_POST['gender'] ?? '';
                $qualification = $_POST['qualification'] ?? '';
                $joining_date = $_POST['joining_date'] ?? '';
                $previous_institute = $_POST['previous_institute'] ?? '';
                
                // Generate unique user ID for student
                $generated_user_id = generateUniqueUserId('student');
                if (!$generated_user_id) {
                    $error_message = "Failed to generate unique user ID for student.";
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
                        $allowed_image_exts = ['jpg', 'jpeg', 'png'];
                        
                        if (in_array($profile_ext, $allowed_image_exts) && $profile_file['size'] <= 200 * 1024) {
                            $imgbb_result = smartUpload(
                                $profile_file['tmp_name'], 
                                $generated_user_id . '_profile'
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
                            $error_message = "Profile image must be JPG, JPEG, or PNG and under 200KB.";
                        }
                    }
                    
                    // Marksheet Upload
                    if (isset($_FILES['marksheet']) && $_FILES['marksheet']['error'] == 0) {
                        $marksheet_file = $_FILES['marksheet'];
                        $marksheet_ext = strtolower(pathinfo($marksheet_file['name'], PATHINFO_EXTENSION));
                        $allowed_doc_exts = ['pdf', 'jpg', 'jpeg', 'png'];
                        
                        if (in_array($marksheet_ext, $allowed_doc_exts) && $marksheet_file['size'] <= 200 * 1024) {
                            $imgbb_result = smartUpload(
                                $marksheet_file['tmp_name'], 
                                $generated_user_id . '_marksheet'
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
                            $error_message = "Marksheet must be PDF, JPG, JPEG, or PNG and under 200KB.";
                        }
                    }
                    
                    // Aadhaar Card Upload
                    if (isset($_FILES['aadhaar_card']) && $_FILES['aadhaar_card']['error'] == 0) {
                        $aadhaar_file = $_FILES['aadhaar_card'];
                        $aadhaar_ext = strtolower(pathinfo($aadhaar_file['name'], PATHINFO_EXTENSION));
                        $allowed_image_exts = ['jpg', 'jpeg', 'png'];
                        
                        if (in_array($aadhaar_ext, $allowed_image_exts) && $aadhaar_file['size'] <= 200 * 1024) {
                            $imgbb_result = smartUpload(
                                $aadhaar_file['tmp_name'], 
                                $generated_user_id . '_aadhaar'
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
                            $error_message = "Aadhaar card must be JPG, JPEG, or PNG and under 200KB.";
                        }
                    }
                    
                    if (empty($error_message)) {
                        // Generate default password (generated_user_id + 123)
                        $default_password = $generated_user_id . '123';
                        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
                        
                        try {
                            $sql = "INSERT INTO users (username, password, email, full_name, user_type_id, phone, address, date_of_birth, gender, qualification, joining_date, profile_image) VALUES (?, ?, ?, ?, 2, ?, ?, ?, ?, ?, ?, ?)";
                            $result = insertData($sql, [$generated_user_id, $hashed_password, $email, $full_name, $phone, $address, $date_of_birth, $gender, $qualification, $joining_date, $profile_image_url]);
                            
                            if ($result) {
                                $user_id = getDBConnection()->lastInsertId();
                                
                                // Insert document records with ImgBB URLs
                                if (!empty($uploaded_files)) {
                                    foreach ($uploaded_files as $file) {
                                        $docSql = "INSERT INTO student_documents (user_id, document_type, file_url, file_name, file_size, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())";
                                        insertData($docSql, [
                                            $user_id,
                                            $file['type'],
                                            $file['url'],
                                            $file['type'] . '_' . $generated_user_id,
                                            $file['size']
                                        ]);
                                    }
                                }
                                
                                $success_message = "Student '$full_name' added successfully!<br>";
                                $success_message .= "<strong>User ID:</strong> $generated_user_id<br>";
                                $success_message .= "<strong>Default Password:</strong> $default_password";
                                
                                if (!empty($uploaded_files)) {
                                    $success_message .= "<br><br><strong>ImgBB URLs (Stored in Database):</strong><br>";
                                    foreach ($uploaded_files as $file) {
                                        $success_message .= "- {$file['type']}: <a href='{$file['url']}' target='_blank'>{$file['url']}</a>";
                                    }
                                }
                            } else {
                                $error_message = "Failed to add student.";
                            }
                        } catch (Exception $e) {
                            $error_message = "Error: " . $e->getMessage();
                        }
                    }
                }
                break;
                
            case 'delete_student':
                $student_id = $_POST['student_id'] ?? 0;
                if ($student_id) {
                    try {
                        // First check if student exists and is actually a student
                        $check_sql = "SELECT id, username, full_name FROM users WHERE id = ? AND user_type_id = 2";
                        $student = getRow($check_sql, [$student_id]);
                        
                        if (!$student) {
                            $error_message = "Student not found or invalid user type.";
                        } else {
                            // Delete student (cascade will handle related records)
                            $sql = "DELETE FROM users WHERE id = ? AND user_type_id = 2";
                            $result = deleteData($sql, [$student_id]);
                            
                            if ($result) {
                                $success_message = "Student '{$student['full_name']}' (ID: {$student['username']}) deleted successfully!";
                                // Note: Related records in student_enrollments, payments, and student_documents 
                                // will be automatically deleted due to CASCADE constraints
                            } else {
                                $error_message = "Failed to delete student. Please check if there are any active enrollments or payments.";
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Student deletion error: " . $e->getMessage());
                        $error_message = "Error deleting student: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Invalid student ID provided.";
                }
                break;
                
            case 'toggle_status':
                $student_id = $_POST['student_id'] ?? 0;
                $new_status = $_POST['new_status'] ?? '';
                if ($student_id) {
                    try {
                        $sql = "UPDATE users SET status = ? WHERE id = ? AND user_type_id = 2";
                        $result = updateData($sql, [$new_status, $student_id]);
                        if ($result) {
                            $success_message = "Student status updated successfully!";
                        } else {
                            $error_message = "Failed to update student status.";
                        }
                    } catch (Exception $e) {
                        $error_message = "Error: " . $e->getMessage();
                    }
                }
                break;
                
            case 'update_password':
                $student_id = $_POST['student_id'] ?? 0;
                $new_password = $_POST['new_password'] ?? '';
                if ($student_id && !empty($new_password)) {
                    try {
                        // Validate password strength
                        if (strlen($new_password) < 8) {
                            throw new Exception("New password must be at least 8 characters long.");
                        }
                        
                        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $new_password)) {
                            throw new Exception("Password must contain at least one lowercase letter, one uppercase letter, and one number.");
                        }
                        
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ? AND user_type_id = 2";
                        $result = updateData($sql, [$hashed_password, $student_id]);
                        if ($result) {
                            $success_message = "Student password updated successfully!";
                        } else {
                            $error_message = "Failed to update student password.";
                        }
                    } catch (Exception $e) {
                        $error_message = "Error: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Please provide a new password.";
                }
                break;
        }
    }
}

// Get all students with their enrollment information
$students = [];
try {
    $sql = "SELECT 
                u.id, u.username, u.email, u.full_name, u.phone, u.address, 
                u.date_of_birth, u.gender, u.joining_date, u.status, u.created_at, u.profile_image,
                COUNT(se.id) as total_enrollments,
                COUNT(CASE WHEN se.status = 'completed' THEN 1 END) as completed_courses,
                COUNT(CASE WHEN se.status = 'enrolled' THEN 1 END) as active_enrollments
            FROM users u 
            LEFT JOIN student_enrollments se ON u.id = se.user_id 
            WHERE u.user_type_id = 2 
            GROUP BY u.id 
            ORDER BY u.created_at DESC";
    $students = getRows($sql);
} catch (Exception $e) {
    $error_message = "Error loading students: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - GICT Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-content {
            padding: 20px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
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
        .add-student-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        .add-student-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        
        .section-header {
            margin-bottom: 25px;
            text-align: center;
        }
        .section-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .section-header h2 i {
            color: #667eea;
        }
        .section-header p {
            color: #6c757d;
            font-size: 16px;
            margin: 0;
        }
        
        /* Search Section Styling */
        .search-section {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 18px;
        }
        .search-box input {
            width: 100%;
            padding: 15px 50px 15px 45px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .clear-search {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .clear-search:hover {
            background: #c82333;
            transform: translateY(-50%) scale(1.1);
        }
        .search-filters {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .search-filters select {
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .search-filters select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }
        .search-filters select:hover {
            border-color: #667eea;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-edit {
            background: #17a2b8;
            color: white;
        }
        
        .btn-edit:hover {
            background: #138496;
        }
        
        .btn-password {
            background: #6f42c1;
            color: white;
        }
        
        .btn-password:hover {
            background: #5a32a3;
        }
        
        .btn-icard {
            background: #17a2b8;
            color: white;
        }
        
        .btn-icard:hover {
            background: #138496;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .btn-toggle {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-toggle:hover {
            background: #e0a800;
        }
        .add-student-btn:hover {
            transform: translateY(-2px);
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
            transition: all 0.3s ease;
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
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }
        .stat-card h3 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 16px;
            font-weight: 600;
        }
        .stat-card .value {
            font-size: 36px;
            font-weight: 800;
            color: #333;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .stat-card .label {
            color: #6c757d;
            font-size: 13px;
            font-weight: 500;
        }
        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }
        
        .students-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        .students-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .students-table th {
            padding: 18px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            white-space: nowrap;
        }
        
        .students-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.2s ease;
        }
        
        .students-table tbody tr:hover {
            background-color: #f8fafc;
        }
        
        .students-table tbody tr:last-child {
            border-bottom: none;
        }
        
        .students-table td {
            padding: 18px 16px;
            vertical-align: middle;
            color: #374151;
            font-size: 14px;
        }
        
        /* Student Info Cell */
        .student-info-cell {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .student-avatar {
            flex-shrink: 0;
        }
        
        .student-avatar img.profile-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e9ecef;
        }
        
        .default-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            border: 3px solid #e9ecef;
        }
        
        .student-details {
            flex: 1;
        }
        
        .student-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 16px;
            margin-bottom: 4px;
        }
        
        .student-username {
            color: #6b7280;
            font-size: 14px;
        }
        
        /* Contact Info Cell */
        .contact-info div {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .contact-info i {
            color: #667eea;
            width: 16px;
            text-align: center;
        }
        
        /* Status Cell */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        /* Enrollment Cell */
        .enrollment-summary {
            margin-bottom: 10px;
        }
        
        .enrollment-stat {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 12px;
        }
        
        .stat-label {
            color: #6b7280;
        }
        
        .stat-value {
            font-weight: 600;
            color: #374151;
        }
        
        .btn-expand {
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-expand:hover {
            background: #5a67d8;
        }
        
        /* Actions Cell */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .action-buttons .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
            min-width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-edit {
            background: #17a2b8;
            color: white;
        }
        
        .btn-edit:hover {
            background: #138496;
        }
        
        .btn-password {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-password:hover {
            background: #e0a800;
        }
        
        .btn-toggle {
            background: #28a745;
            color: white;
        }
        
        .btn-toggle:hover {
            background: #218838;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        /* Enrollment Details Row */
        .enrollment-details-row {
            background: #f8fafc;
        }
        
        .enrollment-details {
            padding: 20px;
        }
        
        .enrollment-details h4 {
            margin: 0 0 15px 0;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .enrollment-details h4 i {
            color: #667eea;
        }
        
        .enrollment-loading {
            text-align: center;
            color: #6b7280;
            padding: 20px;
        }
        
        .enrollment-loading i {
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .enrollment-loading .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .no-enrollments,
        .enrollment-error {
            text-align: center;
            color: #6b7280;
            padding: 20px;
        }
        
        .no-enrollments i {
            font-size: 24px;
            color: #9ca3af;
            margin-bottom: 10px;
        }
        
        .enrollment-error i {
            font-size: 24px;
            color: #ef4444;
            margin-bottom: 10px;
        }
        

        
        /* Enrollment Details Table */
        .enrollment-details-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .enrollment-details-table thead {
            background: #f1f5f9;
            color: #374151;
        }
        
        .enrollment-details-table th {
            padding: 12px 16px;
            text-align: center;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .enrollment-details-table td {
            padding: 12px 16px;
            text-align: center;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
        }
        
        .enrollment-details-table tbody tr:hover {
            background-color: #f9fafb;
        }
        
        .enrollment-details-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .students-table {
                font-size: 12px;
            }
            
            .students-table th,
            .students-table td {
                padding: 12px 8px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons .btn {
                min-width: 32px;
                height: 32px;
                font-size: 11px;
            }
        }
        
        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .students-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 15px;
            }
            .student-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            .student-actions {
                justify-content: center;
            }
            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            .search-filters {
                flex-direction: column;
            }
            .search-filters select {
                width: 100%;
            }
        }
        .student-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .student-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #28a745, #20c997, #17a2b8);
        }
        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        .student-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }
        .student-avatar {
            flex-shrink: 0;
        }
        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #667eea;
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        .default-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            border: 3px solid #667eea;
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        .student-details {
            flex: 1;
        }
        .student-name {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 4px;
        }
        .student-username {
            font-size: 14px;
            color: #667eea;
            font-weight: 500;
        }
        .student-status {
            background: #f8f9fa;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .student-status.active { background: #d4edda; color: #155724; }
        .student-status.inactive { background: #f8d7da; color: #721c24; }
        .student-info {
            margin-bottom: 20px;
            font-size: 14px;
            background: #f8f9fa;
            padding: 18px;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        .student-info strong {
            color: #495057;
            min-width: 100px;
            display: inline-block;
            font-weight: 600;
        }
        .enrollment-stats {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        .enrollment-stats h4 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .enrollment-stats h4::before {
            content: '📊';
            font-size: 18px;
        }
        .stats-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .stats-row:last-child { margin-bottom: 0; }
        .stats-label { color: #666; font-size: 12px; }
        .stats-value { color: #333; font-weight: 500; font-size: 12px; }
        .student-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            min-width: 80px;
            justify-content: center;
        }
        .btn-edit { 
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); 
            color: white; 
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
        }
        .btn-password { 
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); 
            color: #212529; 
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
        }
        .btn-toggle { 
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%); 
            color: white; 
            box-shadow: 0 2px 4px rgba(108, 117, 125, 0.3);
        }
        .btn-delete { 
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); 
            color: white; 
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
        }
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .status-active { color: #28a745; }
        .status-inactive { color: #dc3545; }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
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
        .close:hover { color: #000; }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        .btn-primary { background: #007bff; color: white; padding: 12px 24px; }
        .btn-secondary { background: #6c757d; color: white; padding: 12px 24px; }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Enhanced Modal Styles */
        .form-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }
        
        .form-section h3 {
            margin: 0 0 20px 0;
            color: #495057;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-section h3 i {
            color: #667eea;
            font-size: 20px;
        }
        
        .file-upload-wrapper {
            position: relative;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #fff;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-upload-wrapper:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .file-upload-wrapper input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-upload-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            color: #6c757d;
        }
        
        .file-upload-info i {
            font-size: 24px;
            color: #667eea;
        }
        
        .file-upload-info span {
            font-size: 14px;
            font-weight: 500;
        }
        
        .image-preview {
            margin-top: 15px;
            text-align: center;
        }
        
        .image-preview img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .image-preview .file-info {
            margin-top: 10px;
            padding: 8px 12px;
            background: #e9ecef;
            border-radius: 6px;
            font-size: 12px;
            color: #495057;
        }
        
        .form-text {
            margin-top: 8px;
            font-size: 12px;
            color: #6c757d;
        }
        
        .form-text i {
            margin-right: 5px;
            color: #667eea;
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
                <img src="../assets/images/brijendra.jpeg" alt="Profile" />
                <div>
                    <div class="name"><?php echo htmlspecialchars(strtoupper($user['full_name'])); ?></div>
                    <div class="role"><?php echo htmlspecialchars(ucfirst($user['type'])); ?></div>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="active" href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-user-tie"></i> Staff</a></li>
                <li><a href="courses.php"><i class="fas fa-graduation-cap"></i> Courses</a></li>
                <li><a href="pending-approvals.php"><i class="fas fa-clock"></i> Pending Approvals</a></li>
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
                    <a href="../index.php" class="home-link">Home</a> / 
                    <a href="../dashboard.php">Dashboard</a> / Students
                </div>
            </div>
        </header>

        <main class="admin-content">
            <div class="page-header">
                <h1><i class="fas fa-user-graduate"></i> Student Management</h1>
                <button onclick="openAddStudentModal()" class="add-student-btn">
                    <i class="fas fa-plus"></i> Add New Student
                </button>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <div class="value"><?php echo count($students); ?></div>
                    <div class="label">Enrolled Students</div>
                </div>
                <div class="stat-card">
                    <h3>Active Students</h3>
                    <div class="value"><?php echo count(array_filter($students, function($s) { return $s['status'] === 'active'; })); ?></div>
                    <div class="label">Currently Active</div>
                </div>
                <div class="stat-card">
                    <h3>Total Enrollments</h3>
                    <div class="value"><?php echo array_sum(array_column($students, 'total_enrollments')); ?></div>
                    <div class="label">Course Enrollments</div>
                </div>
                <div class="stat-card">
                    <h3>Completed Courses</h3>
                    <div class="value"><?php echo array_sum(array_column($students, 'completed_courses')); ?></div>
                    <div class="label">Successfully Completed</div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="search-section">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="studentSearch" placeholder="Search students by name, username, email, or phone..." onkeyup="searchStudents()">
                    <button class="clear-search" onclick="clearSearch()" id="clearSearchBtn" style="display: none;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="search-filters">
                    <select id="statusFilter" onchange="filterStudents()">
                        <option value="active">Active Only</option>
                        <option value="">All Status</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                    <select id="sortBy" onchange="sortStudents()">
                        <option value="created_at">Sort by: Join Date</option>
                        <option value="full_name">Sort by: Name</option>
                        <option value="username">Sort by: Username</option>
                        <option value="total_enrollments">Sort by: Enrollments</option>
                    </select>
                </div>
            </div>

            <!-- Students Table -->
            <div class="section-header">
                <h2><i class="fas fa-users"></i> All Students</h2>
                <p>Manage student accounts, view profiles, and track enrollments</p>
            </div>
            
            <div class="table-responsive">
                <table class="students-table" id="studentsTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Contact Info</th>
                            <th>Status</th>
                            <th>Enrollments</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr class="student-row" data-student-id="<?php echo $student['id']; ?>">
                                <td class="student-info-cell">
                                    <div class="student-avatar">
                                        <?php if (!empty($student['profile_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile" class="profile-img">
                                        <?php else: ?>
                                            <div class="default-avatar">
                                                <i class="fas fa-user-graduate"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="student-details">
                                        <div class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                        <div class="student-username">@<?php echo htmlspecialchars($student['username']); ?></div>
                                    </div>
                                </td>
                                <td class="contact-info">
                                    <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email']); ?></div>
                                    <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></div>
                                    <div><i class="fas fa-venus-mars"></i> <?php echo htmlspecialchars(ucfirst($student['gender'] ?? 'N/A')); ?></div>
                                </td>
                                <td class="status-cell">
                                    <span class="status-badge status-<?php echo $student['status']; ?>">
                                        <?php echo htmlspecialchars(ucfirst($student['status'])); ?>
                                    </span>
                                </td>
                                <td class="enrollment-cell">
                                    <div class="enrollment-summary">
                                        <div class="enrollment-stat">
                                            <span class="stat-label">Total:</span>
                                            <span class="stat-value"><?php echo $student['total_enrollments']; ?></span>
                                        </div>
                                        <div class="enrollment-stat">
                                            <span class="stat-label">Active:</span>
                                            <span class="stat-value"><?php echo $student['active_enrollments']; ?></span>
                                        </div>
                                        <div class="enrollment-stat">
                                            <span class="stat-label">Completed:</span>
                                            <span class="stat-value"><?php echo $student['completed_courses']; ?></span>
                                        </div>
                                    </div>
                                    <?php if ($student['total_enrollments'] > 0): ?>
                                        <button class="btn btn-expand" onclick="toggleEnrollments(<?php echo $student['id']; ?>)" data-expanded="false">
                                            <i class="fas fa-chevron-down"></i> View Details
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td class="date-cell">
                                    <?php echo date('M d, Y', strtotime($student['created_at'])); ?>
                                </td>
                                <td class="actions-cell">
                                    <div class="action-buttons">
                                        <button class="btn btn-icard" onclick="showStudentICard(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['full_name']); ?>', '<?php echo htmlspecialchars($student['username']); ?>')" title="View Student ID Card">
                                            <i class="fas fa-id-card"></i>
                                        </button>
                                        <button class="btn btn-edit" onclick="editStudent(<?php echo $student['id']; ?>)" title="Edit Student">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-password" onclick="openPasswordModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['username']); ?>')" title="Change Password">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <button class="btn btn-toggle" onclick="toggleStatus(<?php echo $student['id']; ?>, '<?php echo $student['status'] === 'active' ? 'inactive' : 'active'; ?>')" title="<?php echo $student['status'] === 'active' ? 'Deactivate' : 'Activate'; ?> Student">
                                            <i class="fas fa-<?php echo $student['status'] === 'active' ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                        </button>
                                        <button class="btn btn-delete" onclick="deleteStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['full_name']); ?>')" title="Delete Student">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Expandable Enrollment Details Row -->
                            <tr class="enrollment-details-row" id="enrollment-<?php echo $student['id']; ?>" style="display: none;">
                                <td colspan="6">
                                    <div class="enrollment-details">
                                        <h4><i class="fas fa-graduation-cap"></i> Enrollment Details for <?php echo htmlspecialchars($student['full_name']); ?></h4>
                                        <div class="enrollment-loading">
                                            <i class="fas fa-spinner fa-spin"></i> Loading enrollment details...
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

        <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Add New Student</h2>
                <span class="close" onclick="closeAddStudentModal()">&times;</span>
            </div>
            
            <form id="addStudentForm" method="POST" action="students.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_student">
                
                <!-- Info Alert -->
                <div class="alert alert-info" style="background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note:</strong> A unique User ID will be automatically generated in the format: <code>s[YEAR][3-digit-number]</code> (e.g., s2025001)
                </div>
                
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" required placeholder="Enter student's full name">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required placeholder="student@example.com">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="+91-9876543210">
                        </div>
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="qualification">Current Qualification *</label>
                            <select id="qualification" name="qualification" required>
                                <option value="">Select Qualification</option>
                                <option value="10th">10th Standard</option>
                                <option value="12th">12th Standard</option>
                                <option value="Diploma">Diploma</option>
                                <option value="B.Tech">B.Tech</option>
                                <option value="M.Tech">M.Tech</option>
                                <option value="B.Sc">B.Sc</option>
                                <option value="M.Sc">M.Sc</option>
                                <option value="B.Com">B.Com</option>
                                <option value="M.Com">M.Com</option>
                                <option value="BBA">BBA</option>
                                <option value="MBA">MBA</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3" placeholder="Enter complete address"></textarea>
                    </div>
                </div>
                
                <!-- Academic Information Section -->
                <div class="form-section">
                    <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="joining_date">Joining Date</label>
                            <input type="date" id="joining_date" name="joining_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="previous_institute">Previous Institute (Optional)</label>
                            <input type="text" id="previous_institute" name="previous_institute" placeholder="Name of previous institute">
                        </div>
                    </div>
                </div>
                
                <!-- Document Upload Section -->
                <div class="form-section">
                    <h3><i class="fas fa-file-upload"></i> Document Upload</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="profile_image">Profile Image *</label>
                            <div class="file-upload-wrapper">
                                <input type="file" id="profile_image" name="profile_image" required accept="image/*" onchange="previewImage(this, 'profile-preview')">
                                <div class="file-upload-info">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Click to upload or drag & drop</span>
                                </div>
                            </div>
                            <div id="profile-preview" class="image-preview"></div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Max size: 200KB. Formats: JPG, JPEG, PNG
                            </small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="marksheet">Marksheet/Certificate *</label>
                            <div class="file-upload-wrapper">
                                <input type="file" id="marksheet" name="marksheet" required accept=".pdf,.jpg,.jpeg,.png" onchange="previewImage(this, 'marksheet-preview')">
                                <div class="file-upload-info">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Click to upload or drag & drop</span>
                                </div>
                            </div>
                            <div id="marksheet-preview" class="image-preview"></div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Max size: 200KB. Formats: PDF, JPG, JPEG, PNG
                            </small>
                        </div>
                        <div class="form-group">
                            <label for="aadhaar_card">Aadhaar Card (Optional)</label>
                            <div class="file-upload-wrapper">
                                <input type="file" id="aadhaar_card" name="aadhaar_card" accept="image/*" onchange="previewImage(this, 'aadhaar-preview')">
                                <div class="file-upload-info">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Click to upload or drag & drop</span>
                                </div>
                            </div>
                            <div id="aadhaar-preview" class="image-preview"></div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Max size: 200KB. Formats: JPG, JPEG, PNG
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddStudentModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add Student
                    </button>
                </div>
            </form>
        </div>
    </div>



    <!-- Password Update Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-key"></i> Update Password</h2>
                <span class="close" onclick="closePasswordModal()">&times;</span>
            </div>
            
            <form method="POST" action="students.php">
                <input type="hidden" name="action" value="update_password">
                <input type="hidden" id="password_student_id" name="student_id">
                
                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required 
                           minlength="8" placeholder="Enter new password (min 8 characters)">
                    <small>Password must be at least 8 characters long and contain lowercase, uppercase, and number</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           minlength="8" placeholder="Confirm new password">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mobile Menu JavaScript -->
    <script src="../assets/js/mobile-menu.js"></script>
    
    <script>
        // Image preview functionality
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            
            if (file) {
                // Clear previous preview
                preview.innerHTML = '';
                
                // Check file size (200KB limit)
                const fileSize = file.size / 1024; // Convert to KB
                if (fileSize > 200) {
                    alert('File size must be less than 200KB. Current size: ' + fileSize.toFixed(1) + 'KB');
                    input.value = '';
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid file type: JPG, JPEG, PNG, or PDF');
                    input.value = '';
                    return;
                }
                
                if (file.type.startsWith('image/')) {
                    // Show image preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Preview';
                        preview.appendChild(img);
                        
                        // Add file info
                        const fileInfo = document.createElement('div');
                        fileInfo.className = 'file-info';
                        fileInfo.innerHTML = `
                            <strong>${file.name}</strong><br>
                            Size: ${fileSize.toFixed(1)}KB<br>
                            Type: ${file.type}
                        `;
                        preview.appendChild(fileInfo);
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Show file info for PDFs
                    const fileInfo = document.createElement('div');
                    fileInfo.className = 'file-info';
                    fileInfo.innerHTML = `
                        <i class="fas fa-file-pdf" style="font-size: 48px; color: #dc3545;"></i><br>
                        <strong>${file.name}</strong><br>
                        Size: ${fileSize.toFixed(1)}KB<br>
                        Type: ${file.type}
                    `;
                    preview.appendChild(fileInfo);
                }
            }
        }
        
        // Form validation
        function validateStudentForm() {
            const form = document.getElementById('addStudentForm');
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    field.style.borderColor = '#e1e5e9';
                }
            });
            
            if (!isValid) {
                alert('Please fill in all required fields marked with *');
            }
            
            return isValid;
        }
        
        // Enhanced form submission
        document.getElementById('addStudentForm').addEventListener('submit', function(e) {
            if (!validateStudentForm()) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Student...';
            submitBtn.disabled = true;
            
            // Re-enable after a delay (in case of error)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
        });
        
        function editStudent(studentId) {
            // For now, redirect to a potential edit page or show a message
            alert('Edit functionality will be implemented in the next update. Student ID: ' + studentId);
        }
        
        function deleteStudent(studentId, studentName) {
            if (confirm(`Are you sure you want to delete student "${studentName}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'students.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_student';
                
                const studentIdInput = document.createElement('input');
                studentIdInput.type = 'hidden';
                studentIdInput.name = 'student_id';
                studentIdInput.value = studentId;
                
                form.appendChild(actionInput);
                form.appendChild(studentIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function openPasswordModal(studentId, username) {
            document.getElementById('password_student_id').value = studentId;
            document.getElementById('passwordModal').style.display = 'block';
            document.getElementById('new_password').focus();
        }
        
        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
        }
        
        function toggleStatus(studentId, newStatus) {
            const action = newStatus === 'active' ? 'activate' : 'deactivate';
            if (confirm(`Are you sure you want to ${action} this student?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'students.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'toggle_status';
                
                const studentIdInput = document.createElement('input');
                studentIdInput.type = 'hidden';
                studentIdInput.name = 'student_id';
                studentIdInput.value = studentId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'new_status';
                statusInput.value = newStatus;
                
                form.appendChild(actionInput);
                form.appendChild(studentIdInput);
                form.appendChild(statusInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addStudentModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
        
        // Function to close all enrollment rows
        function closeAllEnrollments() {
            const allEnrollmentRows = document.querySelectorAll('[id^="enrollment-"]');
            allEnrollmentRows.forEach(row => {
                row.style.display = 'none';
                const studentId = row.id.replace('enrollment-', '');
                const button = document.querySelector(`[onclick="toggleEnrollments(${studentId})"]`);
                if (button) {
                    button.innerHTML = '<i class="fas fa-chevron-down"></i> View Details';
                    button.setAttribute('data-expanded', 'false');
                }
                // Reset loading state
                const loadingDiv = row.querySelector('.enrollment-loading');
                if (loadingDiv) {
                    loadingDiv.innerHTML = '<div class="enrollment-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
                }
            });
        }
        
        // Search, Filter, and Sort Functions
        let allStudents = [];
        

        
        function searchStudents() {
            const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
            const clearBtn = document.getElementById('clearSearchBtn');
            
            if (searchTerm.length > 0) {
                clearBtn.style.display = 'block';
            } else {
                clearBtn.style.display = 'none';
            }
            
            // Close all enrollments when searching to ensure clean state
            closeAllEnrollments();
            
            // Apply both search and status filter
            filterStudents();
        }
        
        function clearSearch() {
            document.getElementById('studentSearch').value = '';
            document.getElementById('clearSearchBtn').style.display = 'none';
            
            // Re-apply status filter after clearing search
            filterStudents();
        }
        
        function filterStudents() {
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
            
            allStudents.forEach(student => {
                let show = true;
                
                // Apply status filter
                if (statusFilter && student.status !== statusFilter) {
                    show = false;
                }
                
                // Apply search filter
                if (searchTerm && !(student.name.includes(searchTerm) || 
                                   student.username.includes(searchTerm) || 
                                   student.email.includes(searchTerm))) {
                    show = false;
                }
                
                student.element.style.display = show ? 'table-row' : 'none';
                
                // Also hide/show the corresponding enrollment row
                const enrollmentRow = document.getElementById(`enrollment-${student.element.dataset.studentId}`);
                if (enrollmentRow) {
                    enrollmentRow.style.display = 'none'; // Always hide enrollment rows when filtering
                }
            });
            
                        // Close all enrollment rows when filtering to ensure clean state
            closeAllEnrollments();
            
            updateStudentCount();
        }
        
        // Apply default filter on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Store all students data for search/filter operations
            const studentRows = document.querySelectorAll('.student-row');
            allStudents = Array.from(studentRows).map(row => ({
                element: row,
                name: row.querySelector('.student-name').textContent.toLowerCase(),
                username: row.querySelector('.student-username').textContent.toLowerCase(),
                email: row.querySelector('.contact-info').textContent.toLowerCase(),
                status: row.querySelector('.status-badge').textContent.toLowerCase().trim(),
                enrollments: parseInt(row.querySelector('.enrollment-summary .stat-value').textContent) || 0,
                created_at: new Date(row.querySelector('.date-cell').textContent.trim())
            }));
            
            // Apply default filter to show only active students
            filterStudents();
        });
        
        function updateStudentCount() {
            const visibleCount = allStudents.filter(student => 
                student.element.style.display !== 'none'
            ).length;
            
            // Update the total students count in the stats
            const totalStudentsStat = document.querySelector('.stat-card .value');
            if (totalStudentsStat) {
                totalStudentsStat.textContent = visibleCount;
            }
        }
        
        // Enrollment Toggle Function
        function toggleEnrollments(studentId) {
            const enrollmentRow = document.getElementById(`enrollment-${studentId}`);
            const expandButton = event.target;
            
            // Ensure we have the button element (in case event.target is a child element)
            const button = expandButton.tagName === 'BUTTON' ? expandButton : expandButton.closest('button');
            
            if (enrollmentRow.style.display === 'none') {
                // Close any other open enrollment rows first
                const allEnrollmentRows = document.querySelectorAll('[id^="enrollment-"]');
                allEnrollmentRows.forEach(row => {
                    if (row.style.display !== 'none' && row.id !== `enrollment-${studentId}`) {
                        row.style.display = 'none';
                        // Reset button text for other rows
                        const otherStudentId = row.id.replace('enrollment-', '');
                        const otherButton = document.querySelector(`[onclick="toggleEnrollments(${otherStudentId})"]`);
                        if (otherButton) {
                            otherButton.innerHTML = '<i class="fas fa-chevron-down"></i> View Details';
                            otherButton.setAttribute('data-expanded', 'false');
                        }
                    }
                });
                
                // Show enrollment details for current student
                enrollmentRow.style.display = 'table-row';
                button.innerHTML = '<i class="fas fa-chevron-up"></i> Hide Details';
                button.setAttribute('data-expanded', 'true');
                
                // Always load fresh enrollment data when expanding
                loadEnrollmentDetails(studentId);
            } else {
                // Hide enrollment details
                enrollmentRow.style.display = 'none';
                button.innerHTML = '<i class="fas fa-chevron-down"></i> View Details';
                button.setAttribute('data-expanded', 'false');
                
                // Clear enrollment data to ensure fresh data on next expansion
                const loadingDiv = enrollmentRow.querySelector('.enrollment-loading');
                if (loadingDiv) {
                    loadingDiv.innerHTML = '<div class="enrollment-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
                }
            }
        }
        
        // Load Enrollment Details
        function loadEnrollmentDetails(studentId) {
            const enrollmentRow = document.getElementById(`enrollment-${studentId}`);
            const loadingDiv = enrollmentRow.querySelector('.enrollment-loading');
            
            // Fetch real enrollment data from the server
            fetch(`students.php?action=get_enrollments&student_id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.enrollments.length > 0) {
                        let tableRows = '';
                        data.enrollments.forEach(enrollment => {
                            const statusClass = enrollment.status.toLowerCase() === 'enrolled' ? 'status-active' : 
                                              enrollment.status.toLowerCase() === 'completed' ? 'status-success' : 'status-pending';
                            
                            tableRows += `
                                <tr>
                                    <td>${enrollment.course_name}</td>
                                    <td>${enrollment.sub_course_name}</td>
                                    <td>${enrollment.category}</td>
                                    <td>${enrollment.enrollment_date}</td>
                                    <td><span class="status-badge ${statusClass}">${enrollment.status}</span></td>
                                </tr>
                            `;
                        });
                        
                        loadingDiv.innerHTML = `
                            <div class="enrollment-content">
                                <div class="enrollment-table">
                                    <table class="enrollment-details-table">
                                        <thead>
                                            <tr>
                                                <th>Course</th>
                                                <th>Sub-Course</th>
                                                <th>Category</th>
                                                <th>Enrollment Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${tableRows}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        `;
                    } else {
                        loadingDiv.innerHTML = `
                            <div class="enrollment-content">
                                <div class="no-enrollments">
                                    <i class="fas fa-info-circle"></i>
                                    <p>No enrollment details found for this student.</p>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching enrollment data:', error);
                    loadingDiv.innerHTML = `
                        <div class="enrollment-content">
                            <div class="enrollment-error">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p>Failed to load enrollment details. Please try again.</p>
                            </div>
                        </div>
                    `;
                });
        }
        
        function sortStudents() {
            const sortBy = document.getElementById('sortBy').value;
            const studentsTable = document.getElementById('studentsTable');
            const tbody = studentsTable.querySelector('tbody');
            const visibleStudents = allStudents.filter(student => 
                student.element.style.display !== 'none'
            );
            
            // Sort the visible students
            visibleStudents.sort((a, b) => {
                switch(sortBy) {
                    case 'full_name':
                        return a.name.localeCompare(b.name);
                    case 'username':
                        return a.username.localeCompare(b.username);
                    case 'total_enrollments':
                        return b.enrollments - a.enrollments;
                    case 'created_at':
                    default:
                        return b.created_at - a.created_at;
                }
            });
            
            // Reorder the DOM elements
            visibleStudents.forEach(student => {
                tbody.appendChild(student.element);
                
                // Also move the corresponding enrollment row
                const enrollmentRow = document.getElementById(`enrollment-${student.element.dataset.studentId}`);
                if (enrollmentRow) {
                    tbody.appendChild(enrollmentRow);
                }
            });
        }
        
        function updateStudentCount() {
            const visibleCount = allStudents.filter(student => 
                student.element.style.display !== 'none'
            ).length;
            
            const totalCount = allStudents.length;
            const sectionHeader = document.querySelector('.section-header h2');
            sectionHeader.innerHTML = `<i class="fas fa-users"></i> Students (${visibleCount}/${totalCount})`;
        }
        
        // Add Student Modal Functions
        function openAddStudentModal() {
            document.getElementById('addStudentModal').style.display = 'block';
        }
        
        function closeAddStudentModal() {
            document.getElementById('addStudentModal').style.display = 'none';
            // Reset form
            document.getElementById('addStudentForm').reset();
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addStudentModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
        
        // Show Student ID Card
        function showStudentICard(studentId, fullName, username) {
            // Create modal for i-card display
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.id = 'iCardModal';
            modal.style.display = 'block';
            
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 500px; max-height: 80vh; overflow-y: auto;">
                    <div class="modal-header">
                        <h2><i class="fas fa-id-card"></i> Student ID Card</h2>
                        <span class="close" onclick="closeICardModal()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div id="idCardContainer" style="text-align: center; padding: 20px;">
                            <div style="text-align: center; padding: 40px;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #667eea;"></i>
                                <p style="margin-top: 10px;">Loading ID Card...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Fetch the complete ID card from id.php (same as student dashboard)
            loadStudentIDCard(studentId, fullName, username);
        }
        
        function closeICardModal() {
            const modal = document.getElementById('iCardModal');
            if (modal) {
                modal.remove();
            }
        }
        

        
        function loadStudentIDCard(studentId, fullName, username) {
            // Create form data - only send student ID (same as student dashboard)
            const formData = new FormData();
            formData.append('student_id', studentId);
            
            // Fetch the content from id.php (same as student dashboard)
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
                    // Create the styled ID card (same as student dashboard)
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
                                <p class="id-card-name" style="font-size: 1.2rem; font-weight: 700; margin: 0.3rem 0;">${idCard.querySelector('.id-card-name')?.textContent || fullName}</p>
                                <p class="id-card-studentid" style="font-size: 0.9rem; color: #374151; margin-bottom: 1rem;">STUDENT ID: ${idCard.querySelector('.id-card-studentid')?.textContent?.replace('STUDENT ID: ', '') || username}</p>
                                <div class="id-card-row" style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 0.8rem;">
                                    <div class="id-card-info" style="text-align: left; font-size: 0.9rem;">
                                        <p style="margin: 0.4rem 0;"><span class="label" style="font-weight: 600; color: #1f2937;">Batch:</span> ${idCard.querySelector('.id-card-info .label')?.nextSibling?.textContent?.trim() || new Date().getFullYear()}</p>
                                        <p style="margin: 0.4rem 0;"><span class="label" style="font-weight: 600; color: #1f2937;">Expires:</span> ${idCard.querySelector('.id-card-info .label:last-child')?.nextSibling?.textContent?.trim() || new Date().getFullYear() + 1}</p>
                                    </div>
                                    <img src="${idCard.querySelector('.id-card-qr')?.src || ''}" class="id-card-qr" alt="QR Code" style="width: 90px; height: 90px;">
                                </div>
                            </div>
                            <div class="id-card-footer" style="margin-top: 1rem; font-size: 0.75rem; text-align: center; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 0.5rem;">If found, please return to the university admin office.</div>
                        </div>
                    `;
                    
                    document.getElementById('idCardContainer').innerHTML = styledCard;
                } else {
                    document.getElementById('idCardContainer').innerHTML = '<p style="text-align: center; color: #666;">Error loading ID card</p>';
                }
            })
            .catch(error => {
                console.error('Error loading ID card:', error);
                document.getElementById('idCardContainer').innerHTML = '<p style="text-align: center; color: #666;">Error loading ID card</p>';
            });
        }
    </script>
</body>
</html>
