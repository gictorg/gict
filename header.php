<?php
require_once 'includes/session_manager.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GICT - Global Institute of Compute Technology</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/profile.css">
    <link rel="stylesheet" href="assets/css/gallery.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="header-container">
        <!-- Top Links Bar -->
        <div class="top-links">
            <div class="container">
                <div class="quick-links">
                    <a href="#"><i class="fas fa-link"></i> Quick Links</a>
                    <a href="#">₹ Fee Portal</a>
                    <a href="#"><i class="fas fa-question-circle"></i> FAQs</a>
                    <a href="#"><i class="fas fa-user-graduate"></i> Admission 2024-2025</a>
                    <a href="#"><i class="fas fa-mobile-alt"></i> Download Android Mobile App</a>
                    <a href="#"><i class="fas fa-bullhorn"></i> Notices & Circulars</a>
                </div>
                <div class="accessibility-tools">
                    <a href="#" title="Download"><i class="fas fa-download"></i></a>
                    <a href="#" title="Screen Reader"><i class="fas fa-desktop"></i></a>
                    <a href="#" title="Time"><i class="far fa-clock"></i></a>
                    <a href="#" title="Font Size"><i class="fas fa-font"></i></a>
                    <a href="#" title="High Contrast"><i class="fas fa-adjust"></i></a>
                    <a href="#" class="font-decrease" title="Decrease Font Size">A-</a>
                    <a href="#" class="font-increase" title="Increase Font Size">A+</a>
                    <a href="#" title="Search"><i class="fas fa-search"></i></a>
                </div>
            </div>
        </div>
        <!-- Logo Section -->
        <div class="logo-section">
            <div class="container">
                <div class="logo-left">
                    <img src="assets/images/MINISTRY.png" alt="Ministry of Education">
                </div>
                <div class="logo-center">
                    <img src="assets/images/Add_a_heading.png" alt="GICT Logo">
                </div>
                <div class="logo-right">
                    <img src="assets/images/digi.jpg" alt="Digital India">
                </div>
            </div>
        </div>
        <!-- Main Navigation -->
        <nav class="main-nav">
            <div class="container">
                <div class="nav-left">
                    <a href="index.php" class="home-icon">
                        <i class="fas fa-home"></i>
                    </a>
                    <button class="mobile-menu-toggle" id="mobileMenuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="nav-links" id="navLinks">
                        <button class="mobile-menu-close" id="mobileMenuClose" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="nav-dropdown">
                            <a href="#" class="nav-btn">ABOUT US <i class="fas fa-caret-down"></i></a>
                            <div class="nav-dropdown-content">
                                <a href="#">Option 1</a>
                                <a href="#">Option 2</a>
                            </div>
                        </div>
                        <div class="nav-dropdown">
                            <a href="#" class="nav-btn">STUDENT <i class="fas fa-caret-down"></i></a>
                            <div class="nav-dropdown-content">
                                <a href="#">Option 1</a>
                                <a href="#">Option 2</a>
                            </div>
                        </div>
                        <div class="nav-dropdown">
                            <a href="#" class="nav-btn">COURSES <i class="fas fa-caret-down"></i></a>
                            <div class="nav-dropdown-content">
                                <a href="#">Option 1</a>
                                <a href="#">Option 2</a>
                            </div>
                        </div>
                        <div class="nav-dropdown">
                            <a href="#" class="nav-btn">PROJECT <i class="fas fa-caret-down"></i></a>
                            <div class="nav-dropdown-content">
                                <a href="#">Option 1</a>
                                <a href="#">Option 2</a>
                            </div>
                        </div>
                        <div class="nav-dropdown">
                            <a href="#" class="nav-btn">GALLERY <i class="fas fa-caret-down"></i></a>
                            <div class="nav-dropdown-content">
                                <a href="#">Option 1</a>
                                <a href="#">Option 2</a>
                            </div>
                        </div>
                        <div class="nav-dropdown">
                            <a href="#" class="nav-btn">CONTACT <i class="fas fa-caret-down"></i></a>
                            <div class="nav-dropdown-content">
                                <a href="#">Option 1</a>
                                <a href="#">Option 2</a>
                            </div>
                        </div>
                        <div class="nav-dropdown">
                            <a href="#" class="nav-btn">CERTIFICATE VERIFICATION <i class="fas fa-caret-down"></i></a>
                            <div class="nav-dropdown-content">
                                <a href="#">Option 1</a>
                                <a href="#">Option 2</a>
                            </div>
                        </div>
                        <?php if (isLoggedIn()): ?>
                            <!-- Logged in user dropdown -->
                            <div class="nav-dropdown user-dropdown">
                                <a href="#" class="nav-btn user-btn">
                                    <i class="fas fa-user"></i> 
                                    <?php echo htmlspecialchars(getUserDisplayName()); ?> 
                                    <i class="fas fa-caret-down"></i>
                                </a>
                                <div class="user-dropdown-content">
                                    <a href="<?php echo getDashboardUrl(); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                                    <a href="#"><i class="fas fa-user-circle"></i> Profile</a>
                                    <a href="#"><i class="fas fa-cog"></i> Settings</a>
                                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Login button for non-logged in users -->
                            <div class="nav-dropdown login-dropdown">
                                <a href="login.php" class="nav-btn login-btn">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </div>
    <script src="assets/js/nav-dropdown.js"></script>
    <script src="assets/js/homepage-mobile-menu.js"></script>