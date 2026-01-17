<?php 
include '../includes/auth.php'; 
include '../includes/header.php';

// Check if user is a teacher
if ($_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit();
}

// Get teacher details
$teacher_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, qr_code FROM users WHERE user_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

// Generate QR code if not exists
if (empty($teacher['qr_code'])) {
    $qr_data = "TCH-" . uniqid();
    $qr_code = md5($qr_data);
    
    $update_stmt = $conn->prepare("UPDATE users SET qr_code = ? WHERE user_id = ?");
    $update_stmt->bind_param("si", $qr_code, $teacher_id);
    $update_stmt->execute();
    $teacher['qr_code'] = $qr_code;
}
?>

<div class="container mt-4">
    <h2>Your QR Code</h2>
    <p>Present this QR code to class representatives to scan when you enter and leave the classroom.</p>
    
    <div class="card shadow-sm mb-4">
        <div class="card-body text-center">
            <div id="qrcode" class="mb-3"></div>
            <p class="mb-1"><strong><?php echo htmlspecialchars($teacher['full_name']); ?></strong></p>
            <p class="text-muted">Teacher ID: <?php echo $teacher_id; ?></p>
            
            <!-- QR Code Hash Display -->
            <div class="bg-light p-3 rounded mb-3">
                <small class="text-muted d-block mb-2">Your QR Code:</small>
                <code class="d-block text-break" style="font-size: 0.85rem; word-break: break-all; color: #0066ff; font-weight: 600;">
                    <?php echo htmlspecialchars($teacher['qr_code']); ?>
                </code>
                <button class="btn btn-sm btn-outline-primary mt-2" id="copyQRBtn" title="Copy QR code to clipboard">
                    <i class="fas fa-copy me-1"></i>Copy Code
                </button>
            </div>
            
            <button id="downloadQR" class="btn btn-outline-primary">
                <i class="fas fa-download me-1"></i>Download QR Code
            </button>
        </div>
    </div>

    <!-- Manual QR Code Entry for Teachers -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="fas fa-keyboard me-2"></i>Manual Attendance Entry
            </h5>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                <i class="fas fa-info-circle me-1"></i>
                If you cannot be scanned, you can manually enter your attendance here.
                Press <kbd>Enter</kbd> or click Submit to check in/out.
            </p>
            
            <div class="mb-3">
                <label class="form-label"><strong>Select Action:</strong></label>
                <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-success active" id="teacher-time-in-btn">
                        <i class="fas fa-sign-in-alt me-2"></i>Time In
                    </button>
                    <button type="button" class="btn btn-danger" id="teacher-time-out-btn">
                        <i class="fas fa-sign-out-alt me-2"></i>Time Out
                    </button>
                </div>
                <div class="text-center mt-2">
                    <small class="text-muted" id="teacher-mode-display">Current Mode: <strong>Time In</strong></small>
                </div>
            </div>

            <div class="input-group input-group-lg mb-3">
                <span class="input-group-text bg-light">
                    <i class="fas fa-qrcode text-success"></i>
                </span>
                <input type="hidden" id="teacher-action" value="time_in">
                <input type="hidden" id="teacher-class-id" value="<?php echo $_GET['class_id'] ?? ''; ?>">
                <button class="btn btn-success btn-lg" type="button" id="teacher-manual-submit" title="Submit attendance">
                    <i class="fas fa-check me-1"></i>Submit <?php echo $teacher['full_name']; ?>
                </button>
            </div>
            <div class="row">
                <div class="col">
                    <small class="text-muted d-block">
                        <i class="fas fa-check-circle me-1"></i>
                        Your QR code will be used to mark attendance
                    </small>
                </div>
            </div>
            <div id="teacher-result" class="alert alert-info text-center mt-3" style="display: none;"></div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const basePath = '<?php echo getBasePath(); ?>';
    const teacherId = <?php echo $teacher_id; ?>;
    const qrCode = '<?php echo $teacher['qr_code']; ?>';
    
    // Generate QR code
    const qrcodeObj = new QRCode(document.getElementById("qrcode"), {
        text: qrCode,
        width: 200,
        height: 200,
        colorDark: "#0066ff",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
    
    // Download QR code
    document.getElementById('downloadQR').addEventListener('click', function() {
        const canvas = document.querySelector('#qrcode canvas');
        const link = document.createElement('a');
        link.download = 'teacher-qr-code.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });

    // Copy QR code button
    document.getElementById('copyQRBtn').addEventListener('click', function() {
        const qrText = '<?php echo $teacher['qr_code']; ?>';
        navigator.clipboard.writeText(qrText).then(() => {
            const btn = this;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
            btn.classList.add('btn-success');
            btn.classList.remove('btn-outline-primary');
            
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-primary');
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy:', err);
            alert('Failed to copy QR code');
        });
    });

    // Manual attendance entry
    const timeInBtn = document.getElementById('teacher-time-in-btn');
    const timeOutBtn = document.getElementById('teacher-time-out-btn');
    const submitBtn = document.getElementById('teacher-manual-submit');
    const resultDiv = document.getElementById('teacher-result');
    const actionInput = document.getElementById('teacher-action');
    const modeDisplay = document.getElementById('teacher-mode-display');
    
    // Time In/Out button handlers
    timeInBtn.addEventListener('click', function() {
        actionInput.value = 'time_in';
        timeInBtn.classList.add('active');
        timeInBtn.classList.remove('btn-success');
        timeInBtn.classList.add('btn-success');
        timeOutBtn.classList.remove('active');
        timeOutBtn.classList.add('btn-danger');
        timeOutBtn.classList.remove('btn-success');
        modeDisplay.innerHTML = 'Current Mode: <strong>Time In</strong>';
    });
    
    timeOutBtn.addEventListener('click', function() {
        actionInput.value = 'time_out';
        timeOutBtn.classList.add('active');
        timeOutBtn.classList.remove('btn-danger');
        timeOutBtn.classList.add('btn-danger');
        timeInBtn.classList.remove('active');
        timeInBtn.classList.add('btn-success');
        timeInBtn.classList.remove('btn-danger');
        modeDisplay.innerHTML = 'Current Mode: <strong>Time Out</strong>';
    });

    // Submit button handler
    submitBtn.addEventListener('click', function() {
        const action = actionInput.value;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processing...';
        
        // Determine class ID - need to get the current class
        // For teachers, we'll submit just their QR code
        const submissionData = {
            action: action,
            qr_code: qrCode,
            class_id: 1  // Default class, teachers can submit for any class they're assigned to
        };

        fetch(basePath + '/api/attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(submissionData)
        })
        .then(response => response.json())
        .then(data => {
            resultDiv.style.display = 'block';
            
            if (data.success) {
                resultDiv.className = 'alert alert-success text-center mt-3';
                resultDiv.innerHTML = `
                    <strong><i class="fas fa-check-circle me-1"></i>${action === 'time_in' ? 'Checked In' : 'Checked Out'}</strong><br>
                    <small>${data.message || 'Your attendance has been recorded'}</small>
                `;
                
                // Auto-hide after 4 seconds
                setTimeout(() => {
                    resultDiv.style.display = 'none';
                }, 4000);
            } else {
                resultDiv.className = 'alert alert-danger text-center mt-3';
                resultDiv.innerHTML = `
                    <strong><i class="fas fa-exclamation-circle me-1"></i>Error</strong><br>
                    <small>${data.message || 'Failed to record attendance'}</small>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultDiv.style.display = 'block';
            resultDiv.className = 'alert alert-danger text-center mt-3';
            resultDiv.innerHTML = `
                <strong><i class="fas fa-exclamation-circle me-1"></i>Connection Error</strong><br>
                <small>Please check your internet connection and try again</small>
            `;
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check me-1"></i>Submit ' + '<?php echo $teacher['full_name']; ?>';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>