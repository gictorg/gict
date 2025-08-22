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
                $faculty_sql = "SELECT full_name, qualification, experience_years, profile_image FROM users WHERE user_type = 'faculty' AND status = 'active' ORDER BY experience_years DESC";
                $faculty_members = getRows($faculty_sql);
                
                if (!empty($faculty_members)) {
                    foreach ($faculty_members as $faculty) {
                        $profile_img = !empty($faculty['profile_image']) ? $faculty['profile_image'] : 'assets/images/default-faculty.png';
                        
                        // Map faculty names to specialties for display
                        $specialty_map = [
                            'Sarita Patel' => 'Tally',
                            'Anand Sir' => 'Web Development',
                            'Mukesh Gupta' => 'Hardware Networking',
                            'Madhu Ma\'am' => 'Beauty & Aesthetics',
                            'Tanu Tiwari' => 'Artificial Intelligence',
                            'Anjali Prajapati' => 'Professional Courses'
                        ];
                        
                        $specialty = $specialty_map[$faculty['full_name']] ?? $faculty['qualification'];
                        
                        echo '<div class="gallery-item faculty-item">';
                        echo '<img src="' . htmlspecialchars($profile_img) . '" alt="' . htmlspecialchars($faculty['full_name']) . '">';
                        echo '<div class="caption">';
                        echo '<h3>' . htmlspecialchars($faculty['full_name']) . '</h3>';
                        echo '<p>' . htmlspecialchars($specialty) . '</p>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    // Fallback to static faculty if no database data
                    $static_faculty = [
                        ['name' => 'Sarita Patel', 'specialty' => 'Tally', 'image' => 'assets/images/sarita.png'],
                        ['name' => 'Anand Sir', 'specialty' => 'Web Development', 'image' => 'assets/images/anand sir.png'],
                        ['name' => 'Mukesh Gupta', 'specialty' => 'Hardware Networking', 'image' => 'assets/images/mukesh.png'],
                        ['name' => 'Madhu Ma\'am', 'specialty' => 'Beauty & Aesthetics', 'image' => 'assets/images/madhu.jpeg'],
                        ['name' => 'Tanu Tiwari', 'specialty' => 'Artificial Intelligence', 'image' => 'assets/images/tanu.jpeg'],
                        ['name' => 'Anjali Prajapati', 'specialty' => 'Professional Courses', 'image' => 'assets/images/anjali.jpeg']
                    ];
                    
                    foreach ($static_faculty as $faculty) {
                        echo '<div class="gallery-item faculty-item">';
                        echo '<img src="' . $faculty['image'] . '" alt="' . $faculty['name'] . '">';
                        echo '<div class="caption">';
                        echo '<h3>' . $faculty['name'] . '</h3>';
                        echo '<p>' . $faculty['specialty'] . '</p>';
                        echo '</div>';
                        echo '</div>';
                    }
                }
            } catch (Exception $e) {
                // Fallback to static faculty if database fails
                $static_faculty = [
                    ['name' => 'Sarita Patel', 'specialty' => 'Tally', 'image' => 'assets/images/sarita.png'],
                    ['name' => 'Anand Sir', 'specialty' => 'Web Development', 'image' => 'assets/images/anand sir.png'],
                    ['name' => 'Mukesh Gupta', 'specialty' => 'Hardware Networking', 'image' => 'assets/images/mukesh.png'],
                    ['name' => 'Madhu Ma\'am', 'specialty' => 'Beauty & Aesthetics', 'image' => 'assets/images/madhu.jpeg'],
                    ['name' => 'Tanu Tiwari', 'specialty' => 'Artificial Intelligence', 'image' => 'assets/images/tanu.jpeg'],
                    ['name' => 'Anjali Prajapati', 'specialty' => 'Professional Courses', 'image' => 'assets/images/anjali.jpeg']
                ];
                
                foreach ($static_faculty as $faculty) {
                    echo '<div class="gallery-item faculty-item">';
                    echo '<img src="' . $faculty['image'] . '" alt="' . $faculty['name'] . '">';
                    echo '<div class="caption">';
                    echo '<h3>' . $faculty['name'] . '</h3>';
                    echo '<p>' . $faculty['specialty'] . '</p>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
        </div>

        <!-- Course Offered Section -->
        <h2 class="section-title">Courses Offered</h2>
        <div class="gallery-container courses-gallery">
            <div class="gallery-item course-item">
                <img src="assets/images/computer course.jpeg" alt="Computer Course">
                <div class="caption">Computer Course</div>
            </div>
            <div class="gallery-item course-item">
                <img src="assets/images/Yoga Certificate.jpeg" alt="Yoga Certificate">
                <div class="caption">Yoga Certificate</div>
            </div>
            <div class="gallery-item course-item">
                <img src="assets/images/Vocational Course.jpeg" alt="Vocational Course">
                <div class="caption">Vocational Course</div>
            </div>
            <div class="gallery-item course-item">
                <img src="assets/images/Beautician Certificate.jpeg" alt="Beautician Certificate">
                <div class="caption">Beautician Certificate</div>
            </div>
            <div class="gallery-item course-item">
                <img src="assets/images/Tailoring Certificate.jpeg" alt="Tailoring Certificate">
                <div class="caption">Tailoring Certificate</div>
            </div>
        </div>

        <!-- Recently Joined Student Section -->
        <h2 class="section-title">Recently Joined Student</h2>
        <div class="gallery-container students-gallery">
            <?php
            // Get recent students from database
            try {
                $students_sql = "SELECT full_name, qualification, joining_date, profile_image FROM users WHERE user_type = 'student' AND status = 'active' ORDER BY joining_date DESC LIMIT 6";
                $recent_students = getRows($students_sql);
                
                if (!empty($recent_students)) {
                    foreach ($recent_students as $student) {
                        $profile_img = !empty($student['profile_image']) ? $student['profile_image'] : 'assets/images/default-student.png';
                        echo '<div class="gallery-item student-item">';
                        echo '<img src="' . htmlspecialchars($profile_img) . '" alt="' . htmlspecialchars($student['full_name']) . '">';
                        echo '<div class="caption">' . htmlspecialchars($student['full_name']) . '</div>';
                        echo '</div>';
                    }
                } else {
                    // Fallback to static students if no database data
                    $static_students = [
                        ['image' => 'assets/images/1.jpg', 'name' => 'Student 1'],
                        ['image' => 'assets/images/2.jpg', 'name' => 'Student 2'],
                        ['image' => 'assets/images/3.jpeg', 'name' => 'Student 3'],
                        ['image' => 'assets/images/4.jpeg', 'name' => 'Student 4'],
                        ['image' => 'assets/images/5.jpg', 'name' => 'Student 5'],
                        ['image' => 'assets/images/6.jpeg', 'name' => 'Student 6']
                    ];
                    
                    foreach ($static_students as $student) {
                        echo '<div class="gallery-item student-item">';
                        echo '<img src="' . $student['image'] . '" alt="' . $student['name'] . '">';
                        echo '<div class="caption">' . $student['name'] . '</div>';
                        echo '</div>';
                    }
                }
            } catch (Exception $e) {
                // Fallback to static students if database fails
                $static_students = [
                    ['image' => 'assets/images/1.jpg', 'name' => 'Student 1'],
                    ['image' => 'assets/images/2.jpg', 'name' => 'Student 2'],
                    ['image' => 'assets/images/3.jpeg', 'name' => 'Student 3'],
                    ['image' => 'assets/images/4.jpeg', 'name' => 'Student 4'],
                    ['image' => 'assets/images/5.jpg', 'name' => 'Student 5'],
                    ['image' => 'assets/images/6.jpeg', 'name' => 'Student 6']
                ];
                
                foreach ($static_students as $student) {
                    echo '<div class="gallery-item student-item">';
                    echo '<img src="' . $student['image'] . '" alt="' . $student['name'] . '">';
                    echo '<div class="caption">' . $student['name'] . '</div>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </section>
</div>
<script src="assets/js/student-gallery.js"></script> 