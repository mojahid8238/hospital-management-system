<?php
require_once '../includes/header.php';
redirect_if_not_admin();
?>

<div class="container">
    <h2>Admin Dashboard</h2>
    <p>Welcome, Admin <?php echo $_SESSION['username']; ?>!</p>

    <h3>Quick Actions</h3>
    <ul>
        <li><a href="manage-doctors.php">Manage Doctors</a></li>
        <li><a href="manage-patients.php">Manage Patients</a></li>
        <li><a href="reports.php">View Reports</a></li>
    </ul>

    <!-- Add more admin-specific content here -->
</div>

<?php
require_once '../includes/footer.php';
?>