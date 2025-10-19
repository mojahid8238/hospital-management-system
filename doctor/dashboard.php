<?php
date_default_timezone_set('UTC');
// IMPORTANT: You must ensure these files exist and contain the necessary setup
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_doctor(); // Assumes this function checks for session and redirects if not a doctor

$doctor_id = null;
$doctor_name = 'Doctor User'; // Default value
$doctor_profile_pic = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';

// --- START: Added PHP Logic to initialize Doctor's name and profile pic ---
// Get the doctor_id and name associated with the logged-in user
// This fixes the issue where $doctor_name and the header profile picture path were not correctly set.
if (isset($_SESSION['user_id'])) {
    // Assuming $conn is available from db.php
    $stmt = $conn->prepare("SELECT id, name, profile_pic FROM doctors WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $doctor_id = $row['id'];
        $doctor_name = $row['name'];
        // Ensure the session variable for the header is updated with the latest pic path
        $_SESSION['profile_pic'] = $row['profile_pic'] ?? 'assets/images/default-avatar.png';
        $doctor_profile_pic = $_SESSION['profile_pic'];
    }
    $stmt->close();
}
// --- END: Added PHP Logic ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/shared-table-design.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/view-appointments.css">
    <link rel="stylesheet" href="../assets/css/mini_messenger.css">
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">☰ Toggle Menu</button>
            <a href="#">Doctor Panel</a>
        </div>
        <div class="nav-right">
            <img src="/hospital-management-system/<?php echo htmlspecialchars($doctor_profile_pic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="doctorSidebar">
            <h3>Doctor Options</h3>
            <ul>
                <li><a href="dashboard.php">View Your Appointments</a></li>
                <!-- Re-adding the Cancelled Appointments link as it's typically useful -->
                <li><a href="cancelled-appointments.php">Cancelled Appointments</a></li>
                <li><a href="../messaging/messaging.php">Messages</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container panel-card">
                <h2 class="card-title mb-3">Welcome, <strong>Dr. <?php echo htmlspecialchars($doctor_name); ?></strong>!</h2>
                <p class="text-muted">Here you can manage your scheduled appointments:</p>

                <div class="search-filter-container mb-4">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchPatient" placeholder="Search Patient by name...">
                    </div>

                    <div class="filter-bar">
                        <i class="fas fa-filter"></i>
                        <select id="typeFilter" class="form-select w-auto">
                            <option value="all">All Types</option>
                            <option value="Online">Online</option>
                            <option value="Offline">Offline</option>
                        </select>
                    </div>
                </div>

                <div class="appointments-section mb-5" id="appointmentsContainer">
                    <h3 class="mb-4">Appointment Overview</h3>

                    <div class="card mb-5 p-4 border-l-8 border-yellow-500 shadow-xl" id="pendingAppointmentsCard">
                        <h4 class="card-title text-yellow-600 mb-4"><i class="fas fa-clock mr-2"></i> **Pending Appointments** (Requires Action)</h4>
                        <ul class="doctor-list" id="pendingAppointmentList">
                        </ul>
                    </div>

                    <div class="card p-4 border-l-8 border-blue-500 shadow-xl" id="confirmedAppointmentsCard">
                        <h4 class="card-title text-blue-600 mb-4"><i class="fas fa-calendar-check mr-2"></i> **Confirmed/Scheduled Appointments**</h4>
                        <ul class="doctor-list" id="confirmedAppointmentList">
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <img src="/hospital-management-system/<?php echo htmlspecialchars($doctor_profile_pic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay" style="position: relative; z-index: 10; cursor: pointer;">
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
                <button type="submit" style="display: none;">Upload</button>
            </form>
            <div id="uploadMessage" style="margin-top: 10px; color: green;"></div>
            <h3><?php echo htmlspecialchars($doctor_name); ?></h3>
            <hr>
            <ul>
                <li><a href="dashboard.php">Doctor Dashboard</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>

    <?php include_once '../includes/mini_messenger.php'; ?>

    <script>
        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
    </script>
    <script src="../assets/js/mini_messenger.js"></script>
    <script src="../assets/js/doctor-dashboard-logic.js"></script>
</body>
</html>