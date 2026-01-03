<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_admin();

// Ensure profile_pic session is initialized for display
if (!isset($_SESSION['profile_pic'])) {
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

$total_doctors = $conn->query("SELECT COUNT(*) FROM doctors")->fetch_row()[0];
$total_patients = $conn->query("SELECT COUNT(*) FROM patients")->fetch_row()[0];
$total_appointments = $conn->query("SELECT COUNT(*) FROM appointments")->fetch_row()[0];

// You can add more complex reports here, e.g., appointments by doctor, patient, date range etc.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports | HealthCare Admin</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/shared-table-design.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">
                <i class="fas fa-bars"></i>
                <span>Menu</span>
            </button>
            <a href="dashboard.php">HealthCare Admin</a>
        </div>
        <div class="nav-right">
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['name'] ?? 'Administrator'); ?></span>
            </div>
            <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="adminSidebar">
            <h3>System Management</h3>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="manage-admins.php" class="sidebar-link" data-target="manage-admins.php"><i class="fas fa-user-shield"></i> Admins</a></li>
                <li><a href="manage-doctors.php" class="sidebar-link" data-target="manage-doctors.php"><i class="fas fa-user-md"></i> Doctors</a></li>
                <li><a href="manage-patients.php" class="sidebar-link" data-target="manage-patients.php"><i class="fas fa-user-injured"></i> Patients</a></li>
                <li><a href="reports.php" class="active sidebar-link" data-target="reports.php"><i class="fas fa-file-invoice-dollar"></i> Reports</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container">
                <div class="welcome-section">
                    <h2>System Analytics & Reports</h2>
                    <p>Comprehensive overview of hospital operations and user statistics.</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon-circle bg-primary-light">
                            <i class="fas fa-user-md text-primary"></i>
                        </div>
                        <h4>Total Doctors</h4>
                        <div class="value"><?php echo $total_doctors; ?></div>
                        <p class="text-muted mt-2">Specialists across all departments</p>
                    </div>

                    <div class="stat-card">
                        <div class="icon-circle bg-success-light">
                            <i class="fas fa-user-injured text-success"></i>
                        </div>
                        <h4>Total Patients</h4>
                        <div class="value"><?php echo $total_patients; ?></div>
                        <p class="text-muted mt-2">Registered service users</p>
                    </div>

                    <div class="stat-card">
                        <div class="icon-circle bg-info-light">
                            <i class="fas fa-calendar-check text-info"></i>
                        </div>
                        <h4>Total Appointments</h4>
                        <div class="value"><?php echo $total_appointments; ?></div>
                        <p class="text-muted mt-2">Overall medical consultations</p>
                    </div>
                </div>

                <div class="panel-card mt-4">
                    <h3 class="section-title mb-3">Operational Insights</h3>
                    <p>Detailed breakdown and historical data visualization will be available here soon.</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Profile Overlay -->
    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <div class="profile-pic-wrapper" style="position: relative;">
                <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay" class="profile-overlay-pic">
                <label for="profilePicInput" style="position: absolute; bottom: 30px; right: 10px; background: var(--primary-color); color: #fff; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid #fff;">
                    <i class="fas fa-camera"></i>
                </label>
            </div>
            <h3><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></h3>
            <p>System Administrator</p>
            
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
            </form>
            <div id="uploadMessage"></div>
            
            <hr>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-th-large" style="margin-right: 12px;"></i> Dashboard</a></li>
                <li><a href="../auth/logout.php" style="color: var(--error);"><i class="fas fa-sign-out-alt" style="margin-right: 12px;"></i> Logout</a></li>
            </ul>
            <button class="close-btn" id="closeProfile">Close Panel</button>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
        const BASE_URL = '/';
    </script>
    <script src="../assets/js/profile-overlay.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
    <script src="../assets/js/ui-ux.js"></script>
</body>
</html>