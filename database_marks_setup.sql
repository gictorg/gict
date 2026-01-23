-- Student Marks and Semesters Update
-- This adds marks management support and enhances sub-courses with semester counts

-- Add number_of_semesters to sub_courses
ALTER TABLE sub_courses ADD COLUMN IF NOT EXISTS number_of_semesters INT NOT NULL DEFAULT 1 AFTER duration;

-- Update existing sub-courses with approximate semesters based on duration
UPDATE sub_courses SET number_of_semesters = 1 WHERE duration LIKE '%3 months%' OR duration LIKE '%2 months%';
UPDATE sub_courses SET number_of_semesters = 2 WHERE duration LIKE '%6 months%' OR duration LIKE '%4 months%';
UPDATE sub_courses SET number_of_semesters = 4 WHERE duration LIKE '%12 months%' OR duration LIKE '%8 months%';

-- Create student_marks table
CREATE TABLE IF NOT EXISTS student_marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    subject_id INT NOT NULL,
    semester INT NOT NULL DEFAULT 1,
    theory_marks INT DEFAULT 0,
    practical_marks INT DEFAULT 0,
    total_marks INT DEFAULT 0,
    grade VARCHAR(5),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES course_subjects(id) ON DELETE CASCADE,
    UNIQUE KEY idx_enrollment_subject (enrollment_id, subject_id)
);
