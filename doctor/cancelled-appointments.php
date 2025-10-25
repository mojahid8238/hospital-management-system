<?php
date_default_timezone_set('UTC');
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_doctor();

$doctor_id = null;
$doctor_name = '';

// Get the doctor_id and name associated with the logged-in user
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

$appointments = [];
if ($doctor_id) {
    $stmt = $conn->prepare("SELECT a.id, p.name as patient_name, p.profile_pic as patient_profile_pic, a.appointment_date, a.reason, a.status FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.doctor_id = ? AND a.status = 'Cancelled' ORDER BY a.appointment_date DESC");
    $stmt->bind_param("i", $doctor_id);
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
    <link rel="stylesheet" href="../assets/css/shared-table-design.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">☰ Toggle Menu</button>
            <a href="#">Doctor Panel</a>
        </div>
        <div class="nav-right">
            <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="user-icon user-profile-pic" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="doctorSidebar">
            <h3>Doctor Options</h3>
            <ul>
                <li><a href="dashboard.php">View Your Appointments</a></li>
                <li><a href="cancelled-appointments.php">Cancelled Appointments</a></li>
                <li><a href="../messaging/messaging.php">Messages</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container panel-card">
                <h2 class="card-title mb-3">Welcome, <strong>Dr. <?php echo htmlspecialchars($doctor_name); ?> </h2>
                <p></strong>Here are your cancelled appointments:</p>

                <div class="search-filter-container">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchPatient" placeholder="Search Patient by name...">
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
                            <li class="doctor-item" 
                                data-name="<?php echo strtolower(htmlspecialchars($appointment['patient_name'])); ?>" 
                                data-status="<?php echo strtolower(htmlspecialchars($appointment['status'])); ?>"
                                id="appointment-<?php echo $appointment['id']; ?>">
                                <div class="doctor-avatar">
                                    <img src="../<?php echo htmlspecialchars($appointment['patient_profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" 
                                        alt="<?php echo htmlspecialchars($appointment['patient_name']); ?>" 
                                        class="rounded-circle user-profile-pic">
                                </div>
                                <div class="doctor-info">
                                    <h4><?php echo htmlspecialchars($appointment['patient_name']); ?></h4>
                                    <p>Appointment: <?php echo date('Y-m-d H:i', strtotime($appointment['appointment_date'])); ?></p>
                                    <p>Original Remaining: <?php echo $remaining_time; ?></p>
                                </div>
                                <div class="doctor-info">
                                    <p>Reason: <?php echo htmlspecialchars($appointment['reason']); ?></p>
                                    <p>Status: <span class="badge bg-<?php echo strtolower(htmlspecialchars($appointment['status'])); ?>"><?php echo htmlspecialchars(ucfirst($appointment['status'])); ?></span></p>
                                </div>
                                <div class="doctor-info">
                                    <button class="btn btn-sm btn-outline-danger remove-appointment-btn" data-appointment-id="<?php echo $appointment['id']; ?>">Remove</button>
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
            <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay" class="user-profile-pic" style="position: relative; z-index: 10; cursor: pointer;">
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
                <button type="submit" style="display: none;">Upload</button>
            </form>
            <div id="uploadMessage" style="margin-top: 10px; color: green;"></div>
            <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
            <hr>
            <ul>
                <li><a href="dashboard.php">Doctor Dashboard</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>

    <script>
        const BASE_URL = '/';
    </script>
    <script src="../assets/js/profile-overlay.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function fetchAndRenderCancelledAppointments() {
                // Re-fetch the entire page content or just the appointment list
                // For simplicity, we'll just reload the page for now.
                // A more advanced solution would involve fetching JSON and re-rendering the list.
                window.location.reload(); 
            }

            document.querySelectorAll('.remove-appointment-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const appointmentId = this.dataset.appointmentId;
                    if (confirm('Are you sure you want to permanently remove this cancelled appointment?')) {
                        fetch('delete_cancelled_appointment.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ appointment_id: appointmentId })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert(data.message);
                                fetchAndRenderCancelledAppointments(); // Refresh the list
                            } else {
                                alert('Failed to remove appointment: ' + data.message);
                            }
                        })
                        .catch(error => console.error('Error removing appointment:', error));
                    }
                });
            });
        });
    </script>
</body>
</html>