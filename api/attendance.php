<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

include '../includes/config.php';
include '../includes/auth.php';

// Verify class representative role
if ($_SESSION['role'] !== 'class_rep') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// GET request - fetch today's attendance
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $class_id = intval($_GET['class_id'] ?? 0);
    $today = date('Y-m-d');
    
    $stmt = $conn->prepare("
        SELECT a.attendance_id, u.full_name AS teacher_name, 
               DATE_FORMAT(a.time_in, '%h:%i %p') AS time_in,
               DATE_FORMAT(a.time_out, '%h:%i %p') AS time_out
        FROM attendance a
        JOIN users u ON a.teacher_id = u.user_id
        WHERE a.class_id = ? AND a.date = ? AND a.scanned_by = ?
        ORDER BY a.time_in DESC
    ");
    $stmt->bind_param("isi", $class_id, $today, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    exit();
}

// POST request - process QR scan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($input['action']) || !isset($input['qr_code']) || !isset($input['class_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }
    
    // Process the scan
    try {
        $qr_code = trim($input['qr_code']);
        $class_id = intval($input['class_id']);
        $action = $input['action'] === 'time_in' ? 'time_in' : 'time_out';
        $now = date('Y-m-d H:i:s');
        
        // First, look up the teacher by QR code
        $teacher_lookup = $conn->prepare("SELECT user_id FROM users WHERE qr_code = ?");
        $teacher_lookup->bind_param("s", $qr_code);
        $teacher_lookup->execute();
        $teacher_result = $teacher_lookup->get_result();
        
        if ($teacher_result->num_rows === 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid QR code. Teacher not found.'
            ]);
            exit();
        }
        
        $teacher = $teacher_result->fetch_assoc();
        $teacher_id = intval($teacher['user_id']);
        
        if ($action === 'time_in') {
            // Check if already timed in today
            $check = $conn->prepare("
                SELECT attendance_id FROM attendance 
                WHERE teacher_id = ? AND class_id = ? AND date = CURDATE()
            ");
            $check->bind_param("ii", $teacher_id, $class_id);
            $check->execute();
            
            if ($check->get_result()->num_rows > 0) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Teacher already timed in today'
                ]);
                exit();
            }
            
            // Record time in
            $stmt = $conn->prepare("
                INSERT INTO attendance 
                (teacher_id, class_id, date, time_in, scanned_by)
                VALUES (?, ?, CURDATE(), ?, ?)
            ");
            $stmt->bind_param("iisi", $teacher_id, $class_id, $now, $_SESSION['user_id']);
        } else {
            // Record time out
            $stmt = $conn->prepare("
                UPDATE attendance 
                SET time_out = ?, scanned_by = ?
                WHERE teacher_id = ? AND class_id = ? AND date = CURDATE()
            ");
            $stmt->bind_param("siii", $now, $_SESSION['user_id'], $teacher_id, $class_id);
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => $action === 'time_in' 
                    ? 'Time in recorded successfully' 
                    : 'Time out recorded successfully'
            ]);
        } else {
            throw new Exception('Database error');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error processing attendance: ' . $e->getMessage()
        ]);
    }
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);