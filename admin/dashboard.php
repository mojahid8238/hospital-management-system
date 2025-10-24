<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_admin();

// Define the base URL for asset loading, which includes the project subdirectory
// This is the FIX for the persistent 404 errors.
$base_url = '/hospital-management-system/';

// Ensure profile_pic session is initialized for display
if (!isset($_SESSION['profile_pic'])) {
    // Setting a safe default path
    $_SESSION['profile_pic'] = 'assets/images/default-avatar.png'; 
}

// FIX: Construct the path using the defined $base_url
$image_relative_path = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';
$profile_pic_path = htmlspecialchars($base_url . $image_relative_path); 

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Assets are also fixed to use relative paths as before, they work -->
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <link rel="stylesheet" href="../assets/css/dashboard.css">
   

</head>
<body>
    <header class="navbar">
        <div class="nav-left">
        <button class="sidebar-toggle-btn" id="sidebarToggle">☰ Toggle Menu</button>

        <a href="#">Admin Panel</a>          
        </div>
        <div class="nav-right">
            <img src="<?php echo $profile_pic_path; ?>" alt="Profile Picture" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="adminSidebar">
            <h3>Admin Options</h3>
            <ul>
                <li><a href="manage-admins.php" class="sidebar-link" data-target="manage-admins.php">Manage Admins</a></li>
                <li><a href="manage-doctors.php" class="sidebar-link" data-target="manage-doctors.php">Manage Doctors</a></li>
                <li><a href="manage-patients.php" class="sidebar-link" data-target="manage-patients.php">Manage Patients</a></li>
                <li><a href="reports.php" class="sidebar-link" data-target="reports.php">View Reports</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <h2>Welcome to Admin Dashboard</h2>
            <p>Select an option from the left menu to manage the system.</p>
        </main>
    </div>

    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <img src="<?php echo $profile_pic_path; ?>" alt="Profile Picture" id="profileImageDisplay">
           
            <!-- Hidden form and input for file selection -->
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data" style="display: none;">
                <input type="file" name="profile_pic" id="profilePicInput" accept="image/*">
            </form>
            <!-- Upload message container -->
            <div id="uploadMessage" style="font-size: 0.95rem; text-align: center; margin-top: 5px;"></div>
            
            <h3><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></h3>
            <hr>
            <ul>
                <li><a href="dashboard.php">Admin Dashboard</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
            
        </div>
    </div>

    <script src="../assets/js/profile-overlay.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
</body>
</html>