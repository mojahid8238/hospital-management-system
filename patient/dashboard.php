<?php
date_default_timezone_set('UTC');
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_patient();

$patient_id = null;
$patient_name = '';

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT id, name, profile_pic FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $patient_id = $row['id'];
        $patient_name = $row['name'];
        $_SESSION['profile_pic'] = $row['profile_pic'] ?? 'assets/images/default-avatar.png';
    }
    $stmt->close();

    if ($patient_id) {
        // Fetch Real Stats
        // 1. Total Visits (Completed)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'Completed'");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $stmt->bind_result($total_visits);
        $stmt->fetch();
        $stmt->close();

        // 2. Active Records (Not Cancelled)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status != 'Cancelled'");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $stmt->bind_result($active_records);
        $stmt->fetch();
        $stmt->close();

        // 3. Next Appointment
        $stmt = $conn->prepare("SELECT appointment_date FROM appointments WHERE patient_id = ? AND status = 'Scheduled' AND appointment_date > NOW() ORDER BY appointment_date ASC LIMIT 1");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $stmt->bind_result($next_app_date);
        $stmt->fetch();
        $stmt->close();
    }
}

$rawProfilePic = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';
$profilePic = preg_replace('#^\\.\\./#', '', $rawProfilePic); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard | Hospital Management</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/shared-table-design.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/patient-dashboard.css">
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
                <span><?php echo htmlspecialchars($patient_name); ?></span>
            </div>
            <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="patientSidebar">
            <h3>Patient Menu</h3>
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="book-appointment.php"><i class="fas fa-calendar-plus"></i> Book Appointment</a></li>
                <li><a href="prescriptions.php"><i class="fas fa-file-prescription"></i> Prescriptions</a></li>
                <li><a href="cancelled-appointments.php"><i class="fas fa-calendar-times"></i> Cancelled</a></li>
                <li><a href="../messaging/messaging.php"><i class="fas fa-comments"></i> Messages</a></li>
            </ul>
            <h3 style="margin-top: 32px;">Support</h3>
            <ul>
                <li><a href="#"><i class="fas fa-question-circle"></i> Help Center</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="welcome-section">
                <h2>Welcome back, <?php echo htmlspecialchars($patient_name); ?>!</h2>
                <p>Track your health journey and upcoming appointments.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Total Visits</h4>
                    <div class="value" id="totalVisitsCount"><?php echo $total_visits ?? 0; ?></div>
                    <p style="font-size: 0.85rem; color: var(--success); margin-top: 8px;"><i class="fas fa-heartbeat"></i> Active healthy life</p>
                </div>
                <div class="stat-card">
                    <h4>Active Records</h4>
                    <div class="value" id="activeRecordsCount"><?php echo $active_records ?? 0; ?></div>
                    <p style="font-size: 0.85rem; color: var(--info); margin-top: 8px;"><i class="fas fa-file-medical-alt"></i> View details below</p>
                </div>
                <div class="stat-card">
                    <h4>Next Appointment</h4>
                    <div class="value" id="nextVisitDate" style="font-size: 1.5rem; line-height: 1.2;">
                        <?php echo $next_app_date ? date('M d, h:i A', strtotime($next_app_date)) : 'No upcoming'; ?>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--primary-color); margin-top: 8px;"><i class="fas fa-calendar-day"></i> Stay prepared</p>
                </div>
            </div>

            <div class="container panel-card">
                <div class="search-filter-container">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="universalSearch" placeholder="Search appointments, doctors, or results...">
                    </div>
                    <div class="filter-bar">
                        <select id="universalStatusFilter">
                            <option value="all">All Types</option>
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                        </select>
                        <select id="universalSortBy">
                            <option value="appointment_date_asc">Date (Asc)</option>
                            <option value="appointment_date_desc">Date (Desc)</option>
                            <option value="doctor_name_asc">Doctor (A-Z)</option>
                        </select>
                    </div>
                </div>

                <div class="appointments-section mb-5" id="confirmedAppointmentsSection" style="display: none;">
                    <h4 class="section-title mb-4"><i class="fas fa-calendar-check text-primary"></i> Confirmed Appointments</h4>
                    <ul class="doctor-list" id="upcomingAppointmentList"></ul>
                </div>

                <div class="appointments-section mb-5" id="pendingAppointmentsSection" style="display: none;">
                    <h4 class="section-title mb-4"><i class="fas fa-clock text-warning"></i> Pending Requests</h4>
                    <ul class="doctor-list" id="pendingAppointmentList"></ul>
                </div>

                <div class="medical-history-section" id="medicalHistorySection" style="display: none;">
                    <h4 class="section-title mb-4"><i class="fas fa-history text-info"></i> Medical History</h4>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Doctor</th>
                                    <th>Specialization</th>
                                    <th>Date & Time</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody id="medicalHistoryTableBody"></tbody>
                        </table>
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
            <h3><?php echo htmlspecialchars($patient_name); ?></h3>
            <p>Member Since 2024</p>
            
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
            </form>
            <div id="uploadMessage"></div>
            
            <hr>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-chart-line" style="margin-right: 12px;"></i> Dashboard</a></li>
                <li><a href="../includes/homepage.php"><i class="fas fa-home" style="margin-right: 12px;"></i> Homepage</a></li>
                <li><a href="#"><i class="fas fa-cog" style="margin-right: 12px;"></i> Settings</a></li>
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
    <script src="../assets/js/patient-dashboard-logic.js"></script>
    <script src="../assets/js/profile-overlay.js"></script>
</body>
</html>