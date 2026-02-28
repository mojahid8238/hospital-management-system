<?php
require_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Care</title>
    <link rel="icon" type="image/x-icon" href="/assets/images/logo.ico">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <a href="/index.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                    <img src="/assets/images/logo.png" alt="Health Care Logo" style="height: 40px; margin-right: 10px; border-radius: 5px;">
                    Health Care
                </a>
            </div>
            <ul class="nav-links">
                <?php if (is_logged_in()): ?>
                    <?php if (is_admin()): ?>
                        <li><a href="/admin/dashboard.php">Admin Dashboard</a></li>
                        <li><a href="/admin/manage-doctors.php">Manage Doctors</a></li>
                        <li><a href="/admin/manage-patients.php">Manage Patients</a></li>
                        <li><a href="/admin/reports.php">Reports</a></li>
                    <?php elseif (is_doctor()): ?>
                        <li><a href="/doctor/dashboard.php">Doctor Dashboard</a></li>
                        <li><a href="/doctor/view-appointments.php">View Appointments</a></li>
                    <?php elseif (is_patient()): ?>
                        <li><a href="/patient/dashboard.php">Patient Dashboard</a></li>
                        <li><a href="/patient/book-appointment.php">Book Appointment</a></li>
                        
                    <?php endif; ?>
                    <li><a href="/auth/logout.php">Logout (<?php echo $_SESSION['name']; ?>)</a></li>
                <?php else: ?>
                    <li><a href="/auth/login.php">Login</a></li>
                    <li><a href="/auth/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>