<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_admin();

// Ensure profile_pic session is initialized for display
if (!isset($_SESSION['profile_pic'])) {
    // Setting a safe default path
    $_SESSION['profile_pic'] = 'assets/images/default-avatar.png';
}

// -------------------------------------------------------------------------
// FIX 1: Clean the path stored in the database/session by 
//        removing the leading '../' if it exists.
// -------------------------------------------------------------------------
// Use a default path that is relative to the project root (no ../)
$rawProfilePic = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';
// Use ltrim to safely remove the leading "../" if it exists.
$profilePic = preg_replace('#^\\.\\./#', '', $rawProfilePic); 
// Now $profilePic contains: 'assets/images/profile_pics/patient_2.png' or 'assets/images/default-avatar.png'
// -------------------------------------------------------------------------
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
            <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="user-icon user-profile-pic" id="profileToggle">
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
            <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay" class="user-profile-pic">
           
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

    <script>
        const BASE_URL = '/';
    </script>
    <script src="../assets/js/profile-overlay.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
</body>
</html>