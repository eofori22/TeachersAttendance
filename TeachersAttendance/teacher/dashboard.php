<?php 
include '../includes/auth.php';

// Get base path
$base_path = getBasePath();

// Verify teacher role
if ($_SESSION['role'] !== 'teacher') {
    header('Location: ' . $base_path . '/index.php');
    exit();
}

// Get teacher details
$teacher_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, email, qr_code FROM users WHERE user_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

// Get today's attendance records
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT a.date, a.time_in, a.time_out, c.class_name, 
           TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) AS duration_minutes,
           u.full_name AS scanned_by_name
    FROM attendance a
    JOIN classes c ON a.class_id = c.class_id
    JOIN users u ON a.scanned_by = u.user_id
    WHERE a.teacher_id = ? AND a.date = ?
    ORDER BY a.time_in DESC
");
$stmt->bind_param("is", $teacher_id, $today);
$stmt->execute();
$today_attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get weekly summary
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));

$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(date, '%W') AS day_name,
        COUNT(*) AS class_count,
        SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, time_in, IFNULL(time_out, NOW())))) AS total_duration
    FROM attendance
    WHERE teacher_id = ? AND date BETWEEN ? AND ?
    GROUP BY date, day_name
    ORDER BY date
");
$stmt->bind_param("iss", $teacher_id, $week_start, $week_end);
$stmt->execute();
$weekly_summary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get upcoming classes (from teacher assignments)
$stmt = $conn->prepare("
    SELECT 
        COALESCE(s.subject_name, c.class_name) AS subject_name,
        c.class_name, 
        ta.schedule
    FROM teacher_assignments ta
    LEFT JOIN subjects s ON ta.subject_id = s.subject_id
    JOIN classes c ON ta.class_id = c.class_id
    WHERE ta.teacher_id = ?
    ORDER BY FIELD(ta.schedule, 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'), c.class_name
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$upcoming_classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php'; 
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 col-xl-2 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex flex-column align-items-center text-center mb-4">
                        <div class="avatar-xl mb-3">
                            <?php 
                            $initials = '';
                            $names = explode(' ', $teacher['full_name']);
                            $initials = strtoupper(substr($names[0], 0, 1) . (count($names) > 1 ? substr(end($names), 0, 1) : ''));
                            ?>
                            <span class="avatar-initials bg-primary bg-opacity-10 text-primary rounded-circle"><?= $initials ?></span>
                        </div>
                        <h5 class="mb-1"><?= htmlspecialchars($teacher['full_name']) ?></h5>
                        <p class="text-muted small mb-2">Teacher</p>
                        <div class="badge bg-primary bg-opacity-10 text-primary">Active</div>
                    </div>
                    
                    <hr class="my-3">
                    
                    <ul class="nav nav-pills flex-column gap-2">
                        <li class="nav-item">
                            <a class="nav-link active" href="<?= $base_path ?>/teacher/dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_path ?>/teacher/qr_code.php">
                                <i class="fas fa-qrcode me-2"></i> My QR Code
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_path ?>/teacher/teacher_attendance.php">
                                <i class="fas fa-calendar-check me-2"></i> Attendance Records
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_path ?>/teacher/teacher_schedule.php">
                                <i class="fas fa-calendar-alt me-2"></i> My Schedule
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_path ?>/teacher/profile.php">
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
                <h2 class="mb-0">Teacher Dashboard</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary">
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
                                    <h6 class="text-uppercase text-muted mb-2">Today's Classes</h6>
                                    <h3 class="mb-0"><?= count($today_attendance) ?></h3>
                                </div>
                                <div class="icon-circle bg-primary bg-opacity-10 text-primary">
                                    <i class="fas fa-chalkboard"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-success small">
                                    <i class="fas fa-arrow-up me-1"></i> 2 from yesterday
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
                                    <h6 class="text-uppercase text-muted mb-2">Weekly Hours</h6>
                                    <h3 class="mb-0">
                                        <?php
                                        $total_hours = 0;
                                        foreach ($weekly_summary as $day) {
                                            if ($day['total_duration']) {
                                                list($h, $m, $s) = explode(':', $day['total_duration']);
                                                $total_hours += $h + ($m / 60);
                                            }
                                        }
                                        echo number_format($total_hours, 1);
                                        ?>
                                    </h3>
                                </div>
                                <div class="icon-circle bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-muted small">Target: 40 hours</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-uppercase text-muted mb-2">Classes Taught</h6>
                                    <h3 class="mb-0"><?= count($upcoming_classes) ?></h3>
                                </div>
                                <div class="icon-circle bg-info bg-opacity-10 text-info">
                                    <i class="fas fa-book-open"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-success small">
                                    <i class="fas fa-check-circle me-1"></i> All classes covered
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
                                    <h6 class="text-uppercase text-muted mb-2">Next Class</h6>
                                    <h3 class="mb-0">
                                        <?php 
                                        $next_class = $upcoming_classes[0]['schedule'] ?? 'None';
                                        echo $next_class === 'None' ? 'None' : "Tomorrow";
                                        ?>
                                    </h3>
                                </div>
                                <div class="icon-circle bg-warning bg-opacity-10 text-warning">
                                    <i class="fas fa-bell"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-muted small">
                                    <?= $upcoming_classes[0]['class_name'] ?? 'No upcoming classes' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Today's Attendance -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0">Today's Attendance</h5>
                        </div>
                        <div class="card-body pt-0">
                            <?php if (count($today_attendance) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Class</th>
                                                <th>Time In</th>
                                                <th>Time Out</th>
                                                <th>Duration</th>
                                                <th>Scanned By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($today_attendance as $record): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($record['class_name']) ?></td>
                                                    <td><?= date('h:i A', strtotime($record['time_in'])) ?></td>
                                                    <td>
                                                        <?= $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '<span class="badge bg-info">In Class</span>' ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if ($record['duration_minutes']) {
                                                            $hours = floor($record['duration_minutes'] / 60);
                                                            $minutes = $record['duration_minutes'] % 60;
                                                            echo sprintf("%dh %02dm", $hours, $minutes);
                                                        } else {
                                                            echo '-';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($record['scanned_by_name']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                    <h5>No attendance records for today</h5>
                                    <p class="text-muted">Your attendance will appear here once class reps scan your QR code</p>
                                    <a href="<?= $base_path ?>/teacher/qr_code.php" class="btn btn-outline-primary">
                                        <i class="fas fa-qrcode me-2"></i> View My QR Code
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Weekly Summary -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0">This Week's Summary</h5>
                        </div>
                        <div class="card-body pt-0">
                            <?php if (count($weekly_summary) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php 
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($days as $day): 
                                        $day_data = array_filter($weekly_summary, function($item) use ($day) {
                                            return $item['day_name'] === $day;
                                        });
                                        $day_data = reset($day_data);
                                    ?>
                                        <div class="list-group-item border-0 px-0 py-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?= $day ?></h6>
                                                    <p class="small text-muted mb-0">
                                                        <?= $day_data ? $day_data['class_count'] . ' classes' : 'No classes' ?>
                                                    </p>
                                                </div>
                                                <div class="text-end">
                                                    <span class="d-block fw-bold">
                                                        <?= $day_data && $day_data['total_duration'] ? 
                                                            explode(':', $day_data['total_duration'])[0] . 'h' : '0h' ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="progress mt-2" style="height: 6px;">
                                                <div class="progress-bar bg-primary" role="progressbar" 
                                                     style="width: <?= $day_data ? min(100, (explode(':', $day_data['total_duration'])[0] / 8) * 100) : 0 ?>%" 
                                                     aria-valuenow="<?= $day_data ? explode(':', $day_data['total_duration'])[0] : 0 ?>" 
                                                     aria-valuemin="0" aria-valuemax="8">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-week fa-3x text-muted mb-3"></i>
                                    <h5>No classes this week</h5>
                                    <p class="text-muted">Your weekly summary will appear here</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Classes -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">My Classes</h5>
                                <a href="<?= $base_path ?>/teacher/teacher_schedule.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <?php if (count($upcoming_classes) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Class</th>
                                                <th>Schedule</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($upcoming_classes, 0, 5) as $class): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($class['subject_name']) ?></td>
                                                    <td><?= htmlspecialchars($class['class_name']) ?></td>
                                                    <td>
                                                        <span class="badge bg-primary bg-opacity-10 text-primary">
                                                            <?= htmlspecialchars($class['schedule']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                    <h5>No classes assigned</h5>
                                    <p class="text-muted">Contact your administrator to get assigned to classes</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <a href="<?= $base_path ?>/teacher/qr_code.php" class="card action-card h-100 text-decoration-none">
                                        <div class="card-body text-center py-4">
                                            <div class="icon-circle-lg bg-primary bg-opacity-10 text-primary mb-3 mx-auto">
                                                <i class="fas fa-qrcode fa-2x"></i>
                                            </div>
                                            <h6 class="mb-0">My QR Code</h6>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?= $base_path ?>/teacher/teacher_attendance.php" class="card action-card h-100 text-decoration-none">
                                        <div class="card-body text-center py-4">
                                            <div class="icon-circle-lg bg-success bg-opacity-10 text-success mb-3 mx-auto">
                                                <i class="fas fa-calendar-check fa-2x"></i>
                                            </div>
                                            <h6 class="mb-0">Attendance</h6>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?= $base_path ?>/teacher/teacher_schedule.php" class="card action-card h-100 text-decoration-none">
                                        <div class="card-body text-center py-4">
                                            <div class="icon-circle-lg bg-info bg-opacity-10 text-info mb-3 mx-auto">
                                                <i class="fas fa-calendar-alt fa-2x"></i>
                                            </div>
                                            <h6 class="mb-0">My Schedule</h6>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?= $base_path ?>/teacher/profile.php" class="card action-card h-100 text-decoration-none">
                                        <div class="card-body text-center py-4">
                                            <div class="icon-circle-lg bg-warning bg-opacity-10 text-warning mb-3 mx-auto">
                                                <i class="fas fa-user-cog fa-2x"></i>
                                            </div>
                                            <h6 class="mb-0">Profile</h6>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-xl {
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-initials {
    font-size: 2rem;
    font-weight: 600;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-card {
    transition: all 0.2s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    border-color: rgba(102, 126, 234, 0.5);
}

.icon-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-circle-lg {
    width: 60px;
    height: 60px;
}

.nav-pills .nav-link {
    border-radius: 8px;
    padding: 0.5rem 1rem;
    color: #495057;
}

.nav-pills .nav-link.active {
    background-color: rgba(102, 126, 234, 0.1);
    color: #667eea;
    font-weight: 500;
}

.progress {
    border-radius: 100px;
    background-color: #f0f2f5;
}

.table-hover > tbody > tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
}

@media (max-width: 991.98px) {
    .avatar-xl {
        width: 80px;
        height: 80px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>