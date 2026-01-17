/**
 * Admin Class Alert Notification System
 * Sends beep notifications when teachers haven't checked in for classes that are due
 */

class AdminAlertManager {
    constructor() {
        this.pollInterval = 30000; // Poll every 30 seconds
        this.alertedClasses = new Set(); // Track which classes we've alerted
        this.soundEnabled = localStorage.getItem('adminSoundEnabled') !== 'false';
        this.notificationEnabled = localStorage.getItem('adminNotificationEnabled') !== 'false';
        
        // Check browser support
        this.checkBrowserSupport();
        
        // Request notification permission on init
        if (this.notificationEnabled) {
            this.requestNotificationPermission();
        }
    }

    checkBrowserSupport() {
        if (!('Notification' in window)) {
            console.warn('Browser does not support notifications');
            this.notificationEnabled = false;
        }
    }

    requestNotificationPermission() {
        if (Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    this.notificationEnabled = true;
                    console.log('Admin notification permission granted');
                }
            });
        }
    }

    /**
     * Play urgent beep sound (3 beeps)
     */
    playAlertSound() {
        if (!this.soundEnabled) return;

        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // Play 3 beeps for urgent alert
            const beepCount = 3;
            const beepDuration = 150; // ms
            const beepInterval = 200; // ms between beeps
            
            for (let i = 0; i < beepCount; i++) {
                setTimeout(() => {
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    oscillator.frequency.value = 1000 + (i * 150); // Higher frequency for urgency
                    oscillator.type = 'sine';
                    
                    gainNode.gain.setValueAtTime(0.4, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + beepDuration / 1000);
                    
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + beepDuration / 1000);
                }, beepInterval * i);
            }
        } catch (error) {
            console.warn('Could not play alert sound:', error);
        }
    }

    /**
     * Send urgent browser notification
     */
    sendAlertNotification(title, options = {}) {
        if (!this.notificationEnabled || Notification.permission !== 'granted') {
            return;
        }

        try {
            const notification = new Notification(title, {
                icon: '/assets/img/robot.svg',
                badge: '/assets/img/robot.svg',
                tag: 'admin-alert',
                requireInteraction: true, // Keep notification until user interacts
                ...options
            });

            notification.onclick = () => {
                window.focus();
                notification.close();
            };

            // Auto-close after 15 seconds
            setTimeout(() => notification.close(), 15000);
        } catch (error) {
            console.error('Error sending notification:', error);
        }
    }

    /**
     * Fetch missing teacher attendance data
     */
    async fetchMissingTeachers() {
        try {
            const response = await fetch(`${BASE_PATH}/api/admin_class_alerts.php`);
            const data = await response.json();
            
            if (data.success) {
                this.processMissingTeachers(data.missing_teachers);
                this.updateAlertBadge(data.count);
            }
        } catch (error) {
            console.error('Error fetching missing teachers:', error);
        }
    }

    /**
     * Process missing teachers and send alerts
     */
    processMissingTeachers(teachers) {
        teachers.forEach(teacher => {
            const alertKey = `${teacher.assignment_id}_${teacher.teacher_id}`;
            
            // Alert only if this is the first time we're seeing this missing teacher
            if (!this.alertedClasses.has(alertKey)) {
                this.alertedClasses.add(alertKey);
                this.playAlertSound();
                
                const statusText = {
                    'just_started': '🔴 CLASS JUST STARTED',
                    'in_class': '⚠️ CLASS IN PROGRESS',
                    'late': '🚨 TEACHER LATE'
                };
                
                this.sendAlertNotification(
                    statusText[teacher.status] || '⚠️ Missing Teacher',
                    {
                        body: `${teacher.teacher_name} hasn't checked in for ${teacher.subject_name} (${teacher.class_name}) which started at ${teacher.start_time_formatted}${teacher.room_number ? ' in Room ' + teacher.room_number : ''}`,
                        tag: `alert-${alertKey}`
                    }
                );

                // Also show page alert
                this.showPageAlert(teacher);
            }
        });
    }

    /**
     * Show alert on the page (toast-like)
     */
    showPageAlert(teacher) {
        const alertDiv = document.createElement('div');
        const statusClass = {
            'just_started': 'alert-warning',
            'in_class': 'alert-warning',
            'late': 'alert-danger'
        };

        alertDiv.className = `alert ${statusClass[teacher.status] || 'alert-warning'} alert-dismissible fade show`;
        alertDiv.style.fontWeight = 'bold';
        alertDiv.innerHTML = `
            <strong>⚠️ Teacher Not Checked In</strong><br>
            <strong>${teacher.teacher_name}</strong> - ${teacher.subject_name}<br>
            <small>${teacher.class_name}${teacher.room_number ? ' • Room ' + teacher.room_number : ''}</small><br>
            <small>Started: ${teacher.start_time_formatted} (${teacher.minutes_overdue} min ago)</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Find or create alert container
        let alertContainer = document.getElementById('admin-alerts-container');
        if (!alertContainer) {
            alertContainer = document.createElement('div');
            alertContainer.id = 'admin-alerts-container';
            alertContainer.style.position = 'fixed';
            alertContainer.style.top = '80px';
            alertContainer.style.right = '20px';
            alertContainer.style.zIndex = '9998';
            alertContainer.style.maxWidth = '500px';
            document.body.appendChild(alertContainer);
        }

        alertContainer.appendChild(alertDiv);

        // Auto-remove after 12 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 12000);
    }

    /**
     * Update badge showing number of alerts
     */
    updateAlertBadge(count) {
        const badge = document.getElementById('missing-teachers-badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    /**
     * Start polling for missing teachers
     */
    start() {
        console.log('Starting admin alert manager');
        
        // Check immediately
        this.fetchMissingTeachers();
        
        // Then check periodically
        setInterval(() => {
            this.fetchMissingTeachers();
        }, this.pollInterval);
    }

    /**
     * Stop polling
     */
    stop() {
        console.log('Stopping admin alert manager');
    }

    /**
     * Toggle sound alerts
     */
    toggleSound(enabled) {
        this.soundEnabled = enabled;
        localStorage.setItem('adminSoundEnabled', enabled);
        console.log('Admin sound alerts:', enabled ? 'enabled' : 'disabled');
    }

    /**
     * Toggle browser notifications
     */
    toggleNotifications(enabled) {
        if (enabled && Notification.permission === 'default') {
            this.requestNotificationPermission();
        }
        this.notificationEnabled = enabled && Notification.permission === 'granted';
        localStorage.setItem('adminNotificationEnabled', this.notificationEnabled);
        console.log('Admin browser notifications:', this.notificationEnabled ? 'enabled' : 'disabled');
    }
}

// Initialize on page load
let adminAlertManager = null;

document.addEventListener('DOMContentLoaded', function() {
    if (typeof BASE_PATH !== 'undefined' && typeof USER_ROLE !== 'undefined' && USER_ROLE === 'admin') {
        adminAlertManager = new AdminAlertManager();
        adminAlertManager.start();
    }
});
