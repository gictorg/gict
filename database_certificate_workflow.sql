-- Certificate Workflow Database Schema
-- Additional tables for the complete certificate generation workflow

-- Create course_subjects table for tracking subjects in each sub-course
CREATE TABLE course_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sub_course_id INT NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    max_marks INT NOT NULL DEFAULT 100,
    is_compulsory BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE CASCADE
);

-- Create student_marks table for storing student marks
CREATE TABLE student_marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    subject_id INT NOT NULL,
    marks_obtained INT NOT NULL,
    grade VARCHAR(2),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES course_subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment_subject (enrollment_id, subject_id)
);

-- Create certificates table for storing generated certificates
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

-- Create faculty_courses table to assign courses to faculty
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

-- Insert sample course subjects for existing sub-courses
INSERT INTO course_subjects (sub_course_id, subject_name, max_marks, is_compulsory) VALUES
-- CCC Course subjects
(1, 'Computer Fundamentals', 100, TRUE),
(1, 'Operating System', 100, TRUE),
(1, 'MS Office Applications', 100, TRUE),
(1, 'Internet & Email', 50, TRUE),
(1, 'Practical Lab', 50, TRUE),

-- ADCA Course subjects
(2, 'Computer Fundamentals', 100, TRUE),
(2, 'Programming in C', 100, TRUE),
(2, 'Database Management', 100, TRUE),
(2, 'Web Development', 100, TRUE),
(2, 'Project Work', 100, TRUE),

-- PGDCA Course subjects
(3, 'Advanced Programming', 100, TRUE),
(3, 'Database Systems', 100, TRUE),
(3, 'Software Engineering', 100, TRUE),
(3, 'Web Technologies', 100, TRUE),
(3, 'Project Management', 100, TRUE),
(3, 'Thesis', 200, TRUE);

-- Insert sample faculty course assignments
INSERT INTO faculty_courses (faculty_id, sub_course_id, status) VALUES
(8, 1, 'active'),  -- Faculty assigned to CCC
(8, 2, 'active'),  -- Faculty assigned to ADCA
(8, 3, 'active');  -- Faculty assigned to PGDCA

-- Create indexes for better performance
CREATE INDEX idx_course_subjects_sub_course ON course_subjects(sub_course_id);
CREATE INDEX idx_student_marks_enrollment ON student_marks(enrollment_id);
CREATE INDEX idx_student_marks_subject ON student_marks(subject_id);
CREATE INDEX idx_certificates_enrollment ON certificates(enrollment_id);
CREATE INDEX idx_faculty_courses_faculty ON faculty_courses(faculty_id);
CREATE INDEX idx_faculty_courses_sub_course ON faculty_courses(sub_course_id);
