<?php 
include '../includes/auth.php'; 
include '../includes/header.php';

// Get base path
$base_path = getBasePath();

// Verify admin role
if ($_SESSION['role'] !== 'admin') {
    header('Location: ' . $base_path . '/index.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_path . '/index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get current user data
$stmt = $conn->prepare("SELECT full_name, email, profile_image FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($full_name)) {
        $error = "Please enter your full name";
    } elseif (empty($email)) {
        $error = "Please enter your email address";
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
                // Refresh user data
                $user['full_name'] = $full_name;
                $user['email'] = $email;
                if ($profile_image) {
                    $user['profile_image'] = $profile_image;
                }
            } else {
                $error = "Error updating profile: " . $conn->error;
            }
        }
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Admin Profile Settings</h4>
                </div>
                <div class="card-body p-5">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Profile Picture Section -->
                        <div class="mb-4">
                            <label class="form-label text-muted">Profile Picture</label>
                            <div class="d-flex align-items-center gap-4">
                                <div>
                                    <?php 
                                    $profile_image = $user['profile_image'] ?? null;
                                    if ($profile_image && file_exists($_SERVER['DOCUMENT_ROOT'] . $base_path . '/uploads/profiles/' . $profile_image)):
                                    ?>
                                        <img src="<?php echo $base_path; ?>/uploads/profiles/<?php echo htmlspecialchars($profile_image); ?>" 
                                             alt="Profile" 
                                             class="rounded-circle" 
                                             id="preview-image"
                                             style="width: 120px; height: 120px; object-fit: cover;">
                                    <?php else:
                                        $initials = '';
                                        $names = explode(' ', $user['full_name']);
                                        $initials = strtoupper(substr($names[0], 0, 1) . (count($names) > 1 ? substr(end($names), 0, 1) : ''));
                                    ?>
                                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary" 
                                             id="preview-image"
                                             style="width: 120px; height: 120px; font-size: 2rem; font-weight: 600;">
                                            <?= $initials ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*" onchange="previewImage(event)">
                                    <small class="text-muted d-block mt-2">JPG, PNG, or GIF (Max 5MB)</small>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Full Name -->
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-end">
                            <a href="<?= $base_path ?>/admin/dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(event) {
    const file = event.target.files[0];
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const previewImage = document.getElementById('preview-image');
        if (previewImage.tagName === 'IMG') {
            previewImage.src = e.target.result;
        } else {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'rounded-circle';
            img.id = 'preview-image';
            img.style.width = '120px';
            img.style.height = '120px';
            img.style.objectFit = 'cover';
            previewImage.parentNode.replaceChild(img, previewImage);
        }
    };
    
    if (file) {
        reader.readAsDataURL(file);
    }
}
</script>

<?php include '../includes/footer.php'; ?>
