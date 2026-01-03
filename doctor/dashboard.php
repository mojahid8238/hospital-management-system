<?php
date_default_timezone_set('UTC');
// IMPORTANT: You must ensure these files exist and contain the necessary setup
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_doctor(); // Assumes this function checks for session and redirects if not a doctor

$doctor_id = null;
$doctor_name = 'Doctor User'; // Default value

// --- START: Added PHP Logic to initialize Doctor's name and profile pic ---
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT id, name, profile_pic FROM doctors WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $doctor_id = $row['id'];
        $doctor_name = $row['name'];
        $_SESSION['profile_pic'] = $row['profile_pic'] ?? 'assets/images/default-avatar.png';
    }
    $stmt->close();

    if ($doctor_id) {
        // Fetch Real Stats
        // 1. Total Appointments
        $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ?");
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $stmt->bind_result($total_appointments);
        $stmt->fetch();
        $stmt->close();

        // 2. Pending Requests
        $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'Pending'");
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $stmt->bind_result($pending_requests);
        $stmt->fetch();
        $stmt->close();

        // 3. Completed Today
        $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'Completed' AND DATE(appointment_date) = CURDATE()");
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $stmt->bind_result($completed_today);
        $stmt->fetch();
        $stmt->close();
    }
}
// --- END: Added PHP Logic ---

$rawProfilePic = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';
$profilePic = preg_replace('#^\\.\\./#', '', $rawProfilePic); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | Hospital Management</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/shared-table-design.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/view-appointments.css">
    <link rel="stylesheet" href="../assets/css/mini_messenger.css">
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">
                <i class="fas fa-bars"></i>
                <span>Menu</span>
            </button>
            <a href="dashboard.php">HealthCare</a>
        </div>
        <div class="nav-right">
            <div class="user-info">
                <span>Dr. <?php echo htmlspecialchars($doctor_name); ?></span>
            </div>
            <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="doctorSidebar">
            <h3>Medical Menu</h3>
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="cancelled-appointments.php"><i class="fas fa-calendar-times"></i> Cancelled</a></li>
                <li><a href="../messaging/messaging.php"><i class="fas fa-comment-medical"></i> Consultations</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="welcome-section">
                <h2>Welcome back, Dr. <?php echo htmlspecialchars($doctor_name); ?></h2>
                <p>Here's what's happening with your appointments today.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Total Appointments</h4>
                    <div class="value" id="totalAppointmentsCount"><?php echo $total_appointments ?? 0; ?></div>
                    <p style="font-size: 0.85rem; color: var(--success); margin-top: 8px;"><i class="fas fa-arrow-up"></i> Overview</p>
                </div>
                <div class="stat-card">
                    <h4>Pending Requests</h4>
                    <div class="value" id="pendingAppointmentsCount"><?php echo $pending_requests ?? 0; ?></div>
                    <p style="font-size: 0.85rem; color: var(--warning); margin-top: 8px;"><i class="fas fa-clock"></i> Action required</p>
                </div>
                <div class="stat-card">
                    <h4>Completed Today</h4>
                    <div class="value" id="completedAppointmentsCount"><?php echo $completed_today ?? 0; ?></div>
                    <p style="font-size: 0.85rem; color: var(--info); margin-top: 8px;"><i class="fas fa-check-circle"></i> Good progress</p>
                </div>
            </div>

            <div class="container panel-card">
                <div class="search-filter-container">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchPatient" placeholder="Search Patients by name or ID...">
                    </div>

                    <div class="filter-bar">
                        <select id="typeFilter">
                            <option value="all">All Types</option>
                            <option value="Online">Online</option>
                            <option value="Offline">Offline</option>
                        </select>
                        <select id="statusFilter">
                            <option value="upcoming">Upcoming</option>
                            <option value="pending">Pending</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="all">All Statuses</option>
                        </select>
                    </div>
                </div>

                <div class="appointments-section" id="appointmentsContainer">
                    <div class="appointment-card-wrapper mb-5" id="pendingAppointmentsCard">
                        <h4 class="section-title mb-4"><i class="fas fa-clock text-warning"></i> Pending Requests</h4>
                        <div id="pendingAppointmentList"></div>
                    </div>

                    <div class="appointment-card-wrapper" id="confirmedAppointmentsCard">
                        <h4 class="section-title mb-4"><i class="fas fa-calendar-check text-primary"></i> Confirmed Schedule</h4>
                        <div id="confirmedAppointmentList"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <div class="profile-pic-wrapper" style="position: relative;">
                <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay" class="profile-overlay-pic">
                <label for="profilePicInput" style="position: absolute; bottom: 30px; right: 10px; background: var(--primary-color); color: #fff; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid #fff;">
                    <i class="fas fa-camera"></i>
                </label>
            </div>
            <h3>Dr. <?php echo htmlspecialchars($doctor_name); ?></h3>
            <p>Medical Practitioner</p>
            
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
            </form>
            <div id="uploadMessage"></div>
            
            <hr>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-chart-line" style="margin-right: 12px;"></i> Dashboard</a></li>
                <li><a href="#"><i class="fas fa-user-cog" style="margin-right: 12px;"></i> Account Settings</a></li>
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
    <script src="../assets/js/ui-ux.js"></script>
    <script src="../assets/js/mini_messenger.js"></script>
    <script src="../assets/js/doctor-dashboard-logic.js"></script>
    <script src="../assets/js/profile-overlay.js"></script>
</body>
</html>