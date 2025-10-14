<?php
require_once '../includes/header.php';
redirect_if_not_doctor();
?>

<div class="container">
    <h2>Doctor Dashboard</h2>
    <p>Welcome, Dr. <?php echo $_SESSION['username']; ?>!</p>

    <h3>Your Actions</h3>
    <ul>
        <li><a href="view-appointments.php">View Your Appointments</a></li>
        <!-- Add more doctor-specific actions here -->
    </ul>

    <!-- Add more doctor-specific content here -->
</div>

<?php
require_once '../includes/footer.php';
?>