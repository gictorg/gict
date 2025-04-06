<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GICT - <?php echo ucfirst(str_replace('.php', '', $current_page)); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="quick-links">
                <a href="#">Convocation 2024</a>
                <a href="#">Archives</a>
                <a href="#">Download Logo</a>
                <a href="#">Download Forms</a>
            </div>
            <div class="accessibility">
                <button class="access-btn">A-</button>
                <button class="access-btn">A</button>
                <button class="access-btn">A+</button>
                <button class="access-btn">T</button>
                <button class="access-btn">T</button>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="main-nav">
        <div class="container">
            <div class="logo">
                <img src="images/madardih_logo.png" alt="GICT Logo" class="logo-img">
                <h1>GICT</h1>
            </div>
            <div class="nav-links">
                <ul>
                    <li><a href="index.php" <?php echo $current_page == 'index.php' ? 'class="active"' : ''; ?>>Home</a></li>
                    <li><a href="pages/about.php" <?php echo $current_page == 'about.php' ? 'class="active"' : ''; ?>>About</a></li>
                    <li><a href="pages/academics.php" <?php echo $current_page == 'academics.php' ? 'class="active"' : ''; ?>>Academics</a></li>
                    <li><a href="pages/admission.php" <?php echo $current_page == 'admission.php' ? 'class="active"' : ''; ?>>Admission</a></li>
                    <li><a href="pages/contact.php" <?php echo $current_page == 'contact.php' ? 'class="active"' : ''; ?>>Contact</a></li>
                </ul>
            </div>
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>
</body>
</html> 