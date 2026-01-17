<?php
include '../includes/auth.php';

// Get base path
$base_path = getBasePath();

// Get admin details
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, email, profile_image FROM users WHERE user_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Handle approval/rejection
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $permission_id = $_POST['permission_id'] ?? 0;
    $action = $_POST['action'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    $admin_id = $_SESSION['user_id'];

    if ($action === 'approve' || $action === 'reject') {
        $status = $action === 'approve' ? 'approved' : 'rejected';

        $stmt = $conn->prepare("
            UPDATE teacher_permissions
            SET status = ?, admin_notes = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE permission_id = ?
        ");
        $stmt->bind_param("ssii", $status, $admin_notes, $admin_id, $permission_id);

        if ($stmt->execute()) {
            $message = "Permission request " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully.";
            $message_type = 'success';
        } else {
            $message = "Error updating permission request.";
            $message_type = 'danger';
        }
    }
}

// Get all permission requests with teacher details
$stmt = $conn->prepare("
    SELECT tp.*, u.full_name, u.email, u.profile_image
    FROM teacher_permissions tp
    JOIN users u ON tp.teacher_id = u.user_id
    ORDER BY tp.created_at DESC
");
$stmt->execute();
$permissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats = [
    'total' => count($permissions),
    'pending' => count(array_filter($permissions, fn($p) => $p['status'] === 'pending')),
    'approved' => count(array_filter($permissions, fn($p) => $p['status'] === 'approved')),
    'rejected' => count(array_filter($permissions, fn($p) => $p['status'] === 'rejected'))
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Permissions - Teachers Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <?php if (!empty($admin['profile_image'])): ?>
                                <img src="<?= $base_path ?>/uploads/profiles/<?= htmlspecialchars($admin['profile_image']) ?>"
                                     alt="Profile" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                            <?php else: ?>
                                <div class="avatar-initials bg-danger text-white rounded-circle me-3 d-flex align-items-center justify-content-center"
                                     style="width: 50px; height: 50px; font-size: 1.2rem; font-weight: bold;">
                                    A
                                </div>
                            <?php endif; ?>
                            <div>
                                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($admin['full_name']) ?></h6>
                                <small class="text-muted">Administrator</small>
                            </div>
                        </div>

                        <div class="badge bg-danger bg-opacity-10 text-danger">Admin</div>

                        <hr class="my-3">

                        <ul class="nav nav-pills flex-column gap-2">
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $base_path ?>/admin/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $base_path ?>/admin/manage_teachers.php">
                                    <i class="fas fa-users me-2"></i> Manage Teachers
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $base_path ?>/admin/manage_classes.php">
                                    <i class="fas fa-school me-2"></i> Manage Classes
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $base_path ?>/admin/manage_class_reps.php">
                                    <i class="fas fa-user-graduate me-2"></i> Class Representatives
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="<?= $base_path ?>/admin/manage_permissions.php">
                                    <i class="fas fa-user-clock me-2"></i> Permission Requests
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $base_path ?>/admin/profile.php">
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
                        <h2 class="mb-0">Permission Requests</h2>
                        <p class="text-muted">Review and manage teacher absence requests</p>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Requests</h6>
                                        <h3 class="mb-0 text-primary"><?= $stats['total'] ?></h3>
                                    </div>
                                    <div class="icon-circle bg-primary bg-opacity-10 text-primary">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Pending</h6>
                                        <h3 class="mb-0 text-warning"><?= $stats['pending'] ?></h3>
                                    </div>
                                    <div class="icon-circle bg-warning bg-opacity-10 text-warning">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Approved</h6>
                                        <h3 class="mb-0 text-success"><?= $stats['approved'] ?></h3>
                                    </div>
                                    <div class="icon-circle bg-success bg-opacity-10 text-success">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Rejected</h6>
                                        <h3 class="mb-0 text-danger"><?= $stats['rejected'] ?></h3>
                                    </div>
                                    <div class="icon-circle bg-danger bg-opacity-10 text-danger">
                                        <i class="fas fa-times"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permission Requests Table -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>All Permission Requests
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($permissions)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No permission requests</h5>
                                <p class="text-muted">Teacher permission requests will appear here for review</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Teacher</th>
                                            <th>Absence Date</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Requested</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($permissions as $permission): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($permission['profile_image'])): ?>
                                                            <img src="<?= $base_path ?>/uploads/profiles/<?= htmlspecialchars($permission['profile_image']) ?>"
                                                                 alt="Profile" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="avatar-initials bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center"
                                                                 style="width: 32px; height: 32px; font-size: 0.8rem; font-weight: bold;">
                                                                <?= strtoupper(substr($permission['full_name'], 0, 1)) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($permission['full_name']) ?></div>
                                                            <small class="text-muted"><?= htmlspecialchars($permission['email']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?= date('M j, Y', strtotime($permission['absence_date'])) ?>
                                                    <?php if ($permission['end_date'] && $permission['end_date'] !== $permission['absence_date']): ?>
                                                        <br><small class="text-muted">to <?= date('M j, Y', strtotime($permission['end_date'])) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div style="max-width: 200px;">
                                                        <?= htmlspecialchars(substr($permission['reason'], 0, 100)) ?>
                                                        <?php if (strlen($permission['reason']) > 100): ?>
                                                            ...
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $permission['status'] === 'approved' ? 'success' : ($permission['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                                        <?= ucfirst($permission['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= date('M j, Y', strtotime($permission['created_at'])) ?>
                                                    <br><small class="text-muted"><?= date('g:i A', strtotime($permission['created_at'])) ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($permission['status'] === 'pending'): ?>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-success" 
                                                                    onclick="reviewPermission(<?= $permission['permission_id'] ?>, 'approve'); return false;">
                                                                <i class="fas fa-check"></i> Approve
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger"
                                                                    onclick="reviewPermission(<?= $permission['permission_id'] ?>, 'reject'); return false;">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted small">
                                                            Reviewed <?= date('M j, Y', strtotime($permission['reviewed_at'])) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reviewModalTitle">Review Permission Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="permission_id" id="modalPermissionId">
                        <input type="hidden" name="action" id="modalAction">

                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Admin Notes (Optional)</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3"
                                      placeholder="Add any notes for the teacher..."></textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Action:</strong> <span id="actionText"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn" id="confirmBtn">Confirm</button>
                    </div>
                </form>
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

        function reviewPermission(permissionId, action) {
            console.log('reviewPermission called with:', permissionId, action);
            
            document.getElementById('modalPermissionId').value = permissionId;
            document.getElementById('modalAction').value = action;

            const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
            const confirmBtn = document.getElementById('confirmBtn');
            const actionText = document.getElementById('actionText');

            if (action === 'approve') {
                confirmBtn.className = 'btn btn-success';
                confirmBtn.innerHTML = '<i class="fas fa-check me-2"></i>Approve Request';
                actionText.textContent = 'Approve this permission request';
                document.getElementById('reviewModalTitle').textContent = 'Approve Permission Request';
            } else {
                confirmBtn.className = 'btn btn-danger';
                confirmBtn.innerHTML = '<i class="fas fa-times me-2"></i>Reject Request';
                actionText.textContent = 'Reject this permission request';
                document.getElementById('reviewModalTitle').textContent = 'Reject Permission Request';
            }

            modal.show();
            console.log('Modal shown');
        }
    </script>
</body>
</html>