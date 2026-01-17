<?php include 'includes/header.php'; ?>

<main id="main-content">
    <!-- Hero Section -->
    <section class="hero-section py-5" style="background: linear-gradient(135deg, rgba(0, 102, 255, 0.1) 0%, rgba(28, 28, 28, 0.1) 100%); position: relative; overflow: hidden;">
        <!-- Animated Background Elements -->
        <div class="animated-bg-elements">
            <div class="floating-blob blob-1"></div>
            <div class="floating-blob blob-2"></div>
            <div class="floating-blob blob-3"></div>
        </div>
        
        <div class="container py-5 position-relative z-1">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0 hero-content">
                    <div class="fade-in-up">
                        <span class="badge bg-primary-light text-primary mb-3 animate-pulse">
                            <i class="fas fa-zap me-1"></i> Modern Attendance Solution
                        </span>
                        <h1 class="display-4 fw-bold mb-4 hero-title">Streamline Teacher Attendance with <span class="text-primary-gradient">Smart Tracking</span></h1>
                        <p class="lead mb-4 text-muted">Our QR-code based system simplifies classroom attendance for teachers, class representatives, and administrators with real-time tracking and reporting.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-3 animate-fade-in-delay">
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <a href="login.php" class="btn btn-primary btn-lg px-4 py-3 shadow-sm btn-hover-glow">
                                <i class="fas fa-sign-in-alt me-2"></i> Get Started
                            </a>
                        <?php else: ?>
                            <a href="/index.php" class="btn btn-primary btn-lg px-4 py-3 shadow-sm btn-hover-glow">
                                <i class="fas fa-tachometer-alt me-2"></i> Go to Dashboard
                            </a>
                        <?php endif; ?>
                        <a href="#features" class="btn btn-outline-primary btn-lg px-4 py-3 btn-hover-outline">
                            <i class="fas fa-info-circle me-2"></i> Learn More
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 hero-image">
                    <div class="position-relative hero-image-wrapper">
                        <div class="floating-card">
                            <div class="ratio ratio-16x9 rounded-4 overflow-hidden shadow-lg">
                                <img src="<?php echo isset($base_path) ? $base_path : '/TeachersAttendance'; ?>/assets/img/attendance-demo.png" alt="Teacher Attendance System Interface" class="img-fluid object-fit-cover">
                            </div>
                        </div>
                        <div class="glow-effect"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-white position-relative">
        <div class="container py-5">
            <div class="text-center mb-5 section-header">
                <span class="badge bg-light-primary text-primary mb-3 animate-bounce">
                    <i class="fas fa-star me-1"></i> Features
                </span>
                <h2 class="fw-bold mb-3">Key Features</h2>
                <p class="text-muted mx-auto" style="max-width: 700px;">Designed specifically for educational institutions to simplify teacher attendance management</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-3 feature-card-wrapper">
                    <div class="card h-100 border-0 shadow-sm hover-effect feature-card">
                        <div class="card-body text-center p-4">
                            <div class="icon-circle bg-primary bg-opacity-10 text-primary mb-4 mx-auto icon-animate">
                                <i class="fas fa-qrcode fa-2x"></i>
                            </div>
                            <h5 class="fw-bold mb-3">QR Code Attendance</h5>
                            <p class="text-muted">Teachers check in/out by scanning unique QR codes with class representatives' devices.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 feature-card-wrapper">
                    <div class="card h-100 border-0 shadow-sm hover-effect feature-card">
                        <div class="card-body text-center p-4">
                            <div class="icon-circle bg-success bg-opacity-10 text-success mb-4 mx-auto icon-animate">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                            <h5 class="fw-bold mb-3">Real-time Reports</h5>
                            <p class="text-muted">Administrators get instant access to attendance data and analytics.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 feature-card-wrapper">
                    <div class="card h-100 border-0 shadow-sm hover-effect feature-card">
                        <div class="card-body text-center p-4">
                            <div class="icon-circle bg-info bg-opacity-10 text-info mb-4 mx-auto icon-animate">
                                <i class="fas fa-bell fa-2x"></i>
                            </div>
                            <h5 class="fw-bold mb-3">Automated Alerts</h5>
                            <p class="text-muted">Receive notifications for late arrivals or missed classes.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 feature-card-wrapper">
                    <div class="card h-100 border-0 shadow-sm hover-effect feature-card">
                        <div class="card-body text-center p-4">
                            <div class="icon-circle bg-warning bg-opacity-10 text-warning mb-4 mx-auto icon-animate">
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
    <section class="py-5 bg-light position-relative">
        <div class="container py-5">
            <div class="text-center mb-5 section-header">
                <span class="badge bg-light-success text-success mb-3 animate-bounce">
                    <i class="fas fa-rocket me-1"></i> Process
                </span>
                <h2 class="fw-bold mb-3">How It Works</h2>
                <p class="text-muted mx-auto" style="max-width: 700px;">Simple three-step process for efficient attendance tracking</p>
            </div>
            
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4 step-card-wrapper">
                    <div class="step-card text-center p-4 position-relative step-card-animate">
                        <div class="step-number">1</div>
                        <div class="icon-circle-lg bg-primary bg-opacity-10 text-primary mx-auto mb-4 icon-animate">
                            <i class="fas fa-user-plus fa-2x"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Teacher Registration</h4>
                        <p class="text-muted">Administrators register teachers in the system and generate unique QR codes for each educator.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 step-card-wrapper">
                    <div class="step-card text-center p-4 position-relative step-card-animate">
                        <div class="step-number">2</div>
                        <div class="icon-circle-lg bg-success bg-opacity-10 text-success mx-auto mb-4 icon-animate">
                            <i class="fas fa-camera fa-2x"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Classroom Check-in</h4>
                        <p class="text-muted">Class representatives scan the teacher's QR code when they arrive and leave the classroom.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 step-card-wrapper">
                    <div class="step-card text-center p-4 position-relative step-card-animate">
                        <div class="step-number">3</div>
                        <div class="icon-circle-lg bg-info bg-opacity-10 text-info mx-auto mb-4 icon-animate">
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
            <div class="text-center mb-5 section-header">
                <span class="badge bg-light-warning text-warning mb-3 animate-bounce">
                    <i class="fas fa-quote-left me-1"></i> Testimonials
                </span>
                <h2 class="fw-bold mb-3">What Our Users Say</h2>
                <p class="text-muted mx-auto" style="max-width: 700px;">Feedback from educators and administrators using our system</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4 testimonial-card-wrapper">
                    <div class="card h-100 border-0 shadow-sm testimonial-card hover-effect">
                        <div class="card-body p-4">
                            <div class="rating-stars mb-3">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                            </div>
                            <p class="mb-4 text-muted">"This system has revolutionized how we track teacher attendance. The automated reports save me hours each week."</p>
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-circle bg-primary text-white">SJ</div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1 fw-bold">Sarah Johnson</h6>
                                    <p class="text-muted small mb-0">Head of Academics</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 testimonial-card-wrapper">
                    <div class="card h-100 border-0 shadow-sm testimonial-card hover-effect">
                        <div class="card-body p-4">
                            <div class="rating-stars mb-3">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star-half-alt text-warning"></i>
                            </div>
                            <p class="mb-4 text-muted">"The QR code system is so convenient. No more paper sign-in sheets that always seemed to get lost."</p>
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-circle bg-success text-white">MC</div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1 fw-bold">Michael Chen</h6>
                                    <p class="text-muted small mb-0">Mathematics Teacher</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 testimonial-card-wrapper">
                    <div class="card h-100 border-0 shadow-sm testimonial-card hover-effect">
                        <div class="card-body p-4">
                            <div class="rating-stars mb-3">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                            </div>
                            <p class="mb-4 text-muted">"Scanning teachers in and out takes seconds, and I love that I can do it right from my phone."</p>
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-circle bg-info text-white">NP</div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1 fw-bold">Nat Patel</h6>
                                    <p class="text-muted small mb-0">Class Representative</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 bg-primary text-white position-relative overflow-hidden">
        <div class="cta-animated-bg"></div>
        <div class="container py-5 text-center position-relative z-1">
            <div class="fade-in-up">
                <h2 class="fw-bold mb-4 cta-title">Ready to Transform Your Attendance Tracking?</h2>
                <p class="lead mb-5 mx-auto" style="max-width: 700px;">Join dozens of schools already benefiting from our streamlined teacher attendance system.</p>
                <div class="d-flex flex-wrap justify-content-center gap-3 cta-buttons">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="login.php" class="btn btn-light btn-lg px-5 py-3 fw-bold btn-hover-scale">
                            <i class="fas fa-user-plus me-2"></i> Get Started Now
                        </a>
                        <a href="contact.php" class="btn btn-outline-light btn-lg px-5 py-3 btn-hover-scale">
                            <i class="fas fa-envelope me-2"></i> Contact Us
                        </a>
                    <?php else: ?>
                        <a href="./login.php" class="btn btn-light btn-lg px-5 py-3 fw-bold btn-hover-scale">
                            <i class="fas fa-tachometer-alt me-2"></i> Go to Dashboard
                        </a>
                        <a href="help.php" class="btn btn-outline-light btn-lg px-5 py-3 btn-hover-scale">
                            <i class="fas fa-question-circle me-2"></i> Get Help
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
/* ===== MODERN ANIMATIONS ===== */

