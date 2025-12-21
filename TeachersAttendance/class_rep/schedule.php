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
$class_schedule = [];
$weekly_schedule = [];
$error_message = '';

if (!$user || !isset($user['class_id']) || empty($user['class_id'])) {
    $error_message = "You are not assigned as a class representative or your class is not properly configured. Please contact your administrator.";
} else {
    // Get all teacher assignments for this class
    $stmt = $conn->prepare("
        SELECT 
            ta.assignment_id,
            u.full_name AS teacher_name,
            s.subject_name,
            ta.schedule as day_of_week
        FROM teacher_assignments ta
        JOIN users u ON ta.teacher_id = u.user_id
        JOIN subjects s ON ta.subject_id = s.subject_id
        WHERE ta.class_id = ?
        ORDER BY 
            FIELD(ta.schedule, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'),
            s.subject_name
    ");
    $stmt->bind_param("i", $user['class_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $class_schedule = $result->fetch_all(MYSQLI_ASSOC);
    
    // Organize by day of week for timetable view
    foreach ($class_schedule as $schedule) {
        $day = $schedule['day_of_week'];
        // Normalize day names
        $day_map = [
            'Mon' => 'Monday',
            'Tue' => 'Tuesday',
            'Wed' => 'Wednesday',
            'Thu' => 'Thursday',
            'Fri' => 'Friday',
            'Sat' => 'Saturday',
            'Sun' => 'Sunday'
        ];
        $normalized_day = $day_map[$day] ?? $day;
        $weekly_schedule[$normalized_day][] = $schedule;
    }
    
    // Get current week dates
    $current_week_dates = [];
    $monday = strtotime('last monday', strtotime('tomorrow'));
    for ($i = 0; $i < 7; $i++) {
        $current_week_dates[] = date('Y-m-d', strtotime("+$i days", $monday));
    }
    
    // Days of the week
    $days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
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
                            <a class="nav-link" href="<?= $base_path ?>/class_rep/dashboard.php">
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
                            <a class="nav-link active" href="<?= $base_path ?>/class_rep/schedule.php">
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
                <h2 class="mb-0">Class Schedule</h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if (empty($error_message)): ?>
                <!-- Weekly Schedule View -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">Weekly Schedule - <?= htmlspecialchars($user['class_name']) ?></h5>
                    </div>
                    <div class="card-body pt-0">
                        <?php if (count($class_schedule) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 14%">Day</th>
                                            <th>Subject</th>
                                            <th>Teacher</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($days_of_week as $day): ?>
                                            <?php if (isset($weekly_schedule[$day]) && count($weekly_schedule[$day]) > 0): ?>
                                                <?php foreach ($weekly_schedule[$day] as $index => $schedule): ?>
                                                    <tr>
                                                        <?php if ($index === 0): ?>
                                                            <td rowspan="<?= count($weekly_schedule[$day]) ?>" class="align-middle text-center fw-bold">
                                                                <?= $day ?><br>
                                                                <small class="text-muted">
                                                                    <?php
                                                                    $day_index = array_search($day, $days_of_week);
                                                                    if ($day_index !== false && isset($current_week_dates[$day_index])) {
                                                                        echo date('M j', strtotime($current_week_dates[$day_index]));
                                                                    }
                                                                    ?>
                                                                </small>
                                                            </td>
                                                        <?php endif; ?>
                                                        <td><?= htmlspecialchars($schedule['subject_name']) ?></td>
                                                        <td><?= htmlspecialchars($schedule['teacher_name']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td class="text-center fw-bold"><?= $day ?></td>
                                                    <td colspan="2" class="text-center text-muted">No classes scheduled</td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5>No schedule found</h5>
                                <p class="text-muted">No teachers are currently assigned to this class.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Detailed Schedule List -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">All Class Assignments</h5>
                    </div>
                    <div class="card-body pt-0">
                        <?php if (count($class_schedule) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="scheduleTable">
                                    <thead>
                                        <tr>
                                            <th>Day</th>
                                            <th>Subject</th>
                                            <th>Teacher</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($class_schedule as $schedule): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($schedule['day_of_week']) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($schedule['subject_name']) ?></td>
                                                <td><?= htmlspecialchars($schedule['teacher_name']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No class assignments found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// DataTables initialization
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('scheduleTable');
    if (table) {
        // Check if jQuery and DataTables are loaded
        if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable) {
            jQuery(table).DataTable({
                order: [[0, 'asc']],
                pageLength: 25,
                language: {
                    search: "Search schedule:",
                    lengthMenu: "Show _MENU_ records per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ records",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>
