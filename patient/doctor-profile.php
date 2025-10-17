<?php
date_default_timezone_set('UTC');
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_patient();

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
        $appointment_date = $_POST['appointment_date'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $document = $_FILES['document'] ?? null;

        if (empty($appointment_date) || empty($reason)) {
            $message = "<p style='color: red;'>Appointment date and reason are required.</p>";
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

            $appointment_type = trim($_POST['appointment_type'] ?? 'Scheduled'); // Default to Scheduled if not set

            $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, reason, image, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissss", $patient_id, $doctor_id, $appointment_date, $reason, $document_name, $appointment_type);

            if ($stmt->execute()) {
                $message = "<p style='color: green;'>Appointment booked successfully!</p>";
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

// Fetch doctor details
$stmt = $conn->prepare("SELECT name, image FROM doctors WHERE id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$stmt->bind_result($name, $image);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
   <link rel="stylesheet" href="../assets/css/doctor-profile.css">
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <a href="../includes/homepage.php">Patient Panel</a>
        </div>
       
    </header>

    <div class="container">
        <div class="profile-header">
            <img src="../assets/images/<?php echo htmlspecialchars($image ?? 'default-avatar.png'); ?>" alt="Doctor Profile Picture">
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($name); ?></h2>
            </div>
        </div>

        <div class="ratings">
            <h4>Ratings</h4>
            <p>★★★★☆ (4.5)</p>
        </div>

        <div class="description">
            <h4>Description</h4>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
        </div>

        <div class="reviews">
            <h4>Reviews</h4>
            <div class="review-item">
                <p><strong>Patient A:</strong> "Great doctor, very professional."</p>
            </div>
            <div class="review-item">
                <p><strong>Patient B:</strong> "Highly recommended."</p>
            </div>
        </div>

        <div class="booking-form">
            <h4>Book an Appointment</h4>
            <?php if (!empty($message)) echo $message; ?>
            <form action="doctor-profile.php?id=<?php echo $doctor_id; ?>" method="POST" enctype="multipart/form-data">
                <label for="appointment_date">Appointment Date:</label>
                <input type="datetime-local" id="appointment_date" name="appointment_date" required>
                <br>
                <label for="reason">Reason for Appointment:</label>
                <textarea id="reason" name="reason" rows="4" required></textarea>
                <br>
                <label for="document">Upload Document (optional):</label>
                <input type="file" id="document" name="document">
                <br>
                <label>Appointment Type:</label><br>
                <input type="radio" id="online" name="appointment_type" value="Online" required>
                <label for="online">Online</label><br>
                <input type="radio" id="offline" name="appointment_type" value="Offline" required>
                <label for="offline">Offline</label><br>
                <br>
                <button type="submit" class="book-btn">Book Appointment</button>
            </form>
        </div>
    </div>
</body>
</html>
