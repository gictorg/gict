<?php
// No authentication required for the main homepage
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GICT - Global Institute of Computer Technology</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/news-events.css">
</head>

<?php include 'header.php'; ?>

<div class="main-content">
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-menu">
                <a href="about-gict.php" class="menu-item"><i class="fas fa-info-circle"></i> ABOUT GICT</a>
                <a href="#" class="menu-item"><i class="fas fa-user-graduate"></i> STUDENT SECTION</a>
                <!-- <a href="#" class="menu-item"><i class="fas fa-book"></i> COURSE SECTION</a>
                <a href="#" class="menu-item"><i class="fas fa-download"></i> DOWNLOAD RESULT</a> -->
                <a href="#" class="menu-item"><i class="fas fa-users"></i> GICT TEAM</a>
                <a href="#" class="menu-item"><i class="fas fa-trophy"></i> ACHIEVEMENT</a>
                <a href="#" class="menu-item"><i class="fas fa-laptop"></i> ONLINE STUDY MATERIAL</a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Main Image Section -->
            <div class="slideshow-container">
                <div class="slide active">
                    <img src="assets/images/slide 1.jpg" alt="Training Session">
                </div>
                <div class="slide">
                    <img src="assets/images/slide 2.jpg" alt="Classroom">
                </div>
                <div class="slide">
                    <img src="assets/images/slide 3.jpg" alt="Computer Lab">
                </div>
                <div class="slide">
                    <img src="assets/images/slide 4.jpg" alt="Students">
                </div>
            </div>

            <!-- Include slideshow script -->
            <script src="assets/js/slideshow.js"></script>

            <div class="mission-wrapper">
                <h1 class="mission-heading">Mission Digital India Training & Placement with GICT Team</h1>
            </div>

            <div class="director-section">
                <div class="profile-card">
                    <div class="profile-section">
                        <img src="assets/images/brijendra.jpeg" alt="Mr. Brijendra Patel" class="profile-img">
                        <h3>Mr. Brijendra Patel</h3>
                        <p>Founder & Director</p>
                        <div class="button-group">
                            <button class="btn yellow-btn">PROFILE</button>
                            <button class="btn yellow-btn">MESSAGE</button>
                        </div>
                    </div>
                </div>

                <div class="course-list">
                    <ul>
                        <li>Computer Course</li>
                        <li>Yoga Certificate</li>
                        <li>Vocational Course</li>
                        <li>Beautician Certificate</li>
                        <li>Tailoring Certificate</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'news-events.php'; ?>
<?php include 'gallery.php'; ?>

<!-- WhatsApp Button -->
<div class="whatsapp-float">
    <a href="https://wa.me/918433377466" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>
</div>

<?php include 'footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="assets/js/homepage-mobile-menu.js"></script>
    <script>
        // Add any custom JavaScript here
        document.addEventListener('DOMContentLoaded', function() {
            // Your existing JavaScript
        });
    </script>
</body>
</html>