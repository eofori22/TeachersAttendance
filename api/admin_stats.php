<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

// Get total teachers
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'teacher'");
$total_teachers = $stmt->fetch_assoc()['total'];

// Get total classes
$stmt = $conn->query("SELECT COUNT(*) as total FROM classes");
$total_classes = $stmt->fetch_assoc()['total'];

// Get today's attendance count
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE date = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$today_attendance = $stmt->get_result()->fetch_assoc()['total'];

// Get recent activity (last 10 records)
$stmt = $conn->query("
    SELECT a.*, u1.full_name as teacher_name, u2.full_name as scanned_by_name, c.class_name,
           TIMESTAMPDIFF(MINUTE, CONCAT(a.date, ' ', a.time_in), NOW()) as minutes_ago
    FROM attendance a
    JOIN users u1 ON a.teacher_id = u1.user_id
    JOIN users u2 ON a.scanned_by = u2.user_id
    JOIN classes c ON a.class_id = c.class_id
    ORDER BY a.date DESC, a.time_in DESC
    LIMIT 10
");
$recent_activity = [];
while ($row = $stmt->fetch_assoc()) {
    $time_ago = '';
    if ($row['minutes_ago'] < 60) {
        $time_ago = $row['minutes_ago'] . ' minutes ago';
    } elseif ($row['minutes_ago'] < 1440) {
        $time_ago = floor($row['minutes_ago'] / 60) . ' hours ago';
    } else {
        $time_ago = floor($row['minutes_ago'] / 1440) . ' days ago';
    }
    
    $description = '';
    if ($row['time_out']) {
        $duration = calculateDuration($row['time_in'], $row['time_out']);
        $description = $row['teacher_name'] . ' taught ' . $row['class_name'] . ' for ' . $duration;
    } else {
        $description = $row['teacher_name'] . ' started teaching ' . $row['class_name'];
    }
    
    $recent_activity[] = [
        'time_ago' => $time_ago,
        'description' => $description
    ];
}

function calculateDuration($startTime, $endTime) {
    $start = new DateTime($startTime);
    $end = new DateTime($endTime);
    $diff = $start->diff($end);
    
    return $diff->h . 'h ' . $diff->i . 'm';
}

echo json_encode([
    'total_teachers' => $total_teachers,
    'total_classes' => $total_classes,
    'today_attendance' => $today_attendance,
    'recent_activity' => $recent_activity
]);
?>