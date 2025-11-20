<?php
// Include session manager for user authentication
require_once 'includes/session_manager.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GICT - Global Institute of Computer Technology</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/news-events.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .modal-header .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }
        
        .modal-header .close:hover {
            opacity: 0.7;
        }
        
        .modal-body {
            padding: 30px;
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
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        @media (max-width: 768px) {
            .modal-content {
                margin: 10% auto;
                width: 95%;
            }
            
            .modal-header {
                padding: 15px 20px;
            }
            
            .modal-header h2 {
                font-size: 1.3rem;
            }
            
            .modal-body {
                padding: 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<?php include 'header.php'; ?>

<div class="main-content">
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-menu">
                <a href="about-gict.php" class="menu-item"><i class="fas fa-info-circle"></i> ABOUT GICT</a>
                <a href="#" class="menu-item"><i class="fas fa-user-graduate"></i> STUDENT SECTION</a>
                <!-- <a href="#" class="menu-item"><i class="fas fa-book"></i> COURSE SECTION</a>
                <a href="#" class="menu-item"><i class="fas fa-download"></i> DOWNLOAD RESULT</a> -->
                <a href="#" class="menu-item"><i class="fas fa-users"></i> GICT TEAM</a>
                <a href="#" class="menu-item"><i class="fas fa-trophy"></i> ACHIEVEMENT</a>
                <a href="#" class="menu-item"><i class="fas fa-laptop"></i> ONLINE STUDY MATERIAL</a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Main Image Section -->
            <div class="slideshow-container">
                <div class="slide active">
                    <img src="assets/images/slide 1.jpg" alt="Training Session">
                </div>
                <div class="slide">
                    <img src="assets/images/slide 2.jpg" alt="Classroom">
                </div>
                <div class="slide">
                    <img src="assets/images/slide 3.jpg" alt="Computer Lab">
                </div>
                <div class="slide">
                    <img src="assets/images/slide 4.jpg" alt="Students">
                </div>
            </div>

            <!-- Include slideshow script -->
            <script src="assets/js/slideshow.js"></script>

            <div class="mission-wrapper">
                <h1 class="mission-heading">Mission Digital India Training & Placement with GICT Team</h1>
            </div>

            <div class="director-section">
                <div class="profile-card">
                    <div class="profile-section">
                        <img src="assets/images/brijendra.jpeg" alt="Mr. Brijendra Patel" class="profile-img">
                        <h3>Mr. Brijendra Patel</h3>
                        <p>Founder & Director</p>
                        <div class="button-group">
                            <button class="btn yellow-btn">PROFILE</button>
                            <button class="btn yellow-btn">MESSAGE</button>
                        </div>
                    </div>
                </div>

                <div class="course-list">
                    <ul>
                        <li>Computer Course</li>
                        <li>Yoga Certificate</li>
                        <li>Vocational Course</li>
                        <li>Beautician Certificate</li>
                        <li>Tailoring Certificate</li>
                    </ul>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include 'news-events.php'; ?>
<?php include 'gallery.php'; ?>

<!-- WhatsApp Button -->
<div class="whatsapp-float">
    <a href="https://wa.me/918433377466" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>
</div>

<?php include 'footer.php'; ?>

    <!-- Enquiry Modal -->
    <div id="enquiryModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-question-circle"></i> Course Inquiry</h2>
                <span class="close" onclick="closeEnquiryModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="enquiryMessage" style="display: none; margin-bottom: 20px; padding: 15px; border-radius: 8px; font-weight: 500;"></div>
                
                <form id="enquiryForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="modal_name">Full Name *</label>
                            <input type="text" id="modal_name" name="name" required placeholder="Enter your full name">
                        </div>
                        <div class="form-group">
                            <label for="modal_mobile">Mobile Number *</label>
                            <input type="tel" id="modal_mobile" name="mobile" required placeholder="Enter 10-digit mobile number" pattern="[0-9]{10}" maxlength="10">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="modal_email">Email Address</label>
                        <input type="email" id="modal_email" name="email" placeholder="Enter your email address (optional)">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="modal_course_id">Course Category</label>
                            <select id="modal_course_id" name="course_id" onchange="updateModalSubCourses()">
                                <option value="">Select Course Category</option>
                                <option value="1">Technology</option>
                                <option value="2">Marketing</option>
                                <option value="3">Fashion</option>
                                <option value="4">Wellness</option>
                                <option value="5">Skills</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="modal_sub_course_id">Specific Course</label>
                            <select id="modal_sub_course_id" name="sub_course_id">
                                <option value="">Select Specific Course</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="modal_message">Message</label>
                        <textarea id="modal_message" name="message" rows="3" placeholder="Tell us about your interest in the course or any specific questions you have..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeEnquiryModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-paper-plane"></i> Submit Inquiry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="assets/js/homepage-mobile-menu.js"></script>
    <script>
        // Enquiry Modal Functions
        function openEnquiryModal() {
            document.getElementById('enquiryModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
            // Hide any previous messages
            document.getElementById('enquiryMessage').style.display = 'none';
        }
        
        function closeEnquiryModal() {
            document.getElementById('enquiryModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            // Reset form
            document.getElementById('enquiryForm').reset();
            // Hide any messages
            document.getElementById('enquiryMessage').style.display = 'none';
        }
        
        function showMessage(message, isSuccess = true) {
            const messageDiv = document.getElementById('enquiryMessage');
            messageDiv.textContent = message;
            messageDiv.style.display = 'block';
            messageDiv.style.backgroundColor = isSuccess ? '#d4edda' : '#f8d7da';
            messageDiv.style.color = isSuccess ? '#155724' : '#721c24';
            messageDiv.style.border = isSuccess ? '1px solid #c3e6cb' : '1px solid #f5c6cb';
        }
        
        function updateModalSubCourses() {
            const courseId = document.getElementById('modal_course_id').value;
            const subCourseSelect = document.getElementById('modal_sub_course_id');
            
            // Reset sub-course dropdown
            subCourseSelect.innerHTML = '<option value="">Select Specific Course</option>';
            
            // Course options based on selected category
            const courseOptions = {
                '1': [ // Technology
                    {value: '1', text: 'CCC (Course on Computer Concepts)'},
                    {value: '2', text: 'ADCA (Advanced Diploma in Computer Applications)'},
                    {value: '3', text: 'PGDCA (Post Graduate Diploma in Computer Applications)'},
                    {value: '4', text: 'DCA (Diploma in Computer Applications)'},
                    {value: '5', text: 'Tally ERP 9'}
                ],
                '2': [ // Marketing
                    {value: '6', text: 'SEO (Search Engine Optimization)'},
                    {value: '7', text: 'SEM (Search Engine Marketing)'},
                    {value: '8', text: 'Social Media Marketing'},
                    {value: '9', text: 'Content Marketing'}
                ],
                '3': [ // Fashion
                    {value: '10', text: 'Basic Stitching'},
                    {value: '11', text: 'Pants Sewing'},
                    {value: '12', text: 'Blouse Sewing'},
                    {value: '13', text: 'Kurta Sewing'},
                    {value: '14', text: 'Dress Making'}
                ],
                '4': [ // Wellness
                    {value: '15', text: 'Yoga Certificate'},
                    {value: '16', text: 'Health & Wellness'},
                    {value: '17', text: 'Meditation Course'}
                ],
                '5': [ // Skills
                    {value: '18', text: 'Beautician Certificate'},
                    {value: '19', text: 'Vocational Course'},
                    {value: '20', text: 'Skill Development'}
                ]
            };
            
            if (courseId && courseOptions[courseId]) {
                courseOptions[courseId].forEach(option => {
                    const optionElement = document.createElement('option');
                    optionElement.value = option.value;
                    optionElement.textContent = option.text;
                    subCourseSelect.appendChild(optionElement);
                });
            }
        }
        
        // Form submission handler
        document.getElementById('enquiryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;
            
            // Get form data
            const formData = new FormData(this);
            
            // Submit via AJAX
            fetch('inquiry.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, true);
                    // Reset form after successful submission
                    setTimeout(() => {
                        closeEnquiryModal();
                    }, 2000);
                } else {
                    showMessage(data.message, false);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Failed to submit inquiry. Please try again.', false);
            })
            .finally(() => {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('enquiryModal');
            if (event.target === modal) {
                closeEnquiryModal();
            }
        }
        
        // Add any custom JavaScript here
        document.addEventListener('DOMContentLoaded', function() {
            // Your existing JavaScript
        });
    </script>
</body>
</html>