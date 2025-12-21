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
            <button id="downloadQR" class="btn btn-outline-primary">Download QR Code</button>
        </div>
    </div>
</div>

<script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Generate QR code
    const qrcode = new QRCode(document.getElementById("qrcode"), {
        text: "<?php echo $teacher['qr_code']; ?>",
        width: 200,
        height: 200,
        colorDark: "#000000",
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
});
</script>

<?php include '../includes/footer.php'; ?>