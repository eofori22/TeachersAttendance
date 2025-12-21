<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verify admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get date parameter (default to today)
$date = $_GET['date'] ?? date('Y-m-d');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
    exit();
}

// Get attendance records for the specified date
$stmt = $conn->prepare("
    SELECT 
        a.attendance_id,
        u1.full_name AS teacher_name,
        c.class_name,
        TIME_FORMAT(a.time_in, '%h:%i %p') AS time_in,
        TIME_FORMAT(a.time_out, '%h:%i %p') AS time_out,
        u2.full_name AS scanned_by_name
    FROM attendance a
    JOIN users u1 ON a.teacher_id = u1.user_id
    JOIN classes c ON a.class_id = c.class_id
    JOIN users u2 ON a.scanned_by = u2.user_id
    WHERE a.date = ?
    ORDER BY a.time_in DESC
");

$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$attendance_records = [];
while ($row = $result->fetch_assoc()) {
    $attendance_records[] = $row;
}

echo json_encode($attendance_records);
?>
