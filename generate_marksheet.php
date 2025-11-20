<?php
/**
 * Dynamic Marksheet Generation System
 * This script generates marksheets by overlaying student data on template images
 */

require_once 'config/database.php';

class MarksheetGenerator {
    private $template_path;
    private $output_path;
    
    public function __construct($template_path = null) {
        $this->template_path = $template_path;
        $this->output_path = 'assets/generated_marksheets/';
        
        // Create output directory if it doesn't exist
        if (!is_dir($this->output_path)) {
            mkdir($this->output_path, 0755, true);
        }
    }
    
    /**
     * Generate marksheet for a student
     */
    public function generateMarksheet($student_id, $course_id) {
        try {
            // Get student information
            $student = getRow("SELECT u.*, ut.name as user_type FROM users u JOIN user_types ut ON u.user_type_id = ut.id WHERE u.id = ? AND ut.name = 'student'", [$student_id]);
            if (!$student) {
                throw new Exception("Student not found");
            }
            
            // Get course information
            $course = getRow("SELECT * FROM courses WHERE id = ?", [$course_id]);
            if (!$course) {
                throw new Exception("Course not found");
            }
            
            // Get enrollment information
            $enrollment = getRow("
                SELECT * FROM student_enrollments 
                WHERE user_id = ? AND course_id = ?
            ", [$student_id, $course_id]);
            
            if (!$enrollment) {
                throw new Exception("Student not enrolled in this course");
            }
            
            // Get marksheet template
            $template = getRow("
                SELECT * FROM marksheet_templates 
                WHERE course_id = ? AND is_active = 1
            ", [$course_id]);
            
            if (!$template) {
                throw new Exception("No marksheet template found for this course");
            }
            
            // Check if template file exists
            if (!file_exists($template['template_path'])) {
                throw new Exception("Template file not found: " . $template['template_path']);
            }
            
            // Generate marksheet filename
            $filename = "marksheet_{$student_id}_{$course_id}_" . date('Y-m-d') . ".jpg";
            $output_file = $this->output_path . $filename;
            
            // Create marksheet using GD library
            $this->createMarksheetImage($template['template_path'], $output_file, $student, $course, $enrollment);
            
            // Update enrollment with marksheet generated
            updateData("
                UPDATE student_enrollments 
                SET marksheet_generated = 1, marksheet_path = ?
                WHERE user_id = ? AND course_id = ?
            ", [$output_file, $student_id, $course_id]);
            
            return [
                'success' => true,
                'file_path' => $output_file,
                'filename' => $filename
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create marksheet image by overlaying text on template
     */
    private function createMarksheetImage($template_path, $output_path, $student, $course, $enrollment) {
        // Load template image
        $template = imagecreatefromjpeg($template_path);
        if (!$template) {
            throw new Exception("Failed to load template image");
        }
        
        // Get image dimensions
        $width = imagesx($template);
        $height = imagesy($template);
        
        // Create output image
        $output = imagecreatetruecolor($width, $height);
        
        // Copy template to output
        imagecopy($output, $template, 0, 0, 0, 0, $width, $height);
        
        // Set colors
        $black = imagecolorallocate($output, 0, 0, 0);
        $dark_blue = imagecolorallocate($output, 25, 25, 112);
        
        // Set font (using default font for now)
        $font_size = 5;
        
        // Add student information
        $this->addTextToImage($output, "Student Name: " . $student['full_name'], 50, 150, $font_size, $dark_blue);
        $this->addTextToImage($output, "Student ID: " . $student['username'], 50, 180, $font_size, $dark_blue);
        $this->addTextToImage($output, "Course: " . $course['name'], 50, 210, $font_size, $dark_blue);
        $this->addTextToImage($output, "Duration: " . $course['duration'], 50, 240, $font_size, $dark_blue);
        $this->addTextToImage($output, "Enrollment Date: " . date('d/m/Y', strtotime($enrollment['enrollment_date'])), 50, 270, $font_size, $dark_blue);
        
        // Add marks if available
        if ($enrollment['final_marks']) {
            $this->addTextToImage($output, "Final Marks: " . $enrollment['final_marks'] . "%", 50, 300, $font_size, $dark_blue);
        }
        
        // Add completion date if completed
        if ($enrollment['completion_date']) {
            $this->addTextToImage($output, "Completion Date: " . date('d/m/Y', strtotime($enrollment['completion_date'])), 50, 330, $font_size, $dark_blue);
        }
        
        // Add institute information
        $this->addTextToImage($output, "GICT Institute", $width - 200, 50, $font_size, $dark_blue);
        $this->addTextToImage($output, "Generated on: " . date('d/m/Y H:i'), $width - 200, 80, $font_size, $dark_blue);
        
        // Save the image
        if (!imagejpeg($output, $output_path, 90)) {
            throw new Exception("Failed to save marksheet image");
        }
        
        // Clean up
        imagedestroy($template);
        imagedestroy($output);
    }
    
    /**
     * Add text to image
     */
    private function addTextToImage($image, $text, $x, $y, $font_size, $color) {
        imagestring($image, $font_size, $x, $y, $text, $color);
    }
    
    /**
     * Download marksheet
     */
    public function downloadMarksheet($file_path) {
        if (!file_exists($file_path)) {
            throw new Exception("Marksheet file not found");
        }
        
        $filename = basename($file_path);
        
        // Set headers for download
        header('Content-Type: image/jpeg');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        // Output file
        readfile($file_path);
        exit;
    }
}

// Example usage
if (isset($_GET['test'])) {
    $generator = new MarksheetGenerator();
    
    // Test with student ID 46 and course ID 1
    $result = $generator->generateMarksheet(46, 1);
    
    if ($result['success']) {
        echo "✅ Marksheet generated successfully!\n";
        echo "📁 File: " . $result['file_path'] . "\n";
        echo "📄 Filename: " . $result['filename'] . "\n";
    } else {
        echo "❌ Error: " . $result['error'] . "\n";
    }
}
?>
