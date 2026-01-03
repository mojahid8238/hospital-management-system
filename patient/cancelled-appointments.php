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
    $stmt = $conn->prepare("SELECT a.id, d.id as doctor_id, d.name as doctor_name, d.profile_pic as doctor_profile_pic, a.appointment_date, a.reason, a.status FROM appointments a JOIN doctors d ON a.doctor_id = d.id WHERE a.patient_id = ? AND a.status = 'Cancelled' ORDER BY a.appointment_date DESC");
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
    <title>Cancelled Appointments | Hospital Management</title>
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
            <a href="dashboard.php">HealthCare</a>
        </div>
        <div class="nav-right">
            <div class="user-info">
                <span><?php echo htmlspecialchars($patient_name); ?></span>
            </div>
            <img src="/<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="user-icon user-profile-pic" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="patientSidebar">
            <h3>Patient Menu</h3>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="book-appointment.php"><i class="fas fa-calendar-plus"></i> Book Appointment</a></li>
                <li><a href="dashboard.php"><i class="fas fa-file-medical"></i> Medical History</a></li>
                <li><a href="cancelled-appointments.php" class="active"><i class="fas fa-calendar-times"></i> Cancelled</a></li>
                <li><a href="../messaging/messaging.php"><i class="fas fa-comments"></i> Messages</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="welcome-section">
                <h2>Cancelled Appointments</h2>
                <p>Track your health journey and previous booking history.</p>
            </div>

            <div class="container panel-card">
                <div class="search-filter-container">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchDoctor" placeholder="Search Doctor by name...">
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
                                data-name="<?php echo strtolower(htmlspecialchars($appointment['doctor_name'])); ?>" 
                                id="appointment-<?php echo $appointment['id']; ?>">
                                <div class="doctor-avatar">
                                    <img src="/<?php echo htmlspecialchars($appointment['doctor_profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" 
                                        alt="Doctor">
                                </div>
                                <div class="doctor-details">
                                    <h4>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></h4>
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
                <img src="/<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="user-profile-pic">
            </div>
            <h3><?php echo htmlspecialchars($patient_name); ?></h3>
            <p>Member Since 2024</p>
            <hr>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-th-large" style="margin-right: 12px;"></i> Dashboard</a></li>
                <li><a href="../includes/homepage.php"><i class="fas fa-home" style="margin-right: 12px;"></i> Homepage</a></li>
                <li><a href="#"><i class="fas fa-cog" style="margin-right: 12px;"></i> Settings</a></li>
                <li><a href="../auth/logout.php" style="color: var(--error);"><i class="fas fa-sign-out-alt" style="margin-right: 12px;"></i> Logout</a></li>
            </ul>
            <button class="close-btn" id="closeProfile">Close Panel</button>
        </div>
    </div>

    <script src="../assets/js/ui-ux.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            const searchInput = document.getElementById('searchDoctor');
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