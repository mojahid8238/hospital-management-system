<?php
session_start();
require_once 'includes/auth.php';

if (is_logged_in()) {
     header("Location: includes/homepage.php");
        exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Welcome - Hospital Management System</title>
  <link rel="stylesheet" href="assets/css/index.css" />
</head>
<body>

  <!-- PUBLIC LANDING PAGE -->
  <header class="navbar">
    <div class="nav-left">
      <a href="#services">Services</a>
      <a href="#doctors">Doctors</a>
    </div>
    <div class="nav-right">
      <a href="auth/register.php">Sign Up</a>
      <a href="auth/login.php">Log In</a>
    </div>
  </header>

  <section class="slider">
    <h2>Welcome</h2>
    <p>This is the landing page for guests and new visitors.</p>
  </section>

</body>
</html>
