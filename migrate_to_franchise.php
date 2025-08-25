<?php
/**
 * Migration Script: Convert existing database to Franchise Model
 * Run this script once to migrate your existing data to the new franchise structure
 */

require_once 'config/database.php';

echo "<h1>GICT Institute - Database Migration to Franchise Model</h1>";
echo "<p>This script will migrate your existing database to the new franchise model structure.</p>";

// Check if migration has already been run
$migration_check = getRow("SHOW TABLES LIKE 'institutes'");
if ($migration_check) {
    echo "<p style='color: red;'><strong>Warning:</strong> Franchise tables already exist. Migration may have already been run.</p>";
    echo "<p>If you want to start fresh, please drop all tables and run this script again.</p>";
    exit;
}

try {
    echo "<h2>Starting Migration...</h2>";
    
    // Step 1: Create institutes table
    echo "<p>1. Creating institutes table...</p>";
    $pdo->exec("
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
        )
    ");
    
    // Step 2: Create default institute
    echo "<p>2. Creating default institute...</p>";
    $pdo->exec("
        INSERT INTO institutes (name, slug, address, phone, email, status) VALUES 
        ('GICT Computer Institute - Main Branch', 'gict-main', '123 Main Street, City Center', '+91-9876543210', 'main@gict.com', 'active')
    ");
    $default_institute_id = $pdo->lastInsertId();
    
    // Step 3: Add institute_id to users table
    echo "<p>3. Adding institute_id to users table...</p>";
    $pdo->exec("ALTER TABLE users ADD COLUMN institute_id INT");
    $pdo->exec("ALTER TABLE users ADD FOREIGN KEY (institute_id) REFERENCES institutes(id) ON DELETE CASCADE");
    
    // Step 4: Update existing users to belong to default institute
    echo "<p>4. Updating existing users...</p>";
    $pdo->exec("UPDATE users SET institute_id = $default_institute_id WHERE institute_id IS NULL");
    
    // Step 5: Add super_admin user type
    echo "<p>5. Adding super_admin user type...</p>";
    $pdo->exec("ALTER TABLE users MODIFY COLUMN user_type ENUM('super_admin', 'admin', 'student', 'faculty') NOT NULL");
    
    // Step 6: Create super admin user
    echo "<p>6. Creating super admin user...</p>";
    $super_admin_exists = getRow("SELECT id FROM users WHERE user_type = 'super_admin'");
    if (!$super_admin_exists) {
        $pdo->exec("
            INSERT INTO users (username, email, password, full_name, user_type) VALUES 
            ('superadmin', 'superadmin@gict.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'super_admin')
        ");
    }
    
    // Step 7: Add institute_id to courses table
    echo "<p>7. Adding institute_id to courses table...</p>";
    $pdo->exec("ALTER TABLE courses ADD COLUMN institute_id INT");
    $pdo->exec("ALTER TABLE courses ADD FOREIGN KEY (institute_id) REFERENCES institutes(id) ON DELETE CASCADE");
    
    // Step 8: Update existing courses to belong to default institute
    echo "<p>8. Updating existing courses...</p>";
    $pdo->exec("UPDATE courses SET institute_id = $default_institute_id WHERE institute_id IS NULL");
    
    // Step 9: Create sub_courses table
    echo "<p>9. Creating sub_courses table...</p>";
    $pdo->exec("
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
        )
    ");
    
    // Step 10: Migrate existing courses to sub_courses
    echo "<p>10. Migrating existing courses to sub_courses...</p>";
    $existing_courses = getRows("SELECT * FROM courses");
    foreach ($existing_courses as $course) {
        // Create a default sub-course for each existing course
        $pdo->exec("
            INSERT INTO sub_courses (course_id, name, description, duration_weeks, fee) VALUES 
            ({$course['id']}, '{$course['name']} - Basic', '{$course['description']}', 12, {$course['fee']})
        ");
    }
    
    // Step 11: Update student_enrollments to use sub_courses
    echo "<p>11. Updating student_enrollments table...</p>";
    $pdo->exec("ALTER TABLE student_enrollments ADD COLUMN sub_course_id INT");
    $pdo->exec("ALTER TABLE student_enrollments ADD FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE CASCADE");
    
    // Step 12: Migrate existing enrollments
    echo "<p>12. Migrating existing enrollments...</p>";
    $enrollments = getRows("SELECT * FROM student_enrollments WHERE sub_course_id IS NULL");
    foreach ($enrollments as $enrollment) {
        // Find the corresponding sub-course
        $sub_course = getRow("SELECT id FROM sub_courses WHERE course_id = ?", [$enrollment['course_id']]);
        if ($sub_course) {
            $pdo->exec("UPDATE student_enrollments SET sub_course_id = {$sub_course['id']} WHERE id = {$enrollment['id']}");
        }
    }
    
    // Step 13: Update payments table
    echo "<p>13. Updating payments table...</p>";
    $pdo->exec("ALTER TABLE payments ADD COLUMN sub_course_id INT");
    $pdo->exec("ALTER TABLE payments ADD FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE CASCADE");
    
    // Step 14: Migrate existing payments
    echo "<p>14. Migrating existing payments...</p>";
    $payments = getRows("SELECT * FROM payments WHERE sub_course_id IS NULL");
    foreach ($payments as $payment) {
        // Find the corresponding sub-course
        $sub_course = getRow("SELECT id FROM sub_courses WHERE course_id = ?", [$payment['course_id']]);
        if ($sub_course) {
            $pdo->exec("UPDATE payments SET sub_course_id = {$sub_course['id']} WHERE id = {$payment['id']}");
        }
    }
    
    // Step 15: Add sample data for demonstration
    echo "<p>15. Adding sample franchise data...</p>";
    
    // Add more institutes
    $pdo->exec("
        INSERT INTO institutes (name, slug, address, phone, email, status) VALUES 
        ('GICT Computer Institute - North Branch', 'gict-north', '456 North Avenue, North City', '+91-9876543211', 'north@gict.com', 'active'),
        ('GICT Tailoring Institute', 'gict-tailoring', '789 Fashion Street, Fashion City', '+91-9876543212', 'tailoring@gict.com', 'active')
    ");
    
    $north_institute_id = $pdo->lastInsertId() - 1;
    $tailoring_institute_id = $pdo->lastInsertId();
    
    // Add sample courses for new institutes
    $pdo->exec("
        INSERT INTO courses (institute_id, name, description, category, duration_months, fee) VALUES 
        ($north_institute_id, 'Computer Basics', 'Basic computer operations and MS Office', 'Computer', 2, 3000.00),
        ($north_institute_id, 'Digital Marketing', 'Online marketing and social media', 'Computer', 4, 12000.00),
        ($tailoring_institute_id, 'Basic Tailoring', 'Basic sewing and stitching techniques', 'Tailoring', 3, 8000.00),
        ($tailoring_institute_id, 'Advanced Tailoring', 'Advanced garment making techniques', 'Tailoring', 6, 15000.00)
    ");
    
    // Add sample sub-courses
    $pdo->exec("
        INSERT INTO sub_courses (course_id, name, description, duration_weeks, fee) VALUES 
        (4, 'Computer Operations', 'Basic computer operations', 8, 1500.00),
        (4, 'MS Word', 'Document creation and editing', 6, 1000.00),
        (4, 'MS Excel', 'Spreadsheet and data management', 8, 1200.00),
        (5, 'Social Media Marketing', 'Marketing on social platforms', 12, 4000.00),
        (5, 'Google Ads', 'Google advertising platform', 10, 3500.00),
        (5, 'SEO Optimization', 'Search engine optimization', 14, 4500.00),
        (6, 'Blouse Sewing', 'Learn to sew blouses', 8, 3000.00),
        (6, 'Pants Sewing', 'Learn to sew pants and trousers', 10, 3500.00),
        (6, 'Skirt Making', 'Learn to make skirts', 6, 2500.00),
        (7, 'Dress Making', 'Complete dress making techniques', 16, 6000.00),
        (7, 'Suit Making', 'Professional suit making', 20, 8000.00),
        (7, 'Designer Wear', 'Fashion design and creation', 24, 10000.00)
    ");
    
    // Add sample institute admins
    $pdo->exec("
        INSERT INTO users (institute_id, username, email, password, full_name, user_type) VALUES 
        ($north_institute_id, 'admin_north', 'admin@gict-north.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'North Branch Admin', 'admin'),
        ($tailoring_institute_id, 'admin_tailoring', 'admin@gict-tailoring.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tailoring Institute Admin', 'admin')
    ");
    
    // Add sample students
    $pdo->exec("
        INSERT INTO users (institute_id, username, email, password, full_name, phone, user_type) VALUES 
        ($north_institute_id, 'student3', 'student3@gict-north.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Priya Singh', '+91-9876543212', 'student'),
        ($tailoring_institute_id, 'student4', 'student4@gict-tailoring.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Meera Patel', '+91-9876543213', 'student')
    ");
    
    echo "<h2 style='color: green;'>✅ Migration Completed Successfully!</h2>";
    echo "<p><strong>Migration Summary:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Created institutes table</li>";
    echo "<li>✅ Created sub_courses table</li>";
    echo "<li>✅ Updated users table with institute_id</li>";
    echo "<li>✅ Updated courses table with institute_id</li>";
    echo "<li>✅ Updated student_enrollments table</li>";
    echo "<li>✅ Updated payments table</li>";
    echo "<li>✅ Migrated existing data</li>";
    echo "<li>✅ Added sample franchise data</li>";
    echo "</ul>";
    
    echo "<h3>Demo Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Super Admin:</strong> superadmin / password</li>";
    echo "<li><strong>Main Branch Admin:</strong> admin / password</li>";
    echo "<li><strong>North Branch Admin:</strong> admin_north / password</li>";
    echo "<li><strong>Tailoring Admin:</strong> admin_tailoring / password</li>";
    echo "<li><strong>Student:</strong> student1 / password</li>";
    echo "</ul>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Test the login system with the demo credentials</li>";
    echo "<li>Explore the super admin dashboard to manage institutes</li>";
    echo "<li>Test institute-specific admin dashboards</li>";
    echo "<li>Verify that students can access their institute-specific data</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Migration Failed!</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
}
?>
