<?php
date_default_timezone_set('UTC');
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_patient();

$patient_id = null;
$patient_name = '';

// Get the patient_id and name associated with the logged-in user
$stmt = $conn->prepare("SELECT id, name FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $patient_id = $row['id'];
    $patient_name = $row['name'];
    $_SESSION['patient_id'] = $patient_id; // Store patient_id in session
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/shared-table-design.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/patient-dashboard.css">
    <link rel="stylesheet" href="../assets/css/mini_messenger.css">
    <link rel="stylesheet" href="../assets/css/hidden_call_button.css">
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">☰ Toggle Menu</button>
            <a href="#">Patient Panel</a>
        </div>
        <div class="nav-right">
            <img src="/hospital-management-system/<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="patientSidebar">
            <h3>Patient Options</h3>
            <ul>
                <li><a href="book-appointment.php">Book New Appointment</a></li>
                <li><a href="dashboard.php">Your Appointments & History</a></li>
                <li><a href="cancelled-appointments.php">Cancelled Appointments</a></li>
                <li><a href="../messaging/messaging.php">Messages</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container panel-card">
                <h2 class="card-title mb-3">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Patient'); ?>!</h2>

                <div class="search-filter-container mb-4">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="universalSearch" placeholder="Search all appointments and history...">
                    </div>
                    <div class="filter-bar">
                        <i class="fas fa-filter"></i>
                        <select id="universalStatusFilter" class="form-select w-auto">
                            <option value="all">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                        </select>
                        <select id="universalSortBy" class="form-select w-auto">
                            <option value="appointment_date_asc">Date (Asc)</option>
                            <option value="appointment_date_desc">Date (Desc)</option>
                            <option value="doctor_name_asc">Doctor Name (A-Z)</option>
                            <option value="doctor_name_desc">Doctor Name (Z-A)</option>
                        </select>
                    </div>
                </div>

                <div class="appointments-section mb-5" id="confirmedAppointmentsSection">
                    <div id="confirmedAppointmentsContent">
                        <h3 class="mb-3">Your Confirmed Appointments</h3>
                        <ul class="doctor-list" id="upcomingAppointmentList">
                        </ul>
                    </div>
                </div>

                <div class="appointments-section mb-5" id="pendingAppointmentsSection">
                    <div id="pendingAppointmentsContent">
                        <h3 class="mb-3">Your Pending Appointments</h3>
                        <ul class="doctor-list" id="pendingAppointmentList">
                        </ul>
                    </div>
                </div>

                <div class="medical-history-section" id="medicalHistorySection">
                    <div id="medicalHistoryContent">
                        <h3 class="mb-3">Your Medical History</h3>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Appointment ID</th>
                                        <th>Doctor Image</th>
                                        <th>Doctor Name</th>
                                        <th>Specialization</th>
                                        <th>Date & Time</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody id="medicalHistoryTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <img src="/hospital-management-system/<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay">
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
                <button type="submit" style="display: none;">Upload</button>
            </form>
            <div id="uploadMessage" style="margin-top: 10px; color: green;"></div>
            <h3><?php echo htmlspecialchars($_SESSION['name'] ?? 'Patient'); ?></h3>
            <hr>
            <ul>
                <li><a href="dashboard.php">Patient Dashboard</a></li>
                <li><a href="../includes/homepage.php">Patient Homepage</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>

    <?php include_once '../includes/mini_messenger.php'; ?>

    <script>
        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/mini_messenger.js"></script>
    <script src="../assets/js/patient-dashboard-logic.js"></script>
    <script src="../assets/js/profile-overlay.js"></script>
</body>
</html>