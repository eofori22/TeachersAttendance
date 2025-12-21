
<?php
include '../includes/auth.php';

// Get base path
$base_path = getBasePath();

// Verify admin role
if ($_SESSION['role'] !== 'admin') {
    header('Location: ' . $base_path . '/index.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_class_rep'])) {
        // Add new class rep
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $class_id = $_POST['class_id'] ?? null;
        
        if (!empty($full_name) && !empty($email) && !empty($username) && !empty($_POST['password'])) {
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, username, password, role) VALUES (?, ?, ?, ?, 'class_rep')");
            $stmt->bind_param("ssss", $full_name, $email, $username, $password);
            
            if ($stmt->execute()) {
                $new_user_id = $stmt->insert_id;
                
                // Assign to class if selected
                if ($class_id && $class_id != '') {
                    $update_stmt = $conn->prepare("UPDATE classes SET class_rep_id = ? WHERE class_id = ?");
                    $update_stmt->bind_param("ii", $new_user_id, $class_id);
                    $update_stmt->execute();
                }
                
                $_SESSION['success'] = "Class representative added successfully!";
            } else {
                $_SESSION['error'] = "Error adding class representative: " . $conn->error;
            }
        } else {
            $_SESSION['error'] = "All fields are required!";
        }
    } elseif (isset($_POST['update_class_rep'])) {
        // Update existing class rep
        $user_id = $_POST['user_id'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $class_id = $_POST['class_id'] ?? null;
        
        // Initialize the query and parameters
        $query = "UPDATE users SET full_name = ?, email = ?, username = ?";
        $types = "sss";
        $params = [$full_name, $email, $username];
        
        // Add password update if provided
        if (!empty($_POST['password'])) {
            $query .= ", password = ?";
            $types .= "s";
            $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        $query .= " WHERE user_id = ?";
        $types .= "i";
        $params[] = $user_id;
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            // Update class assignment
            // First, remove from all classes
            $remove_stmt = $conn->prepare("UPDATE classes SET class_rep_id = NULL WHERE class_rep_id = ?");
            $remove_stmt->bind_param("i", $user_id);
            $remove_stmt->execute();
            
            // Then assign to new class if selected
            if ($class_id && $class_id != '') {
                $assign_stmt = $conn->prepare("UPDATE classes SET class_rep_id = ? WHERE class_id = ?");
                $assign_stmt->bind_param("ii", $user_id, $class_id);
                $assign_stmt->execute();
            }
            
            $_SESSION['success'] = "Class representative updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating class representative: " . $conn->error;
        }
    } elseif (isset($_POST['delete_class_rep'])) {
        // Delete class rep
        $user_id = $_POST['user_id'];
        
        // First remove from classes
        $remove_stmt = $conn->prepare("UPDATE classes SET class_rep_id = NULL WHERE class_rep_id = ?");
        $remove_stmt->bind_param("i", $user_id);
        $remove_stmt->execute();
        
        // Then delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'class_rep'");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Class representative deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting class representative: " . $conn->error;
        }
    }
    
    header("Location: " . $base_path . "/admin/manage_class_reps.php");
    exit();
}

// Get all class reps with their assigned classes
$class_reps = $conn->query("
    SELECT u.user_id, u.full_name, u.email, u.username, u.profile_image, c.class_id, c.class_name
    FROM users u
    LEFT JOIN classes c ON u.user_id = c.class_rep_id
    WHERE u.role = 'class_rep'
    ORDER BY u.full_name
")->fetch_all(MYSQLI_ASSOC);

// Get all classes for dropdown
$classes = $conn->query("
    SELECT class_id, class_name, class_code 
    FROM classes 
    ORDER BY class_name
")->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Main Content -->
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Manage Class Representatives</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassRepModal">
                    <i class="fas fa-plus me-2"></i> Add New Class Rep
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
            
            <!-- Class Reps Table -->
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="classRepsTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Username</th>
                                    <th>Assigned Class</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($class_reps as $rep): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($rep['full_name']) ?>
                                            <?php if (!empty($rep['profile_image'])): ?>
                                                <img src="<?= $base_path ?>/uploads/profiles/<?= htmlspecialchars($rep['profile_image']) ?>"
                                                     alt="Profile"
                                                     class="rounded-circle ms-2"
                                                     style="width: 32px; height: 32px; object-fit: cover;">
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($rep['email']) ?></td>
                                        <td><?= htmlspecialchars($rep['username']) ?></td>
                                        <td>
                                            <?php if ($rep['class_name']): ?>
                                                <span class="badge bg-primary"><?= htmlspecialchars($rep['class_name']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Not Assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary edit-class-rep" 
                                                    data-id="<?= $rep['user_id'] ?>"
                                                    data-name="<?= htmlspecialchars($rep['full_name']) ?>"
                                                    data-email="<?= htmlspecialchars($rep['email']) ?>"
                                                    data-username="<?= htmlspecialchars($rep['username']) ?>"
                                                    data-class-id="<?= $rep['class_id'] ?? '' ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger delete-class-rep" 
                                                    data-id="<?= $rep['user_id'] ?>"
                                                    data-name="<?= htmlspecialchars($rep['full_name']) ?>">
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

<!-- Add Class Rep Modal -->
<div class="modal fade" id="addClassRepModal" tabindex="-1" aria-labelledby="addClassRepModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClassRepModalLabel">Add New Class Representative</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="class_id" class="form-label">Assign to Class (Optional)</label>
                        <select class="form-select" id="class_id" name="class_id">
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['class_id'] ?>"><?= htmlspecialchars($class['class_name'] . ' (' . $class['class_code'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_class_rep" class="btn btn-primary">Add Class Rep</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Class Rep Modal -->
<div class="modal fade" id="editClassRepModal" tabindex="-1" aria-labelledby="editClassRepModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editClassRepModalLabel">Edit Class Representative</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">New Password (Leave blank to keep current)</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                    </div>
                    <div class="mb-3">
                        <label for="edit_class_id" class="form-label">Assign to Class</label>
                        <select class="form-select" id="edit_class_id" name="class_id">
                            <option value="">-- Not Assigned --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['class_id'] ?>"><?= htmlspecialchars($class['class_name'] . ' (' . $class['class_code'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_class_rep" class="btn btn-primary">Update Class Rep</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteClassRepModal" tabindex="-1" aria-labelledby="deleteClassRepModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="user_id" id="delete_user_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteClassRepModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="delete_name"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_class_rep" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable) {
        jQuery('#classRepsTable').DataTable({
            order: [[0, 'asc']],
            pageLength: 25
        });
    }
    
    // Edit button handlers
    document.querySelectorAll('.edit-class-rep').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_user_id').value = this.dataset.id;
            document.getElementById('edit_full_name').value = this.dataset.name;
            document.getElementById('edit_email').value = this.dataset.email;
            document.getElementById('edit_username').value = this.dataset.username;
            document.getElementById('edit_class_id').value = this.dataset.classId || '';
            
            const editModal = new bootstrap.Modal(document.getElementById('editClassRepModal'));
            editModal.show();
        });
    });
    
    // Delete button handlers
    document.querySelectorAll('.delete-class-rep').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('delete_user_id').value = this.dataset.id;
            document.getElementById('delete_name').textContent = this.dataset.name;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteClassRepModal'));
            deleteModal.show();
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
ENDOFFILE
