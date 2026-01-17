<?php
// API to get upcoming classes for a teacher
header('Content-Type: application/json');

include '../includes/auth.php';

// Verify teacher role
if ($_SESSION['role'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    http_response_code(403);
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get today's day name
$today = strtolower(date('D')); // Mon, Tue, etc.
$current_time = date('H:i:s');

// Map short day names to full day names
$day_map = [
    'mon' => 'Mon',
    'tue' => 'Tue',
    'wed' => 'Wed',
    'thu' => 'Thu',
    'fri' => 'Fri',
    'sat' => 'Sat',
    'sun' => 'Sun'
];

$today_short = $day_map[$today] ?? $today;

// Get teacher's classes for today
$stmt = $conn->prepare("
    SELECT 
        ta.assignment_id,
        c.class_id,
        COALESCE(s.subject_name, c.class_name) AS subject_name,
        c.class_name,
        ta.schedule as day_of_week,
        ta.start_time,
        ta.end_time,
        TIME_FORMAT(ta.start_time, '%h:%i %p') as start_time_formatted,
        TIME_FORMAT(ta.end_time, '%h:%i %p') as end_time_formatted,
        ta.room_number,
        TIMEDIFF(ta.start_time, ?) as time_until_class
    FROM teacher_assignments ta
    LEFT JOIN subjects s ON ta.subject_id = s.subject_id
    JOIN classes c ON ta.class_id = c.class_id
    WHERE ta.teacher_id = ? AND ta.schedule = ?
    ORDER BY ta.start_time ASC
");
$stmt->bind_param("sis", $current_time, $teacher_id, $today_short);
$stmt->execute();
$result = $stmt->get_result();
$classes = [];

while ($row = $result->fetch_assoc()) {
    // Parse time_until_class
    $time_until = strtotime($row['time_until_class']) - strtotime('00:00:00');
    $minutes_until = $time_until / 60;
    
    // Check if class is starting within 30 minutes from now
    if ($minutes_until >= -30 && $minutes_until <= 120) { // Class within 30 min before to 2 hours after
        $row['minutes_until'] = $minutes_until;
        $row['is_starting_soon'] = ($minutes_until >= -5 && $minutes_until <= 5);
        $row['is_ongoing'] = ($minutes_until >= -30 && $minutes_until < 0);
        $row['status'] = $row['is_starting_soon'] ? 'starting_soon' : ($row['is_ongoing'] ? 'ongoing' : 'upcoming');
        
        $classes[] = $row;
    }
}

// Sort by time until class
usort($classes, function($a, $b) {
    return floatval($a['minutes_until']) - floatval($b['minutes_until']);
});

echo json_encode([
    'success' => true,
    'current_time' => date('H:i:s'),
    'today' => $today_short,
    'classes' => $classes
]);
?>
