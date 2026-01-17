<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$teacher_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Today's classes
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE teacher_id = ? AND date = ?");
$stmt->bind_param('is', $teacher_id, $today);
$stmt->execute();
$today_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Weekly hours
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));
$stmt = $conn->prepare("SELECT SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, time_in, IFNULL(time_out, NOW())))) as total_duration FROM attendance WHERE teacher_id = ? AND date BETWEEN ? AND ?");
$stmt->bind_param('iss', $teacher_id, $week_start, $week_end);
$stmt->execute();
$dur = $stmt->get_result()->fetch_assoc()['total_duration'] ?? '00:00:00';
// convert to hours
list($h,$m,$s) = explode(':', $dur);
$hours = floatval($h) + floatval($m)/60;

// Classes taught (assignments)
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM teacher_assignments WHERE teacher_id = ?");
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$classes_taught = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Next class (simple heuristic)
$stmt = $conn->prepare("SELECT schedule, class_id FROM teacher_assignments WHERE teacher_id = ? ORDER BY FIELD(schedule, 'Mon','Tue','Wed','Thu','Fri','Sat','Sun') LIMIT 1");
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$next = $stmt->get_result()->fetch_assoc();
$next_text = $next ? ($next['schedule'] ?? 'None') : 'None';

echo json_encode([
    'today_classes' => intval($today_count),
    'weekly_hours' => round($hours,1),
    'classes_taught' => intval($classes_taught),
    'next_class' => $next_text
]);
exit();
?>
