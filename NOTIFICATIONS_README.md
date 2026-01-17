# Teacher Class Notification System

This system automatically notifies teachers when their classes are starting through:
- **Sound Alerts**: Audible "beep" notifications using Web Audio API
- **Browser Notifications**: Desktop/mobile push notifications
- **Page Alerts**: Toast-like alerts displayed on the page

## Features

### Automatic Notifications
The system polls every 60 seconds to check for upcoming classes and will notify when:
- **Class Starting Soon** (within 5 minutes): Urgent notification with sound + visual alert
- **Upcoming Class** (10-15 minutes before): Warning notification with sound
- **Class In Progress**: Informational notification when class starts

### Customizable Settings
Teachers can enable/disable notifications from the sidebar on their dashboard:
- **Browser Alerts**: Toggle desktop/mobile notifications (requires browser permission)
- **Sound Beep**: Toggle audio alert notifications

Preferences are saved in browser localStorage and persist across sessions.

## How It Works

### Backend
- **API Endpoint**: `/api/teacher_upcoming_classes.php`
  - Fetches teacher's assigned classes for today
  - Compares class start times with current time
  - Returns classes within notification window (30 min before to 2 hours after)
  - Calculates minutes until each class starts

### Frontend
- **JavaScript Manager**: `/assets/js/teacher-notifications.js`
  - Manages notification state and preferences
  - Polls API every 60 seconds
  - Generates sound alerts using Web Audio API
  - Sends browser notifications with proper formatting
  - Prevents duplicate notifications (tracks notified classes)
  - Displays toast-like alerts on the page

### Integration
- Auto-loads only for teacher accounts via `/includes/header.php`
- Requires `BASE_PATH` and `USER_ROLE` globals (set in header)
- Notification toggles in `/teacher/dashboard.php` sidebar

## Notification Levels

### 1. Sound Beep
- Two-tone beep (800Hz + 900Hz)
- Duration: ~250ms total
- Can be disabled via toggle
- Uses browser's Web Audio API

### 2. Browser Notification
- Desktop/mobile OS notifications
- Requires browser permission (requested on first page load)
- Shows class name, time, and room number
- Click to focus window
- Auto-closes after 10 seconds
- Requires interaction to persist

### 3. Page Alert
- Bootstrap alert toast at top-right of page
- Color-coded by urgency (danger/warning/success)
- Shows class details
- Dismissible
- Auto-closes after 8 seconds

## Permission Handling

### Browser Notifications
The system will:
1. Check if browser supports notifications (`'Notification' in window`)
2. Request permission on first load (if not already granted)
3. Only send notifications if permission is 'granted'
4. Gracefully disable if permission is 'denied'

### Microphone/Audio
Sound alerts use Web Audio API (doesn't require special permissions).

## Class Data Retrieved

Each notification includes:
- Subject name (or class name if no subject)
- Class name
- Start time (formatted)
- End time (formatted)
- Room number (if applicable)
- Minutes until class starts

## Example Response

```json
{
  "success": true,
  "current_time": "09:15:30",
  "today": "Mon",
  "classes": [
    {
      "assignment_id": 5,
      "class_id": 3,
      "subject_name": "Mathematics",
      "class_name": "Grade 10-A",
      "day_of_week": "Mon",
      "start_time": "09:30:00",
      "end_time": "10:15:00",
      "start_time_formatted": "09:30 AM",
      "end_time_formatted": "10:15 AM",
      "room_number": "101",
      "minutes_until": 15,
      "is_starting_soon": false,
      "is_ongoing": false,
      "status": "upcoming"
    }
  ]
}
```

## Browser Support

Supported browsers:
- Chrome/Chromium (desktop & mobile)
- Firefox (desktop & mobile)
- Safari (macOS 13+, iOS 13+)
- Edge (desktop)
- Opera

Requirements:
- HTTPS or localhost for notifications
- Microphone access (optional, for sound verification)

## Testing

To test the notification system:

1. Log in as a teacher
2. Navigate to Dashboard
3. Open browser dev console
4. Check notification toggles in sidebar
5. Create a class assignment starting soon
6. Wait for next polling cycle (60 seconds) or test API directly:

```bash
curl http://localhost/TeachersAttendance/api/teacher_upcoming_classes.php
```

## Troubleshooting

### Notifications not appearing
- Check browser notification permission settings
- Ensure `notificationEnabled` is checked in dashboard
- Check browser console for errors
- Verify teacher has classes assigned for today

### No sound alerts
- Check `soundEnabled` toggle in dashboard
- Test with: `notificationManager.playNotificationSound()`
- Some browsers may mute by default

### Classes not detected
- Verify class is assigned to teacher in database
- Check class start time is within 30 min - 2 hours
- Verify server timezone matches client timezone

## Files Added/Modified

- **Created**: `/api/teacher_upcoming_classes.php`
- **Created**: `/assets/js/teacher-notifications.js`
- **Modified**: `/includes/header.php` (added script tag)
- **Modified**: `/teacher/dashboard.php` (added notification settings card + JS)
