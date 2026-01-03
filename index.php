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
  <title>Hospital Management System</title>
   <link rel="stylesheet" href="assets/css/landing.css">
  
</head>
<body>
  <div class="floating-bg">
    <div class="floating-element shape-1"></div>
    <div class="floating-element shape-2"></div>
    <div class="floating-element shape-3"></div>
  </div>

  <div class="landing-container">
    <h1>Hospital Management <br>System</h1>
    <p>Efficiently manage patients, appointments, staff, and hospital resources — all in one secure, modern platform.</p>
    <div class="buttons">
      <a href="auth/login.php">Login</a>
      <a href="auth/register.php">Register</a>
      <a href="#" class="secondary">Learn More</a>
    </div>
  </div>
</body>

</html>
