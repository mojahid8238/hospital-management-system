<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_patient();

// Fetch doctors
$doctors = [];
$result = $conn->query("SELECT d.id, d.name, d.profile_pic, s.name as specialization FROM doctors d JOIN specializations s ON d.specialization_id = s.id ORDER BY d.name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/view-appointments.css">

</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">☰ Toggle Menu</button>
            <a href="#">Patient Panel</a>
        </div>
        <div class="nav-right">
            <img src="/<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="user-icon user-profile-pic" id="profileToggle">
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
                <h2>Book a New Appointment</h2>
                <div class="search-filter-container">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchDoctor" placeholder="Search Doctor by name...">
                    </div>
                    <div class="filter-bar">
                        <i class="fas fa-filter"></i>
                        <select id="specializationFilter">
                            <option value="">All Specializations</option>
                            <?php
                            $specializations_filter = [];
                            $result_spec = $conn->query("SELECT id, name FROM specializations ORDER BY name ASC");
                            if ($result_spec) {
                                while ($row_spec = $result_spec->fetch_assoc()) {
                                    $specializations_filter[] = $row_spec;
                                }
                            }
                            foreach ($specializations_filter as $spec) {
                                echo "<option value=\"" . htmlspecialchars($spec['name']) . "\">" . htmlspecialchars($spec['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <ul class="doctor-list" id="doctorList">
                    <?php if (empty($doctors)): ?>
                        <p>No doctors available for booking at the moment.</p>
                    <?php else: ?>
                        <?php foreach ($doctors as $doctor):
                            $escaped_name = htmlspecialchars($doctor['name']);
                            $escaped_spec = htmlspecialchars($doctor['specialization']);
                            $base_img_dir = '../assets/images/profile_pics/';
                            $default_img_path = $base_img_dir . 'default-doctor.png';
                            $profile_pic_db = $doctor['profile_pic'] ?? '';
                            if (!empty($profile_pic_db) && $profile_pic_db !== 'default-avatar.png') {
                                $filename = basename($profile_pic_db);
                                $doctor_img_path = $base_img_dir . htmlspecialchars($filename);
                            } else {
                                $doctor_img_path = null; 
                            }
                        ?>
                            <li class="doctor-item" data-name="<?php echo strtolower($escaped_name); ?>" data-spec="<?php echo strtolower($escaped_spec); ?>">
                                <div class="doctor-avatar">
                                    <?php if ($doctor_img_path !== null): ?>
                                        <img src="<?php echo $doctor_img_path; ?>?t=<?php echo time(); ?>" 
                                             alt="Dr. <?php echo $escaped_name; ?> Profile"
                                             onerror="this.onerror=null; this.src='<?php echo $default_img_path; ?>';" class="user-profile-pic">
                                    <?php else:
                                        // Fallback to Font Awesome icon if no custom image is set 
                                    ?><i class="fas fa-user-md"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="doctor-info">
                                    <h4>Dr. <?php echo $escaped_name; ?></h4>
                                    <p><?php echo $escaped_spec; ?></p>
                                </div>
                                <a href="doctor-profile.php?id=<?php echo $doctor['id']; ?>" class="book-btn">Book Now</a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </main>
    </div>

    <!-- Profile side overlay -->
    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <img src="/<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay" class="user-profile-pic">
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
                <button type="submit" style="display: none;">Upload</button>
            </form>
            <div id="uploadMessage" style="margin-top: 10px; color: green;"></div>
            <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
            <hr>
            <ul>
                <li><a href="dashboard.php">Patient Dashboard</a></li>
                <li><a href="../includes/homepage.php">Patient Homepage</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>

    <script>
        const BASE_URL = '/';
    </script>
    <script src="../assets/js/profile-overlay.js"></script>
</body>
</html>