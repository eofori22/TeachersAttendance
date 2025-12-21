<?php
require_once 'config.php';

// Function to get base path for redirects
function getBasePath() {
    $script_name = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
    $script_dir = dirname($script_name);
    
    if (strpos($script_dir, '/TeachersAttendance') !== false || strpos($script_dir, 'TeachersAttendance') !== false) {
        return '/TeachersAttendance';
    } elseif ($script_dir === '/' || $script_dir === '\\' || $script_dir === '.') {
        return '';
    } else {
        $parts = explode('/', trim($script_dir, '/'));
        if (in_array('TeachersAttendance', $parts)) {
            return '/TeachersAttendance';
        } else {
            $base_path = $script_dir;
            $base_path = str_replace('\\', '/', $base_path);
            if ($base_path !== '' && $base_path !== '/') {
                $base_path = rtrim($base_path, '/');
            }
            return $base_path;
        }
    }
}

class Auth {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Check if user has a specific role
     */
    public function hasRole($role) {
        if (!$this->isLoggedIn()) return false;
        return $_SESSION['role'] === $role;
    }
    
    /**
     * Login user with username and password
     */
    public function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT user_id, username, password, role, full_name, profile_image FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent fixation
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['profile_image'] = $user['profile_image'] ?? null;
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Logout current user
     */
    public function logout() {
        // Unset all session variables
        $_SESSION = array();
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
    }
    
    /**
     * Redirect if not logged in
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            $base_path = getBasePath();
            header("Location: " . $base_path . "/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
            exit();
        }
    }
    
    /**
     * Redirect if user doesn't have required role
     */
    public function requireRole($role) {
        $this->requireLogin();
        
        if (!$this->hasRole($role)) {
            $base_path = getBasePath();
            header("Location: " . $base_path . "/unauthorized.php");
            exit();
        }
    }
    
    /**
     * Get current user's ID
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user's role
     */
    public function getUserRole() {
        return $_SESSION['role'] ?? null;
    }
    
    /**
     * Get current user's name
     */
    public function getUserName() {
        return $_SESSION['full_name'] ?? null;
    }
    
    /**
     * CSRF token generation and validation
     */
    public function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Password strength validation
     */
    public function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        
        if (!preg_match("#[0-9]+#", $password)) {
            $errors[] = "Password must include at least one number";
        }
        
        if (!preg_match("#[a-zA-Z]+#", $password)) {
            $errors[] = "Password must include at least one letter";
        }
        
        if (!preg_match("#[^a-zA-Z0-9]+#", $password)) {
            $errors[] = "Password must include at least one special character";
        }
        
        return $errors;
    }
}

// Initialize Auth class
$auth = new Auth($conn);

// Auto-require login for all pages that include this file (except whitelisted)
$whitelist = ['login.php', 'register.php', 'forgot-password.php', 'reset-password.php', 'logout.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (!in_array($current_page, $whitelist)) {
    $auth->requireLogin();
    
    // Additional security checks
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        // 30 minutes inactivity timeout
        $auth->logout();
        $base_path = getBasePath();
        header("Location: " . $base_path . "/login.php?timeout=1");
        exit();
    }
    
    // Prevent session fixation
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 1800) {
        // Regenerate session ID every 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
    
    $_SESSION['last_activity'] = time();
    
    // Verify user still exists in database
    $check_user = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $check_user->bind_param("i", $_SESSION['user_id']);
    $check_user->execute();
    
    if ($check_user->get_result()->num_rows === 0) {
        $auth->logout();
        $base_path = getBasePath();
        header("Location: " . $base_path . "/login.php?invalid=1");
        exit();
    }
}
?>
