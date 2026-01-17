<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../includes/auth.php';

// Get base path
$base_path = getBasePath();

// Auto-set demo mode for testing
if (!isset($_SESSION['user_id']) && !isset($_POST['username'])) {
    $_SESSION['user_id'] = 4;
    $_SESSION['role'] = 'teacher';
    $_SESSION['full_name'] = 'Ebenezer Ofori';
}

// Check if user has teacher role
if ($_SESSION['role'] !== 'teacher') {
    die("<h1>Access Denied</h1><p>You must be logged in as a teacher.</p>");
}

// Get teacher details
$teacher_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, email, profile_image FROM users WHERE user_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    die("Error: Teacher not found");
}

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $absence_date = $_POST['absence_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (empty($absence_date) || empty($reason)) {
        $message = 'Please fill in all required fields.';
        $message_type = 'danger';
    } else {
        // Validate dates
        $today = date('Y-m-d');
        if ($absence_date < $today) {
            $message = 'Absence date cannot be in the past.';
            $message_type = 'danger';
        } elseif (!empty($end_date) && $end_date < $absence_date) {
            $message = 'End date must be after or equal to absence date.';
            $message_type = 'danger';
        } else {
            // Insert permission request
            $stmt = $conn->prepare("
                INSERT INTO teacher_permissions (teacher_id, request_date, absence_date, end_date, reason, status)
                VALUES (?, CURDATE(), ?, ?, ?, 'pending')
            ");
            $stmt->bind_param("isss", $teacher_id, $absence_date, $end_date, $reason);

            if ($stmt->execute()) {
                $message = 'Permission request submitted successfully! It will be reviewed by an administrator.';
                $message_type = 'success';
                $absence_date = '';
                $end_date = '';
                $reason = '';
            } else {
                $message = 'Error submitting request. Please try again.';
                $message_type = 'danger';
            }
        }
    }
}

// Get existing permission requests
$stmt = $conn->prepare("
    SELECT tp.permission_id, tp.absence_date, tp.end_date, tp.reason, tp.status, tp.created_at, tp.admin_notes,
           u.full_name AS reviewed_by_name, tp.reviewed_at
    FROM teacher_permissions tp
    LEFT JOIN users u ON tp.reviewed_by = u.user_id
    WHERE tp.teacher_id = ?
    ORDER BY tp.created_at DESC
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$permissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Permission - Teachers Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/style.css">
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <?php if (!empty($teacher['profile_image'])): ?>
                                <img src="<?= $base_path ?>/uploads/profiles/<?= htmlspecialchars($teacher['profile_image']) ?>"
                                     alt="Profile" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                            <?php else: ?>
                                <div class="avatar-initials bg-primary text-white rounded-circle me-3 d-flex align-items-center justify-content-center"
                                     style="width: 50px; height: 50px; font-size: 1.2rem; font-weight: bold;">
                                    <?= strtoupper(substr($teacher['full_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($teacher['full_name']) ?></h6>
                                <small class="text-muted">Teacher</small>
                            </div>
                        </div>

                        <div class="badge bg-primary bg-opacity-10 text-primary">Active</div>

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
                                <a class="nav-link" href="<?= $base_path ?>/teacher/teacher_schedule.php">
                                    <i class="fas fa-calendar-alt me-2"></i> My Schedule
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="<?= $base_path ?>/teacher/request_permission.php">
                                    <i class="fas fa-user-clock me-2"></i> Request Permission
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
            <div class="col-md-9 col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0">Request Permission</h2>
                        <p class="text-muted">Submit absence requests for administrator approval</p>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Request Form -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-plus-circle me-2"></i>New Permission Request
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="absence_date" class="form-label">
                                            <i class="fas fa-calendar-day me-1"></i>Absence Date <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" class="form-control" id="absence_date" name="absence_date"
                                               min="<?= date('Y-m-d') ?>" required>
                                        <div class="form-text">Select the date you will be absent</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">
                                            <i class="fas fa-calendar-week me-1"></i>End Date (Optional)
                                        </label>
                                        <input type="date" class="form-control" id="end_date" name="end_date"
                                               min="<?= date('Y-m-d') ?>">
                                        <div class="form-text">For multi-day absences, select the end date</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="reason" class="form-label">
                                            <i class="fas fa-comment me-1"></i>Reason for Absence <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="reason" name="reason" rows="4"
                                                  placeholder="Please provide a detailed reason for your absence..."
                                                  maxlength="500" required></textarea>
                                        <div class="form-text">Maximum 500 characters</div>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Request
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Request History -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-history me-2"></i>Request History
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($permissions)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No permission requests yet</p>
                                        <small class="text-muted">Your submitted requests will appear here</small>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($permissions as $permission): ?>
                                            <div class="list-group-item px-0">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center mb-1">
                                                            <span class="badge bg-<?= $permission['status'] === 'approved' ? 'success' : ($permission['status'] === 'rejected' ? 'danger' : 'warning') ?> me-2">
                                                                <?= ucfirst($permission['status']) ?>
                                                            </span>
                                                            <small class="text-muted">
                                                                <?= date('M j, Y', strtotime($permission['absence_date'])) ?>
                                                                <?php if ($permission['end_date'] && $permission['end_date'] !== $permission['absence_date']): ?>
                                                                    - <?= date('M j, Y', strtotime($permission['end_date'])) ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                        <p class="mb-1 small text-truncate" style="max-width: 300px;">
                                                            <?= htmlspecialchars($permission['reason']) ?>
                                                        </p>
                                                        <small class="text-muted">
                                                            Requested: <?= date('M j, Y g:i A', strtotime($permission['created_at'])) ?>
                                                        </small>
                                                        <?php if ($permission['reviewed_by_name']): ?>
                                                            <br><small class="text-muted">
                                                                Reviewed by <?= htmlspecialchars($permission['reviewed_by_name']) ?> on <?= date('M j, Y', strtotime($permission['reviewed_at'])) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                        <?php if ($permission['admin_notes']): ?>
                                                            <div class="mt-2 p-2 bg-light rounded small">
                                                                <strong>Admin Note:</strong> <?= htmlspecialchars($permission['admin_notes']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Update end date min when absence date changes
        document.getElementById('absence_date').addEventListener('change', function() {
            document.getElementById('end_date').min = this.value;
        });
    </script>
</body>
</html>