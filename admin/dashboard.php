<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_admin();

// Fetch Admin Stats
// 1. Total Doctors
$result_doctors = $conn->query("SELECT COUNT(*) as total FROM doctors");
$total_doctors = $result_doctors->fetch_assoc()['total'] ?? 0;

// 2. Total Patients
$result_patients = $conn->query("SELECT COUNT(*) as total FROM patients");
$total_patients = $result_patients->fetch_assoc()['total'] ?? 0;

// 3. New Admin Requests
$result_admins = $conn->query("SELECT COUNT(*) as total FROM admin WHERE status = 'pending'");
$pending_admins = $result_admins->fetch_assoc()['total'] ?? 0;

// 4. Total Appointments
$result_appointments = $conn->query("SELECT COUNT(*) as total FROM appointments");
$total_appointments = $result_appointments->fetch_assoc()['total'] ?? 0;

// Ensure profile_pic session is initialized
$rawProfilePic = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';
$profilePic = preg_replace('#^\\.\\./#', '', $rawProfilePic); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Hospital Management</title>
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
                <li><a href="../messaging/messaging.php"><i class="fas fa-comments"></i> Messages</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="welcome-section">
                <h2>Welcome back, Admin!</h2>
                <p>Monitor system activity and manage users across the platform.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon-circle bg-primary-light" style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        <i class="fas fa-user-md text-primary" style="font-size: 1.25rem;"></i>
                    </div>
                    <h4>Total Doctors</h4>
                    <div class="value"><?php echo $total_doctors; ?></div>
                    <p style="font-size: 0.85rem; color: var(--primary-color); margin-top: 8px;"><i class="fas fa-check-circle"></i> Registered Specialists</p>
                </div>
                <div class="stat-card">
                    <div class="icon-circle bg-success-light" style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        <i class="fas fa-user-injured text-success" style="font-size: 1.25rem;"></i>
                    </div>
                    <h4>Total Patients</h4>
                    <div class="value text-success"><?php echo $total_patients; ?></div>
                    <p style="font-size: 0.85rem; color: var(--success); margin-top: 8px;"><i class="fas fa-user-circle"></i> Active Service Users</p>
                </div>
                <div class="stat-card">
                    <div class="icon-circle bg-error-light" style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        <i class="fas fa-user-shield text-error" style="font-size: 1.25rem;"></i>
                    </div>
                    <h4>Pending Admins</h4>
                    <div class="value" style="<?php echo $pending_admins > 0 ? 'color: var(--error);' : 'color: var(--text-muted);'; ?>">
                        <?php echo $pending_admins; ?>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 8px;"><i class="fas fa-clock"></i> Awaiting verification</p>
                </div>
                <div class="stat-card">
                    <div class="icon-circle bg-info-light" style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        <i class="fas fa-calendar-check text-info" style="font-size: 1.25rem;"></i>
                    </div>
                    <h4>Total Appointments</h4>
                    <div class="value text-info"><?php echo $total_appointments; ?></div>
                    <p style="font-size: 0.85rem; color: var(--info); margin-top: 8px;"><i class="fas fa-calendar-check"></i> Overall consultations</p>
                </div>
            </div>

            <div class="panel-card">
                <h3 class="section-title mb-3">Recent System Overview</h3>
                <p>Monitor your system activity and manage users across the platform. Select an option from the sidebar to manage specific records.</p>
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
                <li><a href="dashboard.php"><i class="fas fa-chart-line" style="margin-right: 12px;"></i> Dashboard</a></li>
                <li><a href="../auth/logout.php" style="color: var(--error);"><i class="fas fa-sign-out-alt" style="margin-right: 12px;"></i> Logout</a></li>
            </ul>
            <button class="close-btn" id="closeProfile">Close Panel</button>
        </div>
    </div>

    <?php include_once '../includes/mini_messenger.php'; ?>

    <script>
        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
        const BASE_URL = '/';
    </script>
    <script src="../assets/js/profile-overlay.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
    <script src="../assets/js/ui-ux.js"></script>
    <script src="../assets/js/mini_messenger.js"></script>
</body>
</html>