/* Keyframe Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

@keyframes glow {
    0%, 100% { 
        box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
    }
    50% { 
        box-shadow: 0 0 20px rgba(102, 126, 234, 0.6);
    }
}

@keyframes rotate-360 {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

@keyframes blob-animation {
    0%, 100% { 
        transform: translate(0, 0) scale(1);
        opacity: 0.7;
    }
    33% { 
        transform: translate(30px, -50px) scale(1.1);
        opacity: 0.5;
    }
    66% { 
        transform: translate(-20px, 20px) scale(0.9);
        opacity: 0.6;
    }
}

@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

/* Hero Section Animations */
.hero-section {
    position: relative;
    overflow: hidden;
}

.animated-bg-elements {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 0;
}

.floating-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(40px);
    opacity: 0.6;
}

.blob-1 {
    width: 200px;
    height: 200px;
    background: linear-gradient(135deg, #0066ff, #1c1c1c);
    top: -10%;
    right: -5%;
    animation: blob-animation 8s infinite;
}

.blob-2 {
    width: 150px;
    height: 150px;
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    bottom: 10%;
    left: -10%;
    animation: blob-animation 10s infinite reverse;
}

.blob-3 {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #0066ff, #06b6d4);
    top: 50%;
    left: 50%;
    animation: blob-animation 12s infinite;
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-title {
    animation: fadeInUp 0.8s ease-out;
    line-height: 1.3;
}

.fade-in-up {
    animation: fadeInUp 0.8s ease-out;
}

.animate-fade-in-delay {
    animation: fadeInUp 0.8s ease-out 0.2s both;
}

.hero-image-wrapper {
    animation: slideInRight 0.8s ease-out 0.2s both;
}

.floating-card {
    animation: float 3s ease-in-out infinite;
}

.glow-effect {
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(0, 102, 255, 0.1) 0%, transparent 70%);
    animation: float 5s ease-in-out infinite reverse;
    pointer-events: none;
}

/* Button Animations */
.btn-hover-glow {
    transition: all 0.3s ease;
    position: relative;
}

.btn-hover-glow:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0, 102, 255, 0.3);
}

