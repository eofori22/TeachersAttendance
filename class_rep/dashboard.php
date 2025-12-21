<?php 
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../includes/auth.php';
include '../includes/header.php';

if (!isset($conn)) {
    die("Database connection not available");
}

// Get base path
$base_path = getBasePath();

// Verify class representative role
if ($_SESSION['role'] !== 'class_rep') {
    header('Location: ' . $base_path . '/index.php');
    exit();
}

// Add error handling for user_id
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_path . '/index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get class rep details and class information
$stmt = $conn->prepare("SELECT u.full_name, u.email, c.class_id, c.class_name 
FROM users u 
LEFT JOIN classes c ON u.user_id = c.class_rep_id 
WHERE u.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Initialize variables
$today_scans = [];
$weekly_summary = [];
$today_schedule = [];
$error_message = '';

if (!$user || !isset($user['class_id']) || empty($user['class_id'])) {
    // Show a more user-friendly error message instead of dying
    $error_message = "You are not assigned as a class representative or your class is not properly configured. Please contact your administrator.";
} else {
    // Get today's scanned attendance
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT a.attendance_id, a.time_in, a.time_out, 
               u.full_name AS teacher_name,
               TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) AS duration_minutes
        FROM attendance a
        JOIN users u ON a.teacher_id = u.user_id
        WHERE a.class_id = ? AND a.date = ? AND a.scanned_by = ?
        ORDER BY a.time_in DESC
    ");
    $stmt->bind_param("isi", $user['class_id'], $today, $user_id);
    $stmt->execute();
    $today_scans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get weekly scan summary
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $week_end = date('Y-m-d', strtotime('sunday this week'));

    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(a.date, '%W') AS day_name,
            COUNT(*) AS scan_count,
            GROUP_CONCAT(DISTINCT u.full_name SEPARATOR ', ') AS teachers_scanned
        FROM attendance a
        JOIN users u ON a.teacher_id = u.user_id
        WHERE a.class_id = ? AND a.date BETWEEN ? AND ? AND a.scanned_by = ?
        GROUP BY a.date, day_name
        ORDER BY a.date
    ");
    $stmt->bind_param("issi", $user['class_id'], $week_start, $week_end, $user_id);
    $stmt->execute();
    $weekly_summary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get upcoming scheduled teachers for this class
    // Note: Schedule may be stored as full day name (Monday) or short (Mon)
    $today_name = date('l'); // Full day name like 'Monday'
    $today_name_short = date('D'); // Short day name like 'Mon'
    
    $stmt = $conn->prepare("
        SELECT 
            u.full_name AS teacher_name,
            COALESCE(s.subject_name, c.class_name) AS subject_name,
            ta.schedule
        FROM teacher_assignments ta
        JOIN users u ON ta.teacher_id = u.user_id
        JOIN classes c ON ta.class_id = c.class_id
        LEFT JOIN subjects s ON ta.subject_id = s.subject_id
        WHERE ta.class_id = ? AND (ta.schedule = ? OR ta.schedule = ?)
        ORDER BY subject_name
    ");
    $stmt->bind_param("iss", $user['class_id'], $today_name, $today_name_short);
    $stmt->execute();
    $today_schedule = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="container-fluid py-4">
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 col-xl-2 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex flex-column align-items-center text-center mb-4">
                        <div class="avatar-xl mb-3">
                            <?php 
                            $initials = '';
                            if (!empty($user) && isset($user['full_name'])) {
                                $names = explode(' ', $user['full_name']);
                                $initials = strtoupper(substr($names[0], 0, 1) . (count($names) > 1 ? substr(end($names), 0, 1) : ''));
                            }
                            ?>
                            <span class="avatar-initials bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 1.5rem;"><?= $initials ?></span>
                        </div>
                        <h5 class="mb-1"><?= htmlspecialchars(!empty($user) && isset($user['full_name']) ? $user['full_name'] : 'N/A') ?></h5>
                        <p class="text-muted small mb-2">Class Representative</p>
                        <?php if (!empty($user) && isset($user['class_name'])): ?>
                            <div class="badge bg-primary bg-opacity-10 text-primary"><?= htmlspecialchars($user['class_name']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <hr class="my-3">
                    
                    <ul class="nav nav-pills flex-column gap-2">
                        <li class="nav-item">
                            <a class="nav-link active" href="<?= $base_path ?>/class_rep/dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_path ?>/class_rep/scan.php">
                                <i class="fas fa-camera me-2"></i> Scan QR Code
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_path ?>/class_rep/attendance.php">
                                <i class="fas fa-calendar-check me-2"></i> Attendance Records
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_path ?>/class_rep/schedule.php">
                                <i class="fas fa-calendar-alt me-2"></i> Class Schedule
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_path ?>/class_rep/profile.php">
                                <i class="fas fa-user me-2"></i> Profile Settings
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9 col-xl-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Class Representative Dashboard</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary" id="refreshBtn">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-calendar me-1"></i> <?= date('F Y') ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">This Week</a></li>
                            <li><a class="dropdown-item" href="#">This Month</a></li>
                            <li><a class="dropdown-item" href="#">Custom Range</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-uppercase text-muted mb-2">Today's Scans</h6>
                                    <h3 class="mb-0"><?= count($today_scans) ?></h3>
                                </div>
                                <div class="icon-circle bg-primary bg-opacity-10 text-primary">
                                    <i class="fas fa-qrcode"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-success small">
                                    <?= count($today_scans) > 0 ? 'Active scanning' : 'No scans today' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-uppercase text-muted mb-2">Weekly Scans</h6>
                                    <h3 class="mb-0">
                                        <?= array_sum(array_column($weekly_summary, 'scan_count')) ?>
                                    </h3>
                                </div>
                                <div class="icon-circle bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-calendar-week"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-muted small">
                                    <?= count($weekly_summary) ?> days with scans
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-uppercase text-muted mb-2">Today's Teachers</h6>
                                    <h3 class="mb-0"><?= count($today_schedule) ?></h3>
                                </div>
                                <div class="icon-circle bg-info bg-opacity-10 text-info">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="<?= count($today_schedule) > 0 ? 'text-success' : 'text-muted' ?> small">
                                    <?= count($today_schedule) > 0 ? 'Teachers scheduled' : 'No classes today' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-uppercase text-muted mb-2">Pending Scans</h6>
                                    <h3 class="mb-0">
                                        <?= max(0, count($today_schedule) - count($today_scans)) ?>
                                    </h3>
                                </div>
                                <div class="icon-circle bg-warning bg-opacity-10 text-warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="<?= (count($today_schedule) - count($today_scans)) > 0 ? 'text-warning' : 'text-success' ?> small">
                                    <?= (count($today_schedule) - count($today_scans)) > 0 ? 'Need attention' : 'All scanned' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Today's Scans -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Today's Scanned Attendance</h5>
                                <a href="<?= $base_path ?>/class_rep/scan.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-camera me-1"></i> New Scan
                                </a>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <?php if (count($today_scans) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Teacher</th>
                                                <th>Time In</th>
                                                <th>Time Out</th>
                                                <th>Duration</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($today_scans as $scan): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($scan['teacher_name']) ?></td>
                                                    <td><?= date('h:i A', strtotime($scan['time_in'])) ?></td>
                                                    <td>
                                                        <?= $scan['time_out'] ? date('h:i A', strtotime($scan['time_out'])) : '<span class="badge bg-info">In Class</span>' ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if ($scan['duration_minutes']) {
                                                            $hours = floor($scan['duration_minutes'] / 60);
                                                            $minutes = $scan['duration_minutes'] % 60;
                                                            echo sprintf("%dh %02dm", $hours, $minutes);
                                                        } else {
                                                            echo '-';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($scan['time_out']): ?>
                                                            <span class="badge bg-success">Completed</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">In Progress</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-qrcode fa-3x text-muted mb-3"></i>
                                    <h5>No scans recorded today</h5>
                                    <p class="text-muted">Scan teacher QR codes when they arrive and leave your class</p>
                                    <a href="<?= $base_path ?>/class_rep/scan.php" class="btn btn-outline-primary">
                                        <i class="fas fa-qrcode me-2"></i> Start Scanning
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Today's Schedule -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0">Today's Schedule (<?= date('l') ?>)</h5>
                        </div>
                        <div class="card-body pt-0">
                            <?php if (count($today_schedule) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($today_schedule as $class): ?>
                                        <?php
                                        $is_scanned = false;
                                        foreach ($today_scans as $scan) {
                                            if (strpos($scan['teacher_name'], $class['teacher_name']) !== false) {
                                                $is_scanned = true;
                                                break;
                                            }
                                        }
                                        ?>
                                        <div class="list-group-item border-0 px-0 py-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0"><?= htmlspecialchars($class['subject_name']) ?></h6>
                                                <span class="badge <?= $is_scanned ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $is_scanned ? 'Scanned' : 'Pending' ?>
                                                </span>
                                            </div>
                                            <p class="small text-muted mb-1">
                                                <i class="fas fa-user-tie me-1"></i> <?= htmlspecialchars($class['teacher_name']) ?>
                                            </p>
                                            <?php if (!empty($class['schedule'])): ?>
                                                <p class="small text-muted mb-0">
                                                    <i class="fas fa-calendar me-1"></i> <?= htmlspecialchars($class['schedule']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-day fa-3x text-muted mb-3"></i>
                                    <h5>No classes scheduled</h5>
                                    <p class="text-muted">Enjoy your day off!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Refresh button functionality
document.getElementById('refreshBtn').addEventListener('click', function() {
    window.location.reload();
});
</script>

<?php include '../includes/footer.php'; ?>