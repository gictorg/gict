<?php
/**
 * Database Setup Script for GICT Application
 * 
 * This script creates the necessary database and tables for the
 * Global Institute of Computer Technology application.
 */

// Include database configuration
require_once 'config/database.php';

// Database creation SQL
$createDatabaseSQL = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

// Table creation SQL statements
$tables = [
    'users' => "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            full_name VARCHAR(100) NOT NULL,
            user_type ENUM('admin', 'student', 'faculty') NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            profile_image VARCHAR(255),
            date_of_birth DATE,
            gender ENUM('male', 'female', 'other'),
            qualification VARCHAR(200),
            experience_years INT,
            joining_date DATE,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'courses' => "
        CREATE TABLE IF NOT EXISTS courses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            duration VARCHAR(100),
            fee DECIMAL(10,2) NOT NULL,
            capacity INT DEFAULT 50,
            category VARCHAR(50),
            status ENUM('active', 'inactive', 'draft') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'enrollments' => "
        CREATE TABLE IF NOT EXISTS enrollments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            enrollment_date DATE NOT NULL,
            completion_date DATE,
            status ENUM('enrolled', 'completed', 'dropped') DEFAULT 'enrolled',
            certificate_issued BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'certificates' => "
        CREATE TABLE IF NOT EXISTS certificates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            certificate_number VARCHAR(50) UNIQUE NOT NULL,
            issue_date DATE NOT NULL,
            certificate_file VARCHAR(255),
            status ENUM('active', 'expired', 'revoked') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'news_events' => "
        CREATE TABLE IF NOT EXISTS news_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            content TEXT NOT NULL,
            event_date DATE,
            event_time TIME,
            location VARCHAR(200),
            image VARCHAR(255),
            type ENUM('news', 'event') DEFAULT 'news',
            status ENUM('published', 'draft') DEFAULT 'published',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'gallery' => "
        CREATE TABLE IF NOT EXISTS gallery (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200),
            description TEXT,
            image_path VARCHAR(255) NOT NULL,
            category VARCHAR(50),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'payments' => "
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('cash', 'online', 'upi') DEFAULT 'cash',
            transaction_id VARCHAR(100),
            payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            notes TEXT,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'user_logins' => "
        CREATE TABLE IF NOT EXISTS user_logins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent TEXT,
            status ENUM('success', 'failed') DEFAULT 'success',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    'student_documents' => "
        CREATE TABLE IF NOT EXISTS student_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            document_type ENUM('profile_image', 'marksheet', 'aadhaar_card') NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            imgbb_id VARCHAR(255),
            original_filename VARCHAR(255),
            file_size INT,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'payments' => "
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('upi', 'card', 'cash', 'bank_transfer') DEFAULT 'upi',
            status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
            transaction_id VARCHAR(100),
            payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
];

