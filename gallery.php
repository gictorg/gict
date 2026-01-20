<!-- Gallery Section -->
<div class="gallery-wrapper">
    <section class="gallery-section">
        <!-- Faculty Members Section -->
        <h2 class="section-title">Faculty Members</h2>
        <div class="faculty-gallery">
            <?php
            // Get active faculty members from database
            try {
                // Ensure database config is loaded (only once)
                if (!function_exists('getRows')) {
                    require_once __DIR__ . '/config/database.php';
                }
                $faculty_sql = "SELECT u.full_name, u.qualification, u.experience_years, u.profile_image 
                FROM users u 
                WHERE u.user_type_id = 3 AND u.status = 'active' 
                ORDER BY u.experience_years DESC";
                $faculty_members = getRows($faculty_sql);

                if (!empty($faculty_members)) {
                    foreach ($faculty_members as $faculty) {
                        $profile_img = !empty($faculty['profile_image']) ? $faculty['profile_image'] : 'assets/images/default-faculty.png';

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
            // Get active courses from database with images
            try {
                $courses_sql = "SELECT c.name, c.description, c.duration, c.course_image, c.course_image_alt, c.category_id
                FROM courses c 
                WHERE c.status = 'active' 
                ORDER BY c.name";
                $courses = getRows($courses_sql);

                if (!empty($courses)) {
                    foreach ($courses as $course) {
                        // Use ImgBB image if available, fallback to default
                        $course_image = !empty($course['course_image']) ? $course['course_image'] : 'assets/images/computer course.jpeg';
                        $image_alt = !empty($course['course_image_alt']) ? $course['course_image_alt'] : $course['name'];

                        echo '<div class="gallery-item course-item">';
                        echo '<img src="' . htmlspecialchars($course_image) . '" alt="' . htmlspecialchars($image_alt) . '" onerror="this.onerror=null; this.src=\'assets/images/computer course.jpeg\'">';
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
        <div class="faculty-gallery students-gallery">
            <?php
            // Get recent students from database
            try {
                $students_sql = "SELECT u.full_name, u.qualification, u.joining_date, u.profile_image 
                FROM users u 
                WHERE u.user_type_id = 2 AND u.status = 'active' 
                ORDER BY u.joining_date DESC LIMIT 6";
                $recent_students = getRows($students_sql);

                if (!empty($recent_students)) {
                    foreach ($recent_students as $student) {
                        $profile_img = !empty($student['profile_image']) ? $student['profile_image'] : 'assets/images/default-student.png';

                        // Use qualification as subtitle, fallback to joining date
                        $subtitle = !empty($student['qualification']) ? $student['qualification'] :
                            (!empty($student['joining_date']) ? 'Joined: ' . date('M Y', strtotime($student['joining_date'])) : 'Student');

                        echo '<div class="gallery-item faculty-item student-item">';
                        echo '<img src="' . htmlspecialchars($profile_img) . '" alt="' . htmlspecialchars($student['full_name']) . '" onerror="this.onerror=null; this.src=\'assets/images/default-student.png\'">';
                        echo '<div class="caption">';
                        echo '<h3>' . htmlspecialchars($student['full_name']) . '</h3>';
                        echo '<p>' . htmlspecialchars($subtitle) . '</p>';
                        echo '</div>';
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
<script src="assets/js/faculty-gallery.js"></script>