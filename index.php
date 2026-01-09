<?php
require_once 'includes/auth.php';

// Redirect logged-in users based on role
if (is_logged_in()) {
    if (is_admin()) {
        header("Location: admin/dashboard.php");
    } elseif (is_doctor()) {
        header("Location: doctor/dashboard.php");
    } else {
        header("Location: includes/homepage.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Hospital Management System | Modern Care</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>
    <div class="background-decorations">
        <div class="blur-circle circle-1"></div>
        <div class="blur-circle circle-2"></div>
    </div>

    <header class="landing-navbar">
        <div class="container">
            <a href="#" class="logo">
                <i class="fas fa-heartbeat"></i> HealthCare
            </a>
            <nav class="nav-links">
                <a href="#features">Features</a>
                <a href="#about">About</a>
                <a href="#contact">Contact</a>
            </nav>
            <div class="auth-buttons">
                <a href="auth/login.php" class="btn btn-ghost">Log In</a>
                <a href="auth/register.php" class="btn btn-primary">Get Started</a>
            </div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="container hero-content">
                <div class="hero-text">
                    <h1>Modern Care for a <br><span class="highlight">Healthier Tomorrow</span></h1>
                    <p>Experience the future of hospital management. Streamlined operations, secure patient records, and seamless communication—all in one place.</p>
                    <div class="hero-buttons">
                        <a href="auth/register.php" class="btn btn-xl btn-primary">Start Your Journey <i class="fas fa-arrow-right"></i></a>
                        <a href="auth/login.php" class="btn btn-xl btn-secondary">Patient Login</a>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="mockup-card">
                        <div class="card-header">
                            <div class="dot red"></div>
                            <div class="dot yellow"></div>
                            <div class="dot green"></div>
                        </div>
                        <div class="card-body">
                           <div class="stat-row">
                               <div class="stat-item">
                                   <div class="icon-box bg-blue"><i class="fas fa-user-md"></i></div>
                                   <div>
                                       <h4>Top Doctors</h4>
                                       <span>Available 24/7</span>
                                   </div>
                               </div>
                               <div class="stat-item">
                                   <div class="icon-box bg-green"><i class="fas fa-calendar-check"></i></div>
                                   <div>
                                       <h4>Easy Booking</h4>
                                       <span>Instant Confirmation</span>
                                   </div>
                               </div>
                           </div>
                           <div class="chart-mockup">
                               <div class="bar bar-1"></div>
                               <div class="bar bar-2"></div>
                               <div class="bar bar-3"></div>
                           </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="features-section">
            <div class="container">
                <div class="section-header">
                    <h2>Everything You Need</h2>
                    <p>A comprehensive suite of tools designed to simplify healthcare management.</p>
                </div>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="icon-circle bg-primary-light">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3>Smart Scheduling</h3>
                        <p>Book appointments seamlessly with instant availability checks and automated reminders.</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon-circle bg-success-light">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Secure Records</h3>
                        <p>Keep your medical history safe and accessible. We prioritize your data privacy with top-tier security.</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon-circle bg-accent-light">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3>Direct Communication</h3>
                        <p>Connect with doctors via secure messaging or video consultations from the comfort of your home.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <h3><i class="fas fa-heartbeat"></i> HealthCare</h3>
                    <p>Innovating healthcare management for a better patient experience.</p>
                </div>
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Contact Support</a>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <?php echo date('Y'); ?> HealthCare System. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>
