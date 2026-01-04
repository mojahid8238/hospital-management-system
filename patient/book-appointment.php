<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_patient();

// Fetch doctors
$doctors = [];
$result = $conn->query("SELECT d.id, d.name, d.profile_pic, s.name as specialization FROM doctors d LEFT JOIN specializations s ON d.specialization_id = s.id ORDER BY d.name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
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
    <title>Book Appointment | Hospital Management</title>
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
                <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            </div>
            <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="patientSidebar">
            <h3>Patient Menu</h3>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="book-appointment.php" class="active"><i class="fas fa-calendar-plus"></i> Book Appointment</a></li>
                <li><a href="prescriptions.php"><i class="fas fa-file-prescription"></i> Prescriptions</a></li>
                <li><a href="cancelled-appointments.php"><i class="fas fa-calendar-times"></i> Cancelled</a></li>
                <li><a href="../messaging/messaging.php"><i class="fas fa-comments"></i> Messages</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="welcome-section">
                <h2>Book Appointment</h2>
                <p>Find the best specialists and schedule your consultation today.</p>
            </div>

            <div class="container panel-card">
                <div class="search-filter-container">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchDoctor" placeholder="Search Doctor by name or specialization...">
                    </div>
                </div>

                <div class="doctor-list" id="doctorList">
                    <?php if (empty($doctors)): ?>
                        <div class="alert alert-info" style="padding: 24px; text-align: center;">
                            <i class="fas fa-user-md"></i> No doctors available at the moment.
                        </div>
                    <?php else: ?>
                        <?php foreach ($doctors as $doctor):
                            $dPic = preg_replace('#^\\.\\./#', '', $doctor['profile_pic'] ?? 'assets/images/default-avatar.png');
                        ?>
                            <div class="doctor-list-item" data-name="<?php echo strtolower(htmlspecialchars($doctor['name'])); ?>" data-spec="<?php echo strtolower(htmlspecialchars($doctor['specialization'] ?? 'General Specialist')); ?>">
                                <div class="doctor-avatar">
                                    <img src="../<?php echo htmlspecialchars($dPic); ?>?t=<?php echo time(); ?>" alt="Doctor">
                                </div>
                                <div class="doctor-details">
                                    <h4>Dr. <?php echo htmlspecialchars($doctor['name']); ?></h4>
                                    <p><i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($doctor['specialization'] ?? 'General Practitioner'); ?></p>
                                </div>
                                <div class="doctor-info">
                                    <p><i class="fas fa-info-circle"></i> Highly rated specialist</p>
                                </div>
                                <div class="button-group">
                                    <a href="doctor-profile.php?id=<?php echo $doctor['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-calendar-alt"></i> Book Now
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
            <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
            <p>Member Since 2024</p>
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

    <script src="../assets/js/ui-ux.js"></script>
    <script src="../assets/js/profile-overlay.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchDoctor');
            const items = document.querySelectorAll('.doctor-list-item');

            if(searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    items.forEach(item => {
                        const name = item.dataset.name;
                        const spec = item.dataset.spec;
                        item.style.display = (name.includes(query) || spec.includes(query)) ? 'grid' : 'none';
                    });
                });
            }
        });
    </script>
</body>
</html>