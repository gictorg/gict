-- Subjects with Semester Support Database Migration
-- This adds semester field to course_subjects and updates the table structure

-- First, check if course_subjects table exists and add semester column if needed
-- If table doesn't exist, create it with semester support from the start

-- Drop and recreate course_subjects table with semester support (safe for fresh installs)
-- For existing installations, run the ALTER TABLE instead

-- Option 1: For fresh installations (drops existing data)
-- DROP TABLE IF EXISTS student_marks;
-- DROP TABLE IF EXISTS course_subjects;

-- Option 2: For existing installations (preserves data) - Run this first
-- ALTER TABLE course_subjects ADD COLUMN semester INT DEFAULT 1 AFTER sub_course_id;

-- Create course_subjects table with semester support (for fresh installs)
CREATE TABLE IF NOT EXISTS course_subjects (
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
    FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE CASCADE,
    INDEX idx_subjects_sub_course (sub_course_id),
    INDEX idx_subjects_semester (sub_course_id, semester)
);

-- Add semester column to existing table (run this for existing installations)
-- ALTER TABLE course_subjects ADD COLUMN IF NOT EXISTS semester INT NOT NULL DEFAULT 1 AFTER sub_course_id;
-- ALTER TABLE course_subjects ADD COLUMN IF NOT EXISTS subject_code VARCHAR(50) AFTER subject_name;
-- ALTER TABLE course_subjects ADD COLUMN IF NOT EXISTS theory_marks INT DEFAULT 100 AFTER max_marks;
-- ALTER TABLE course_subjects ADD COLUMN IF NOT EXISTS practical_marks INT DEFAULT 0 AFTER theory_marks;
-- ALTER TABLE course_subjects ADD COLUMN IF NOT EXISTS credit_hours DECIMAL(3,1) DEFAULT 3.0 AFTER is_compulsory;
-- ALTER TABLE course_subjects ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active';
-- ALTER TABLE course_subjects ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add number_of_semesters column to sub_courses table
-- ALTER TABLE sub_courses ADD COLUMN IF NOT EXISTS number_of_semesters INT DEFAULT 1;

-- Sample data with semester support
INSERT INTO course_subjects (sub_course_id, semester, subject_name, subject_code, max_marks, theory_marks, practical_marks, is_compulsory) VALUES
-- CCC Course subjects (3 months - 1 semester)
(1, 1, 'Computer Fundamentals', 'CCC101', 100, 70, 30, TRUE),
(1, 1, 'Operating System', 'CCC102', 100, 70, 30, TRUE),
(1, 1, 'MS Office Applications', 'CCC103', 100, 50, 50, TRUE),
(1, 1, 'Internet & Email Basics', 'CCC104', 50, 30, 20, TRUE),
(1, 1, 'Practical Lab Work', 'CCC105', 50, 0, 50, TRUE),

-- ADCA Course subjects (6 months - 2 semesters)
-- Semester 1
(2, 1, 'Computer Fundamentals', 'ADCA101', 100, 70, 30, TRUE),
(2, 1, 'Programming in C', 'ADCA102', 100, 60, 40, TRUE),
(2, 1, 'Digital Electronics', 'ADCA103', 100, 80, 20, TRUE),
-- Semester 2
(2, 2, 'Database Management', 'ADCA201', 100, 60, 40, TRUE),
(2, 2, 'Web Development', 'ADCA202', 100, 40, 60, TRUE),
(2, 2, 'Project Work', 'ADCA203', 100, 0, 100, TRUE),

-- PGDCA Course subjects (12 months - 4 semesters)
-- Semester 1
(3, 1, 'Computer Fundamentals', 'PGDCA101', 100, 70, 30, TRUE),
(3, 1, 'Programming in C', 'PGDCA102', 100, 60, 40, TRUE),
-- Semester 2
(3, 2, 'Object Oriented Programming', 'PGDCA201', 100, 60, 40, TRUE),
(3, 2, 'Database Systems', 'PGDCA202', 100, 60, 40, TRUE),
-- Semester 3
(3, 3, 'Web Technologies', 'PGDCA301', 100, 50, 50, TRUE),
(3, 3, 'Software Engineering', 'PGDCA302', 100, 80, 20, TRUE),
-- Semester 4
(3, 4, 'Project Management', 'PGDCA401', 100, 70, 30, TRUE),
(3, 4, 'Major Project', 'PGDCA402', 200, 0, 200, TRUE);

-- Create index for faster lookups
CREATE INDEX idx_course_subjects_semester ON course_subjects(sub_course_id, semester);