.btn-hover-outline {
    transition: all 0.3s ease;
}

.btn-hover-outline:hover {
    background-color: #0066ff;
    color: white !important;
    transform: translateY(-2px);
}

.btn-hover-scale {
    transition: all 0.3s ease;
}

.btn-hover-scale:hover {
    transform: scale(1.05);
}

/* Feature Cards Animation */
.feature-card-wrapper {
    animation: fadeInUp 0.6s ease-out;
}

.feature-card-wrapper:nth-child(1) { animation-delay: 0.1s; }
.feature-card-wrapper:nth-child(2) { animation-delay: 0.2s; }
.feature-card-wrapper:nth-child(3) { animation-delay: 0.3s; }
.feature-card-wrapper:nth-child(4) { animation-delay: 0.4s; }

.feature-card {
    border-radius: 12px;
    background: white;
}

.icon-animate {
    animation: bounce 2s ease-in-out infinite;
    transition: all 0.3s ease;
}

.feature-card:hover .icon-animate {
    animation: rotate-360 0.6s ease-in-out;
}

.hover-effect {
    transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
}

.hover-effect:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12) !important;
}

/* Step Card Animations */
.step-card-wrapper {
    animation: fadeInUp 0.6s ease-out;
}

.step-card-wrapper:nth-child(1) { animation-delay: 0.1s; }
.step-card-wrapper:nth-child(2) { animation-delay: 0.3s; }
.step-card-wrapper:nth-child(3) { animation-delay: 0.5s; }

