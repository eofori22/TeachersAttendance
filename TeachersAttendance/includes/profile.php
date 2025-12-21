<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';

$auth = new Auth($conn);
$auth->requireLogin();

$user_id = $auth->getUserId();
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
        // Update profile
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $full_name, $email, $user_id);
        
        if ($stmt->execute()) {
            // Update session data
            $_SESSION['full_name'] = $full_name;
            $success = "Profile updated successfully!";
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
    }
}

// Get current user data
$stmt = $conn->prepare("SELECT username, full_name, email, role, qr_code FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar -->
        <?php include './includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Profile</h1>
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
                <div class="col-md-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body text-center">
                            <div class="avatar-xl mb-3 mx-auto">
                                <?php 
                                $initials = '';
                                $names = explode(' ', $user['full_name']);
                                $initials = strtoupper(substr($names[0], 0, 1) . (count($names) > 1 ? substr(end($names), 0, 1) : ''));
                                ?>
                                <span class="avatar-initials bg-primary bg-opacity-10 text-primary rounded-circle">
                                    <?= $initials ?>
                                </span>
                            </div>
                            <h5 class="card-title"><?= htmlspecialchars($user['full_name']) ?></h5>
                            <p class="card-text text-muted">
                                <i class="fas fa-user-tag me-1"></i> <?= ucfirst($user['role']) ?>
                            </p>
                            
                            <?php if ($user['role'] === 'teacher' && $user['qr_code']): ?>
                                <a href="qr_code.php" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="fas fa-qrcode me-1"></i> View My QR Code
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.avatar-xl {
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-initials {
    font-size: 2.5rem;
    font-weight: 600;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<?php include '../includes/footer.php'; ?>