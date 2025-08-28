<!-- Gallery Section -->
<div class="gallery-wrapper">
    <section class="gallery-section">
        <!-- Faculty Members Section -->
        <h2 class="section-title">Faculty Members</h2>
        <div class="gallery-container faculty-gallery">
            <?php
            // Get active faculty members from database
            try {
                require_once 'config/database.php';
                $faculty_sql = "SELECT u.full_name, u.qualification, u.experience_years, u.profile_image 
                FROM users u 
                JOIN user_types ut ON u.user_type_id = ut.id 
                WHERE ut.name = 'faculty' AND u.status = 'active' 
                ORDER BY u.experience_years DESC";
                $faculty_members = getRows($faculty_sql);
                
                if (!empty($faculty_members)) {
                    foreach ($faculty_members as $faculty) {
                        $profile_img = !empty($faculty['profile_image']) ? $faculty['profile_image'] : 'assets/images/default-faculty.png';
                        
                        // Debug: Show what's being fetched
                        if (empty($faculty['profile_image'])) {
                            error_log("Faculty {$faculty['full_name']} has no profile image");
                        }
                        
                        // Use qualification as specialty, fallback to experience years
                        $specialty = !empty($faculty['qualification']) ? $faculty['qualification'] : 
                                    ($faculty['experience_years'] > 0 ? $faculty['experience_years'] . ' years experience' : 'Faculty Member');
                        
                        echo '<div class="gallery-item faculty-item">';
                        echo '<img src="' . htmlspecialchars($profile_img) . '" alt="' . htmlspecialchars($faculty['full_name']) . '" onerror="this.onerror=null; this.src=\'assets/images/default-faculty.png\'">';
                        echo '<div class="caption">';
                        echo '<h3>' . htmlspecialchars($faculty['full_name']) . '</h3>';
                        echo '<p>' . htmlspecialchars($specialty) . '</p>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p class="no-data">No faculty members available at the moment.</p>';
                }
            } catch (Exception $e) {
                echo '<p class="no-data">Unable to load faculty information. Please try again later.</p>';
            }
            ?>
        </div>

        <!-- Course Offered Section -->
        <h2 class="section-title">Courses Offered</h2>
        <div class="gallery-container courses-gallery">
            <?php
            // Get active courses from database
            try {
                $courses_sql = "SELECT c.name, c.description, c.duration, cc.name as category_name
                FROM courses c 
                JOIN course_categories cc ON c.category_id = cc.id 
                WHERE c.status = 'active' 
                ORDER BY c.name";
                $courses = getRows($courses_sql);
                
                if (!empty($courses)) {
                    foreach ($courses as $course) {
                        // Map course names to images (you can add more mappings as needed)
                        $course_images = [
                            'Computer Course' => 'assets/images/computer course.jpeg',
                            'Yoga Course' => 'assets/images/Yoga Certificate.jpeg',
                            'Vocational Course' => 'assets/images/Vocational Course.jpeg',
                            'Beautician Course' => 'assets/images/Beautician Certificate.jpeg',
                            'Tailoring Course' => 'assets/images/Tailoring Certificate.jpeg'
                        ];
                        
                        $course_image = $course_images[$course['name']] ?? 'assets/images/default-course.png';
                        
                        echo '<div class="gallery-item course-item">';
                        echo '<img src="' . htmlspecialchars($course_image) . '" alt="' . htmlspecialchars($course['name']) . '">';
                        echo '<div class="caption">' . htmlspecialchars($course['name']) . '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p class="no-data">No courses available at the moment.</p>';
                }
            } catch (Exception $e) {
                echo '<p class="no-data">Unable to load course information. Please try again later.</p>';
            }
            ?>
        </div>

        <!-- Recently Joined Student Section -->
        <h2 class="section-title">Recently Joined Student</h2>
        <div class="gallery-container students-gallery">
            <?php
            // Get recent students from database
            try {
                $students_sql = "SELECT u.full_name, u.qualification, u.joining_date, u.profile_image 
                FROM users u 
                JOIN user_types ut ON u.user_type_id = ut.id 
                WHERE ut.name = 'student' AND u.status = 'active' 
                ORDER BY u.joining_date DESC LIMIT 6";
                $recent_students = getRows($students_sql);
                
                if (!empty($recent_students)) {
                    foreach ($recent_students as $student) {
                        $profile_img = !empty($student['profile_image']) ? $student['profile_image'] : 'assets/images/default-student.png';
                        echo '<div class="gallery-item student-item">';
                        echo '<img src="' . htmlspecialchars($profile_img) . '" alt="' . htmlspecialchars($student['full_name']) . '" onerror="this.onerror=null; this.src=\'assets/images/default-student.png\'">';
                        echo '<div class="caption">' . htmlspecialchars($student['full_name']) . '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p class="no-data">No students available at the moment.</p>';
                }
            } catch (Exception $e) {
                echo '<p class="no-data">Unable to load student information. Please try again later.</p>';
            }
            ?>
        </div>
    </section>
</div>
<script src="assets/js/student-gallery.js"></script> 