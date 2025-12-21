<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'login') {
    $username = $input['username'];
    $password = $input['password'];
    
    $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Determine redirect based on role
            $redirect = '';
            switch ($user['role']) {
                case 'admin':
                    $redirect = 'admin/dashboard.php';
                    break;
                case 'teacher':
                    $redirect = 'teacher/dashboard.php';
                    break;
                case 'class_rep':
                    $redirect = 'class_rep/dashboard.php';
                    break;
            }
            
            echo json_encode([
                'success' => true,
                'redirect' => $redirect
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid username or password'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password'
        ]);
    }
} elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
}
?>
