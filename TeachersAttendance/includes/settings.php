<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';

$auth = new Auth($conn);
$auth->requireLogin();

$user_id = $auth->getUserId();
$success = '';
$error = '';

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
        $user = $result->fetch_assoc();
        
        if (password_verify($current_password, $user['password'])) {
            // Validate password strength
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

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar -->
        <?php include './includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Account Settings</h1>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Change Password</h5>
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
                                    <div class="form-text">Password must be at least 8 characters long and include numbers and special characters</div>
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
                    
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Account Preferences</h5>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="mb-3">
                                    <label class="form-label">Theme Preference</label>
                                    <select class="form-select">
                                        <option>System Default</option>
                                        <option>Light Mode</option>
                                        <option>Dark Mode</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="email_notifications">
                                    <label class="form-check-label" for="email_notifications">Receive email notifications</label>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Save Preferences
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Account Security</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6 class="fw-bold">Two-Factor Authentication</h6>
                                <p class="text-muted small">Add an extra layer of security to your account</p>
                                <button class="btn btn-sm btn-outline-primary">Enable 2FA</button>
                            </div>
                            
                            <div class="mb-4">
                                <h6 class="fw-bold">Login Activity</h6>
                                <p class="text-muted small">Last login: <?= date('M j, Y \a\t g:i A', strtotime('-1 hour')) ?></p>
                                <a href="#" class="btn btn-sm btn-outline-secondary">View All Activity</a>
                            </div>
                            
                            <div>
                                <h6 class="fw-bold">Danger Zone</h6>
                                <p class="text-muted small">Permanently delete your account</p>
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                    <i class="fas fa-trash-alt me-1"></i> Delete Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAccountModalLabel">Confirm Account Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete your account? This action cannot be undone.</p>
                <p class="fw-bold">All your data will be permanently removed from our systems.</p>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Warning: This will delete all your attendance records and related data.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger">Delete My Account</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>