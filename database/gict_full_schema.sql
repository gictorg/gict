-- GICT Institute Full Database Schema
-- Consolidates all tables, workflows, and performance enhancements

SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if they exist (in correct order due to foreign keys)
DROP TABLE IF EXISTS assignment_submissions;
DROP TABLE IF EXISTS assignments;
DROP TABLE IF EXISTS student_attendance;
DROP TABLE IF EXISTS certificates;
DROP TABLE IF EXISTS student_marks;
DROP TABLE IF EXISTS payment_verification;
DROP TABLE IF EXISTS payment_methods;
DROP TABLE IF EXISTS student_documents;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS faculty_courses;
DROP TABLE IF EXISTS student_enrollments;
DROP TABLE IF EXISTS course_subjects;
DROP TABLE IF EXISTS sub_courses;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS course_categories;
DROP TABLE IF EXISTS user_types;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Create user types table
CREATE TABLE user_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    permissions JSON,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Create course categories table
CREATE TABLE course_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    profile_image VARCHAR(500),
    user_type_id INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    experience_years INT DEFAULT 0,
    joining_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_type_id) REFERENCES user_types(id) ON DELETE RESTRICT
);

-- 4. Create main courses table
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    duration VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES course_categories(id) ON DELETE CASCADE
);

-- 5. Create sub-courses table
CREATE TABLE sub_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    fee DECIMAL(10,2) NOT NULL,
    duration VARCHAR(100),
    number_of_semesters INT NOT NULL DEFAULT 1,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- 6. Create course subjects table
CREATE TABLE course_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sub_course_id INT NOT NULL,
    semester INT NOT NULL DEFAULT 1,
    subject_name VARCHAR(255) NOT NULL,
    subject_code VARCHAR(50),
    max_marks INT NOT NULL DEFAULT 100,
    theory_marks INT DEFAULT 100,
    practical_marks INT DEFAULT 0,
    is_compulsory BOOLEAN DEFAULT TRUE,
    credit_hours DECIMAL(3,1) DEFAULT 3.0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE CASCADE
);

-- 7. Create student enrollments table
CREATE TABLE student_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sub_course_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    completion_date DATE,
    marksheet_no VARCHAR(100) UNIQUE,
    status ENUM('payment_pending', 'pending', 'enrolled', 'completed', 'dropped', 'rejected') DEFAULT 'payment_pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE CASCADE
);

-- 8. Create payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sub_course_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(255),
    payment_proof_url VARCHAR(500),
    payment_verified_by INT,
    payment_verified_at TIMESTAMP NULL,
    payment_notes TEXT,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 9. Create payment methods table
CREATE TABLE payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('online', 'offline') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 10. Create payment verification table
CREATE TABLE payment_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    verified_by INT NOT NULL,
    verification_status ENUM('verified', 'rejected', 'pending') DEFAULT 'pending',
    verification_notes TEXT,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE CASCADE
);

-- 11. Create student marks table
CREATE TABLE student_marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    subject_id INT NOT NULL,
    semester INT NOT NULL DEFAULT 1,
    theory_marks INT DEFAULT 0,
    practical_marks INT DEFAULT 0,
    total_marks INT DEFAULT 0,
    grade VARCHAR(5),
    remarks TEXT,
    checked_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES course_subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (checked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY idx_enrollment_subject (enrollment_id, subject_id)
);

-- 12. Create certificates table
CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    certificate_number VARCHAR(100) UNIQUE NOT NULL,
    certificate_url VARCHAR(500),
    marksheet_url VARCHAR(500),
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generated_by INT NOT NULL,
    status ENUM('generated', 'issued', 'revoked') DEFAULT 'generated',
    FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE
);

-- 13. Create student documents table
CREATE TABLE student_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type ENUM('profile', 'marksheet', 'aadhaar', 'pan', 'other') NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    file_name VARCHAR(255),
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 14. Create faculty courses table
CREATE TABLE faculty_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    sub_course_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_faculty_course (faculty_id, sub_course_id)
);

-- 15. Create student attendance table
CREATE TABLE student_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
    remarks TEXT,
    marked_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_enrollment_date (enrollment_id, attendance_date)
);

