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
    if (isset($_POST['add_teacher'])) {
        // Add new teacher
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        if (!empty($full_name) && !empty($email) && !empty($username) && !empty($_POST['password'])) {
            // Check if username already exists
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $_SESSION['error'] = "Username already exists. Please choose a different username.";
            } else {
                // Generate QR code
                $qr_data = "TCH-" . uniqid();
                $qr_code = md5($qr_data);
                
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, username, password, role, qr_code) VALUES (?, ?, ?, ?, 'teacher', ?)");
                $stmt->bind_param("sssss", $full_name, $email, $username, $password, $qr_code);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Teacher added successfully!";
                } else {
                    $_SESSION['error'] = "Error adding teacher: " . $conn->error;
                }
            }
        } else {
            $_SESSION['error'] = "All fields are required!";
        }
    } elseif (isset($_POST['update_teacher'])) {
        // Update existing teacher
        $user_id = $_POST['user_id'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        
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
            $_SESSION['success'] = "Teacher updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating teacher: " . $conn->error;
        }
    } elseif (isset($_POST['delete_teacher'])) {
        // Delete teacher
        $user_id = $_POST['user_id'];
        
        // First check if teacher is assigned to any classes
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM teacher_assignments WHERE teacher_id = ?");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $count = $result->fetch_row()[0];
        
        if ($count > 0) {
            $_SESSION['error'] = "Cannot delete teacher assigned to classes. Please reassign classes first.";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'teacher'");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Teacher deleted successfully!";
            } else {
                $_SESSION['error'] = "Error deleting teacher: " . $conn->error;
            }
        }
    } elseif (isset($_POST['add_class_assignment'])) {
        // Add class assignment to teacher
        $teacher_id = $_POST['teacher_id'];
        $class_id = $_POST['class_id'];
        $subject_id = $_POST['subject_id'];
        $schedule = $_POST['schedule'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $room_number = trim($_POST['room_number'] ?? '');
        
        if (!empty($teacher_id) && !empty($class_id) && !empty($subject_id) && !empty($schedule) && !empty($start_time) && !empty($end_time)) {
            $stmt = $conn->prepare("INSERT INTO teacher_assignments (teacher_id, class_id, subject_id, schedule, start_time, end_time, room_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiissss", $teacher_id, $class_id, $subject_id, $schedule, $start_time, $end_time, $room_number);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Class assignment added successfully!";
            } else {
                $_SESSION['error'] = "Error adding class assignment: " . $conn->error;
            }
        } else {
            $_SESSION['error'] = "All required fields must be filled!";
        }
    } elseif (isset($_POST['delete_assignment'])) {
        // Delete class assignment
        $assignment_id = $_POST['assignment_id'];
        
        $stmt = $conn->prepare("DELETE FROM teacher_assignments WHERE assignment_id = ?");
        $stmt->bind_param("i", $assignment_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Class assignment removed successfully!";
        } else {
            $_SESSION['error'] = "Error removing class assignment: " . $conn->error;
        }
    }
    
    $base_path = getBasePath();
    header("Location: " . $base_path . "/admin/manage_teachers.php");
    exit();
}

