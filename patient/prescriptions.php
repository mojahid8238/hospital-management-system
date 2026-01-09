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
}

$rawProfilePic = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';
$profilePic = preg_replace('#^\\.\\./#', '', $rawProfilePic); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Prescriptions | Hospital Management</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="book-appointment.php"><i class="fas fa-calendar-plus"></i> Book Appointment</a></li>
                <li><a href="prescriptions.php" class="active"><i class="fas fa-file-prescription"></i> Prescriptions</a></li>
                <li><a href="cancelled-appointments.php"><i class="fas fa-calendar-times"></i> Cancelled</a></li>
                <li><a href="../messaging/messaging.php"><i class="fas fa-comments"></i> Messages</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="welcome-section">
                <h2>My Prescriptions</h2>
                <p>View and download your digital prescriptions.</p>
            </div>

            <div class="container panel-card">
                <div id="prescriptionList" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 24px;">
                    <!-- JS will inject here -->
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                        Loading...
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Profile Overlay -->
    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <div class="profile-pic-wrapper" style="position: relative;">
                <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="profile-overlay-pic">
            </div>
            <h3><?php echo htmlspecialchars($patient_name); ?></h3>

            <hr>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-chart-line" style="margin-right: 12px;"></i> Dashboard</a></li>
                <li><a href="../includes/homepage.php"><i class="fas fa-home" style="margin-right: 12px;"></i> Homepage</a></li>
                <li><a href="settings.php"><i class="fas fa-user-cog" style="margin-right: 12px;"></i> Account Settings</a></li>
                <li><a href="../auth/logout.php" style="color: var(--error);"><i class="fas fa-sign-out-alt" style="margin-right: 12px;"></i> Logout</a></li>
            </ul>
            <button class="close-btn" id="closeProfile">Close Panel</button>
        </div>
    </div>

    <script src="../assets/js/ui-ux.js"></script>
    <script src="../assets/js/patient-prescriptions.js"></script>
    <script src="../assets/js/profile-overlay.js"></script>
</body>
</html>
