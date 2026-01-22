-- GICT Institute Database Schema
-- Updated for Main Courses and Sub-Courses structure

CREATE DATABASE IF NOT EXISTS gict_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gict_db;

-- Drop existing tables if they exist (in correct order due to foreign keys)
DROP TABLE IF EXISTS student_documents;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS student_enrollments;
DROP TABLE IF EXISTS sub_courses;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS institutes;

-- Also drop old tables that might exist
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS certificates;
DROP TABLE IF EXISTS news_events;
DROP TABLE IF EXISTS gallery;
DROP TABLE IF EXISTS user_logins;

-- Create institutes table
CREATE TABLE institutes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    logo_url VARCHAR(500),
    banner_url VARCHAR(500),
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institute_id INT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    father_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    profile_image VARCHAR(500),
    user_type ENUM('super_admin', 'admin', 'student', 'faculty') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (institute_id) REFERENCES institutes(id) ON DELETE SET NULL
);

-- Create main courses table (no fees here)
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institute_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    duration VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (institute_id) REFERENCES institutes(id) ON DELETE CASCADE
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
    session VARCHAR(50),
    enrollment_date DATE NOT NULL,
    completion_date DATE,
    status ENUM('enrolled', 'completed', 'dropped') DEFAULT 'enrolled',
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

-- Insert institutes
INSERT INTO institutes (name, slug, address, phone, email, status) VALUES
('GICT Main Branch', 'gict-main', '123 Main Street, City Center', '+91-9876543210', 'main@gict.edu', 'active'),
('GICT North Branch', 'gict-north', '456 North Avenue, North City', '+91-9876543211', 'north@gict.edu', 'active'),
('GICT Tailoring Institute', 'gict-tailoring', '789 Fashion Street, Fashion City', '+91-9876543212', 'tailoring@gict.edu', 'active');

-- Insert users (including super admin)
INSERT INTO users (institute_id, username, password, full_name, email, phone, user_type, status) VALUES
(NULL, 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'Admin Father', 'superadmin@gict.edu', '+91-9999999999', 'super_admin', 'active'),
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Main Branch Admin', 'Admin Father', 'admin@gict.edu', '+91-8888888888', 'admin', 'active'),
(2, 'admin_north', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'North Branch Admin', 'Admin Father', 'admin_north@gict.edu', '+91-8888888889', 'admin', 'active'),
(3, 'admin_tailoring', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tailoring Admin', 'Admin Father', 'admin_tailoring@gict.edu', '+91-8888888890', 'admin', 'active'),
(1, 'student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rahul Kumar', 'Suresh Kumar', 'rahul@example.com', '+91-7777777777', 'student', 'active'),
(2, 'student2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Priya Sharma', 'Rajesh Sharma', 'priya@example.com', '+91-7777777778', 'student', 'active'),
(3, 'student3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Amit Patel', 'Dinesh Patel', 'amit@example.com', '+91-7777777779', 'student', 'active'),
(1, 'faculty1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Rajesh Singh', 'Singh Sr.', 'rajesh@example.com', '+91-6666666666', 'faculty', 'active');

-- Insert main courses (no fees)
INSERT INTO courses (institute_id, name, description, category, duration, status) VALUES
(1, 'Computer Course', 'Comprehensive computer training program covering various aspects of computing', 'Technology', '6-12 months', 'active'),
(1, 'Digital Marketing', 'Complete digital marketing course covering SEO, SEM, Social Media', 'Marketing', '3-6 months', 'active'),
(2, 'Computer Course', 'Computer training program for North Branch', 'Technology', '6-12 months', 'active'),
(2, 'Web Development', 'Full-stack web development course', 'Technology', '4-8 months', 'active'),
(3, 'Tailoring Course', 'Professional tailoring and fashion design course', 'Fashion', '6-12 months', 'active'),
(3, 'Embroidery Course', 'Traditional and modern embroidery techniques', 'Fashion', '3-6 months', 'active');

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

-- North Branch Computer Course sub-courses
(3, 'CCC (Course on Computer Concepts)', 'Basic computer concepts and applications', 2500.00, '3 months', 'active'),
(3, 'ADCA (Advanced Diploma in Computer Applications)', 'Advanced computer applications and programming', 5000.00, '6 months', 'active'),
(3, 'Web Design', 'HTML, CSS, and JavaScript basics', 4000.00, '4 months', 'active'),

-- Web Development sub-courses
(4, 'Frontend Development', 'HTML, CSS, JavaScript, React', 6000.00, '4 months', 'active'),
(4, 'Backend Development', 'PHP, MySQL, Node.js', 7000.00, '4 months', 'active'),
(4, 'Full Stack Development', 'Complete web development stack', 12000.00, '8 months', 'active'),

-- Tailoring Course sub-courses
(5, 'Basic Stitching', 'Basic sewing and stitching techniques', 3000.00, '3 months', 'active'),
(5, 'Pants Sewing', 'Professional pants making techniques', 4000.00, '2 months', 'active'),
(5, 'Blouse Sewing', 'Traditional and modern blouse making', 3500.00, '2 months', 'active'),
(5, 'Kurta Sewing', 'Kurta and traditional wear making', 3000.00, '2 months', 'active'),
(5, 'Dress Making', 'Western dress making techniques', 4500.00, '3 months', 'active'),

-- Embroidery Course sub-courses
(6, 'Basic Embroidery', 'Basic hand embroidery techniques', 2000.00, '2 months', 'active'),
(6, 'Machine Embroidery', 'Machine embroidery and digitizing', 3500.00, '3 months', 'active'),
(6, 'Zari Work', 'Traditional zari embroidery', 4000.00, '2 months', 'active'),
(6, 'Kashida Embroidery', 'Kashmiri embroidery techniques', 3000.00, '2 months', 'active');

-- Insert sample enrollments
INSERT INTO student_enrollments (user_id, sub_course_id, session, enrollment_date, status) VALUES
(5, 1, '2024-2025', '2024-01-15', 'enrolled'),
(5, 2, '2024-2025', '2024-02-01', 'enrolled'),
(6, 9, '2024-2025', '2024-01-20', 'enrolled'),
(7, 14, '2024-2025', '2024-01-10', 'enrolled'),
(7, 15, '2024-2025', '2024-02-05', 'enrolled');

-- Insert sample payments
INSERT INTO payments (user_id, sub_course_id, amount, payment_date, payment_method, status) VALUES
(5, 1, 2500.00, '2024-01-15', 'UPI', 'completed'),
(5, 2, 5000.00, '2024-02-01', 'UPI', 'completed'),
(6, 9, 2500.00, '2024-01-20', 'UPI', 'completed'),
(7, 14, 3000.00, '2024-01-10', 'UPI', 'completed'),
(7, 15, 4000.00, '2024-02-05', 'UPI', 'completed');

-- Create indexes for better performance
CREATE INDEX idx_users_institute ON users(institute_id);
CREATE INDEX idx_users_type ON users(user_type);
CREATE INDEX idx_courses_institute ON courses(institute_id);
CREATE INDEX idx_sub_courses_course ON sub_courses(course_id);
CREATE INDEX idx_enrollments_user ON student_enrollments(user_id);
CREATE INDEX idx_enrollments_sub_course ON student_enrollments(sub_course_id);
CREATE INDEX idx_payments_user ON payments(user_id);
CREATE INDEX idx_payments_sub_course ON payments(sub_course_id);
CREATE INDEX idx_documents_user ON student_documents(user_id);
