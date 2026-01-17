# QR Scanner Mobile Support - Android & iOS

## Overview
The QR code scanner has been enhanced with full compatibility for Android and iOS devices. The scanner now includes proper device detection, better error handling, and fallback options for all mobile platforms.

## Changes Made

### 1. **Video Element Improvements**
- Added `webkit-playsinline` and `x5-playsinline` attributes for better mobile support
- These attributes ensure the video element displays properly in mobile browsers instead of going fullscreen

### 2. **Mobile-Specific Camera Constraints**
- Implemented device detection to apply appropriate camera constraints
- Android: Optimal resolution of 1280x720
- iOS: Adaptive constraints with ideal facing mode
- Desktop: Higher resolution support (1920x1080)

### 3. **Enhanced Error Handling**
- iOS-specific error messages with actionable steps
- Android-specific error messages with actionable steps
- Improved detection of common issues:
  - Permission denied
  - Camera not found
  - Camera in use by another app
  - HTTPS requirement
  - Browser compatibility

### 4. **Browser Polyfills**
- Added polyfills for older browsers that don't support modern APIs
- `getUserMedia` polyfill for webkit and Mozilla implementations
- Fallback for devices that don't support `enumerateDevices`

### 5. **Mobile Meta Tags**
- Added `viewport-fit=cover` for notch-aware layouts
- Added `apple-mobile-web-app-capable` for better iOS integration
- Proper safe area inset handling for devices with notches

### 6. **Improved Mobile CSS**
- Prevented zoom on input focus (16px minimum on iOS)
- Added overscroll behavior prevention
- Disabled user-select on video and buttons to prevent drag issues
- Proper safe area padding for notched devices

## Browser Support

### iOS
✅ Safari (recommended - most recent version)
✅ Chrome for iOS
✅ Firefox for iOS
⚠️ Third-party browsers may have limitations

**Important Note**: iOS restricts camera access to Safari and apps with embedded browsers. Other browsers may show limited functionality.

### Android
✅ Chrome
✅ Firefox
✅ Edge
✅ Samsung Internet
✅ Opera

## Setup Instructions

### For iOS Users
1. **Use Safari**: Camera access works best in Safari
2. **Grant Permissions**: When prompted, tap "Allow" to grant camera access
3. **Check Settings**: Go to Settings > Privacy > Camera and ensure the browser has permission
4. **Use HTTPS**: For best results, access the site via HTTPS
5. **Orientation**: Hold device in portrait orientation for best scanning
6. **Lighting**: Ensure adequate lighting for QR code detection

### For Android Users
1. **Check Permissions**: Open Settings > Apps > [Your Browser] > Permissions > Camera
2. **Enable Camera**: Ensure camera permission is enabled for the browser
3. **Check App Storage**: Ensure the browser has storage permission (sometimes needed for camera)
4. **HTTPS Access**: Access the site via HTTPS when possible
5. **Clear Cache**: If camera doesn't work, try clearing the browser cache and restarting
6. **Try Different Browser**: If issues persist, try Chrome or Firefox

## Troubleshooting

### Camera Won't Open

**On iOS:**
- Go to Settings > Privacy > Camera
- Scroll down and check if Safari has permission
- If not listed, allow the website to request permission
- Close Safari completely and reopen the page
- Try restarting your device
- Ensure iOS is updated to the latest version

**On Android:**
- Open Settings > Apps > [Your Browser] > Permissions
- Enable Camera permission
- Clear app cache: Settings > Apps > [Your Browser] > Storage > Clear Cache
- Restart the browser
- Try a different browser (Chrome recommended)

### "HTTPS Required" Message

- The site appears to be accessed over HTTP
- Ensure you're using HTTPS (look for 🔒 icon in address bar)
- On local networks, HTTP is allowed for 192.168.x.x and 10.x.x.x ranges

### "No Camera Found"

- Ensure your device has a camera
- Check that no other app is using the camera
- Restart your device
- Try closing all other camera apps

### QR Code Not Scanning

- Ensure the QR code is in good condition
- Provide adequate lighting
- Hold the device 6-12 inches from the QR code
- Move slowly for better focus
- Ensure the camera lens is clean

### Manual Entry Alternative

If the camera doesn't work, use the **Manual Entry** section:
1. Scan the QR code with your phone's camera app or another scanner
2. Copy the scanned code
3. Paste it into the "Manual QR Code Entry" field
4. Press Enter or click Submit

## Technical Details

### Device Detection
The scanner automatically detects iOS and Android devices and applies:
- Appropriate error messages
- Correct camera constraints
- Platform-specific hints and guidance

### Fallback Options
1. **Manual QR Entry**: Always available as a backup
2. **Browser Support Check**: Validates browser compatibility before attempting camera access
3. **Error Recovery**: Detailed error messages with retry options

### Security
- Camera access requires explicit user permission
- HTTPS is recommended for production environments
- All camera operations are local to the device (no data sent to servers for processing)

## Device Recommendations

### Ideal Devices
- iPhone 12+, iPad Air 3+
- Samsung Galaxy S10+
- Google Pixel 5+
- Modern tablets with cameras

### Minimum Requirements
- iOS 12+ with Safari
- Android 6+ with Chrome/Firefox
- Devices with rear-facing cameras
- Adequate camera resolution (minimum 5MP recommended)

## Performance Tips

1. **Lighting**: Good lighting is essential for QR code detection
2. **Focus**: Hold device steady for auto-focus to work
3. **Distance**: Keep QR code 6-12 inches from camera
4. **Clean Lens**: Ensure camera lens is clean
5. **Orientation**: Portrait orientation works best on mobile
6. **Processor Load**: Close other apps for better performance

## Known Limitations

1. **iOS Safari**: Some older iOS versions may have limitations with fullscreen requests
2. **Android Chrome**: Some older Android versions may require permissions restart
3. **Tablets**: Large devices may have dual cameras - the app uses the rear camera by default
4. **Low Light**: QR detection is slower in dim lighting
5. **Screen Reflections**: Reflective surfaces may interfere with scanning

## Support & Fallback

If all else fails:
1. Use the **Manual QR Code Entry** feature
2. Scan with phone's built-in camera and paste the code
3. This feature is always available and doesn't require camera permissions

---

## For Developers

### Modifications Made
- `class_rep/scan.php`: Enhanced with mobile detection and improved error handling
- Video element: Added webkit-specific attributes
- Camera constraints: Applied based on device type
- Error messages: Platform-specific guidance included
- Polyfills: Added for older browser compatibility

### Testing Recommendations
1. Test on actual iOS devices (simulator may not work correctly)
2. Test on various Android devices with different Chrome versions
3. Test on different browsers: Safari, Chrome, Firefox, Edge
4. Test with HTTPS and HTTP
5. Test permission denial scenarios
6. Test with poor lighting conditions
7. Test manual entry as fallback
