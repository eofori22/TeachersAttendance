# Admin Class Alert Notification System

This system automatically alerts the admin when teachers haven't checked in for classes that are due/starting through:
- **Sound Alerts**: 3 escalating beeps using Web Audio API
- **Browser Notifications**: Desktop/mobile push notifications
- **Page Alerts**: Urgent toast-like alerts displayed on the page

## Features

### Automatic Class Monitoring
The system polls every 30 seconds to check for:
- **Classes that just started** (0-5 min): Yellow warning alert
- **Classes in progress** (5-30 min): Yellow warning alert  
- **Late classes** (30+ min): Red danger alert

Admin gets **immediate notifications** if a teacher hasn't checked in.

### Customizable Settings
Admins can enable/disable alerts from the sidebar on their dashboard:
- **Browser Alerts**: Toggle desktop/mobile notifications (requires browser permission)
- **Sound Beep**: Toggle 3-beep alert sound

Preferences are saved in browser localStorage and persist across sessions.

## How It Works

### Backend
- **API Endpoint**: `/api/admin_class_alerts.php`
  - Fetches all teacher assignments for today
  - Gets current time and day
  - Checks if each class has started
  - Cross-references with attendance records
  - Returns list of missing teachers
  - Calculates minutes overdue

### Frontend
- **JavaScript Manager**: `/assets/js/admin-alerts.js`
  - Manages notification state and preferences
  - Polls API every 30 seconds
  - Generates 3-tone alert sound using Web Audio API
  - Sends browser notifications with teacher/class details
  - Prevents duplicate alerts (tracks alerted classes)
  - Displays urgent page alerts
  - Updates missing teacher badge count

### Integration
- Auto-loads only for admin accounts via `/includes/header.php`
- Requires `BASE_PATH` and `USER_ROLE` globals (set in header)
- Alert toggles in `/admin/dashboard.php` sidebar
- Missing teachers badge shows count of current alerts

## Alert Levels

### 1. Sound Beep
- 3 escalating beeps (1000Hz, 1150Hz, 1300Hz)
- Duration: ~500ms total
- Can be disabled via toggle
- Uses browser's Web Audio API
- Plays immediately when teacher is detected missing

### 2. Browser Notification
- Desktop/mobile OS notifications
- Requires browser permission (requested on first page load)
- Shows teacher name, subject, class name, room number
- Shows time class started
- Click to focus window
- Auto-closes after 15 seconds
- Requires interaction to persist

### 3. Page Alert
- Bootstrap alert toast at top-right of page
- Color-coded by urgency:
  - **Yellow** (alert-warning): Class just started or in progress
  - **Red** (alert-danger): Teacher is late (30+ min)
- Shows teacher name, subject, class details
- Shows minutes overdue
- Dismissible
- Auto-closes after 12 seconds

## Permission Handling

### Browser Notifications
The system will:
1. Check if browser supports notifications (`'Notification' in window`)
2. Request permission on first load (if not already granted)
3. Only send notifications if permission is 'granted'
4. Gracefully disable if permission is 'denied'

### Audio
Sound alerts use Web Audio API (doesn't require special permissions).

## Data Flow

1. **API Query**: Fetches today's assignments and attendance
2. **Comparison**: For each assignment, checks if attendance record exists
3. **Filtering**: Only alerts for classes that have started
4. **Deduplication**: Tracks alerted assignments to prevent repeats
5. **Alert**: Plays sound → Shows notification → Shows page alert

## Alert Criteria

Teachers are considered **missing** when:
- ✅ Class is assigned for today
- ✅ Current day matches assignment day (Mon-Sun)
- ✅ Class start time has passed
- ✅ No attendance record exists for teacher+class+date combination

## Example Response

```json
{
  "success": true,
  "current_time": "09:35:00",
  "today": "MON",
  "missing_teachers": [
    {
      "assignment_id": 5,
      "class_id": 3,
      "teacher_id": 12,
      "teacher_name": "John Smith",
      "class_name": "Grade 10-A",
      "subject_name": "Mathematics",
      "start_time": "09:30:00",
      "end_time": "10:15:00",
      "start_time_formatted": "09:30 AM",
      "end_time_formatted": "10:15 AM",
      "room_number": "101",
      "minutes_overdue": 5,
      "status": "just_started"
    }
  ],
  "count": 1
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
- Modern browser with Web Audio API support

## Notification Badge

The "Class Alerts" card in the sidebar shows:
- **Badge with count**: Number of currently missing teachers
- **Hidden when 0**: No missing teachers = no badge display
- **Updates every 30 seconds**: Real-time monitoring

## Testing

To test the alert system:

1. Log in as admin
2. Navigate to Dashboard
3. Check notification toggles in sidebar
4. Create a teacher assignment for the current time
5. Wait for next polling cycle (30 seconds) or refresh
6. If teacher hasn't checked in, you'll see:
   - 3 beeps
   - Browser notification
   - Page alert with details
   - Badge count incremented

## Troubleshooting

### Alerts not appearing
- Check browser notification permission settings
- Ensure `Browser Alerts` toggle is checked in dashboard
- Check browser console for errors
- Verify teacher hasn't already checked in for the class

### No sound alerts
- Check `Sound Beep` toggle in dashboard
- Test with: `adminAlertManager.playAlertSound()`
- Some browsers may mute by default

### Classes not detected
- Verify teacher is assigned to class for today
- Check class start time has passed
- Verify attendance record doesn't already exist
- Check server timezone matches client timezone

## Files Added/Modified

- **Created**: `/api/admin_class_alerts.php`
- **Created**: `/assets/js/admin-alerts.js`
- **Modified**: `/includes/header.php` (added admin alert script)
- **Modified**: `/admin/dashboard.php` (added alert settings + JS handlers)

## Performance

- **Poll Interval**: 30 seconds (configurable)
- **Alert Sound**: ~500ms duration
- **Notification**: 15s timeout
- **Page Alert**: 12s timeout
- **Memory**: Alert tracking uses Set (efficient memory usage)

## Security

- ✅ Admin role verification in API
- ✅ Session-based authentication
- ✅ No sensitive data in notifications
- ✅ Proper error handling
- ✅ Input validation

## Future Enhancements

- [ ] Custom polling interval setting
- [ ] Snooze alerts feature
- [ ] Email notifications
- [ ] SMS alerts
- [ ] Attendance reconciliation report
- [ ] Teacher absence patterns analysis