// Get all teachers with their class assignments
$teachers = $conn->query("
    SELECT u.user_id, u.full_name, u.email, u.username, u.qr_code, u.profile_image,
           COUNT(ta.assignment_id) AS class_count
    FROM users u
    LEFT JOIN teacher_assignments ta ON u.user_id = ta.teacher_id
    WHERE u.role = 'teacher'
    GROUP BY u.user_id
    ORDER BY u.full_name
")->fetch_all(MYSQLI_ASSOC);

// Get all classes for dropdown
$classes = $conn->query("
    SELECT class_id, class_name, class_code 
    FROM classes 
    ORDER BY class_name
")->fetch_all(MYSQLI_ASSOC);

// Get all subjects for dropdown
// If subjects table is empty or not configured, fall back to using classes
$subjects = [];
$subjects_result = $conn->query("
    SELECT subject_id, subject_name 
    FROM subjects 
    ORDER BY subject_name
");

if ($subjects_result) {
    $subjects = $subjects_result->fetch_all(MYSQLI_ASSOC);
}

// Fallback: if no subjects are defined, use classes as subject options
if (empty($subjects) && !empty($classes)) {
    $subjects = array_map(function($class) {
        return [
            'subject_id' => $class['class_id'],
            'subject_name' => $class['class_name']
        ];
    }, $classes);
}

// Days of the week
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Main Content -->
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Manage Teachers</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                    <i class="fas fa-plus me-2"></i> Add New Teacher
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
            
            <!-- Teachers Table -->
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="teachersTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Username</th>
                                    <th>Classes</th>
                                    <th>QR Code</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teachers as $teacher): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($teacher['full_name']) ?>
                                            <?php if (!empty($teacher['profile_image'])): ?>
                                                <img src="<?= $base_path ?>/uploads/profiles/<?= htmlspecialchars($teacher['profile_image']) ?>"
                                                     alt="Profile"
                                                     class="rounded-circle ms-2"
                                                     style="width: 32px; height: 32px; object-fit: cover;">
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($teacher['email']) ?></td>
                                        <td><?= htmlspecialchars($teacher['username']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $teacher['class_count'] > 0 ? 'primary' : 'secondary' ?>">
                                                <?= $teacher['class_count'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($teacher['qr_code']): ?>
                                                <span class="badge bg-success">Generated</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary edit-teacher" 
                                                    data-id="<?= $teacher['user_id'] ?>"
                                                    data-name="<?= htmlspecialchars($teacher['full_name']) ?>"
                                                    data-email="<?= htmlspecialchars($teacher['email']) ?>"
                                                    data-username="<?= htmlspecialchars($teacher['username']) ?>"
                                                    title="Edit Teacher">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success assign-class" 
                                                    data-id="<?= $teacher['user_id'] ?>"
                                                    data-name="<?= htmlspecialchars($teacher['full_name']) ?>"
                                                    title="Assign Classes">
                                                <i class="fas fa-chalkboard-teacher"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger delete-teacher" 
                                                    data-id="<?= $teacher['user_id'] ?>"
                                                    data-name="<?= htmlspecialchars($teacher['full_name']) ?>"
                                                    title="Delete Teacher">
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

<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTeacherModalLabel">Add New Teacher</h5>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_teacher" class="btn btn-primary">Add Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Teacher Modal -->
<div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="user_id" id="edit_user_id">
                <input type="hidden" name="update_teacher" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTeacherModalLabel">Edit Teacher</h5>
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
                        <label for="edit_password" class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Classes Modal -->
<div class="modal fade" id="assignClassModal" tabindex="-1" aria-labelledby="assignClassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignClassModalLabel">Assign Classes to <span id="assign_teacher_name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add New Assignment Form -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-plus me-2"></i>Add New Class Assignment</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="addAssignmentForm">
                            <input type="hidden" name="teacher_id" id="assign_teacher_id">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="class_id" class="form-label">Class <span class="text-danger">*</span></label>
                                    <select class="form-select" id="class_id" name="class_id" required>
                                        <option value="">-- Select Class --</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?= $class['class_id'] ?>"><?= htmlspecialchars($class['class_name'] . ' (' . $class['class_code'] . ')') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                                    <select class="form-select" id="subject_id" name="subject_id" required>
                                        <option value="">-- Select Subject --</option>
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?= $subject['subject_id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="schedule" class="form-label">Day of Week <span class="text-danger">*</span></label>
                                    <select class="form-select" id="schedule" name="schedule" required>
                                        <option value="">-- Select Day --</option>
                                        <?php foreach ($days_of_week as $day): ?>
                                            <option value="<?= $day ?>"><?= $day ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="room_number" class="form-label">Room Number</label>
                                    <input type="text" class="form-control" id="room_number" name="room_number" placeholder="Optional">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="add_class_assignment" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add Assignment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Current Assignments -->
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="fas fa-list me-2"></i>Current Class Assignments</h6>
                    </div>
                    <div class="card-body">
                        <div id="assignments_list">
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteTeacherModal" tabindex="-1" aria-labelledby="deleteTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="user_id" id="delete_user_id">
                <input type="hidden" name="delete_teacher" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteTeacherModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this teacher? This action cannot be undone.</p>
                    <p class="fw-bold" id="delete_teacher_name"></p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This will permanently remove all attendance records for this teacher.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Teacher</button>
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
    $('#teachersTable').DataTable({
        responsive: true,
        columnDefs: [
            { orderable: false, targets: [3, 4, 5] }
        ],
        order: [[0, 'asc']]
    });
    
    // Edit Teacher button click
    $('.edit-teacher').click(function() {
        const userId = $(this).data('id');
        const fullName = $(this).data('name');
        const email = $(this).data('email');
        const username = $(this).data('username');
        
        $('#edit_user_id').val(userId);
        $('#edit_full_name').val(fullName);
        $('#edit_email').val(email);
        $('#edit_username').val(username);
        
        $('#editTeacherModal').modal('show');
    });
    
    // Delete Teacher button click
    $('.delete-teacher').click(function() {
        const userId = $(this).data('id');
        const fullName = $(this).data('name');
        
        $('#delete_user_id').val(userId);
        $('#delete_teacher_name').text(fullName);
        
        $('#deleteTeacherModal').modal('show');
    });
    
    // Assign Classes button click
    $('.assign-class').click(function() {
        const teacherId = $(this).data('id');
        const teacherName = $(this).data('name');
        
        $('#assign_teacher_id').val(teacherId);
        $('#assign_teacher_name').text(teacherName);
        
        // Load current assignments
        loadAssignments(teacherId);
        
        $('#assignClassModal').modal('show');
    });
    
    // Load assignments for a teacher
    function loadAssignments(teacherId) {
        $('#assignments_list').html('<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        
        $.ajax({
            url: '<?= $base_path ?>/api/get_teacher_assignments.php',
            method: 'GET',
            data: { teacher_id: teacherId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.assignments.length > 0) {
                    let html = '<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Class</th><th>Subject</th><th>Day</th><th>Time</th><th>Room</th><th>Action</th></tr></thead><tbody>';
                    
                    response.assignments.forEach(function(assignment) {
                        html += '<tr>';
                        html += '<td>' + assignment.class_name + '</td>';
                        html += '<td>' + assignment.subject_name + '</td>';
                        html += '<td>' + assignment.schedule + '</td>';
                        html += '<td>' + assignment.start_time + ' - ' + assignment.end_time + '</td>';
                        html += '<td>' + (assignment.room_number || '-') + '</td>';
                        html += '<td><form method="POST" style="display:inline;"><input type="hidden" name="assignment_id" value="' + assignment.assignment_id + '"><button type="submit" name="delete_assignment" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Are you sure you want to remove this assignment?\')"><i class="fas fa-trash-alt"></i></button></form></td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table></div>';
                    $('#assignments_list').html(html);
                } else {
                    $('#assignments_list').html('<div class="text-center py-3 text-muted"><i class="fas fa-info-circle me-2"></i>No class assignments found. Add one above.</div>');
                }
            },
            error: function() {
                $('#assignments_list').html('<div class="text-center py-3 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading assignments. Please refresh the page.</div>');
            }
        });
    }
    
    // Reload assignments when modal is shown
    $('#assignClassModal').on('shown.bs.modal', function() {
        const teacherId = $('#assign_teacher_id').val();
        if (teacherId) {
            loadAssignments(teacherId);
        }
    });
    
    // Handle form submission - submit normally to show success message
    // The page will reload and show success/error message
    // User can reopen modal to see updated assignments
    
    // Generate random password (if button exists)
    $('#generatePassword').click(function() {
        const randomString = Math.random().toString(36).slice(-8);
        $('#password').val(randomString);
        $('#edit_password').val(randomString);
    });
});
</script>

<style>
/* Custom styles for manage teachers page */
.card {
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.table th {
    font-weight: 600;
    background-color: #f8f9fa;
    border-top: none;
}

.table td {
    vertical-align: middle;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 6px;
}

.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
    border-radius: 50px;
}

.modal-content {
    border-radius: 10px;
    border: none;
}

.form-control, .form-select {
    border-radius: 8px;
    padding: 10px 15px;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
}

.btn-outline-primary:hover, .btn-outline-danger:hover, .btn-outline-info:hover {
    color: white;
}

.btn-outline-primary:hover {
    background-color: #667eea;
}

.btn-outline-danger:hover {
    background-color: #dc3545;
}

.btn-outline-info:hover {
    background-color: #17a2b8;
}

/* Responsive table adjustments */
@media (max-width: 768px) {
    .table-responsive {
        border: none;
    }
    
    .btn-sm {
        margin-bottom: 4px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>