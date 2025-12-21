<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'teacher_attendance_system');

// Create connection
// For XAMPP, use socket path if localhost doesn't work
$socket_path = '/opt/lampp/var/mysql/mysql.sock';
if (file_exists($socket_path)) {
    $conn = new mysqli(null, DB_USER, DB_PASS, DB_NAME, null, $socket_path);
} else {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Start session
session_start();
?>