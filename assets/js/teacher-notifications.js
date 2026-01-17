/**
 * Teacher Class Notification System
 * Sends browser notifications and sound alerts when classes are upcoming
 */

class TeacherNotificationManager {
    constructor() {
        this.pollInterval = 60000; // Poll every 60 seconds
        this.notifiedClasses = new Set(); // Track which classes we've notified
        this.soundEnabled = localStorage.getItem('soundEnabled') !== 'false'; // Default true
        this.notificationEnabled = localStorage.getItem('notificationEnabled') !== 'false'; // Default true
        this.lastCheck = null;
        
        // Check browser support
        this.checkBrowserSupport();
        
        // Request notification permission on init
        if (this.notificationEnabled) {
            this.requestNotificationPermission();
        }
    }

    checkBrowserSupport() {
        // Check notification support
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
                    console.log('Notification permission granted');
                } else {
                    this.notificationEnabled = false;
                    console.warn('Notification permission denied');
                }
            });
        }
    }

    /**
     * Play a notification sound (beep)
     */
    playNotificationSound() {
        if (!this.soundEnabled) return;

        // Use Web Audio API to generate a beep sound
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // Create oscillator for the beep
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            // Beep pattern: 2 short beeps
            oscillator.frequency.value = 800; // Frequency in Hz
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.1);
            
            // Second beep
            setTimeout(() => {
                const osc2 = audioContext.createOscillator();
                const gain2 = audioContext.createGain();
                
                osc2.connect(gain2);
                gain2.connect(audioContext.destination);
                
                osc2.frequency.value = 900;
                osc2.type = 'sine';
                
                gain2.gain.setValueAtTime(0.3, audioContext.currentTime);
                gain2.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
                
                osc2.start(audioContext.currentTime);
                osc2.stop(audioContext.currentTime + 0.1);
            }, 150);
        } catch (error) {
            console.warn('Could not play notification sound:', error);
        }
    }

    /**
     * Send a browser notification
     */
    sendBrowserNotification(title, options = {}) {
        if (!this.notificationEnabled || Notification.permission !== 'granted') {
            return;
        }

        try {
            const notification = new Notification(title, {
                icon: '/assets/img/robot.svg',
                badge: '/assets/img/robot.svg',
                tag: 'class-notification',
                requireInteraction: true, // Keep notification until user interacts
                ...options
            });

            notification.onclick = () => {
                window.focus();
                notification.close();
            };

            // Auto-close after 10 seconds
            setTimeout(() => notification.close(), 10000);
        } catch (error) {
            console.error('Error sending notification:', error);
        }
    }

    /**
     * Fetch upcoming classes from API
     */
    async fetchUpcomingClasses() {
        try {
            const response = await fetch(`${BASE_PATH}/api/teacher_upcoming_classes.php`);
            const data = await response.json();
            
            if (data.success) {
                this.processClasses(data.classes);
            }
        } catch (error) {
            console.error('Error fetching upcoming classes:', error);
        }
    }

    /**
     * Process classes and send notifications
     */
    processClasses(classes) {
        classes.forEach(classItem => {
            const classKey = `${classItem.class_id}_${classItem.assignment_id}`;
            const minutesUntil = Math.round(classItem.minutes_until);

            // Notify about classes starting soon (within 5 minutes)
            if (classItem.is_starting_soon && !this.notifiedClasses.has(classKey)) {
                this.notifiedClasses.add(classKey);
                this.playNotificationSound();
                
                this.sendBrowserNotification(
                    `⏰ Class Starting Now!`,
                    {
                        body: `${classItem.subject_name} (${classItem.class_name}) is starting at ${classItem.start_time_formatted}${classItem.room_number ? ' in Room ' + classItem.room_number : ''}`,
                        tag: `class-${classKey}`
                    }
                );

                // Also show toast/alert on page
                this.showPageAlert(classItem, 'starting');
            }

            // Notify about upcoming classes (10-15 minutes before)
            else if (minutesUntil <= 15 && minutesUntil > 5 && !this.notifiedClasses.has(`upcoming_${classKey}`)) {
                this.notifiedClasses.add(`upcoming_${classKey}`);
                this.playNotificationSound();
                
                this.sendBrowserNotification(
                    `📚 Upcoming Class`,
                    {
                        body: `${classItem.subject_name} (${classItem.class_name}) starts in ${minutesUntil} minutes at ${classItem.start_time_formatted}`,
                        tag: `upcoming-${classKey}`
                    }
                );

                this.showPageAlert(classItem, 'upcoming');
            }

            // Notify about ongoing classes
            else if (classItem.is_ongoing && !this.notifiedClasses.has(`ongoing_${classKey}`)) {
                this.notifiedClasses.add(`ongoing_${classKey}`);
                this.playNotificationSound();
                
                this.sendBrowserNotification(
                    `✅ Class In Progress`,
                    {
                        body: `${classItem.subject_name} (${classItem.class_name}) is now in progress until ${classItem.end_time_formatted}`,
                        tag: `ongoing-${classKey}`
                    }
                );

                this.showPageAlert(classItem, 'ongoing');
            }
        });
    }

    /**
     * Show alert on the page (toast-like)
     */
    showPageAlert(classItem, status) {
        const alertDiv = document.createElement('div');
        const statusText = {
            'starting': '⏰ Class Starting Now!',
            'upcoming': `📚 Upcoming in ${Math.round(classItem.minutes_until)} min`,
            'ongoing': '✅ Class In Progress'
        };

        const statusClass = {
            'starting': 'alert-danger',
            'upcoming': 'alert-warning',
            'ongoing': 'alert-success'
        };

        alertDiv.className = `alert ${statusClass[status] || 'alert-info'} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <strong>${statusText[status]}</strong><br>
            <small>${classItem.subject_name} (${classItem.class_name})</small>
            ${classItem.room_number ? `<br><small>Room: ${classItem.room_number}</small>` : ''}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Find or create alert container
        let alertContainer = document.getElementById('class-notifications-container');
        if (!alertContainer) {
            alertContainer = document.createElement('div');
            alertContainer.id = 'class-notifications-container';
            alertContainer.style.position = 'fixed';
            alertContainer.style.top = '20px';
            alertContainer.style.right = '20px';
            alertContainer.style.zIndex = '9999';
            alertContainer.style.maxWidth = '400px';
            document.body.appendChild(alertContainer);
        }

        alertContainer.appendChild(alertDiv);

        // Auto-remove after 8 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 8000);
    }

    /**
     * Start polling for upcoming classes
     */
    start() {
        console.log('Starting teacher notification manager');
        
        // Check immediately
        this.fetchUpcomingClasses();
        
        // Then check periodically
        setInterval(() => {
            this.fetchUpcomingClasses();
        }, this.pollInterval);
    }

    /**
     * Stop polling
     */
    stop() {
        console.log('Stopping teacher notification manager');
    }

    /**
     * Toggle sound notifications
     */
    toggleSound(enabled) {
        this.soundEnabled = enabled;
        console.log('Sound notifications:', enabled ? 'enabled' : 'disabled');
    }

    /**
     * Toggle browser notifications
     */
    toggleNotifications(enabled) {
        if (enabled && Notification.permission === 'default') {
            this.requestNotificationPermission();
        }
        this.notificationEnabled = enabled && Notification.permission === 'granted';
        console.log('Browser notifications:', this.notificationEnabled ? 'enabled' : 'disabled');
    }
}

// Initialize on page load
let notificationManager = null;

document.addEventListener('DOMContentLoaded', function() {
    if (typeof BASE_PATH !== 'undefined' && typeof USER_ROLE !== 'undefined' && USER_ROLE === 'teacher') {
        notificationManager = new TeacherNotificationManager();
        notificationManager.start();
    }
});
