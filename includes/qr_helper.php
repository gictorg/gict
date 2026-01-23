<?php
if (realpath(__FILE__) === realpath($_SERVER["SCRIPT_FILENAME"])) { header("HTTP/1.1 403 Forbidden"); exit("Direct access prohibited."); }
/**
 * QR Code Helper for GICT Institute
 * Generates proper QR codes for student IDs using PHP QR Code library
 */

// Check if the PHP QR Code library exists
$libraryPath = 'phpqrcode/qrlib.php';
if (!file_exists($libraryPath)) {
    // If the file doesn't exist, use fallback
    function generateQRCodeHTML($studentId, $studentName, $size = 80)
    {
        $data = "GICT_STUDENT_" . $studentId . "_" . $studentName . "_" . date('Y');
        return '<div class="qr-code-container" data-qr="' . htmlspecialchars($data) . '" style="width: ' . $size . 'px; height: ' . $size . 'px; background: #fff; border: 2px solid #000; border-radius: 8px; overflow: hidden; position: relative; z-index: 2; display: flex; align-items: center; justify-content: center;"></div>';
    }
} else {
    // Include the PHP QR Code library
    require_once $libraryPath;

    function generateQRCodeHTML($studentId, $studentName, $size = 80)
    {
        // Structure the data as JSON for better parsing
        $dataToEncode = json_encode([
            'studentName' => $studentName,
            'studentId' => $studentId,
            'institute' => 'GICT',
            'year' => date('Y'),
            'type' => 'student_id'
        ]);

        // Use a more reliable temporary file method to generate the QR code
        $tempDir = sys_get_temp_dir(); // Get system's temp directory
        $tempFile = tempnam($tempDir, 'qr') . '.png'; // Create a unique temporary file

        // Generate the QR code and save it to the temporary file
        QRcode::png($dataToEncode, $tempFile, QR_ECLEVEL_H, 4, 2);

        // Read the image data from the file
        $pngData = file_get_contents($tempFile);

        // Delete the temporary file
        unlink($tempFile);

        // Create a base64-encoded data URI
        $qrCodeDataUri = 'data:image/png;base64,' . base64_encode($pngData);

        return '<img src="' . $qrCodeDataUri . '" alt="QR Code" style="width: ' . $size . 'px; height: ' . $size . 'px; border-radius: 8px; border: 2px solid #000;">';
    }
}

function generateQRCode($studentId, $studentName, $size = 80)
{
    return generateQRCodeHTML($studentId, $studentName, $size);
}

// Function to generate QR code for certificates
function generateCertificateQRCode($studentId, $studentName, $courseName, $size = 80)
{
    // Structure the data as JSON for better parsing
    $dataToEncode = json_encode([
        'studentName' => $studentName,
        'studentId' => $studentId,
        'courseName' => $courseName,
        'institute' => 'GICT',
        'year' => date('Y'),
        'type' => 'certificate'
    ]);

    // Use a more reliable temporary file method to generate the QR code
    $tempDir = sys_get_temp_dir(); // Get system's temp directory
    $tempFile = tempnam($tempDir, 'qr') . '.png'; // Create a unique temporary file

    // Generate the QR code and save it to the temporary file
    QRcode::png($dataToEncode, $tempFile, QR_ECLEVEL_H, 4, 2);

    // Read the image data from the file
    $pngData = file_get_contents($tempFile);

    // Delete the temporary file
    unlink($tempFile);

    // Create a base64-encoded data URI
    $qrCodeDataUri = 'data:image/png;base64,' . base64_encode($pngData);

    return '<img src="' . $qrCodeDataUri . '" alt="QR Code" style="width: ' . $size . 'px; height: ' . $size . 'px; border-radius: 8px; border: 2px solid #000;">';
}
?>