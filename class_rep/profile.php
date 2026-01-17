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
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    
    // Basic validation
    if (empty($full_name) || empty($email)) {
        $error = "Please fill in all required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // Handle image upload
        $profile_image = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/profiles/';
            // Try to create directory if it doesn't exist, but don't fail if we can't
            if (!file_exists($upload_dir)) {
                @mkdir($upload_dir, 0755, true);
            }
            // Check if directory is writable
            if (!is_writable($upload_dir) && file_exists($upload_dir)) {
                $error = "Upload directory is not writable. Please contact administrator.";
            }
            
            $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    // Delete old image if exists
                    $old_image = $user['profile_image'] ?? null;
                    if ($old_image && file_exists($upload_dir . $old_image)) {
                        unlink($upload_dir . $old_image);
                    }
                    $profile_image = $new_filename;
                } else {
                    $error = "Error uploading image. Please try again.";
                }
            } else {
                $error = "Invalid image format. Please upload JPG, PNG, or GIF files only.";
            }
        }
        
        // Update profile
        if (empty($error)) {
            if ($profile_image) {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, profile_image = ? WHERE user_id = ?");
                $stmt->bind_param("sssi", $full_name, $email, $profile_image, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
                $stmt->bind_param("ssi", $full_name, $email, $user_id);
            }
            
            if ($stmt->execute()) {
                // Update session data
                $_SESSION['full_name'] = $full_name;
                if ($profile_image) {
                    $_SESSION['profile_image'] = $profile_image;
                }
                $success = "Profile updated successfully!";
            } else {
                $error = "Error updating profile: " . $conn->error;
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all password fields";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match";
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        
        if (password_verify($current_password, $user_data['password'])) {
            // Validate password strength
            $auth = new Auth($conn);
            $password_errors = $auth->validatePasswordStrength($new_password);
            
            if (empty($password_errors)) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $update_stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($update_stmt->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Error changing password: " . $conn->error;
                }
            } else {
                $error = implode("<br>", $password_errors);
            }
        } else {
            $error = "Current password is incorrect";
        }
    }
}

// Get class rep details and class information
$stmt = $conn->prepare("SELECT u.username, u.full_name, u.email, u.role, u.profile_image, c.class_id, c.class_name 
FROM users u 
LEFT JOIN classes c ON u.user_id = c.class_rep_id 
WHERE u.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<div class="container-fluid py-4">
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
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
                            $profile_image = $user['profile_image'] ?? null;
                            $initials = '';
                            if (!empty($user) && isset($user['full_name'])) {
                                $names = explode(' ', $user['full_name']);
                                $initials = strtoupper(substr($names[0], 0, 1) . (count($names) > 1 ? substr(end($names), 0, 1) : ''));
                            }
                            
                            if ($profile_image && file_exists($_SERVER['DOCUMENT_ROOT'] . $base_path . '/uploads/profiles/' . $profile_image)):
                            ?>
                                <img src="<?= $base_path ?>/uploads/profiles/<?= htmlspecialchars($profile_image) ?>" 
                                     alt="Profile" 
                                     class="rounded-circle" 
                                     style="width: 80px; height: 80px; object-fit: cover;">
                            <?php else: ?>
                                <span class="avatar-initials bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 1.5rem;"><?= $initials ?></span>
                            <?php endif; ?>
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
                            <a class="nav-link" href="<?= $base_path ?>/class_rep/schedule.php">
                                <i class="fas fa-calendar-alt me-2"></i> Class Schedule
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?= $base_path ?>/class_rep/profile.php">
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
                <h2 class="mb-0">Profile Settings</h2>
            </div>
            
            <div class="row">
                <!-- Profile Information -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="profile_image" class="form-label">Profile Image</label>
                                    <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif">
                                    <div class="form-text">Upload JPG, PNG, or GIF image (max 2MB)</div>
                                    <?php if ($profile_image): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">Current image: <?= htmlspecialchars($profile_image) ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" 
                                           value="<?= htmlspecialchars(!empty($user) && isset($user['username']) ? $user['username'] : '') ?>" 
                                           readonly>
                                    <div class="form-text">Username cannot be changed</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?= htmlspecialchars(!empty($user) && isset($user['full_name']) ? $user['full_name'] : '') ?>" 
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars(!empty($user) && isset($user['email']) ? $user['email'] : '') ?>" 
                                           required>
                                </div>
                                
                                <?php if (!empty($user) && isset($user['class_name'])): ?>
                                <div class="mb-3">
                                    <label class="form-label">Class</label>
                                    <input type="text" class="form-control" 
                                           value="<?= htmlspecialchars($user['class_name']) ?>" 
                                           readonly>
                                    <div class="form-text">Class assignment is managed by administrator</div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-end">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0"><i class="fas fa-key me-2"></i>Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="form-text">
                                        Password must be at least 8 characters long and include:
                                        <ul class="mb-0 small">
                                            <li>At least one number</li>
                                            <li>At least one letter</li>
                                            <li>At least one special character</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="fas fa-key me-1"></i> Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Account Information -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Account Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Role</label>
                                    <p class="mb-0">
                                        <span class="badge bg-primary"><?= htmlspecialchars(!empty($user) && isset($user['role']) ? ucfirst(str_replace('_', ' ', $user['role'])) : 'N/A') ?></span>
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">User ID</label>
                                    <p class="mb-0"><?= htmlspecialchars($user_id) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
