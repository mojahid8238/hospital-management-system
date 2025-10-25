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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="doctor-profile-container">
                <div class="doctor-details-card">
                    <div class="profile-summary">
                        <div class="large-avatar">
                            <img src="/<?php echo htmlspecialchars($profile_pic ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Doctor Profile Picture" class="user-profile-pic">
                        </div>
                        <div class="profile-header">
                            <h3><?php echo htmlspecialchars($name); ?></h3>
                            <p>★★★★☆ (4.5)</p>
                            <a href="../messaging/messaging.php?doctor_id=<?php echo $doctor_id; ?>" class="btn btn-sm btn-outline-success mt-2">Message Doctor</a>
                        </div>
                    </div>
                    <div class="section-title">Description</div>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                    <div class="section-title">Reviews</div>
                    <div class="review-item">
                        <p><strong>Patient A:</strong> "Great doctor, very professional."</p>
                    </div>
                    <div class="review-item">
                        <p><strong>Patient B:</strong> "Highly recommended."</p>
                    </div>
                </div>
                <div class="booking-form-card">
                    <h4>Book an Appointment</h4>
                    <?php if (!empty($message)) echo $message; ?>
                    <form action="doctor-profile.php?id=<?php echo $doctor_id; ?>" method="POST" enctype="multipart/form-data">
                        <label for="appointment_date">Appointment Date:</label>
                        <select id="appointment_date" name="appointment_date" required>
                            <?php
                                for ($i = 0; $i < 7; $i++) {
                                    $date = date('Y-m-d', strtotime("+" . $i . " days"));
                                    echo "<option value='" . htmlspecialchars($date) . "'>" . htmlspecialchars($date) . "</option>";
                                }
                            ?>
                        </select>
                        
                        <label for="appointment_time">Appointment Time:</label>
                        <select id="appointment_time" name="appointment_time" required>
                            <?php
                                for ($h = 9; $h < 17; $h++) {
                                    for ($m = 0; $m < 60; $m += 30) {
                                        $time = sprintf('%02d:%02d', $h, $m);
                                        echo "<option value='" . htmlspecialchars($time) . "'>" . htmlspecialchars($time) . "</option>";
                                    }
                                }
                            ?>
                        </select>

                        <label for="reason">Reason for Appointment:</label>
                        <textarea id="reason" name="reason" rows="4" required></textarea>

                        <label for="document">Upload Document (optional):</label>
                        <input type="file" id="document" name="document">

                        <label>Appointment Type:</label>
                        <div class="radio-group">
                            <input type="radio" id="online" name="appointment_type" value="Online" required>
                            <label for="online">Online</label>
                            <input type="radio" id="offline" name="appointment_type" value="Offline" required>
                            <label for="offline">Offline</label>
                        </div>

                        <button type="submit" class="book-btn">Book Appointment</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Profile side overlay -->
    <div class="profile-overlay" id="profileOverlay">
        <!-- ... content from dashboard ... -->
    </div>

    <script>
        // Script from dashboard
    </script>
</body>
</html>