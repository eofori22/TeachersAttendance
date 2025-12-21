<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

// Verify the request is from an authenticated teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Check if teacher already has a QR code
$stmt = $conn->prepare("SELECT qr_code FROM users WHERE user_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $teacher = $result->fetch_assoc();
    
    if (!empty($teacher['qr_code'])) {
        echo json_encode([
            'success' => true,
            'qr_code' => $teacher['qr_code'],
            'message' => 'QR code already exists'
        ]);
        exit();
    }
}

// Generate new QR code data
$qr_data = "TCH-" . uniqid() . "-" . $teacher_id;
$qr_code = md5($qr_data);

// Update the teacher's record with the new QR code
$update_stmt = $conn->prepare("UPDATE users SET qr_code = ? WHERE user_id = ?");
$update_stmt->bind_param("si", $qr_code, $teacher_id);

if ($update_stmt->execute()) {
    echo json_encode([
        'success' => true,
        'qr_code' => $qr_code,
        'message' => 'QR code generated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate QR code'
    ]);
}
?>