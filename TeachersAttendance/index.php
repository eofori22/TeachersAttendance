<?php include 'includes/header.php'; ?>

<main id="main-content">
    <!-- Hero Section -->
    <section class="hero-section py-5" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <h1 class="display-4 fw-bold mb-4">Streamline Teacher Attendance with <span class="text-primary-gradient">Smart Tracking</span></h1>
                    <p class="lead mb-4">Our QR-code based system simplifies classroom attendance for teachers, class representatives, and administrators with real-time tracking and reporting.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <a href="login.php" class="btn btn-primary btn-lg px-4 py-3 shadow-sm">
                                <i class="fas fa-sign-in-alt me-2"></i> Get Started
                            </a>
                        <?php else: ?>
                            <a href="/index.php" class="btn btn-primary btn-lg px-4 py-3 shadow-sm">
                                <i class="fas fa-tachometer-alt me-2"></i> Go to Dashboard
                            </a>
                        <?php endif; ?>
                        <a href="#features" class="btn btn-outline-primary btn-lg px-4 py-3">
                            <i class="fas fa-info-circle me-2"></i> Learn More
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="position-relative">
                        <div class="ratio ratio-16x9 rounded-4 overflow-hidden shadow-lg">
                            <img src="/assets/img/attendance-demo.png" alt="Teacher Attendance System Interface" class="img-fluid object-fit-cover">
                        </div>
                        <div class="position-absolute top-0 start-0 w-100 h-100 bg-primary opacity-10 rounded-4"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-white">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-3">Key Features</h2>
                <p class="text-muted mx-auto" style="max-width: 700px;">Designed specifically for educational institutions to simplify teacher attendance management</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm hover-effect">
                        <div class="card-body text-center p-4">
                            <div class="icon-circle bg-primary bg-opacity-10 text-primary mb-4 mx-auto">
                                <i class="fas fa-qrcode fa-2x"></i>
                            </div>
                            <h5 class="fw-bold mb-3">QR Code Attendance</h5>
                            <p class="text-muted">Teachers check in/out by scanning unique QR codes with class representatives' devices.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm hover-effect">
                        <div class="card-body text-center p-4">
                            <div class="icon-circle bg-success bg-opacity-10 text-success mb-4 mx-auto">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                            <h5 class="fw-bold mb-3">Real-time Reports</h5>
                            <p class="text-muted">Administrators get instant access to attendance data and analytics.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm hover-effect">
                        <div class="card-body text-center p-4">
                            <div class="icon-circle bg-info bg-opacity-10 text-info mb-4 mx-auto">
                                <i class="fas fa-bell fa-2x"></i>
                            </div>
                            <h5 class="fw-bold mb-3">Automated Alerts</h5>
                            <p class="text-muted">Receive notifications for late arrivals or missed classes.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm hover-effect">
                        <div class="card-body text-center p-4">
                            <div class="icon-circle bg-warning bg-opacity-10 text-warning mb-4 mx-auto">
                                <i class="fas fa-mobile-alt fa-2x"></i>
                            </div>
                            <h5 class="fw-bold mb-3">Mobile Friendly</h5>
                            <p class="text-muted">Works seamlessly on all devices, including smartphones and tablets.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-5 bg-light">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-3">How It Works</h2>
                <p class="text-muted mx-auto" style="max-width: 700px;">Simple three-step process for efficient attendance tracking</p>
            </div>
            
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4">
                    <div class="step-card text-center p-4 position-relative">
                        <div class="step-number">1</div>
                        <div class="icon-circle-lg bg-primary bg-opacity-10 text-primary mx-auto mb-4">
                            <i class="fas fa-user-plus fa-2x"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Teacher Registration</h4>
                        <p class="text-muted">Administrators register teachers in the system and generate unique QR codes for each educator.</p>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="step-card text-center p-4 position-relative">
                        <div class="step-number">2</div>
                        <div class="icon-circle-lg bg-success bg-opacity-10 text-success mx-auto mb-4">
                            <i class="fas fa-camera fa-2x"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Classroom Check-in</h4>
                        <p class="text-muted">Class representatives scan the teacher's QR code when they arrive and leave the classroom.</p>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="step-card text-center p-4 position-relative">
                        <div class="step-number">3</div>
                        <div class="icon-circle-lg bg-info bg-opacity-10 text-info mx-auto mb-4">
                            <i class="fas fa-file-alt fa-2x"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Automated Reporting</h4>
                        <p class="text-muted">The system generates attendance reports and sends them to academic heads automatically.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-5 bg-white">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-3">What Our Users Say</h2>
                <p class="text-muted mx-auto" style="max-width: 700px;">Feedback from educators and administrators using our system</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <img src="/assets/img/testimonial1.jpg" class="rounded-circle" width="60" height="60" alt="Sarah Johnson">
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-1">Sarah Johnson</h5>
                                    <p class="text-muted small mb-0">Head of Academics</p>
                                    <div class="text-warning small">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mb-0">"This system has revolutionized how we track teacher attendance. The automated reports save me hours each week."</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <img src="/assets/img/testimonial2.jpg" class="rounded-circle" width="60" height="60" alt="Michael Chen">
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-1">Michael Chen</h5>
                                    <p class="text-muted small mb-0">Mathematics Teacher</p>
                                    <div class="text-warning small">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star-half-alt"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mb-0">"The QR code system is so convenient. No more paper sign-in sheets that always seemed to get lost."</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <img src="/assets/img/testimonial3.jpg" class="rounded-circle" width="60" height="60" alt="Priya Patel">
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-1">Nat</h5>
                                    <p class="text-muted small mb-0">Class Representative</p>
                                    <div class="text-warning small">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mb-0">"Scanning teachers in and out takes seconds, and I love that I can do it right from my phone."</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 bg-primary text-white">
        <div class="container py-5 text-center">
            <h2 class="fw-bold mb-4">Ready to Transform Your Attendance Tracking?</h2>
            <p class="lead mb-5 mx-auto" style="max-width: 700px;">Join dozens of schools already benefiting from our streamlined teacher attendance system.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class="btn btn-light btn-lg px-5 py-3 fw-bold">
                        <i class="fas fa-user-plus me-2"></i> Get Started Now
                    </a>
                    <a href="contact.php" class="btn btn-outline-light btn-lg px-5 py-3">
                        <i class="fas fa-envelope me-2"></i> Contact Us
                    </a>
                <?php else: ?>
                    <a href="./login.php" class="btn btn-light btn-lg px-5 py-3 fw-bold">
                        <i class="fas fa-tachometer-alt me-2"></i> Go to Dashboard
                    </a>
                    <a href="help.php" class="btn btn-outline-light btn-lg px-5 py-3">
                        <i class="fas fa-question-circle me-2"></i> Get Help
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<style>
/* Custom styles for index page */
.hero-section {
    position: relative;
    overflow: hidden;
}

.text-primary-gradient {
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

.icon-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-circle-lg {
    width: 80px;
    height: 80px;
}

.hover-effect {
    transition: all 0.3s ease;
}

.hover-effect:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
}

.step-card {
    background: white;
    border-radius: 12px;
}

.step-number {
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
}

.object-fit-cover {
    object-fit: cover;
    width: 100%;
    height: 100%;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .hero-section .col-lg-6:last-child {
        margin-top: 2rem;
    }
    
    .display-4 {
        font-size: 2.5rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>