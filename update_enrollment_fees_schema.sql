-- Update student_enrollments table to add fee tracking
ALTER TABLE student_enrollments 
ADD COLUMN paid_fees DECIMAL(10,2) DEFAULT 0.00 AFTER sub_course_id,
ADD COLUMN remaining_fees DECIMAL(10,2) DEFAULT 0.00 AFTER paid_fees,
ADD COLUMN total_fee DECIMAL(10,2) DEFAULT 0.00 AFTER remaining_fees;

-- Update existing enrollments to calculate fees from sub_courses
UPDATE student_enrollments se
JOIN sub_courses sc ON se.sub_course_id = sc.id
SET se.total_fee = sc.fee,
    se.remaining_fees = sc.fee - COALESCE(se.paid_fees, 0.00);

-- Create certificates table if it doesn't exist
CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    certificate_number VARCHAR(100) UNIQUE,
    certificate_url VARCHAR(500),
    marksheet_url VARCHAR(500),
    status ENUM('pending', 'generated', 'issued') DEFAULT 'pending',
    generated_at TIMESTAMP NULL,
    issued_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id) ON DELETE CASCADE
);

-- Create student_marks table if it doesn't exist
CREATE TABLE IF NOT EXISTS student_marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    marks_obtained DECIMAL(5,2) NOT NULL,
    max_marks DECIMAL(5,2) NOT NULL,
    grade VARCHAR(10),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id) ON DELETE CASCADE
);

