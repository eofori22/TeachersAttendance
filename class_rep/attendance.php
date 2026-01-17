<?php 
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
$attendance_records = [];
$monthly_stats = ['total_scans' => 0, 'total_duration' => '00:00:00'];
$error_message = '';

if (!$user || !isset($user['class_id']) || empty($user['class_id'])) {
    $error_message = "You are not assigned as a class representative or your class is not properly configured. Please contact your administrator.";
} else {
    // Handle month/year filter
    $current_month = date('m');
    $current_year = date('Y');
    $selected_month = $_GET['month'] ?? $current_month;
    $selected_year = $_GET['year'] ?? $current_year;
    
    // Validate month/year inputs
    if (!is_numeric($selected_month) || $selected_month < 1 || $selected_month > 12) {
        $selected_month = $current_month;
    }
    if (!is_numeric($selected_year) || strlen($selected_year) != 4) {
        $selected_year = $current_year;
    }
    
    // Get attendance records scanned by this class rep for selected month/year
    $stmt = $conn->prepare("
        SELECT 
            a.date,
            TIME_FORMAT(a.time_in, '%h:%i %p') as time_in,
            TIME_FORMAT(a.time_out, '%h:%i %p') as time_out,
            TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) as duration_minutes,
            u.full_name as teacher_name,
            s.subject_name
        FROM attendance a
        JOIN users u ON a.teacher_id = u.user_id
        LEFT JOIN teacher_assignments ta ON a.teacher_id = ta.teacher_id AND a.class_id = ta.class_id
        LEFT JOIN classes c ON a.class_id = c.class_id
        LEFT JOIN subjects s ON ta.subject_id = s.subject_id
        WHERE a.class_id = ? 
        AND a.scanned_by = ?
        AND MONTH(a.date) = ?
        AND YEAR(a.date) = ?
        ORDER BY a.date DESC, a.time_in DESC
    ");
    $stmt->bind_param("iiii", $user['class_id'], $user_id, $selected_month, $selected_year);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendance_records = $result->fetch_all(MYSQLI_ASSOC);
    
    // Calculate monthly stats
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_scans,
            SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, time_in, IFNULL(time_out, NOW())))) as total_duration
        FROM attendance
        WHERE class_id = ? 
        AND scanned_by = ?
        AND MONTH(date) = ?
        AND YEAR(date) = ?
    ");
    $stmt->bind_param("iiii", $user['class_id'], $user_id, $selected_month, $selected_year);
    $stmt->execute();
    $stats_result = $stmt->get_result();
    $monthly_stats = $stats_result->fetch_assoc() ?: $monthly_stats;
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
                            <a class="nav-link active" href="<?= $base_path ?>/class_rep/attendance.php">
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
                <h2 class="mb-0">Attendance Records</h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="export-btn">
                            <i class="fas fa-download me-1"></i> Export
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if (empty($error_message)): ?>
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
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Monthly Statistics -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="text-uppercase text-muted mb-2">Total Scans</h6>
                                        <h3 class="mb-0"><?= $monthly_stats['total_scans'] ?? 0 ?></h3>
                                    </div>
                                    <div class="icon-circle bg-primary bg-opacity-10 text-primary">
                                        <i class="fas fa-qrcode"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-muted small">
                                        <?= date('F Y', mktime(0, 0, 0, $selected_month, 1, $selected_year)) ?>
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
                                        <h6 class="text-uppercase text-muted mb-2">Total Duration</h6>
                                        <h3 class="mb-0">
                                            <?php
                                            $duration = $monthly_stats['total_duration'] ?? '00:00:00';
                                            $parts = explode(':', $duration);
                                            if (count($parts) >= 2) {
                                                echo $parts[0] . 'h ' . $parts[1] . 'm';
                                            } else {
                                                echo '0h 0m';
                                            }
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="icon-circle bg-success bg-opacity-10 text-success">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-muted small">This month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="text-uppercase text-muted mb-2">Records</h6>
                                        <h3 class="mb-0"><?= count($attendance_records) ?></h3>
                                    </div>
                                    <div class="icon-circle bg-info bg-opacity-10 text-info">
                                        <i class="fas fa-list"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-muted small">Attendance entries</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="text-uppercase text-muted mb-2">Class</h6>
                                        <h3 class="mb-0 small"><?= htmlspecialchars($user['class_name'] ?? 'N/A') ?></h3>
                                    </div>
                                    <div class="icon-circle bg-warning bg-opacity-10 text-warning">
                                        <i class="fas fa-chalkboard"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-muted small">Your assigned class</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance Records Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">Attendance Records for <?= date('F Y', mktime(0, 0, 0, $selected_month, 1, $selected_year)) ?></h5>
                    </div>
                    <div class="card-body pt-0">
                        <?php if (count($attendance_records) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="attendanceTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Teacher</th>
                                            <th>Subject</th>
                                            <th>Time In</th>
                                            <th>Time Out</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendance_records as $record): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= date('M d, Y', strtotime($record['date'])) ?></strong><br>
                                                    <small class="text-muted"><?= date('l', strtotime($record['date'])) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($record['teacher_name']) ?></td>
                                                <td><?= htmlspecialchars($record['subject_name'] ?? ($record['class_name'] ?? 'N/A')) ?></td>
                                                <td><?= $record['time_in'] ?></td>
                                                <td>
                                                    <?php if ($record['time_out']): ?>
                                                        <?= $record['time_out'] ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-info">In Progress</span>
                                                    <?php endif; ?>
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
                                <h5>No attendance records found</h5>
                                <p class="text-muted">No attendance records were scanned for the selected month.</p>
                                <a href="<?= $base_path ?>/class_rep/scan.php" class="btn btn-outline-primary">
                                    <i class="fas fa-camera me-2"></i> Start Scanning
                                </a>
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
    const table = document.getElementById('attendanceTable');
    if (table) {
        // Check if jQuery and DataTables are loaded
        if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable) {
            jQuery(table).DataTable({
                order: [[0, 'desc']],
                pageLength: 25,
                language: {
                    search: "Search records:",
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
    
    // Export button functionality
    const exportBtn = document.getElementById('export-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            const month = document.getElementById('month')?.value || '<?= $selected_month ?>';
            const year = document.getElementById('year')?.value || '<?= $selected_year ?>';
            const basePath = '<?= $base_path ?>';
            
            // Create dropdown menu for export options
            const existingDropdown = document.querySelector('.export-dropdown');
            if (existingDropdown) {
                existingDropdown.remove();
                return;
            }
            
            const dropdown = document.createElement('div');
            dropdown.className = 'export-dropdown position-absolute bg-white border rounded shadow-sm';
            dropdown.style.cssText = 'top: 100%; right: 0; z-index: 1000; min-width: 150px;';
            dropdown.innerHTML = `
                <a href="${basePath}/api/export_attendance.php?format=csv&month=${month}&year=${year}" class="dropdown-item px-3 py-2 text-decoration-none d-block">
                    <i class="fas fa-file-csv me-2 text-success"></i>Export CSV
                </a>
                <a href="${basePath}/api/export_attendance.php?format=pdf&month=${month}&year=${year}" class="dropdown-item px-3 py-2 text-decoration-none d-block" target="_blank">
                    <i class="fas fa-file-pdf me-2 text-danger"></i>Export PDF
                </a>
            `;
            
            this.parentElement.style.position = 'relative';
            this.parentElement.appendChild(dropdown);
            
            // Close dropdown when clicking outside
            setTimeout(() => {
                document.addEventListener('click', function closeDropdown(e) {
                    if (!dropdown.contains(e.target) && e.target !== exportBtn) {
                        dropdown.remove();
                        document.removeEventListener('click', closeDropdown);
                    }
                });
            }, 100);
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