// Sample data for testing
$sampleData = [
    'users' => [
        ['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin@gict.com', 'Administrator', 'admin', '1234567890', 'GICT Office', '1990-01-01', 'male', 'M.Tech Computer Science', 5, '2020-01-01'],
        ['student1', password_hash('student123', PASSWORD_DEFAULT), 'rahul.kumar@gmail.com', 'Rahul Kumar', 'student', '9876543210', 'Delhi, India', '2000-05-15', 'male', '12th Standard', 0, '2024-06-01'],
        ['student2', password_hash('student123', PASSWORD_DEFAULT), 'rahul.kumar@gmail.com', 'Priya Kumar', 'student', '9876543211', 'Mumbai, India', '1999-08-20', 'female', '12th Standard', 0, '2024-06-01'],
        ['student3', password_hash('student123', PASSWORD_DEFAULT), 'family.sharma@gmail.com', 'Arjun Sharma', 'student', '9876543212', 'Chennai, India', '2001-03-12', 'male', '12th Standard', 0, '2024-06-01'],
        ['student4', password_hash('student123', PASSWORD_DEFAULT), 'family.sharma@gmail.com', 'Kavya Sharma', 'student', '9876543213', 'Chennai, India', '2002-07-18', 'female', '12th Standard', 0, '2024-06-01'],
        ['faculty1', password_hash('faculty123', PASSWORD_DEFAULT), 'faculty@gict.com', 'Dr. Amit Patel', 'faculty', '5555555555', 'Bangalore, India', '1985-03-10', 'male', 'Ph.D Computer Science', 8, '2020-03-01'],
        ['faculty2', password_hash('faculty123', PASSWORD_DEFAULT), 'faculty@gict.com', 'Prof. Neha Singh', 'faculty', '5555555556', 'Chennai, India', '1988-07-25', 'female', 'M.Tech Information Technology', 6, '2021-01-01']
    ],
    
    'courses' => [
        ['Computer Course', 'Basic computer skills and programming', '3 months', 5000.00, 50, 'Technology'],
        ['Yoga Certificate', 'Professional yoga training and certification', '2 months', 3000.00, 30, 'Wellness'],
        ['Vocational Course', 'Practical vocational skills training', '6 months', 8000.00, 40, 'Skills'],
        ['Beautician Certificate', 'Beauty and wellness training', '4 months', 6000.00, 25, 'Beauty'],
        ['Tailoring Certificate', 'Professional tailoring and design', '5 months', 7000.00, 35, 'Fashion']
    ],
    
    'payments' => [
        [1, 1, 5000.00, 'upi', 'completed', 'TXN001'],
        [2, 2, 3000.00, 'upi', 'completed', 'TXN002'],
        [3, 3, 8000.00, 'upi', 'completed', 'TXN003'],
        [4, 4, 6000.00, 'upi', 'completed', 'TXN004']
    ],
    
    'enrollments' => [
        [1, 1, '2024-06-01', NULL, 'enrolled'],
        [2, 2, '2024-06-02', NULL, 'enrolled'],
        [3, 3, '2024-06-03', NULL, 'enrolled'],
        [4, 4, '2024-06-04', NULL, 'enrolled'],
        [1, 2, '2024-05-15', '2024-07-15', 'completed'],
        [2, 3, '2024-05-20', '2024-11-20', 'completed']
    ]
];

// Function to create database and tables
function setupDatabase() {
    global $createDatabaseSQL, $tables, $sampleData;
    
    try {
        // Connect to MySQL without specifying database
        $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database
        $pdo->exec($createDatabaseSQL);
        echo "✓ Database '" . DB_NAME . "' created successfully\n";
        
        // Select the database
        $pdo->exec("USE " . DB_NAME);
        
        // Create tables
        foreach ($tables as $tableName => $sql) {
            $pdo->exec($sql);
            echo "✓ Table '$tableName' created successfully\n";
        }
        
        // Insert sample data
        foreach ($sampleData as $tableName => $data) {
            if ($tableName === 'users') {
                $sql = "INSERT IGNORE INTO users (username, password, email, full_name, user_type, phone, address, date_of_birth, gender, qualification, experience_years, joining_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                foreach ($data as $row) {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($row);
                }
            } elseif ($tableName === 'courses') {
                $sql = "INSERT IGNORE INTO courses (name, description, duration, fee, capacity, category) VALUES (?, ?, ?, ?, ?, ?)";
                foreach ($data as $row) {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($row);
                }
            } elseif ($tableName === 'payments') {
                $sql = "INSERT IGNORE INTO payments (student_id, course_id, amount, payment_method, status, transaction_id) VALUES (?, ?, ?, ?, ?, ?)";
                foreach ($data as $row) {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($row);
                }
            } elseif ($tableName === 'enrollments') {
                $sql = "INSERT IGNORE INTO enrollments (student_id, course_id, enrollment_date, completion_date, status) VALUES (?, ?, ?, ?, ?)";
                foreach ($data as $row) {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($row);
                }
            }
            echo "✓ Sample data inserted into '$tableName' table\n";
        }
        
        echo "\n🎉 Database setup completed successfully!\n";
        echo "You can now use the following test credentials:\n";
        echo "- Admin: admin / admin123\n";
        echo "- Students: student1, student2, student3, student4 / student123\n";
        echo "- Faculty: faculty1, faculty2 / faculty123\n";
        echo "\nNote: Multiple users can share the same email address, but usernames are unique.\n";
        
    } catch (PDOException $e) {
        echo "❌ Database setup failed: " . $e->getMessage() . "\n";
    }
}

// Run setup if this file is accessed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    echo "Setting up GICT database...\n";
    echo "================================\n";
    setupDatabase();
}
?>
