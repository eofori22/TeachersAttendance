<?php include 'includes/header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0 rounded-lg backdrop-blur">
                <div class="card-header bg-gradient text-white text-center py-4" style="background: linear-gradient(45deg, #2196F3, #1976D2);">
                    <h3 class="mb-0 fw-light">Welcome Back</h3>
                </div>
                <div class="card-body p-5">
                    <div class="text-center mb-5">
                        <div class="avatar-circle mb-4 mx-auto">
                            <i class="fas fa-user-circle fa-4x text-primary"></i>
                        </div>
                        <h2 class="card-title fw-bold">Ompad Stem Teacher Attendance System</h2>
                        <p class="text-muted">Please sign in to continue</p>
                    </div>
                    <form id="loginForm" class="needs-validation">
                        <div class="form-floating mb-4">


                            <input type="text" class="form-control form-control-lg border-0 bg-light" id="username" placeholder="Username" required>
                            <label for="username" class="text-muted">Username</label>
                        </div>
                        <div class="form-floating mb-5">


                            <input type="password" class="form-control form-control-lg border-0 bg-light" id="password" placeholder="Password" required>
                            <label for="password" class="text-muted">Password</label>
                        </div>


                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg text-uppercase fw-bold py-3 rounded-pill" style="background: linear-gradient(45deg, #2196F3, #1976D2); border: none;">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </div>
                    </form>
                    <div id="loginMessage" class="mt-4 text-center"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom Styles */
.min-vh-100 {
    min-height: 100vh;
}

.logo-circle {
    width: 70px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.9);
    transition: all 0.3s ease;
}

.card {
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.card-header {
    border-bottom: none;
    padding: 2rem 1rem;
}

.form-control {
    padding: 10px 15px;
    border-radius: 8px !important;
    transition: all 0.3s ease;
}

.form-control:focus {
    box-shadow: 0 0 0 4px rgba(0, 102, 255, 0.2);
    border-color: #0066ff;
}

.btn-primary {
    background: linear-gradient(135deg, #0066ff 0%, #1c1c1c 100%);
    border: none;
    border-radius: 8px;
    padding: 12px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 102, 255, 0.4);
}

.toggle-password {
    border-radius: 0 8px 8px 0 !important;
}

/* Animation for logo */
@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}

.logo-circle {
    animation: float 4s ease-in-out infinite;
}
</style>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    // Show loading state
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in...';
    submitButton.disabled = true;
    
    fetch('api/auth.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'login',
            username: username,
            password: password
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            document.getElementById('loginMessage').innerHTML = 

                `<div class="alert alert-danger alert-dismissible fade show rounded-pill">
                    <i class="fas fa-exclamation-circle me-2"></i>${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('loginMessage').innerHTML = 

            `<div class="alert alert-danger rounded-pill">An error occurred. Please try again.</div>`;
    })
    .finally(() => {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
});
</script>

<?php include 'includes/footer.php'; ?>