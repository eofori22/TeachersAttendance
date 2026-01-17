<?php
// API to get classes with missing teacher attendance
header('Content-Type: application/json');

include '../includes/auth.php';

// Verify admin role
if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    http_response_code(403);
    exit();
}

// Get today's date and current time
$today = date('Y-m-d');
$current_time = date('H:i:s');
$current_day = strtoupper(substr(date('D'), 0, 3)); // Mon, Tue, etc.

// Get all class assignments for today that should have started
$stmt = $conn->prepare("
    SELECT 
        ta.assignment_id,
        ta.class_id,
        ta.teacher_id,
        u.full_name AS teacher_name,
        c.class_name,
        COALESCE(s.subject_name, c.class_name) AS subject_name,
        ta.start_time,
        ta.end_time,
        TIME_FORMAT(ta.start_time, '%h:%i %p') as start_time_formatted,
        TIME_FORMAT(ta.end_time, '%h:%i %p') as end_time_formatted,
        ta.room_number,
        TIMEDIFF(ta.start_time, ?) as time_until_class
    FROM teacher_assignments ta
    JOIN users u ON ta.teacher_id = u.user_id
    JOIN classes c ON ta.class_id = c.class_id
    LEFT JOIN subjects s ON ta.subject_id = s.subject_id
    WHERE ta.schedule = ? AND ta.start_time <= ?
    ORDER BY ta.start_time ASC
");

$stmt->bind_param("sss", $current_time, $current_day, $current_time);
$stmt->execute();
$result = $stmt->get_result();
$all_assignments = [];

while ($row = $result->fetch_assoc()) {
    $all_assignments[] = $row;
}

// For each assignment, check if there's an attendance record for today
$missing_teachers = [];

foreach ($all_assignments as $assignment) {
    $check_stmt = $conn->prepare("
        SELECT attendance_id 
        FROM attendance 
        WHERE teacher_id = ? 
        AND class_id = ? 
        AND date = ?
        LIMIT 1
    ");
    $check_stmt->bind_param("iis", $assignment['teacher_id'], $assignment['class_id'], $today);
    $check_stmt->execute();
    $attendance_result = $check_stmt->get_result();
    
    // If no attendance record, this teacher is missing
    if ($attendance_result->num_rows === 0) {
        // Calculate minutes since class started
        $start = new DateTime($assignment['start_time']);
        $now = new DateTime($current_time);
        $diff = $now->diff($start);
        $minutes_overdue = ($diff->h * 60) + $diff->i;
        
        // Only alert if class has started (not in future)
        if ($minutes_overdue >= 0) {
            $assignment['minutes_overdue'] = $minutes_overdue;
            $assignment['status'] = $minutes_overdue <= 5 ? 'just_started' : ($minutes_overdue <= 30 ? 'in_class' : 'late');
            $missing_teachers[] = $assignment;
        }
    }
}

echo json_encode([
    'success' => true,
    'current_time' => date('H:i:s'),
    'today' => $current_day,
    'missing_teachers' => $missing_teachers,
    'count' => count($missing_teachers)
]);
?>
