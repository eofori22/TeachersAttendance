<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Calculate base URL path for the project (works with XAMPP subdirectories)
// Get the directory of the current script relative to document root
$script_name = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
$script_dir = dirname($script_name);

// Check if we're in the TeachersAttendance directory
if (strpos($script_dir, '/TeachersAttendance') !== false || strpos($script_dir, 'TeachersAttendance') !== false) {
    $base_path = '/TeachersAttendance';
} elseif ($script_dir === '/' || $script_dir === '\\' || $script_dir === '.') {
    $base_path = '';
} else {
    // Extract the project root from the script directory
    $parts = explode('/', trim($script_dir, '/'));
    if (in_array('TeachersAttendance', $parts)) {
        $base_path = '/TeachersAttendance';
    } else {
        $base_path = $script_dir;
        $base_path = str_replace('\\', '/', $base_path);
        if ($base_path !== '' && $base_path !== '/') {
            $base_path = rtrim($base_path, '/');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ompad STEM - Teacher Attendance System</title>
    
    <!-- Favicon (commented out - files don't exist) -->
    <!-- 
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $base_path; ?>/assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base_path; ?>/assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $base_path; ?>/assets/favicon/favicon-16x16.png">
    <link rel="manifest" href="<?php echo $base_path; ?>/assets/favicon/site.webmanifest">
    -->
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/style.css">
    
    <!-- Dark mode preference -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const storedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', storedTheme);
        });
    </script>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Skip to content link for accessibility -->
    <a href="#main-content" class="visually-hidden-focusable position-absolute top-0 start-0 p-2 bg-dark text-white rounded-bottom-end">Skip to main content</a>
    
    <!-- Header Navigation -->
    <header class="sticky-top shadow-sm">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="<?php echo $base_path; ?>/index.php">
                    <i class="fas fa-chalkboard-teacher me-2"></i>
                    <span class="fw-bold">Ompad STEM</span>
                    <span class="ms-2 d-none d-sm-inline text-white-50">| Teacher Attendance</span>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <!-- Authenticated User Menu -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="avatar-sm me-2">
                                        <?php 
                                        $profile_image = $_SESSION['profile_image'] ?? null;
                                        $initials = '';
                                        if (isset($_SESSION['full_name'])) {
                                            $names = explode(' ', $_SESSION['full_name']);
                                            $initials = strtoupper(substr($names[0], 0, 1) . (count($names) > 1 ? substr(end($names), 0, 1) : ''));
                                        }
                                        
                                        if ($profile_image && file_exists($_SERVER['DOCUMENT_ROOT'] . $base_path . '/uploads/profiles/' . $profile_image)):
                                        ?>
                                            <img src="<?php echo $base_path; ?>/uploads/profiles/<?php echo htmlspecialchars($profile_image); ?>" 
                                                 alt="Profile" 
                                                 class="rounded-circle" 
                                                 style="width: 32px; height: 32px; object-fit: cover;">
                                        <?php else: ?>
                                            <span class="avatar-initials bg-white text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.875rem;"><?php echo $initials; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="d-none d-lg-inline"><?php echo $_SESSION['full_name'] ?? 'Account'; ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <li><h6 class="dropdown-header">Signed in as <?php echo $_SESSION['role'] ?? 'User'; ?></h6></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo $base_path; ?>/includes/profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                    <li><a class="dropdown-item" href="<?php echo $base_path; ?>/includes/settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo $base_path; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sign out</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <!-- Guest Menu -->
                            <li class="nav-item">
                                <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Theme Toggle -->
                        <li class="nav-item ms-lg-2">
                            <button class="btn btn-link nav-link py-2 px-2 theme-toggle" type="button" aria-label="Toggle theme">
                                <i class="fas fa-moon fa-fw d-none" data-theme-icon="dark"></i>
                                <i class="fas fa-sun fa-fw d-none" data-theme-icon="light"></i>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Secondary Navigation -->
        <div class="bg-white border-bottom">
            <div class="container">
                <ul class="nav nav-underline">
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'admin') !== false ? 'active' : ''; ?>" href="<?php echo $base_path; ?>/admin/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'admin') !== false ? 'active' : ''; ?>" href="<?php echo $base_path; ?>/admin/dashboard.php">
                                <i class="fas fa-lock me-1"></i> Admin
                            </a>
                        </li>
                    <?php elseif ($_SESSION['role'] === 'teacher'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'teacher') !== false && basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>/teacher/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>/teacher/qrcode.php">
                                <i class="fas fa-qrcode me-1"></i> My QR Code
                            </a>
                        </li>
                    <?php elseif ($_SESSION['role'] === 'class_rep'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'class_rep') !== false && basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>/class_rep/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'scan.php') !== false ? 'active' : ''; ?>" href="<?php echo $base_path; ?>/class_rep/scan.php">
                                <i class="fas fa-camera me-1"></i> Scan QR
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </header>

    <!-- Main Content Container -->
    <main id="main-content" class="flex-grow-1">