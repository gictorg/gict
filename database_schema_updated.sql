-- GICT Institute Database Schema
-- Updated without institutes table and with course categories

-- Drop existing tables if they exist (in correct order due to foreign keys)
DROP TABLE IF EXISTS student_documents;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS student_enrollments;
DROP TABLE IF EXISTS sub_courses;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS course_categories;
DROP TABLE IF EXISTS user_types;
DROP TABLE IF EXISTS institutes;

-- Create user types table
CREATE TABLE user_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    permissions JSON,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create course categories table
CREATE TABLE course_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create users table (with user_type_id)
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_type_id) REFERENCES user_types(id) ON DELETE RESTRICT
);

-- Create main courses table (with category_id instead of institute_id)
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

-- Create sub-courses table (fees are here)
CREATE TABLE sub_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    fee DECIMAL(10,2) NOT NULL,
    duration VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Create student enrollments table (enrolls in sub-courses)
CREATE TABLE student_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sub_course_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    completion_date DATE,
    status ENUM('pending', 'enrolled', 'completed', 'dropped', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE CASCADE
);

-- Create payments table (payments for sub-courses)
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sub_course_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(255),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE CASCADE
);

-- Create student documents table
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

-- Insert sample data

-- Insert user types
INSERT INTO user_types (name, description, permissions, status) VALUES
('admin', 'Administrator with full access', '{"dashboard": true, "users": true, "courses": true, "enrollments": true, "payments": true, "reports": true}', 'active'),
('student', 'Student with limited access', '{"dashboard": true, "courses": true, "enrollments": true, "payments": true, "documents": true}', 'active'),
('faculty', 'Faculty member with teaching access', '{"dashboard": true, "courses": true, "students": true}', 'active');

-- Insert course categories
INSERT INTO course_categories (name, description, status) VALUES
('Technology', 'Computer and technology related courses', 'active'),
('Marketing', 'Digital marketing and business courses', 'active'),
('Fashion', 'Fashion design and tailoring courses', 'active'),
('Wellness', 'Health and wellness courses', 'active'),
('Skills', 'Vocational and skill development courses', 'active');

