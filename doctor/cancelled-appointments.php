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
    <title>Cancelled Appointments | Health Care</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo.ico">
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
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">
                <i class="fas fa-bars"></i>
                <span>Menu</span>
            </button>
            <a href="dashboard.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; font-weight: 800; font-size: 1.25rem;">
                <img src="../assets/images/logo.png" alt="Logo" style="height: 35px; border-radius: 5px;">
                Health Care
            </a>
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
                <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="cancelled-appointments.php" class="active"><i class="fas fa-calendar-times"></i> Cancelled</a></li>
                <li><a href="../messaging/messaging.php"><i class="fas fa-comments"></i> Messages</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="welcome-section">
                <h2>Cancelled Appointments</h2>
                <p>History of revoked or declined medical consultations.</p>
            </div>

            <div class="container panel-card">
                <div class="search-filter-container">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchPatient" placeholder="Search Patient by name...">
                    </div>
                </div>

                <?php if (empty($appointments)): ?>
                    <div class="alert alert-info mt-4" style="padding: 24px; background: var(--bg-color); border-radius: var(--radius-md); text-align: center;">
                        <i class="fas fa-info-circle"></i> You have no cancelled appointments.
                    </div>
                <?php else: ?>
                    <div class="doctor-list" id="appointmentList">
                        <?php 
                        foreach ($appointments as $appointment): 
                            $date = date('M d, Y', strtotime($appointment['appointment_date']));
                            $time = date('H:i', strtotime($appointment['appointment_date']));
                        ?>
                            <div class="doctor-list-item" 
                                data-name="<?php echo strtolower(htmlspecialchars($appointment['patient_name'])); ?>" 
                                id="appointment-<?php echo $appointment['id']; ?>">
                                <div class="doctor-avatar">
                                    <img src="../<?php echo htmlspecialchars($appointment['patient_profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" 
                                        alt="Patient">
                                </div>
                                <div class="doctor-details">
                                    <h4><?php echo htmlspecialchars($appointment['patient_name']); ?></h4>
                                    <p><i class="fas fa-notes-medical"></i> <?php echo htmlspecialchars($appointment['reason']); ?></p>
                                </div>
                                <div class="doctor-info">
                                    <p><strong><i class="far fa-calendar-alt"></i></strong> <?php echo $date; ?></p>
                                    <p><strong><i class="far fa-clock"></i></strong> <?php echo $time; ?></p>
                                </div>
                                <div class="button-group">
                                    <button class="btn btn-outline-danger btn-sm remove-appointment-btn" data-appointment-id="<?php echo $appointment['id']; ?>">
                                        <i class="fas fa-trash-alt"></i> Remove
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
            <h3>Dr. <?php echo htmlspecialchars($doctor_name); ?></h3>
            <p>Medical Practitioner</p>
            
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
            </form>
            <div id="uploadMessage"></div>
            
            <hr>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-chart-line" style="margin-right: 12px;"></i> Dashboard</a></li>
                <li><a href="settings.php"><i class="fas fa-user-cog" style="margin-right: 12px;"></i> Account Settings</a></li>
                <li><a href="../auth/logout.php" style="color: var(--error);"><i class="fas fa-sign-out-alt" style="margin-right: 12px;"></i> Logout</a></li>
            </ul>
            <button class="close-btn" id="closeProfile">Close Panel</button>
        </div>
    </div>

    <script src="../assets/js/ui-ux.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            const searchInput = document.getElementById('searchPatient');
            const items = document.querySelectorAll('.doctor-list-item');

            if(searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    items.forEach(item => {
                        const name = item.dataset.name;
                        item.style.display = name.includes(query) ? 'grid' : 'none';
                    });
                });
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
                                document.getElementById('appointment-' + appointmentId).remove();
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
    <script src="../assets/js/profile-overlay.js"></script>
</body>
</html>