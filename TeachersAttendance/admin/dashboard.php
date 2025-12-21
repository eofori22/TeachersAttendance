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
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3">
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
                    </div>
                </div>
            </div>
            
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
        
        <div class="col-md-9">
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
        const start = new Date(`2000-01-01 ${startTime}`);
        const end = new Date(`2000-01-01 ${endTime}`);
        const diff = end - start;
        
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        
        return `${hours}h ${minutes}m`;
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
        window.location.href = basePath + '/api/export_attendance.php?date=' + date;
    });
    
    // Initial load
    loadStats();
    loadAttendanceData();
});
</script>

<?php include '../includes/footer.php'; ?>