<?php
require_once 'includes/auth.php';

if (!is_logged_in()) {
    header("Location: auth/login.php");
    exit();
}

// Redirect based on user role
if (is_admin()) {
    header("Location: admin/dashboard.php");
} elseif (is_doctor()) {
    header("Location: doctor/dashboard.php");
} elseif (is_patient()) {
    header("Location: patient/dashboard.php");
} else {
    // Fallback for unknown roles or if role is not set
    echo "<h1>Welcome to the Hospital Management System!</h1>";
    echo "<p>Your role is not recognized or set. Please contact support.</p>";
    echo "<p><a href=\"auth/logout.php\">Logout</a></p>";
}
exit();
?>