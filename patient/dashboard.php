<?php
require_once '../includes/header.php';
redirect_if_not_patient();
?>

<div class="container">
    <h2>Patient Dashboard</h2>
    <p>Welcome, <?php echo $_SESSION['username']; ?>!</p>

    <h3>Your Actions</h3>
    <ul>
        <li><a href="book-appointment.php">Book New Appointment</a></li>
        <li><a href="medical-history.php">View Medical History</a></li>
        <!-- Add more patient-specific actions here -->
    </ul>

    <!-- Add more patient-specific content here -->
</div>

<?php
require_once '../includes/footer.php';
?>