.step-card {
    background: white;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.step-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(102, 126, 234, 0.15) !important;
}

.step-number {
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #0066ff, #1c1c1c);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 102, 255, 0.4);
    animation: fadeInDown 0.6s ease-out;
}

/* Testimonial Cards */
.testimonial-card-wrapper {
    animation: fadeInUp 0.6s ease-out;
}

.testimonial-card-wrapper:nth-child(1) { animation-delay: 0.1s; }
.testimonial-card-wrapper:nth-child(2) { animation-delay: 0.2s; }
.testimonial-card-wrapper:nth-child(3) { animation-delay: 0.3s; }

.testimonial-card {
    border-radius: 12px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.testimonial-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #0066ff, #1c1c1c);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
}

.testimonial-card:hover::before {
    transform: scaleX(1);
}

.avatar-circle {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.875rem;
    animation: fadeInUp 0.6s ease-out;
}

.rating-stars {
    animation: fadeInDown 0.6s ease-out;
}

.rating-stars i {
    animation: bounce 2s ease-in-out infinite;
}

.rating-stars i:nth-child(1) { animation-delay: 0.1s; }
.rating-stars i:nth-child(2) { animation-delay: 0.2s; }
.rating-stars i:nth-child(3) { animation-delay: 0.3s; }
.rating-stars i:nth-child(4) { animation-delay: 0.4s; }
.rating-stars i:nth-child(5) { animation-delay: 0.5s; }

/* CTA Section */
.cta-animated-bg {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
    animation: float 8s ease-in-out infinite;
    z-index: 0;
}

.cta-title {
    animation: fadeInUp 0.8s ease-out;
}

.cta-buttons {
    animation: fadeInUp 0.8s ease-out 0.2s both;
}

/* Section Headers */
.section-header {
    animation: fadeInUp 0.8s ease-out;
}

.badge {
    animation: pulse 2s ease-in-out infinite;
}

.animate-bounce {
    animation: bounce 2s ease-in-out infinite;
}

.animate-pulse {
    animation: pulse 2s ease-in-out infinite;
}

/* Utility Classes */
.bg-primary-light {
    background-color: rgba(0, 102, 255, 0.1) !important;
}

.bg-light-primary {
    background-color: rgba(0, 102, 255, 0.1) !important;
}

.bg-light-success {
    background-color: rgba(13, 110, 253, 0.1) !important;
}

.bg-light-warning {
    background-color: rgba(255, 193, 7, 0.1) !important;
}

.text-primary-gradient {
    background: linear-gradient(135deg, #0066ff, #1c1c1c);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    display: inline-block;
}

.z-1 {
    position: relative;
    z-index: 1;
}

/* Icon Circle Styles */
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
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.object-fit-cover {
    object-fit: cover;
    width: 100%;
    height: 100%;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .display-4 {
        font-size: 2rem;
    }
    
    .hero-image-wrapper {
        margin-top: 2rem;
    }
    
    .cta-buttons {
        flex-direction: column;
    }
    
    .blob-1, .blob-2, .blob-3 {
        filter: blur(60px);
    }
}

/* Scroll-triggered animations (can be enhanced with JS) */
.scroll-animate {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.6s ease, transform 0.6s ease;
}

.scroll-animate.visible {
    opacity: 1;
    transform: translateY(0);
}
</style>

<?php include 'includes/footer.php'; ?>