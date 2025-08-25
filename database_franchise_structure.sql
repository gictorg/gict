-- Franchise Model Database Structure
-- GICT Institute Management System

-- Drop existing tables if they exist
DROP TABLE IF EXISTS student_documents;
DROP TABLE IF EXISTS student_enrollments;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS sub_courses;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS institutes;

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

-- Create users table (now includes institute_id and super_admin role)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institute_id INT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    profile_image VARCHAR(500),
    user_type ENUM('super_admin', 'admin', 'student', 'faculty') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (institute_id) REFERENCES institutes(id) ON DELETE CASCADE
);

-- Create courses table (main course categories)
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institute_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    duration_months INT,
    fee DECIMAL(10,2),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (institute_id) REFERENCES institutes(id) ON DELETE CASCADE
);

-- Create sub_courses table (specific courses under main courses)
CREATE TABLE sub_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    duration_weeks INT,
    fee DECIMAL(10,2),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Create student_enrollments table (now enrolls in sub_courses)
CREATE TABLE student_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sub_course_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    completion_date DATE,
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    final_marks DECIMAL(5,2),
    certificate_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE CASCADE
);

-- Create payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sub_course_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    receipt_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE CASCADE
);

-- Create student_documents table
CREATE TABLE student_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    imgbb_url VARCHAR(500) NOT NULL,
    file_extension VARCHAR(10),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default super admin
INSERT INTO users (username, email, password, full_name, user_type) VALUES 
('superadmin', 'superadmin@gict.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'super_admin');

-- Insert sample institutes
INSERT INTO institutes (name, slug, address, phone, email, status) VALUES 
('GICT Computer Institute - Main Branch', 'gict-main', '123 Main Street, City Center', '+91-9876543210', 'main@gict.com', 'active'),
('GICT Computer Institute - North Branch', 'gict-north', '456 North Avenue, North City', '+91-9876543211', 'north@gict.com', 'active'),
('GICT Tailoring Institute', 'gict-tailoring', '789 Fashion Street, Fashion City', '+91-9876543212', 'tailoring@gict.com', 'active');

-- Insert sample courses for each institute
INSERT INTO courses (institute_id, name, description, category, duration_months, fee) VALUES 
-- Computer courses for Main Branch
(1, 'Computer Fundamentals', 'Basic computer knowledge and operations', 'Computer', 3, 5000.00),
(1, 'Programming Languages', 'Learn various programming languages', 'Computer', 6, 15000.00),
(1, 'Web Development', 'Complete web development course', 'Computer', 8, 25000.00),

-- Computer courses for North Branch
(2, 'Computer Basics', 'Basic computer operations and MS Office', 'Computer', 2, 3000.00),
(2, 'Digital Marketing', 'Online marketing and social media', 'Computer', 4, 12000.00),

-- Tailoring courses
(3, 'Basic Tailoring', 'Basic sewing and stitching techniques', 'Tailoring', 3, 8000.00),
(3, 'Advanced Tailoring', 'Advanced garment making techniques', 'Tailoring', 6, 15000.00);

-- Insert sample sub-courses
INSERT INTO sub_courses (course_id, name, description, duration_weeks, fee) VALUES 
-- Computer Fundamentals sub-courses
(1, 'CCC (Course on Computer Concepts)', 'Basic computer concepts and operations', 12, 2000.00),
(1, 'MS Office Suite', 'Microsoft Office applications', 8, 1500.00),
(1, 'Internet & Email', 'Internet browsing and email usage', 4, 800.00),

-- Programming Languages sub-courses
(2, 'C Programming', 'Learn C programming language', 16, 5000.00),
(2, 'Java Programming', 'Learn Java programming language', 20, 6000.00),
(2, 'Python Programming', 'Learn Python programming language', 18, 5500.00),

-- Web Development sub-courses
(3, 'HTML & CSS', 'Web page structure and styling', 12, 4000.00),
(3, 'JavaScript', 'Client-side web programming', 16, 5000.00),
(3, 'PHP & MySQL', 'Server-side web development', 20, 7000.00),

-- Computer Basics sub-courses
(4, 'Computer Operations', 'Basic computer operations', 8, 1500.00),
(4, 'MS Word', 'Document creation and editing', 6, 1000.00),
(4, 'MS Excel', 'Spreadsheet and data management', 8, 1200.00),

-- Digital Marketing sub-courses
(5, 'Social Media Marketing', 'Marketing on social platforms', 12, 4000.00),
(5, 'Google Ads', 'Google advertising platform', 10, 3500.00),
(5, 'SEO Optimization', 'Search engine optimization', 14, 4500.00),

-- Basic Tailoring sub-courses
(6, 'Blouse Sewing', 'Learn to sew blouses', 8, 3000.00),
(6, 'Pants Sewing', 'Learn to sew pants and trousers', 10, 3500.00),
(6, 'Skirt Making', 'Learn to make skirts', 6, 2500.00),

-- Advanced Tailoring sub-courses
(7, 'Dress Making', 'Complete dress making techniques', 16, 6000.00),
(7, 'Suit Making', 'Professional suit making', 20, 8000.00),
(7, 'Designer Wear', 'Fashion design and creation', 24, 10000.00);

-- Insert sample institute admins
INSERT INTO users (institute_id, username, email, password, full_name, user_type) VALUES 
(1, 'admin_main', 'admin@gict.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Main Branch Admin', 'admin'),
(2, 'admin_north', 'admin@gict-north.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'North Branch Admin', 'admin'),
(3, 'admin_tailoring', 'admin@gict-tailoring.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tailoring Institute Admin', 'admin');

-- Insert sample students
INSERT INTO users (institute_id, username, email, password, full_name, phone, user_type) VALUES 
(1, 'student1', 'student1@gict.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anjali Sharma', '+91-9876543210', 'student'),
(1, 'student2', 'student2@gict.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rahul Kumar', '+91-9876543211', 'student'),
(2, 'student3', 'student3@gict-north.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Priya Singh', '+91-9876543212', 'student'),
(3, 'student4', 'student4@gict-tailoring.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Meera Patel', '+91-9876543213', 'student');

-- Insert sample enrollments
INSERT INTO student_enrollments (user_id, sub_course_id, enrollment_date, status) VALUES 
(5, 1, '2024-01-15', 'active'),
(5, 4, '2024-02-01', 'active'),
(6, 2, '2024-01-20', 'completed'),
(7, 13, '2024-01-10', 'active'),
(8, 16, '2024-01-25', 'active');

-- Insert sample payments
INSERT INTO payments (user_id, sub_course_id, amount, payment_date, payment_method, status) VALUES 
(5, 1, 2000.00, '2024-01-15', 'UPI', 'completed'),
(5, 4, 5000.00, '2024-02-01', 'UPI', 'completed'),
(6, 2, 1500.00, '2024-01-20', 'UPI', 'completed'),
(7, 13, 4000.00, '2024-01-10', 'UPI', 'completed'),
(8, 16, 3000.00, '2024-01-25', 'UPI', 'completed');
