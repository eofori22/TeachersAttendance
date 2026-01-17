        </main>
        
        <!-- Footer -->
        <footer class="text-white py-4 mt-auto" style="background: linear-gradient(135deg, #1c1c1c, #0f172a);">
            <div class="container">
                <div class="row g-4">
                    <div class="col-md-4">
                        <h5 class="fw-bold mb-3">Ompad STEM</h5>
                        <p>Teacher Attendance System designed to streamline classroom presence tracking and academic management.</p>
                        <div class="social-icons mt-3">
                            <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="text-white"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <h5 class="fw-bold mb-3">Quick Links</h5>
                        <ul class="nav flex-column">
                            <li class="nav-item mb-2"><a href="/" class="nav-link p-0 text-white-50">Home</a></li>
                            <li class="nav-item mb-2"><a href="/about" class="nav-link p-0 text-white-50">About</a></li>
                            <li class="nav-item mb-2"><a href="/contact" class="nav-link p-0 text-white-50">Contact</a></li>
                            <li class="nav-item mb-2"><a href="/privacy" class="nav-link p-0 text-white-50">Privacy Policy</a></li>
                        </ul>
                    </div>
                    
                    <div class="col-md-3">
                        <h5 class="fw-bold mb-3">Support</h5>
                        <ul class="nav flex-column">
                            <li class="nav-item mb-2"><a href="/help" class="nav-link p-0 text-white-50">Help Center</a></li>
                            <li class="nav-item mb-2"><a href="/faq" class="nav-link p-0 text-white-50">FAQs</a></li>
                            <li class="nav-item mb-2"><a href="/feedback" class="nav-link p-0 text-white-50">Feedback</a></li>
                            <li class="nav-item mb-2"><a href="/report" class="nav-link p-0 text-white-50">Report Issue</a></li>
                        </ul>
                    </div>
                    
                    <div class="col-md-3">
                        <h5 class="fw-bold mb-3">Contact</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> Abomosu Stem, School Campus</li>
                            <li class="mb-2"><i class="fas fa-phone me-2"></i> (233) 0543487450</li>
                            <li class="mb-2"><i class="fas fa-envelope me-2"></i> support@ompadschool.edu</li>
                        </ul>
                    </div>
                </div>
                
                <hr class="my-4 border-primary border-opacity-25">
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                    <div class="mb-3 mb-md-0">
                        <span class="text-white-50">&copy; <?php echo date('Y'); ?> Ompad STEM. All rights reserved.</span>
                    </div>
                    <div>
                        <a href="#" class="text-white-50 me-3 text-decoration-none">Terms of Service</a>
                        <a href="#" class="text-white-50 text-decoration-none">Privacy Policy</a>
                    </div>
                </div>
            </div>
        </footer>
        
        <!-- Back to Top Button -->
        <button type="button" class="btn btn-back-to-top shadow rounded-circle" style="background: #0066ff; color: white;" aria-label="Back to top">
            <i class="fas fa-arrow-up"></i>
        </button>
        
        <!-- Bootstrap Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- Custom JavaScript -->
        <?php 
        // Get base path (same calculation as in header.php)
        $script_name = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
        $script_dir = dirname($script_name);
        // Check if we're in the TeachersAttendance directory
        if (strpos($script_dir, '/TeachersAttendance') !== false || strpos($script_dir, 'TeachersAttendance') !== false) {
            $base_path = '/TeachersAttendance';
        } elseif ($script_dir === '/' || $script_dir === '\\' || $script_dir === '.') {
            $base_path = '';
        } else {
            // Extract the project root from the script directory
            $parts = explode('/', trim($script_dir, '/'));
            if (in_array('TeachersAttendance', $parts)) {
                $base_path = '/TeachersAttendance';
            } else {
                $base_path = $script_dir;
                $base_path = str_replace('\\', '/', $base_path);
                if ($base_path !== '' && $base_path !== '/') {
                    $base_path = rtrim($base_path, '/');
                }
            }
        }
        ?>
        <script src="<?php echo $base_path; ?>/assets/js/main.js"></script>
        
        <!-- Theme and UI Scripts -->
        <script>
            // Theme toggle functionality
            document.addEventListener('DOMContentLoaded', function() {
                const storedTheme = localStorage.getItem('theme') || 'light';
                const themeIcons = document.querySelectorAll('[data-theme-icon]');
                
                // Set initial theme icon
                themeIcons.forEach(icon => {
                    if (icon.getAttribute('data-theme-icon') === storedTheme) {
                        icon.classList.remove('d-none');
                    }
                });
                
                // Theme toggle button
                const themeToggle = document.querySelector('.theme-toggle');
                if (themeToggle) {
                    themeToggle.addEventListener('click', function() {
                    const currentTheme = document.documentElement.getAttribute('data-bs-theme');
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    
                    document.documentElement.setAttribute('data-bs-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                    
                    // Toggle icons
                    themeIcons.forEach(icon => {
                        icon.classList.toggle('d-none');
                    });
                });
                }
                
                // Back to top button
                const backToTopButton = document.querySelector('.btn-back-to-top');
                if (backToTopButton) {
                    window.addEventListener('scroll', function() {
                        if (window.pageYOffset > 300) {
                            backToTopButton.classList.add('show');
                        } else {
                            backToTopButton.classList.remove('show');
                        }
                    });
                    
                    backToTopButton.addEventListener('click', function() {
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    });
                }
            });
        </script>
    </body>
</html>