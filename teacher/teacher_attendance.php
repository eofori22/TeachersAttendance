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
$current_month = date('m');
$current_year = date('Y');

// Handle month/year filter
$selected_month = $_GET['month'] ?? $current_month;
$selected_year = $_GET['year'] ?? $current_year;

// Validate month/year inputs
if (!is_numeric($selected_month) || $selected_month < 1 || $selected_month > 12) {
    $selected_month = $current_month;
}
if (!is_numeric($selected_year) || strlen($selected_year) != 4) {
    $selected_year = $current_year;
}

// Get teacher's attendance records for selected month/year
$stmt = $conn->prepare("
    SELECT 
        a.date,
        TIME_FORMAT(a.time_in, '%h:%i %p') as time_in,
        TIME_FORMAT(a.time_out, '%h:%i %p') as time_out,
        TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) as duration_minutes,
        c.class_name,
        u.full_name as scanned_by_name
    FROM attendance a
    JOIN classes c ON a.class_id = c.class_id
    JOIN users u ON a.scanned_by = u.user_id
    WHERE a.teacher_id = ?
    AND MONTH(a.date) = ?
    AND YEAR(a.date) = ?
    ORDER BY a.date DESC, a.time_in DESC
");
$stmt->bind_param("iii", $teacher_id, $selected_month, $selected_year);
$stmt->execute();
$result = $stmt->get_result();
$attendance_records = $result->fetch_all(MYSQLI_ASSOC);

// Calculate monthly stats
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_classes,
        SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, time_in, IFNULL(time_out, NOW())))) as total_duration
    FROM attendance
    WHERE teacher_id = ?
    AND MONTH(date) = ?
    AND YEAR(date) = ?
");
$stmt->bind_param("iii", $teacher_id, $selected_month, $selected_year);
$stmt->execute();
$stats_result = $stmt->get_result();
$monthly_stats = $stats_result->fetch_assoc();
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
                            <a class="nav-link active" href="<?= $base_path ?>/teacher/teacher_attendance.php">
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
                <h2 class="mb-0">My Attendance</h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="export-btn">
                            <i class="fas fa-download me-1"></i> Export
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Month/Year Filter -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="month" class="form-label">Month</label>
                            <select class="form-select" id="month" name="month">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $m == $selected_month ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="year" class="form-label">Year</label>
                            <select class="form-select" id="year" name="year">
                                <?php for ($y = $current_year - 2; $y <= $current_year + 1; $y++): ?>
                                    <option value="<?= $y ?>" <?= $y == $selected_year ? 'selected' : '' ?>>
                                        <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary h-100">
                        <div class="card-body">
                            <h5 class="card-title">Classes Taught</h5>
                            <h2 class="card-text"><?= $monthly_stats['total_classes'] ?? 0 ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success h-100">
                        <div class="card-body">
                            <h5 class="card-title">Total Teaching Time</h5>
                            <h2 class="card-text">
                                <?php 
                                if (!empty($monthly_stats['total_duration'])) {
                                    list($h, $m, $s) = explode(':', $monthly_stats['total_duration']);
                                    echo "$h hours $m minutes";
                                } else {
                                    echo "0 hours";
                                }
                                ?>
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info h-100">
                        <div class="card-body">
                            <h5 class="card-title">Average per Class</h5>
                            <h2 class="card-text">
                                <?php 
                                if (!empty($monthly_stats['total_duration']) && $monthly_stats['total_classes'] > 0) {
                                    list($h, $m, $s) = explode(':', $monthly_stats['total_duration']);
                                    $total_min = ($h * 60) + $m;
                                    $avg_min = round($total_min / $monthly_stats['total_classes']);
                                    echo floor($avg_min / 60) . "h " . ($avg_min % 60) . "m";
                                } else {
                                    echo "0h 0m";
                                }
                                ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Attendance Records -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Attendance Records for <?= date('F Y', mktime(0, 0, 0, $selected_month, 1, $selected_year)) ?></h5>
                </div>
                <div class="card-body">
                    <?php if (count($attendance_records) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="attendanceTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Class</th>
                                        <th>Time In</th>
                                        <th>Time Out</th>
                                        <th>Duration</th>
                                        <th>Recorded By</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance_records as $record): ?>
                                        <tr>
                                            <td><?= date('D, M j', strtotime($record['date'])) ?></td>
                                            <td><?= htmlspecialchars($record['class_name']) ?></td>
                                            <td><?= $record['time_in'] ?></td>
                                            <td><?= $record['time_out'] ?? '-' ?></td>
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
                                            <td>
                                                <?php if ($record['time_out']): ?>
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
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h4>No attendance records found</h4>
                            <p class="text-muted">Your attendance records will appear here once class reps scan your QR code</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

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

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable with export buttons
    $('#attendanceTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csv',
                text: '<i class="fas fa-file-csv me-1"></i> CSV',
                className: 'btn btn-sm btn-outline-secondary',
                title: 'Teacher_Attendance_<?= date('F_Y', mktime(0, 0, 0, $selected_month, 1, $selected_year)) ?>',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            },
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel me-1"></i> Excel',
                className: 'btn btn-sm btn-outline-secondary',
                title: 'Teacher_Attendance_<?= date('F_Y', mktime(0, 0, 0, $selected_month, 1, $selected_year)) ?>',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            }
        ],
        order: [[0, 'desc']],
        responsive: true
    });
    
    // Standalone export button
    $('#export-btn').click(function() {
        $('.buttons-csv').click();
    });
});
</script>

<style>
.card {
    border-radius: 10px;
}

.table th {
    font-weight: 600;
    background-color: #f8f9fa;
}

.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
}

.dt-buttons .btn {
    margin-right: 5px;
}

@media (max-width: 768px) {
    .table-responsive {
        border: none;
    }
}
</style>

<?php include '../includes/footer.php'; ?>