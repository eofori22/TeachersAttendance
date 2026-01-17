<?php
include '../includes/auth.php';
include '../includes/config.php';

// Verify class representative role
if ($_SESSION['role'] !== 'class_rep') {
    header('Location: ../index.php');
    exit();
}

// Get class information
$class_rep_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT c.class_id, c.class_name FROM classes c JOIN users u ON c.class_rep_id = u.user_id WHERE u.user_id = ?");
$stmt->bind_param("i", $class_rep_id);
$stmt->execute();
$result = $stmt->get_result();
$class = $result->fetch_assoc();

if (!$class) {
    die("You are not assigned as a class representative.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Scan Teacher QR Code</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #scanner-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            min-height: 300px;
            background: #000;
        }
        #qr-video {
            width: 100%;
            height: auto;
            display: block;
        }
        #scan-result {
            margin-top: 20px;
        }
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .camera-error {
            padding: 20px;
            color: white;
            text-align: center;
            background: #dc3545;
            border-radius: 8px;
        }
        .camera-controls {
            margin-top: 15px;
            text-align: center;
        }
        #camera-feed {
            position: relative;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Scan Teacher QR Code</h4>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <p class="lead">Class: <strong><?= htmlspecialchars($class['class_name']) ?></strong></p>
                </div>
                
                <div id="scanner-container">
                    <div id="camera-feed">
                        <video id="qr-video" playsinline></video>
                    </div>
                    <div id="camera-error" class="camera-error" style="display: none;"></div>
                </div>
                
                <div class="camera-controls">
                    <button id="start-camera" class="btn btn-success">Start Camera</button>
                    <button id="stop-camera" class="btn btn-danger" disabled>Stop Camera</button>
                    <button id="switch-camera" class="btn btn-info" disabled>Switch Camera</button>
                </div>
                
                <div class="action-buttons">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary active" id="time-in-btn">Time In</button>
                        <button type="button" class="btn btn-secondary" id="time-out-btn">Time Out</button>
                    </div>
                </div>
                
                <div id="scan-result" class="alert alert-info text-center" style="display: none;"></div>
                
                <div class="manual-entry mt-4">
                    <label class="form-label">Manual Entry (if QR fails):</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="manual-qr" placeholder="Enter QR code manually">
                        <button class="btn btn-outline-secondary" type="button" id="manual-submit">Submit</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card shadow mt-4">
            <div class="card-header">
                <h5 class="mb-0">Today's Scans</h5>
            </div>
            <div class="card-body">
                <div id="today-attendance">
                    <p class="text-center text-muted">Scan results will appear here</p>
                </div>
            </div>
        </div>
    </div>

    <script type="module">
        import QrScanner from "https://cdn.jsdelivr.net/npm/qr-scanner@1.4.1/qr-scanner.min.js";
        window.QrScanner = QrScanner;
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const qrVideo = document.getElementById('qr-video');
        const scanResult = document.getElementById('scan-result');
        const timeInBtn = document.getElementById('time-in-btn');
        const timeOutBtn = document.getElementById('time-out-btn');
        const manualQr = document.getElementById('manual-qr');
        const manualSubmit = document.getElementById('manual-submit');
        const todayAttendance = document.getElementById('today-attendance');
        const cameraError = document.getElementById('camera-error');
        const startBtn = document.getElementById('start-camera');
        const stopBtn = document.getElementById('stop-camera');
        const switchBtn = document.getElementById('switch-camera');
        
        let action = 'time_in';
        let scanner = null;
        let currentCamera = 'environment';
        let cameras = [];

        // Set button states
        stopBtn.disabled = true;
        switchBtn.disabled = true;

        // Action button handlers
        timeInBtn.addEventListener('click', function() {
            action = 'time_in';
            timeInBtn.classList.add('active');
            timeOutBtn.classList.remove('active');
        });
        
        timeOutBtn.addEventListener('click', function() {
            action = 'time_out';
            timeOutBtn.classList.add('active');
            timeInBtn.classList.remove('active');
        });
        
        manualSubmit.addEventListener('click', function() {
            const qrCode = manualQr.value.trim();
            if (qrCode) {
                processQRCode(qrCode);
            } else {
                showAlert('Please enter a QR code', 'warning');
            }
        });
        
        async function processQRCode(qrCode) {
            showAlert(`Processing ${action === 'time_in' ? 'Time In' : 'Time Out'}...`, 'info');
            
            try {
                const response = await fetch('../api/attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: action,
                        qr_code: qrCode,
                        class_id: <?= $class['class_id'] ?>
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    showAlert(data.message, 'success');
                    loadTodayAttendance();
                    manualQr.value = '';
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            }
        }

        async function initScanner() {
            try {
                cameraError.style.display = 'none';
                qrVideo.style.display = 'block';

                if (!QrScanner.hasCamera()) {
                    throw new Error('No camera found on this device');
                }

                // List available cameras
                cameras = await QrScanner.listCameras(true);
                if (cameras.length === 0) {
                    throw new Error('No cameras found');
                }

                // Create scanner instance
                scanner = new QrScanner(
                    qrVideo,
                    result => {
                        processQRCode(result.data);
                    },
                    {
                        preferredCamera: currentCamera,
                        highlightScanRegion: true,
                        highlightCodeOutline: true,
                    }
                );

                // Start scanning
                await scanner.start();
                startBtn.disabled = true;
                stopBtn.disabled = false;
                switchBtn.disabled = cameras.length < 2;

            } catch (error) {
                handleCameraError(error);
            }
        }

        function handleCameraError(error) {
            console.error('Camera Error:', error);
            if (scanner) {
                scanner.stop();
                scanner.destroy();
                scanner = null;
            }
            
            qrVideo.style.display = 'none';
            cameraError.style.display = 'block';
            cameraError.innerHTML = `
                <h5>Camera Error</h5>
                <p>${error.message || 'Failed to access camera'}</p>
                <button onclick="location.reload()" class="btn btn-light">Try Again</button>
            `;
            
            startBtn.disabled = false;
            stopBtn.disabled = true;
            switchBtn.disabled = true;
        }

        function showAlert(message, type) {
            scanResult.style.display = 'block';
            scanResult.textContent = message;
            scanResult.className = `alert alert-${type} text-center`;
        }
        
        async function switchCamera() {
            if (scanner) {
                currentCamera = currentCamera === 'environment' ? 'user' : 'environment';
                await scanner.setCamera(currentCamera);
            }
        }

        // Event listeners for camera controls
        startBtn.addEventListener('click', async () => {
            try {
                await initScanner();
            } catch (error) {
                handleCameraError(error);
            }
        });

        stopBtn.addEventListener('click', async () => {
            if (scanner) {
                await scanner.stop();
                scanner.destroy();
                scanner = null;
                startBtn.disabled = false;
                stopBtn.disabled = true;
                switchBtn.disabled = true;
            }
        });

        switchBtn.addEventListener('click', async () => {
            try {
                await switchCamera();
            } catch (error) {
                handleCameraError(error);
            }
        });

        async function loadTodayAttendance() {
            try {
                const response = await fetch(`../api/attendance.php?class_id=<?= $class['class_id'] ?>`);
                const data = await response.json();
                
                if (data.length > 0) {
                    let html = '<div class="table-responsive"><table class="table table-striped">';
                    html += '<thead><tr><th>Teacher</th><th>Time In</th><th>Time Out</th><th>Status</th></tr></thead>';
                    html += '<tbody>';
                    
                    data.forEach(record => {
                        const status = record.time_out ? 
                            '<span class="badge bg-success">Completed</span>' : 
                            '<span class="badge bg-warning">In Progress</span>';
                        
                        html += `<tr>
                            <td>${escapeHtml(record.teacher_name)}</td>
                            <td>${record.time_in || '-'}</td>
                            <td>${record.time_out || '-'}</td>
                            <td>${status}</td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table></div>';
                    todayAttendance.innerHTML = html;
                } else {
                    todayAttendance.innerHTML = '<p class="text-center text-muted">No attendance records for today</p>';
                }
            } catch (error) {
                console.error('Error:', error);
                todayAttendance.innerHTML = '<p class="text-center text-danger">Error loading attendance records</p>';
            }
        }
        
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
        
        // Load initial attendance data
        loadTodayAttendance();
    });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>