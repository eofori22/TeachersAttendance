<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../includes/auth.php';

// Get base path
$base_path = getBasePath();

// Check database connection
if (!isset($conn)) {
    die("Database connection not available");
}

// Verify admin role
if ($_SESSION['role'] !== 'admin') {
    header('Location: ' . $base_path . '/index.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_class'])) {
        // Add new class
        $class_name = trim($_POST['class_name']);
        $class_code = trim($_POST['class_code']);
        $class_rep_id = $_POST['class_rep_id'] ?? null;
        
        if (!empty($class_name) && !empty($class_code)) {
            $stmt = $conn->prepare("INSERT INTO classes (class_name, class_code, class_rep_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $class_name, $class_code, $class_rep_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Class added successfully!";
            } else {
                $_SESSION['error'] = "Error adding class: " . $conn->error;
            }
        } else {
            $_SESSION['error'] = "Class name and code are required!";
        }
    } elseif (isset($_POST['update_class'])) {
        // Update existing class
        $class_id = $_POST['class_id'];
        $class_name = trim($_POST['class_name']);
        $class_code = trim($_POST['class_code']);
        $class_rep_id = $_POST['class_rep_id'] ?? null;
        
        $stmt = $conn->prepare("UPDATE classes SET class_name = ?, class_code = ?, class_rep_id = ? WHERE class_id = ?");
        $stmt->bind_param("ssii", $class_name, $class_code, $class_rep_id, $class_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Class updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating class: " . $conn->error;
        }
    } elseif (isset($_POST['delete_class'])) {
        // Delete class
        $class_id = $_POST['class_id'];
        
        $stmt = $conn->prepare("DELETE FROM classes WHERE class_id = ?");
        $stmt->bind_param("i", $class_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Class deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting class: " . $conn->error;
        }
    }
    
    $base_path = getBasePath();
    header("Location: " . $base_path . "/admin/manage_classes.php");
    exit();
}

// Get all classes with their representatives
$classes = $conn->query("
    SELECT c.*, u.full_name AS rep_name 
    FROM classes c
    LEFT JOIN users u ON c.class_rep_id = u.user_id
    ORDER BY c.class_name
")->fetch_all(MYSQLI_ASSOC);

// Get all potential class representatives (users with class_rep role)
$class_reps = $conn->query("
    SELECT user_id, full_name 
    FROM users 
    WHERE role = 'class_rep'
    ORDER BY full_name
")->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Main Content -->
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Manage Classes</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                    <i class="fas fa-plus me-2"></i> Add New Class
                </button>
            </div>
            
            <!-- Status Messages -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <!-- Classes Table -->
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="classesTable">
                            <thead>
                                <tr>
                                    <th>Class Name</th>
                                    <th>Class Code</th>
                                    <th>Class Representative</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($classes as $class): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($class['class_name']) ?></td>
                                        <td><?= htmlspecialchars($class['class_code']) ?></td>
                                        <td>
                                            <?php if ($class['rep_name']): ?>
                                                <?= htmlspecialchars($class['rep_name']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary edit-class" 
                                                    data-id="<?= $class['class_id'] ?>"
                                                    data-name="<?= htmlspecialchars($class['class_name']) ?>"
                                                    data-code="<?= htmlspecialchars($class['class_code']) ?>"
                                                    data-rep="<?= $class['class_rep_id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger delete-class" 
                                                    data-id="<?= $class['class_id'] ?>"
                                                    data-name="<?= htmlspecialchars($class['class_name']) ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClassModalLabel">Add New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="class_name" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="class_name" name="class_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="class_code" class="form-label">Class Code</label>
                        <input type="text" class="form-control" id="class_code" name="class_code" required>
                    </div>
                    <div class="mb-3">
                        <label for="class_rep_id" class="form-label">Class Representative</label>
                        <select class="form-select" id="class_rep_id" name="class_rep_id">
                            <option value="">-- Select Representative --</option>
                            <?php foreach ($class_reps as $rep): ?>
                                <option value="<?= $rep['user_id'] ?>"><?= htmlspecialchars($rep['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_class" class="btn btn-primary">Add Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Class Modal -->
<div class="modal fade" id="editClassModal" tabindex="-1" aria-labelledby="editClassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="class_id" id="edit_class_id">
                <input type="hidden" name="update_class" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="editClassModalLabel">Edit Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_class_name" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="edit_class_name" name="class_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_class_code" class="form-label">Class Code</label>
                        <input type="text" class="form-control" id="edit_class_code" name="class_code" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_class_rep_id" class="form-label">Class Representative</label>
                        <select class="form-select" id="edit_class_rep_id" name="class_rep_id">
                            <option value="">-- Select Representative --</option>
                            <?php foreach ($class_reps as $rep): ?>
                                <option value="<?= $rep['user_id'] ?>"><?= htmlspecialchars($rep['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteClassModal" tabindex="-1" aria-labelledby="deleteClassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="class_id" id="delete_class_id">
                <input type="hidden" name="delete_class" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteClassModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this class? This action cannot be undone.</p>
                    <p class="fw-bold" id="delete_class_name"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- jQuery (required for DataTables and Bootstrap modals) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#classesTable').DataTable({
        responsive: true,
        columnDefs: [
            { orderable: false, targets: [3] }
        ]
    });
    
    // Edit Class button click
    $('.edit-class').click(function() {
        const classId = $(this).data('id');
        const className = $(this).data('name');
        const classCode = $(this).data('code');
        const classRep = $(this).data('rep');
        
        $('#edit_class_id').val(classId);
        $('#edit_class_name').val(className);
        $('#edit_class_code').val(classCode);
        $('#edit_class_rep_id').val(classRep);
        
        $('#editClassModal').modal('show');
    });
    
    // Delete Class button click
    $('.delete-class').click(function() {
        const classId = $(this).data('id');
        const className = $(this).data('name');
        
        $('#delete_class_id').val(classId);
        $('#delete_class_name').text('Class: ' + className);
        
        $('#deleteClassModal').modal('show');
    });
});
</script>

<style>
/* Custom styles for manage classes page */
.card {
    border-radius: 10px;
}

.table th {
    font-weight: 600;
    background-color: #f8f9fa;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.dataTables_wrapper .dataTables_filter input {
    border-radius: 20px;
    padding: 5px 10px;
    border: 1px solid #dee2e6;
}

.dataTables_wrapper .dataTables_length select {
    border-radius: 20px;
    padding: 5px 10px;
    border: 1px solid #dee2e6;
}

.modal-content {
    border-radius: 10px;
    border: none;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
}

.form-select, .form-control {
    border-radius: 8px;
    padding: 10px 15px;
}

.form-control:focus, .form-select:focus {
    border-color: #0066ff;
    box-shadow: 0 0 0 0.25rem rgba(0, 102, 255, 0.25);
}

.btn-outline-primary:hover, .btn-outline-danger:hover {
    color: white;
}

.btn-outline-primary:hover {
    background-color: #0066ff;
    border-color: #0066ff;
}

.btn-outline-danger:hover {
    background-color: #ef4444;
    border-color: #ef4444;
}
</style>

<?php include '../includes/footer.php'; ?>