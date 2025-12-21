<?php
include '../includes/auth.php';
include '../includes/header.php';

// Get base path
$base_path = getBasePath();

// Verify teacher role
if ($_SESSION['role'] !== 'teacher') {
    header('Location: ' . $base_path . '/index.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get teacher's assigned classes
$stmt = $conn->prepare("
    SELECT 
        ta.assignment_id,
        COALESCE(s.subject_name, c.class_name) AS subject_name,
        c.class_name,
        ta.schedule as day_of_week,
        ta.start_time,
        ta.end_time,
        TIME_FORMAT(ta.start_time, '%h:%i %p') as start_time_formatted,
        TIME_FORMAT(ta.end_time, '%h:%i %p') as end_time_formatted,
        CONCAT(TIME_FORMAT(ta.start_time, '%h:%i %p'), ' - ', TIME_FORMAT(ta.end_time, '%h:%i %p')) as time_slot,
        ta.room_number
    FROM teacher_assignments ta
    LEFT JOIN subjects s ON ta.subject_id = s.subject_id
    JOIN classes c ON ta.class_id = c.class_id
    WHERE ta.teacher_id = ?
    ORDER BY 
        FIELD(ta.schedule, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
        ta.start_time
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$assigned_classes = $result->fetch_all(MYSQLI_ASSOC);

// Organize by day of week for timetable view
$weekly_schedule = [];
foreach ($assigned_classes as $class) {
    // Normalize day names to full names (e.g., Mon -> Monday)
    $day = $class['day_of_week'];
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
    $weekly_schedule[$normalized_day][] = $class;
}

// Get current week dates
$current_week_dates = [];
$monday = strtotime('last monday', strtotime('tomorrow'));
for ($i = 0; $i < 7; $i++) {
    $current_week_dates[] = date('Y-m-d', strtotime("+$i days", $monday));
}

// Days of the week
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
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
                            if (isset($_SESSION['full_name'])) {
                                $names = explode(' ', $_SESSION['full_name']);
                                $initials = strtoupper(substr($names[0], 0, 1) . (count($names) > 1 ? substr(end($names), 0, 1) : ''));
                            }
                            ?>
                            <span class="avatar-initials bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 1.5rem;"><?= $initials ?></span>
                        </div>
                        <h5 class="mb-1"><?= htmlspecialchars($_SESSION['full_name'] ?? 'N/A') ?></h5>
                        <p class="text-muted small mb-2">Teacher</p>
                        <div class="badge bg-primary bg-opacity-10 text-primary">Active</div>
                    </div>
                    
                    <hr class="my-3">
                    
                    <ul class="nav nav-pills flex-column gap-2">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_path ?>/teacher/dashboard.php">
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
                            <a class="nav-link active" href="<?= $base_path ?>/teacher/teacher_schedule.php">
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
                <h2 class="mb-0">My Schedule</h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Weekly Schedule View -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Weekly Timetable</h5>
                </div>
                <div class="card-body">
                    <?php if (count($assigned_classes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="weeklyTimetable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 12%">Time</th>
                                        <th>Monday<br><?= date('M j', strtotime($current_week_dates[0])) ?></th>
                                        <th>Tuesday<br><?= date('M j', strtotime($current_week_dates[1])) ?></th>
                                        <th>Wednesday<br><?= date('M j', strtotime($current_week_dates[2])) ?></th>
                                        <th>Thursday<br><?= date('M j', strtotime($current_week_dates[3])) ?></th>
                                        <th>Friday<br><?= date('M j', strtotime($current_week_dates[4])) ?></th>
                                        <th>Saturday<br><?= date('M j', strtotime($current_week_dates[5])) ?></th>
                                        <th>Sunday<br><?= date('M j', strtotime($current_week_dates[6])) ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Generate time slots from 7 AM to 7 PM
                                    for ($hour = 7; $hour <= 19; $hour++): 
                                        $time_slot = sprintf("%02d:00", $hour);
                                        $next_hour = $hour + 1;
                                        $next_time_slot = sprintf("%02d:00", $next_hour);
                                        ?>
                                        <tr>
                                            <td class="text-center"><?= date('g:i A', strtotime($time_slot)) ?></td>
                                            <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day): ?>
                                                <td>
                                                    <?php
                                                    if (isset($weekly_schedule[$day])) {
                                                        foreach ($weekly_schedule[$day] as $class) {
                                                            // Get raw time values for comparison (they come as TIME type from DB in HH:MM:SS format)
                                                            $class_start = date('H:i', strtotime($class['start_time']));
                                                            $class_end = date('H:i', strtotime($class['end_time']));
                                                            
                                                            // Only show if class starts in this hour slot
                                                            if ($class_start >= $time_slot && $class_start < $next_time_slot) {
                                                                $duration = (strtotime($class_end) - strtotime($class_start)) / 3600;
                                                                $rowspan = max(1, ceil($duration));
                                                                
                                                                echo '<div class="schedule-item p-2 mb-1 bg-primary text-white rounded" style="cursor: pointer;" 
                                                                        data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                        title="' . htmlspecialchars($class['subject_name']) . ' - ' . htmlspecialchars($class['class_name']) . '">
                                                                        <strong>' . htmlspecialchars($class['subject_name']) . '</strong><br>
                                                                        ' . htmlspecialchars($class['class_name']) . '<br>
                                                                        ' . $class['time_slot'] . '
                                                                        ' . (!empty($class['room_number']) ? '<br>Room: ' . htmlspecialchars($class['room_number']) : '') . '
                                                                    </div>';
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                            <h4>No classes assigned</h4>
                            <p class="text-muted">Your schedule will appear here once you're assigned to classes</p>
                            <a href="<?= $base_path ?>/index.php" class="btn btn-outline-primary">
                                <i class="fas fa-envelope me-1"></i> Contact Administrator
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- List View -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Class Assignments</h5>
                </div>
                <div class="card-body">
                    <?php if (count($assigned_classes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="classAssignments">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Subject</th>
                                        <th>Class</th>
                                        <th>Time</th>
                                        <th>Room</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assigned_classes as $class): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($class['day_of_week']) ?></td>
                                            <td><?= htmlspecialchars($class['subject_name']) ?></td>
                                            <td><?= htmlspecialchars($class['class_name']) ?></td>
                                            <td><?= $class['time_slot'] ?></td>
                                            <td><?= !empty($class['room_number']) ? htmlspecialchars($class['room_number']) : '-' ?></td>
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
        </div>
    </div>
</div>

<script>
// DataTables initialization
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('classAssignments');
    if (table) {
        // Check if jQuery and DataTables are loaded
        if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable) {
            jQuery(table).DataTable({
                responsive: true,
                order: [[0, 'asc'], [3, 'asc']],
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
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Highlight current day
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const today = new Date().getDay();
    const todayName = days[today];
    
    const timetable = document.getElementById('weeklyTimetable');
    if (timetable) {
        const headers = timetable.querySelectorAll('th');
        headers.forEach(function(header) {
            if (header.textContent.includes(todayName)) {
                header.classList.add('table-info');
            }
        });
    }
});
</script>

<style>
.avatar-xl {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-initials {
    font-size: 1.5rem;
    font-weight: 600;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
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
</style>

<style>
/* Schedule Styles */
#weeklyTimetable {
    font-size: 0.875rem;
}

.schedule-item {
    transition: all 0.2s ease;
}

.schedule-item:hover {
    transform: scale(1.02);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.table th {
    white-space: nowrap;
    vertical-align: middle;
}

/* Print Styles */
@media print {
    body * {
        visibility: hidden;
    }
    #weeklyTimetable, #weeklyTimetable * {
        visibility: visible;
    }
    #weeklyTimetable {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .table {
        page-break-inside: avoid;
    }
    .no-print {
        display: none !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>