-- Database Update for Student Fields and Inquiry System
-- Adds mother's name, father's name to users table and creates inquiry system

-- Add mother's and father's name fields to users table
ALTER TABLE users 
ADD COLUMN mother_name VARCHAR(255) NULL AFTER full_name,
ADD COLUMN father_name VARCHAR(255) NULL AFTER mother_name;

-- Create inquiries table
CREATE TABLE inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    email VARCHAR(255) NULL,
    course_id INT NULL,
    sub_course_id INT NULL,
    message TEXT NULL,
    status ENUM('new', 'contacted', 'enrolled', 'closed') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE SET NULL
);

-- Create indexes for better performance
CREATE INDEX idx_inquiries_status ON inquiries(status);
CREATE INDEX idx_inquiries_created_at ON inquiries(created_at);
CREATE INDEX idx_inquiries_course ON inquiries(course_id);
CREATE INDEX idx_inquiries_sub_course ON inquiries(sub_course_id);

-- Insert sample inquiry data (optional)
INSERT INTO inquiries (name, mobile, email, course_id, sub_course_id, message, status) VALUES
('John Doe', '9876543210', 'john@example.com', 1, 1, 'Interested in CCC course. Please provide more details.', 'new'),
('Jane Smith', '9876543211', 'jane@example.com', 2, 5, 'Want to know about Digital Marketing course fees and duration.', 'new'),
('Mike Johnson', '9876543212', NULL, 3, 8, 'Interested in Web Development. Please call me.', 'contacted');
