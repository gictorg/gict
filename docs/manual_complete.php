<?php
require_once 'config/database.php';

$enrollment_id = 20;
$pdo = getDBConnection();

// 1. Get Sub Course ID
$enrollment = getRow("SELECT sub_course_id FROM student_enrollments WHERE id = ?", [$enrollment_id]);
$sub_course_id = $enrollment['sub_course_id'];

// 2. Get All Subjects (we need names now)
$subjects = getRows("SELECT id, subject_name, max_marks FROM course_subjects WHERE sub_course_id = ?", [$sub_course_id]);
$subject_map = []; // Name -> ID
foreach ($subjects as $s) {
    $subject_map[$s['subject_name']] = $s;
}

// 3. Get Marked Subjects (by name)
$marked = getRows("SELECT subject_name FROM student_marks WHERE enrollment_id = ?", [$enrollment_id]);
$marked_names = array_column($marked, 'subject_name');

// 4. Find Missing
$all_names = array_keys($subject_map);
$missing_names = array_diff($all_names, $marked_names);

if (empty($missing_names)) {
    echo "No missing subjects found. Checking counts...\n";
    // Check if maybe we just need to ensure 6 marks are there
    if (count($marked) < 6) {
        // Just in case names mismatch slightly?
        echo "Count mismatch. " . count($marked) . " vs 6.\n";
    }
}

foreach ($missing_names as $name) {
    echo "Inserting marks for subject '$name'...\n";
    $s_info = $subject_map[$name];

    // Insert using the schema found: subject_name, marks_obtained, max_marks
    insertData(
        "INSERT INTO student_marks (enrollment_id, subject_name, marks_obtained, max_marks, grade, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
        [$enrollment_id, $name, 85, $s_info['max_marks'], 'A']
    );
}

// 5. Complete Course
echo "Marking course as completed...\n";
updateData("UPDATE student_enrollments SET status = 'completed', completion_date = CURDATE() WHERE id = ?", [$enrollment_id]);

// 6. Generate Certificate
$cert = getRow("SELECT id FROM certificates WHERE enrollment_id = ?", [$enrollment_id]);
if (!$cert) {
    echo "Generating certificate...\n";
    $cert_no = generateUniqueNumber(12); // Using the helper I added
    $cert_url = "assets/generated_certificates/certificate_{$cert_no}.pdf";
    $marks_url = "assets/generated_marksheets/marksheet_{$cert_no}.pdf";

    insertData(
        "INSERT INTO certificates (enrollment_id, certificate_number, certificate_url, marksheet_url, generated_by, status) VALUES (?, ?, ?, ?, ?, 'generated')",
        [$enrollment_id, $cert_no, $cert_url, $marks_url, 1]
    );
    echo "Certificate generated: $cert_no\n";
} else {
    echo "Certificate already exists.\n";
}

echo "Done.\n";
?>