-- 16. Create assignments table
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sub_course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATETIME NOT NULL,
    max_score INT DEFAULT 100,
    created_by INT NOT NULL,
    status ENUM('active', 'draft', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- 17. Create assignment submissions table
CREATE TABLE assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    enrollment_id INT NOT NULL,
    submission_text TEXT,
    file_url VARCHAR(500),
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    score INT,
    feedback TEXT,
    graded_by INT,
    status ENUM('submitted', 'graded', 'resubmission_requested') DEFAULT 'submitted',
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY idx_assignment_enrollment (assignment_id, enrollment_id)
);

-- Sample Data

-- 18. Insert user types
INSERT INTO user_types (name, description, permissions, status) VALUES
('admin', 'Administrator with full access', '{"dashboard": true, "users": true, "courses": true, "enrollments": true, "payments": true, "reports": true}', 'active'),
('student', 'Student with limited access', '{"dashboard": true, "courses": true, "enrollments": true, "payments": true, "documents": true}', 'active'),
('faculty', 'Faculty member with teaching access', '{"dashboard": true, "courses": true, "students": true}', 'active');

-- 19. Insert course categories
INSERT INTO course_categories (name, description, status) VALUES
('Technology', 'Computer and technology related courses', 'active'),
('Marketing', 'Digital marketing and business courses', 'active'),
('Fashion', 'Fashion design and tailoring courses', 'active'),
('Wellness', 'Health and wellness courses', 'active'),
('Skills', 'Vocational and skill development courses', 'active');

-- 20. Insert sample users
INSERT INTO users (username, password, full_name, email, phone, user_type_id, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Main Admin', 'admin@gict.edu', '+91-8888888888', 1, 'active'),
('student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rahul Kumar', 'rahul@example.com', '+91-7777777777', 2, 'active'),
('student2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Priya Sharma', 'priya@example.com', '+91-7777777778', 2, 'active'),
('faculty1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Rajesh Singh', 'rajesh@example.com', '+91-6666666666', 3, 'active');

-- 21. Insert main courses
INSERT INTO courses (category_id, name, description, duration, status) VALUES
(1, 'Computer Course', 'Comprehensive computer training program', '6-12 months', 'active'),
(2, 'Digital Marketing', 'Digital marketing training', '3-6 months', 'active');

-- 22. Insert sub-courses
INSERT INTO sub_courses (course_id, name, description, fee, duration, number_of_semesters, status) VALUES
(1, 'CCC (Course on Computer Concepts)', 'Basic computer concepts', 2500.00, '3 months', 1, 'active'),
(1, 'ADCA (Advanced Diploma in Computer Applications)', 'Advanced computer diploma', 5000.00, '6 months', 2, 'active');

-- 23. Insert payment methods
INSERT INTO payment_methods (name, type, is_active) VALUES
('UPI Payment', 'online', TRUE),
('Cash Payment', 'offline', TRUE),
('Bank Transfer', 'offline', TRUE);

-- 24. Create Indexes for better performance
CREATE INDEX idx_users_type ON users(user_type_id);
CREATE INDEX idx_user_type_status ON users(user_type_id, status, created_at);
CREATE INDEX idx_courses_category ON courses(category_id);
CREATE INDEX idx_courses_status ON courses(status);
CREATE INDEX idx_sub_courses_course ON sub_courses(course_id);
CREATE INDEX idx_enrollments_user ON student_enrollments(user_id);
CREATE INDEX idx_enrollments_sub_course ON student_enrollments(sub_course_id);
CREATE INDEX idx_enrollments_status ON student_enrollments(status);
CREATE INDEX idx_payments_user ON payments(user_id);
CREATE INDEX idx_payments_sub_course ON payments(sub_course_id);
CREATE INDEX idx_payments_verification ON payments(payment_verified_by);
CREATE INDEX idx_attendance_date ON student_attendance(attendance_date);
CREATE INDEX idx_attendance_enrollment ON student_attendance(enrollment_id);
CREATE INDEX idx_assignments_course ON assignments(sub_course_id);
CREATE INDEX idx_submissions_assignment ON assignment_submissions(assignment_id);
CREATE INDEX idx_course_subjects_semester ON course_subjects(sub_course_id, semester);
