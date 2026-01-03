<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_patient();

date_default_timezone_set('UTC');

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = null;
    $stmt_patient = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt_patient->bind_param("i", $_SESSION['user_id']);
    $stmt_patient->execute();
    $stmt_patient->bind_result($patient_id);
    $stmt_patient->fetch();
    $stmt_patient->close();

    if ($patient_id) {
        $doctor_id = $_GET['id'];
        $appointment_date_str = $_POST['appointment_date'] ?? '';
        $appointment_time_str = $_POST['appointment_time'] ?? '';
        $appointment_date = $appointment_date_str . ' ' . $appointment_time_str;
        $reason = $_POST['reason'] ?? '';
        $document = $_FILES['document'] ?? null;

        if (empty($appointment_date_str) || empty($appointment_time_str) || empty($reason)) {
            $message = "<p style='color: red;'>All fields are required.</p>";
        } else {
            $document_name = '';
            if ($document && $document['error'] === UPLOAD_ERR_OK) {
                $document_name = basename($document['name']);
                $target_dir = "../assets/documents/";
                if (!is_dir($target_dir)) {
                    if (!mkdir($target_dir, 0777, true)) {
                        $message = "<p style='color: red;'>Failed to create documents directory.</p>";
                    }
                }
                $target_file = $target_dir . $document_name;
                if (!move_uploaded_file($document['tmp_name'], $target_file)) {
                    $message = "<p style='color: red;'>Failed to move uploaded file. Check permissions.</p>";
                }
            }

            $appointment_type = trim($_POST['appointment_type'] ?? 'Scheduled');
            $status = 'Pending'; // Default status for new appointments

            $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, reason, image, status, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssss", $patient_id, $doctor_id, $appointment_date, $reason, $document_name, $status, $appointment_type);

            if ($stmt->execute()) {
                
                header('Location: dashboard.php');
                exit();
            } else {
                $message = "<p style='color: red;'>Error booking appointment: " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
    } else {
        $message = "<p style='color: red;'>Could not find patient information.</p>";
    }
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: book-appointment.php');
    exit();
}

$doctor_id = $_GET['id'];

$stmt = $conn->prepare("SELECT name, profile_pic FROM doctors WHERE id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$stmt->bind_result($name, $profile_pic);
$stmt->fetch();
$stmt->close();

if (!$name) {
    echo "Doctor not found.";
    exit();
}

$rawProfilePic = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';
$profilePic = preg_replace('#^\\.\\./#', '', $rawProfilePic);
$dPic = preg_replace('#^\\.\\./#', '', $profile_pic ?? 'assets/images/default-avatar.png');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile | Hospital Management</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/shared-table-design.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .doctor-profile-container {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 32px;
            margin-top: 24px;
        }
        @media (max-width: 992px) {
            .doctor-profile-container { grid-template-columns: 1fr; }
        }
        .profile-summary {
            text-align: center;
            padding: 32px;
        }
        .large-avatar img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--bg-color);
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
        }
        .booking-form-card label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-main);
        }
        .booking-form-card select, 
        .booking-form-card textarea,
        .booking-form-card input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            background: var(--bg-color);
        }
        .radio-group {
            display: flex;
            gap: 24px;
            margin-bottom: 24px;
        }
    </style>
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
            <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="patientSidebar">
            <h3>Patient Menu</h3>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="book-appointment.php" class="active"><i class="fas fa-calendar-plus"></i> Book Appointment</a></li>
                <li><a href="dashboard.php"><i class="fas fa-file-medical"></i> Medical History</a></li>
                <li><a href="cancelled-appointments.php"><i class="fas fa-calendar-times"></i> Cancelled</a></li>
                <li><a href="../messaging/messaging.php"><i class="fas fa-comments"></i> Messages</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="welcome-section">
                <h2>Doctor Profile & Booking</h2>
                <p>Learn more about your specialist and schedule your visit.</p>
            </div>

            <div class="doctor-profile-container">
                <div class="panel-card">
                    <div class="profile-summary">
                        <div class="large-avatar">
                            <img src="../<?php echo htmlspecialchars($dPic); ?>?t=<?php echo time(); ?>" alt="Doctor Profile Picture">
                        </div>
                        <div class="profile-header">
                            <h3 class="mb-2">Dr. <?php echo htmlspecialchars($name); ?></h3>
                            <div class="stars mb-3" style="color: #f1c40f;">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                                <span style="color: var(--text-muted); font-size: 0.9rem;">(4.5/5)</span>
                            </div>
                            <a href="../messaging/messaging.php?doctor_id=<?php echo $doctor_id; ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-comment-medical"></i> Message Doctor
                            </a>
                        </div>
                    </div>
                    <hr>
                    <div class="mt-4">
                        <h4 class="section-title mb-3"><i class="fas fa-info-circle"></i> Biography</h4>
                        <p class="text-muted">Dr. <?php echo htmlspecialchars($name); ?> is a highly qualified specialist with years of clinical experience in providing compassionate care and advanced medical treatments.</p>
                    </div>
                </div>

                <div class="panel-card booking-form-card">
                    <h4 class="section-title mb-4"><i class="fas fa-calendar-alt"></i> Select Date & Time</h4>
                    <?php if (!empty($message)) echo $message; ?>
                    <form action="doctor-profile.php?id=<?php echo $doctor_id; ?>" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="appointment_date">Consultation Date</label>
                                <select id="appointment_date" name="appointment_date" required>
                                    <?php
                                        for ($i = 0; $i < 14; $i++) {
                                            $date = date('Y-m-d', strtotime("+" . $i . " days"));
                                            $displayDate = date('D, M d', strtotime($date));
                                            echo "<option value='" . htmlspecialchars($date) . "'>" . htmlspecialchars($displayDate) . "</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="appointment_time">Available Slots</label>
                                <select id="appointment_time" name="appointment_time" required>
                                    <?php
                                        for ($h = 9; $h < 17; $h++) {
                                            for ($m = 0; $m < 60; $m += 60) {
                                                $time = sprintf('%02d:%02d', $h, $m);
                                                echo "<option value='" . htmlspecialchars($time) . "'>" . htmlspecialchars($time) . " AM</option>";
                                            }
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <label for="reason">Reason for Appointment</label>
                        <textarea id="reason" name="reason" rows="3" placeholder="Briefly describe your symptoms or reason for visit..." required></textarea>

                        <label for="document">Supporting Documents (Optional)</label>
                        <input type="file" id="document" name="document">

                        <label>Appointment Channel</label>
                        <div class="radio-group">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="appointment_type" value="Online" required> Online Video
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="appointment_type" value="Offline" required> In-Person Clinic
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3">
                            <i class="fas fa-check-circle"></i> Confirm Appointment
                        </button>
                    </form>
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
                <li><a href="dashboard.php"><i class="fas fa-th-large" style="margin-right: 12px;"></i> Dashboard</a></li>
                <li><a href="../includes/homepage.php"><i class="fas fa-home" style="margin-right: 12px;"></i> Homepage</a></li>
                <li><a href="#"><i class="fas fa-cog" style="margin-right: 12px;"></i> Settings</a></li>
                <li><a href="../auth/logout.php" style="color: var(--error);"><i class="fas fa-sign-out-alt" style="margin-right: 12px;"></i> Logout</a></li>
            </ul>
            <button class="close-btn" id="closeProfile">Close Panel</button>
        </div>
    </div>

    <script src="../assets/js/ui-ux.js"></script>
    <script src="../assets/js/profile-overlay.js"></script>
</body>
</html>