-- Insert users (with user_type_id)
INSERT INTO users (username, password, full_name, email, phone, user_type_id, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Main Admin', 'admin@gict.edu', '+91-8888888888', 1, 'active'),
('student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rahul Kumar', 'rahul@example.com', '+91-7777777777', 2, 'active'),
('student2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Priya Sharma', 'priya@example.com', '+91-7777777778', 2, 'active'),
('student3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Amit Patel', 'amit@example.com', '+91-7777777779', 2, 'active'),
('faculty1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Rajesh Singh', 'rajesh@example.com', '+91-6666666666', 3, 'active');

-- Insert main courses (with category_id)
INSERT INTO courses (category_id, name, description, duration, status) VALUES
(1, 'Computer Course', 'Comprehensive computer training program covering various aspects of computing', '6-12 months', 'active'),
(2, 'Digital Marketing', 'Complete digital marketing course covering SEO, SEM, Social Media', '3-6 months', 'active'),
(1, 'Web Development', 'Full-stack web development course', '4-8 months', 'active'),
(3, 'Tailoring Course', 'Professional tailoring and fashion design course', '6-12 months', 'active'),
(3, 'Embroidery Course', 'Traditional and modern embroidery techniques', '3-6 months', 'active'),
(4, 'Yoga Course', 'Professional yoga training and certification', '2-4 months', 'active'),
(5, 'Vocational Course', 'Practical vocational skills training', '6-12 months', 'active');

-- Insert sub-courses (with fees)
INSERT INTO sub_courses (course_id, name, description, fee, duration, status) VALUES
-- Computer Course sub-courses
(1, 'CCC (Course on Computer Concepts)', 'Basic computer concepts and applications', 2500.00, '3 months', 'active'),
(1, 'ADCA (Advanced Diploma in Computer Applications)', 'Advanced computer applications and programming', 5000.00, '6 months', 'active'),
(1, 'PGDCA (Post Graduate Diploma in Computer Applications)', 'Post graduate level computer applications', 8000.00, '12 months', 'active'),
(1, 'DCA (Diploma in Computer Applications)', 'Diploma level computer applications', 4000.00, '6 months', 'active'),
(1, 'Tally ERP 9', 'Accounting software training', 3000.00, '2 months', 'active'),

-- Digital Marketing sub-courses
(2, 'SEO (Search Engine Optimization)', 'Search engine optimization techniques', 4000.00, '2 months', 'active'),
(2, 'SEM (Search Engine Marketing)', 'Search engine marketing and Google Ads', 3500.00, '2 months', 'active'),
(2, 'Social Media Marketing', 'Social media marketing strategies', 3000.00, '2 months', 'active'),
(2, 'Content Marketing', 'Content creation and marketing', 2500.00, '2 months', 'active'),

-- Web Development sub-courses
(3, 'Frontend Development', 'HTML, CSS, JavaScript, React', 6000.00, '4 months', 'active'),
(3, 'Backend Development', 'PHP, MySQL, Node.js', 7000.00, '4 months', 'active'),
(3, 'Full Stack Development', 'Complete web development stack', 12000.00, '8 months', 'active'),

-- Tailoring Course sub-courses
(4, 'Basic Stitching', 'Basic sewing and stitching techniques', 3000.00, '3 months', 'active'),
(4, 'Pants Sewing', 'Professional pants making techniques', 4000.00, '2 months', 'active'),
(4, 'Blouse Sewing', 'Traditional and modern blouse making', 3500.00, '2 months', 'active'),
(4, 'Kurta Sewing', 'Kurta and traditional wear making', 3000.00, '2 months', 'active'),
(4, 'Dress Making', 'Western dress making techniques', 4500.00, '3 months', 'active'),

-- Embroidery Course sub-courses
(5, 'Basic Embroidery', 'Basic hand embroidery techniques', 2000.00, '2 months', 'active'),
(5, 'Machine Embroidery', 'Machine embroidery and digitizing', 3500.00, '3 months', 'active'),
(5, 'Zari Work', 'Traditional zari embroidery', 4000.00, '2 months', 'active'),
(5, 'Kashida Embroidery', 'Kashmiri embroidery techniques', 3000.00, '2 months', 'active'),

-- Yoga Course sub-courses
(6, 'Basic Yoga', 'Foundation yoga training', 2000.00, '2 months', 'active'),
(6, 'Advanced Yoga', 'Advanced yoga techniques and certification', 3500.00, '4 months', 'active'),
(6, 'Yoga Therapy', 'Therapeutic yoga applications', 4000.00, '3 months', 'active'),

-- Vocational Course sub-courses
(7, 'Basic Skills', 'Fundamental vocational skills', 3000.00, '6 months', 'active'),
(7, 'Advanced Skills', 'Advanced vocational training', 5000.00, '12 months', 'active');

-- Insert sample enrollments
INSERT INTO student_enrollments (user_id, sub_course_id, enrollment_date, status) VALUES
(2, 1, '2024-01-15', 'enrolled'),
(2, 2, '2024-02-01', 'enrolled'),
(3, 9, '2024-01-20', 'enrolled'),
(4, 14, '2024-01-10', 'enrolled'),
(4, 15, '2024-02-05', 'enrolled');

-- Insert sample payments
INSERT INTO payments (user_id, sub_course_id, amount, payment_date, payment_method, status) VALUES
(2, 1, 2500.00, '2024-01-15', 'UPI', 'completed'),
(2, 2, 5000.00, '2024-02-01', 'UPI', 'completed'),
(3, 9, 6000.00, '2024-01-20', 'UPI', 'completed'),
(4, 14, 3000.00, '2024-01-10', 'UPI', 'completed'),
(4, 15, 4000.00, '2024-02-05', 'UPI', 'completed');

-- Create indexes for better performance
CREATE INDEX idx_users_type ON users(user_type_id);
CREATE INDEX idx_courses_category ON courses(category_id);
CREATE INDEX idx_sub_courses_course ON sub_courses(course_id);
CREATE INDEX idx_enrollments_user ON student_enrollments(user_id);
CREATE INDEX idx_enrollments_sub_course ON student_enrollments(sub_course_id);
CREATE INDEX idx_payments_user ON payments(user_id);
CREATE INDEX idx_payments_sub_course ON payments(sub_course_id);
CREATE INDEX idx_documents_user ON student_documents(user_id);
