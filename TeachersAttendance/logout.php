<?php
// Start session and include authentication
require_once 'includes/auth.php';

// Get base path
$base_path = getBasePath();

// Initialize Auth class
$auth = new Auth($conn);

// Verify CSRF token if provided (extra security)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!$auth->validateCSRFToken($_POST['csrf_token'])) {
        // Log potential CSRF attack
        error_log("Potential CSRF attack detected from IP: " . $_SERVER['REMOTE_ADDR']);
        die("Security error: Invalid CSRF token");
    }
}

// Perform logout
$auth->logout();

// Check if this is an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // AJAX request - return JSON response
    header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Logged out successfully',
                'redirect' => $base_path . '/login.php'
            ]);
    exit();
}

// Regular request - redirect to login page
$redirect_url = $base_path . '/login.php';

// Add logout message parameter
if (isset($_GET['timeout'])) {
    $redirect_url .= '?timeout=1';
} elseif (isset($_GET['admin'])) {
    $redirect_url .= '?admin=1';
}

// Redirect to login page
header("Location: $redirect_url");
exit();
?>