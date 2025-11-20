<?php
require_once 'config/database.php';
require_once 'includes/session_manager.php';

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $course_id = intval($_POST['course_id'] ?? 0);
        $sub_course_id = intval($_POST['sub_course_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        
        // Validation
        if (empty($name)) {
            throw new Exception("Name is required.");
        }
        if (empty($mobile)) {
            throw new Exception("Mobile number is required.");
        }
        if (!preg_match('/^[0-9]{10}$/', $mobile)) {
            throw new Exception("Please enter a valid 10-digit mobile number.");
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }
        
        // Insert inquiry
        $sql = "INSERT INTO inquiries (name, mobile, email, course_id, sub_course_id, message, status) VALUES (?, ?, ?, ?, ?, ?, 'new')";
        $result = insertData($sql, [$name, $mobile, $email, $course_id ?: null, $sub_course_id ?: null, $message]);
        
        if ($result) {
            // Check if this is an AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => true, 'message' => 'Thank you for your inquiry! We will contact you soon.']);
                exit;
            } else {
                $success_message = "Thank you for your inquiry! We will contact you soon.";
                $_POST = [];
            }
        } else {
            throw new Exception("Failed to submit inquiry. Please try again.");
        }
    } catch (Exception $e) {
        // Check if this is an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        } else {
            $error_message = $e->getMessage();
        }
    }
}

// Get courses and sub-courses for dropdowns
$courses = getRows("SELECT id, name FROM courses WHERE status = 'active' ORDER BY name");
$sub_courses = getRows("SELECT id, name, course_id FROM sub_courses WHERE status = 'active' ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Inquiry - GICT Institute</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .inquiry-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .inquiry-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .inquiry-header h1 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .inquiry-header p {
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
            width: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .inquiry-container {
                margin: 1rem;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="inquiry-container">
        <div class="inquiry-header">
            <h1><i class="fas fa-question-circle"></i> Course Inquiry</h1>
            <p>Interested in our courses? Fill out the form below and we'll get back to you soon!</p>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                           placeholder="Enter your full name">
                </div>
                <div class="form-group">
                    <label for="mobile">Mobile Number *</label>
                    <input type="tel" id="mobile" name="mobile" required 
                           value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>"
                           placeholder="Enter 10-digit mobile number"
                           pattern="[0-9]{10}" maxlength="10">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       placeholder="Enter your email address (optional)">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="course_id">Course Category</label>
                    <select id="course_id" name="course_id" onchange="updateSubCourses()">
                        <option value="">Select Course Category</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" 
                                    <?php echo (isset($_POST['course_id']) && $_POST['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sub_course_id">Specific Course</label>
                    <select id="sub_course_id" name="sub_course_id">
                        <option value="">Select Specific Course</option>
                        <?php foreach ($sub_courses as $sub_course): ?>
                            <option value="<?php echo $sub_course['id']; ?>" 
                                    data-course-id="<?php echo $sub_course['course_id']; ?>"
                                    <?php echo (isset($_POST['sub_course_id']) && $_POST['sub_course_id'] == $sub_course['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sub_course['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="4" 
                          placeholder="Tell us about your interest in the course or any specific questions you have..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Submit Inquiry
            </button>
        </form>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        function updateSubCourses() {
            const courseId = document.getElementById('course_id').value;
            const subCourseSelect = document.getElementById('sub_course_id');
            
            // Reset sub-course dropdown
            subCourseSelect.innerHTML = '<option value="">Select Specific Course</option>';
            
            if (courseId) {
                // Show only sub-courses for selected course
                const options = subCourseSelect.querySelectorAll('option[data-course-id]');
                options.forEach(option => {
                    if (option.getAttribute('data-course-id') === courseId) {
                        option.style.display = 'block';
                        subCourseSelect.appendChild(option);
                    }
                });
            } else {
                // Show all sub-courses
                const options = subCourseSelect.querySelectorAll('option[data-course-id]');
                options.forEach(option => {
                    option.style.display = 'block';
                    subCourseSelect.appendChild(option);
                });
            }
        }
        
        // Initialize sub-courses based on selected course
        document.addEventListener('DOMContentLoaded', function() {
            updateSubCourses();
        });
    </script>
</body>
</html>
