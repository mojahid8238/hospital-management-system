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
}
$stmt->close();

$appointments = [];
if ($patient_id) {
    $stmt = $conn->prepare("SELECT a.id, d.name as doctor_name, d.profile_pic as doctor_profile_pic, a.appointment_date, a.reason, a.status FROM appointments a JOIN doctors d ON a.doctor_id = d.id WHERE a.patient_id = ? AND a.status = 'Cancelled' ORDER BY a.appointment_date DESC");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelled Appointments</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/shared-table.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
                <li><a href="medical-history.php">View Medical History</a></li>
                <li><a href="cancelled-appointments.php">Cancelled Appointments</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container panel-card">
                <h2 class="card-title mb-3">Your Cancelled Appointments</h2>
                <p class="text-muted">Welcome, <strong><?php echo htmlspecialchars($patient_name); ?></strong>! Here are your cancelled appointments:</p>

                <div class="search-filter-container">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchDoctor" placeholder="Search Doctor by name...">
                    </div>
                </div>

                <?php if (empty($appointments)): ?>
                    <div class="alert alert-info mt-4">You have no cancelled appointments.</div>
                <?php else: ?>
                    <ul class="doctor-list" id="appointmentList">
                        <?php 
                        $serial = 1;
                        foreach ($appointments as $appointment): 
                            $appointment_time = new DateTime($appointment['appointment_date']);
                            $now = new DateTime();
                            $interval = $now->diff($appointment_time);
                            $remaining_time = $interval->format('%a Days %h Hours %i Minutes');
                        ?>
                            <li class="doctor-item" data-name="<?php echo strtolower(htmlspecialchars($appointment['doctor_name'])); ?>" data-status="<?php echo strtolower(htmlspecialchars($appointment['status'])); ?>">
                                <div class="doctor-avatar">
                                    <img src="/hospital-management-system/<?php echo htmlspecialchars($appointment['doctor_profile_pic'] ?? 'assets/images/default-avatar.png'); ?>" 
                                        alt="<?php echo htmlspecialchars($appointment['doctor_name']); ?>" 
                                        class="rounded-circle">
                                </div>
                                <div class="doctor-info">
                                    <h4><?php echo htmlspecialchars($appointment['doctor_name']); ?></h4>
                                    <p>Appointment: <?php echo date('Y-m-d H:i', strtotime($appointment['appointment_date'])); ?></p>
                                    <p>Original Remaining: <?php echo $remaining_time; ?></p>
                                </div>
                                <div class="doctor-info">
                                    <p>Reason: <?php echo htmlspecialchars($appointment['reason']); ?></p>
                                    <p>Status: <span class="badge bg-<?php echo strtolower(htmlspecialchars($appointment['status'])); ?>"><?php echo htmlspecialchars(ucfirst($appointment['status'])); ?></span></p>
                                </div>
                                <div class="doctor-info">
                                    <!-- No action buttons for cancelled appointments -->
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Profile side overlay -->
    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <img src="/hospital-management-system/<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay">
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
                <button type="submit" style="display: none;">Upload</button>
            </form>
            <div id="uploadMessage" style="margin-top: 10px; color: green;"></div>
            <h3><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
            <p>Role: Patient</p>
            <hr>
            <h4>Dashboards</h4>
            <ul>
                <li><a href="dashboard.php">Patient Dashboard</a></li>
                <li><a href="../includes/homepage.php">Patient Homepage</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const patientSidebar = document.getElementById('patientSidebar');
            sidebarToggle.addEventListener('click', () => {
                patientSidebar.classList.toggle('closed');
            });

            const profileToggle = document.getElementById('profileToggle');
            const profileOverlay = document.getElementById('profileOverlay');
            profileToggle.addEventListener('click', (event) => {
                event.stopPropagation();
                profileOverlay.classList.add('open');
            });

            profileOverlay.addEventListener('click', function(event) {
                if (event.target === profileOverlay) {
                    profileOverlay.classList.remove('open');
                }
            });

            const mainContent = document.getElementById('mainContent');
            mainContent.addEventListener('click', () => {
                profileOverlay.classList.remove('open');
            });

            const searchDoctor = document.getElementById('searchDoctor');
            const appointmentList = document.getElementById('appointmentList');
            const appointmentItems = Array.from(appointmentList.getElementsByClassName('doctor-item'));

            function filterAppointments() {
                const searchTerm = searchDoctor.value.toLowerCase();

                appointmentItems.forEach(item => {
                    const name = item.dataset.name;

                    const nameMatch = name.includes(searchTerm);

                    if (nameMatch) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }

            searchDoctor.addEventListener('input', filterAppointments);
        });
    </script>
</body>
</html>