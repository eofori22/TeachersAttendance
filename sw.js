// Service Worker for Teachers Attendance System
const CACHE_NAME = 'teachers-attendance-v1';
const urlsToCache = [
  '/',
  '/TeachersAttendance/class_rep/scan.php',
  '/TeachersAttendance/assets/css/style.css',
  '/TeachersAttendance/assets/js/qr-scanner.min.js',
  'https://cdn.jsdelivr.net/npm/qr-scanner@1.4.2/qr-scanner.min.js',
  'https://cdn.jsdelivr.net/npm/qr-scanner@1.4.2/qr-scanner-worker.min.js'
];

self.addEventListener('install', function(event) {
  // Perform install steps
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', function(event) {
  event.respondWith(
    caches.match(event.request)
      .then(function(response) {
        // Cache hit - return response
        if (response) {
          return response;
        }
        return fetch(event.request);
      }
    )
  );
});