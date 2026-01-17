<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'class_rep') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
// find class id
$stmt = $conn->prepare("SELECT c.class_id FROM users u LEFT JOIN classes c ON u.user_id = c.class_rep_id WHERE u.user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();
$class_id = $class['class_id'] ?? null;

$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));

if (!$class_id) {
    echo json_encode(['error' => 'No class assigned']);
    exit();
}

// today's scans by this rep
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE class_id = ? AND date = ? AND scanned_by = ?");
$stmt->bind_param('isi', $class_id, $today, $user_id);
$stmt->execute();
$today_scans = intval($stmt->get_result()->fetch_assoc()['total'] ?? 0);

// weekly scans for class by this rep
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE class_id = ? AND date BETWEEN ? AND ? AND scanned_by = ?");
$stmt->bind_param('issi', $class_id, $week_start, $week_end, $user_id);
$stmt->execute();
$weekly_scans = intval($stmt->get_result()->fetch_assoc()['total'] ?? 0);

// today's scheduled teachers for this class
$today_name = date('l');
$today_name_short = date('D');
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM teacher_assignments WHERE class_id = ? AND (schedule = ? OR schedule = ?)");
$stmt->bind_param('iss', $class_id, $today_name, $today_name_short);
$stmt->execute();
$today_teachers = intval($stmt->get_result()->fetch_assoc()['total'] ?? 0);

// pending scans
$pending = max(0, $today_teachers - $today_scans);

echo json_encode([
    'today_scans' => $today_scans,
    'weekly_scans' => $weekly_scans,
    'today_teachers' => $today_teachers,
    'pending_scans' => $pending
]);
exit();
?>
