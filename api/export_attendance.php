<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo 'Unauthorized';
    exit();
}

$format = $_GET['format'] ?? 'csv';
$date = $_GET['date'] ?? date('Y-m-d');
$month = $_GET['month'] ?? null;
$year = $_GET['year'] ?? null;
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Validate inputs
if (!in_array($format, ['csv', 'pdf'])) {
    http_response_code(400);
    echo 'Invalid format. Use csv or pdf';
    exit();
}

// Build query based on user role and parameters
if ($month && $year) {
    // Monthly export
    $date_condition = "MONTH(a.date) = ? AND YEAR(a.date) = ?";
    $filename_date = date('F_Y', mktime(0, 0, 0, $month, 1, $year));
    $params = [$month, $year];
    $param_types = 'ii';
} else {
    // Daily export
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo 'Invalid date format. Use YYYY-MM-DD';
        exit();
    }
    $date_condition = "a.date = ?";
    $filename_date = date('Y-m-d', strtotime($date));
    $params = [$date];
    $param_types = 's';
}

// Build query based on user role
if ($user_role === 'admin') {
    $sql = "SELECT 
        a.attendance_id,
        u1.full_name AS teacher_name,
        c.class_name,
        a.date,
        TIME_FORMAT(a.time_in, '%h:%i %p') AS time_in,
        TIME_FORMAT(a.time_out, '%h:%i %p') AS time_out,
        TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) as duration_minutes,
        u2.full_name AS scanned_by_name
    FROM attendance a
    JOIN users u1 ON a.teacher_id = u1.user_id
    JOIN classes c ON a.class_id = c.class_id
    JOIN users u2 ON a.scanned_by = u2.user_id
    WHERE $date_condition
    ORDER BY a.date DESC, a.time_in DESC";
} elseif ($user_role === 'teacher') {
    $sql = "SELECT 
        a.attendance_id,
        u1.full_name AS teacher_name,
        c.class_name,
        a.date,
        TIME_FORMAT(a.time_in, '%h:%i %p') AS time_in,
        TIME_FORMAT(a.time_out, '%h:%i %p') AS time_out,
        TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) as duration_minutes,
        u2.full_name AS scanned_by_name
    FROM attendance a
    JOIN users u1 ON a.teacher_id = u1.user_id
    JOIN classes c ON a.class_id = c.class_id
    JOIN users u2 ON a.scanned_by = u2.user_id
    WHERE a.teacher_id = ? AND $date_condition
    ORDER BY a.date DESC, a.time_in DESC";
    array_unshift($params, $user_id);
    $param_types = 'i' . $param_types;
} elseif ($user_role === 'class_rep') {
    $sql = "SELECT 
        a.attendance_id,
        u1.full_name AS teacher_name,
        c.class_name,
        a.date,
        TIME_FORMAT(a.time_in, '%h:%i %p') AS time_in,
        TIME_FORMAT(a.time_out, '%h:%i %p') AS time_out,
        TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) as duration_minutes,
        u2.full_name AS scanned_by_name
    FROM attendance a
    JOIN users u1 ON a.teacher_id = u1.user_id
    JOIN classes c ON a.class_id = c.class_id
    JOIN users u2 ON a.scanned_by = u2.user_id
    WHERE a.scanned_by = ? AND $date_condition
    ORDER BY a.date DESC, a.time_in DESC";
    array_unshift($params, $user_id);
    $param_types = 'i' . $param_types;
} else {
    http_response_code(403);
    echo 'Unauthorized';
    exit();
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$records = $result->fetch_all(MYSQLI_ASSOC);

if ($format === 'csv') {
    // CSV Export
    $filename = "attendance_{$filename_date}.csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // BOM for Excel
    
    // CSV headers
    fputcsv($output, ['Date', 'Teacher', 'Class', 'Time In', 'Time Out', 'Duration', 'Scanned By']);
    
    foreach ($records as $record) {
        $duration = '';
        if ($record['duration_minutes']) {
            $hours = floor($record['duration_minutes'] / 60);
            $minutes = $record['duration_minutes'] % 60;
            $duration = sprintf("%dh %02dm", $hours, $minutes);
        }
        
        fputcsv($output, [
            $record['date'],
            $record['teacher_name'],
            $record['class_name'],
            $record['time_in'],
            $record['time_out'] ?: 'In Progress',
            $duration ?: '-',
            $record['scanned_by_name']
        ]);
    }
    
    fclose($output);
} else {
    // PDF Export using simple HTML to PDF
    $filename = "attendance_{$filename_date}.pdf";
    
    // Simple PDF generation using DomPDF or similar
    // For now, we'll use a simple HTML approach that browsers can print to PDF
    
    header('Content-Type: text/html; charset=utf-8');
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .no-records { text-align: center; padding: 20px; color: #666; }
        @media print { body { margin: 0; } }
    </style>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>Attendance Report</h1>
        <p>Generated on: ' . date('F j, Y g:i A') . '</p>
        <p>Period: ' . ($month && $year ? date('F Y', mktime(0, 0, 0, $month, 1, $year)) : date('F j, Y', strtotime($date))) . '</p>
    </div>';
    
    if (count($records) > 0) {
        echo '<table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Teacher</th>
                    <th>Class</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Duration</th>
                    <th>Scanned By</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($records as $record) {
            $duration = '';
            if ($record['duration_minutes']) {
                $hours = floor($record['duration_minutes'] / 60);
                $minutes = $record['duration_minutes'] % 60;
                $duration = sprintf("%dh %02dm", $hours, $minutes);
            }
            
            echo '<tr>
                <td>' . htmlspecialchars($record['date']) . '</td>
                <td>' . htmlspecialchars($record['teacher_name']) . '</td>
                <td>' . htmlspecialchars($record['class_name']) . '</td>
                <td>' . htmlspecialchars($record['time_in']) . '</td>
                <td>' . htmlspecialchars($record['time_out'] ?: 'In Progress') . '</td>
                <td>' . htmlspecialchars($duration ?: '-') . '</td>
                <td>' . htmlspecialchars($record['scanned_by_name']) . '</td>
            </tr>';
        }
        
        echo '</tbody>
        </table>';
    } else {
        echo '<div class="no-records">No attendance records found for the selected period.</div>';
    }
    
    echo '</body>
</html>';
}

exit();
?>
