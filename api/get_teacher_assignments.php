<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get teacher_id from request
$teacher_id = $_GET['teacher_id'] ?? null;

if (!$teacher_id || !is_numeric($teacher_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid teacher ID']);
    exit();
}

// Get teacher assignments
$stmt = $conn->prepare("
    SELECT 
        ta.assignment_id,
        c.class_name,
        c.class_code,
        COALESCE(s.subject_name, c.class_name) AS subject_name,
        ta.schedule,
        TIME_FORMAT(ta.start_time, '%h:%i %p') as start_time,
        TIME_FORMAT(ta.end_time, '%h:%i %p') as end_time,
        ta.room_number
    FROM teacher_assignments ta
    JOIN classes c ON ta.class_id = c.class_id
    LEFT JOIN subjects s ON ta.subject_id = s.subject_id
    WHERE ta.teacher_id = ?
    ORDER BY 
        FIELD(ta.schedule, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
        ta.start_time
");

$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$assignments = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'assignments' => $assignments
]);
?>


