<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_admin();

// Fetch report data
$total_doctors = $conn->query("SELECT COUNT(*) FROM doctors")->fetch_row()[0];
$total_patients = $conn->query("SELECT COUNT(*) FROM patients")->fetch_row()[0];
$total_appointments = $conn->query("SELECT COUNT(*) FROM appointments")->fetch_row()[0];

// You can add more complex reports here, e.g., appointments by doctor, patient, date range etc.
?>

<div class="container">
    <h2>Admin Reports</h2>
    <p>Welcome, Admin <?php echo $_SESSION['name']; ?>! Here are some system reports:</p>

    <div class="report-card">
        <h3>Total Doctors</h3>
        <p><?php echo $total_doctors; ?></p>
    </div>

    <div class="report-card">
        <h3>Total Patients</h3>
        <p><?php echo $total_patients; ?></p>
    </div>

    <div class="report-card">
        <h3>Total Appointments</h3>
        <p><?php echo $total_appointments; ?></p>
    </div>

    <!-- Add more report sections as needed -->

</div>