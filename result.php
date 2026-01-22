<?php
require_once 'config/database.php';
require_once 'header.php';
?>

<div class="main-content">
    <link rel="stylesheet" href="assets/css/student-corner.css">

    <div class="container student-corner-container">
        <div class="student-corner-card">
            <h2 class="student-corner-title">Student Result</h2>

            <form method="POST" action="" class="verification-form">
                <div class="form-group">
                    <label for="enrollment_no" class="form-label">Roll No / Enrollment No:</label>
                    <input type="text" id="enrollment_no" name="enrollment_no" class="form-control" required
                        placeholder="Enter your roll no or enrollment no">
                </div>
                <button type="submit" class="btn-verify">Check Result</button>
            </form>

            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo '<div class="maintenance-message"><i class="fas fa-tools"></i> Result functionality is currently under maintenance. Please contact the administration.</div>';
            }
            ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>