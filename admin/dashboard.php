<?php 
include '../includes/auth.php'; 
include '../includes/header.php';

// Get base path for API calls
$base_path = getBasePath();

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: ' . $base_path . '/index.php');
    exit();
}

// Get admin user details for profile display
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, profile_image FROM users WHERE user_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_user = $result->fetch_assoc();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Admin Profile Sidebar -->
        <div class="col-md-3">
            <div class="card shadow-sm mb-4 h-100">
                <div class="card-body">
                    <div class="d-flex flex-column align-items-center text-center mb-4">
                        <div class="mb-3">
                            <?php 
                            $profile_image = $admin_user['profile_image'] ?? null;
                            if ($profile_image && file_exists($_SERVER['DOCUMENT_ROOT'] . $base_path . '/uploads/profiles/' . $profile_image)):
                            ?>
                                <img src="<?php echo $base_path; ?>/uploads/profiles/<?php echo htmlspecialchars($profile_image); ?>" 
                                     alt="Profile" 
                                     class="rounded-circle" 
                                     style="width: 80px; height: 80px; object-fit: cover;">
                            <?php else:
                                $initials = '';
                                $names = explode(' ', $admin_user['full_name']);
                                $initials = strtoupper(substr($names[0], 0, 1) . (count($names) > 1 ? substr(end($names), 0, 1) : ''));
                            ?>
                                <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary" 
                                     style="width: 80px; height: 80px; font-size: 1.5rem; font-weight: 600;">
                                    <?= $initials ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h5 class="mb-1"><?= htmlspecialchars($admin_user['full_name']) ?></h5>
                        <p class="text-muted small mb-3">Administrator</p>
                        <a href="<?= $base_path ?>/admin/profile.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit me-1"></i> Edit Profile
                        </a>
                    </div>
                    
                    <hr class="my-3">

                        <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Management</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="<?= $base_path ?>/admin/manage_teachers.php" class="btn btn-outline-success">
                                    <i class="fas fa-user-plus"></i> Manage Teachers
                                </a>
                                <a href="<?= $base_path ?>/admin/manage_classes.php" class="btn btn-outline-success">
                                    <i class="fas fa-layer-group"></i> Manage Classes
                                </a>
                                <a href="<?= $base_path ?>/admin/manage_class_reps.php" class="btn btn-outline-success">
                                    <i class="fas fa-user-tie"></i> Manage Class Reps
                                </a>
                                <a href="<?= $base_path ?>/admin/manage_permissions.php" class="btn btn-outline-success position-relative">
                                    <i class="fas fa-user-clock"></i> Permission Requests
                                    <?php
                                    // Get pending permissions count
                                    $stmt = $conn->prepare("SELECT COUNT(*) as pending_count FROM teacher_permissions WHERE status = 'pending'");
                                    $stmt->execute();
                                    $pending_result = $stmt->get_result()->fetch_assoc();
                                    $pending_count = $pending_result['pending_count'];
                                    if ($pending_count > 0):
                                    ?>
                                        <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">
                                            <?= $pending_count ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- Class Alerts Settings -->
                    <div class="card shadow-sm border-0 bg-light">
                        <div class="card-body">
                            <h6 class="card-title mb-3">
                                <i class="fas fa-bell me-2 text-danger"></i>Class Alerts
                                <span class="badge bg-danger" id="missing-teachers-badge" style="display: none;">0</span>
                            </h6>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="admin-notification-toggle" checked>
                                <label class="form-check-label" for="admin-notification-toggle">
                                    <small>Browser Alerts</small>
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="admin-sound-toggle" checked>
                                <label class="form-check-label" for="admin-sound-toggle">
                                    <small>Sound Beep</small>
                                </label>
                            </div>
                            <p class="text-muted small mt-3 mb-0">
                                <i class="fas fa-info-circle me-1"></i>Get alerts when teachers miss classes
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        
        <!-- Stats and Management Column -->
        <div class="col-md-9">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Quick Stats</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Total Teachers</span>
                        <span class="badge bg-primary" id="total-teachers">0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Total Classes</span>
                        <span class="badge bg-primary" id="total-classes">0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Today's Attendance</span>
                        <span class="badge bg-primary" id="today-attendance-count">0</span>
                    </div>
                </div>
            </div>
            
            <!-- Management moved to sidebar -->
            
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush" id="recent-activity">
                        <li class="list-group-item">Loading activity...</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-9 offset-md-3">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Daily Attendance Overview</h5>
                </div>
                <div class="card-body">
                    <canvas id="attendanceChart" height="200"></canvas>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Today's Attendance Records</h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" id="refresh-btn">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button class="btn btn-sm btn-primary" id="export-btn">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="attendance-table">
                            <thead>
                                <tr>
                                    <th>Teacher</th>
                                    <th>Class</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Duration</th>
                                    <th>Scanned By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">Loading data...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load stats
    function loadStats() {
        const basePath = '<?php echo $base_path; ?>';
        return fetch(basePath + '/api/admin_stats.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total-teachers').textContent = data.total_teachers;
            document.getElementById('total-classes').textContent = data.total_classes;
            document.getElementById('today-attendance-count').textContent = data.today_attendance;
            
            // Update recent activity
            const activityList = document.getElementById('recent-activity');
            activityList.innerHTML = '';
            
            if (data.recent_activity && data.recent_activity.length > 0) {
                data.recent_activity.forEach(activity => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item';
                    li.innerHTML = `
                        <small class="d-block text-muted">${activity.time_ago}</small>
                        ${activity.description}
                    `;
                    activityList.appendChild(li);
                });
            } else {
                activityList.innerHTML = '<li class="list-group-item">No recent activity</li>';
            }
        })
        .catch(error => {
            console.error('Error loading stats:', error);
            document.getElementById('recent-activity').innerHTML = '<li class="list-group-item text-danger">Error loading activity</li>';
        });
    }
    
    // Load attendance data
    function loadAttendanceData() {
        const basePath = '<?php echo $base_path; ?>';
        return fetch(basePath + '/api/admin_attendance.php?date=<?php echo date('Y-m-d'); ?>')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load attendance data');
            }
            return response.json();
        })
        .then(data => {
            const tableBody = document.querySelector('#attendance-table tbody');
            tableBody.innerHTML = '';
            
            if (data && data.length > 0) {
                data.forEach(record => {
                    const duration = record.time_out ? 
                        calculateDuration(record.time_in, record.time_out) : 'In Progress';
                    
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${record.teacher_name || '-'}</td>
                        <td>${record.class_name || '-'}</td>
                        <td>${record.time_in || '-'}</td>
                        <td>${record.time_out || '-'}</td>
                        <td>${duration}</td>
                        <td>${record.scanned_by_name || '-'}</td>
                    `;
                    tableBody.appendChild(row);
                });
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center">No attendance records for today</td>
                    </tr>
                `;
            }
            
            // Update chart
            updateChart(data || []);
        })
        .catch(error => {
            console.error('Error loading attendance data:', error);
            const tableBody = document.querySelector('#attendance-table tbody');
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-danger">Error loading attendance data. Please check if admin_attendance.php exists.</td>
                </tr>
            `;
        });
    }
    
    // Calculate duration between two times
    function calculateDuration(startTime, endTime) {
        // Parse 12-hour format (e.g., "09:30 AM") to 24-hour format
        function parseTime(timeStr) {
            const [time, period] = timeStr.split(' ');
            let [hours, minutes] = time.split(':').map(Number);
            
            if (period === 'PM' && hours !== 12) {
                hours += 12;
            } else if (period === 'AM' && hours === 12) {
                hours = 0;
            }
            
            return new Date(`2000-01-01 ${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:00`);
        }
        
        try {
            const start = parseTime(startTime);
            const end = parseTime(endTime);
            const diff = end - start;
            
            if (diff < 0) {
                return '0h 0m';
            }
            
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            
            return `${hours}h ${minutes}m`;
        } catch (error) {
            console.error('Error calculating duration:', error, startTime, endTime);
            return '-';
        }
    }
    
    // Update chart
    let attendanceChart = null;
    function updateChart(data) {
        // Group by class
        const classData = {};
        data.forEach(record => {
            if (!classData[record.class_name]) {
                classData[record.class_name] = {
                    present: 0,
                    total: 0
                };
            }
            
            classData[record.class_name].total++;
            if (record.time_in) {
                classData[record.class_name].present++;
            }
        });
        
        const classes = Object.keys(classData);
        const presentData = classes.map(cls => classData[cls].present);
        const absentData = classes.map(cls => classData[cls].total - classData[cls].present);
        
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        
        if (attendanceChart) {
            attendanceChart.destroy();
        }
        
        attendanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: classes,
                datasets: [
                    {
                        label: 'Present',
                        data: presentData,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Absent',
                        data: absentData,
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Refresh button
    document.getElementById('refresh-btn').addEventListener('click', function() {
        const btn = this;
        const icon = btn.querySelector('i');
        icon.classList.add('fa-spin');
        btn.disabled = true;
        
        // Reload both stats and attendance data
        Promise.all([loadStats(), loadAttendanceData()]).then(() => {
            icon.classList.remove('fa-spin');
            btn.disabled = false;
        }).catch((error) => {
            console.error('Error refreshing data:', error);
            icon.classList.remove('fa-spin');
            btn.disabled = false;
        });
    });
    
    // Export button
    document.getElementById('export-btn').addEventListener('click', function() {
        const basePath = '<?php echo $base_path; ?>';
        const date = '<?php echo date('Y-m-d'); ?>';
        
        // Create dropdown menu for export options
        const existingDropdown = document.querySelector('.export-dropdown');
        if (existingDropdown) {
            existingDropdown.remove();
            return;
        }
        
        const dropdown = document.createElement('div');
        dropdown.className = 'export-dropdown position-absolute bg-white border rounded shadow-sm';
        dropdown.style.cssText = 'top: 100%; right: 0; z-index: 1000; min-width: 150px;';
        dropdown.innerHTML = `
            <a href="${basePath}/api/export_attendance.php?format=csv&date=${date}" class="dropdown-item px-3 py-2 text-decoration-none d-block">
                <i class="fas fa-file-csv me-2 text-success"></i>Export CSV
            </a>
            <a href="${basePath}/api/export_attendance.php?format=pdf&date=${date}" class="dropdown-item px-3 py-2 text-decoration-none d-block" target="_blank">
                <i class="fas fa-file-pdf me-2 text-danger"></i>Export PDF
            </a>
        `;
        
        this.parentElement.style.position = 'relative';
        this.parentElement.appendChild(dropdown);
        
        // Close dropdown when clicking outside
        setTimeout(() => {
            document.addEventListener('click', function closeDropdown(e) {
                if (!dropdown.contains(e.target) && e.target !== document.getElementById('export-btn')) {
                    dropdown.remove();
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }, 100);
    });

    // Alert Settings Toggles
    const adminNotificationToggle = document.getElementById('admin-notification-toggle');
    const adminSoundToggle = document.getElementById('admin-sound-toggle');

    // Load saved preferences
    const savedAdminNotification = localStorage.getItem('adminNotificationEnabled');
    const savedAdminSound = localStorage.getItem('adminSoundEnabled');
    
    if (savedAdminNotification !== null) {
        adminNotificationToggle.checked = savedAdminNotification === 'true';
    }
    if (savedAdminSound !== null) {
        adminSoundToggle.checked = savedAdminSound === 'true';
    }

    // Handle notification toggle
    if (adminNotificationToggle) {
        adminNotificationToggle.addEventListener('change', function() {
            const enabled = this.checked;
            localStorage.setItem('adminNotificationEnabled', enabled);
            
            if (typeof adminAlertManager !== 'undefined') {
                adminAlertManager.toggleNotifications(enabled);
            }
            
            console.log('Admin browser notifications:', enabled ? 'enabled' : 'disabled');
        });
    }

    // Handle sound toggle
    if (adminSoundToggle) {
        adminSoundToggle.addEventListener('change', function() {
            const enabled = this.checked;
            localStorage.setItem('adminSoundEnabled', enabled);
            
            if (typeof adminAlertManager !== 'undefined') {
                adminAlertManager.toggleSound(enabled);
            }
            
            console.log('Admin sound alerts:', enabled ? 'enabled' : 'disabled');
        });
    }
    
    // Initial load
    loadStats();
    loadAttendanceData();
});
</script>

<?php include '../includes/footer.php'; ?>