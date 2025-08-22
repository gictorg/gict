<?php
/**
 * QR Code Helper for GICT Institute
 * Generates proper QR codes for student IDs
 */

function generateQRCodeHTML($studentId, $studentName, $size = 80) {
    $data = "GICT_STUDENT_" . $studentId . "_" . $studentName . "_" . date('Y');
    
    // Return HTML with data attributes for JavaScript QR generation
    return '<div class="qr-code-container" data-qr="' . htmlspecialchars($data) . '" style="width: ' . $size . 'px; height: ' . $size . 'px; background: #fff; border: 2px solid #000; border-radius: 8px; overflow: hidden; position: relative; z-index: 2; display: flex; align-items: center; justify-content: center;"></div>';
}

function generateQRCode($studentId, $studentName, $size = 80) {
    return generateQRCodeHTML($studentId, $studentName, $size);
}
?>
