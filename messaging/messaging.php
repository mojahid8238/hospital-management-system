<?php
session_start();
include_once '../includes/db.php';
include_once '../includes/auth.php';

redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$logged_in_patient_id = null;
$logged_in_doctor_id = null;

if ($role === 'patient') {
    $stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $logged_in_patient_id = $row['id'];
    }
    $stmt->close();
} elseif ($role === 'doctor') {
    $stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $logged_in_doctor_id = $row['id'];
    }
    $stmt->close();
}

$doctor_id_param = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
$patient_id_param = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;
$appointment_id_param = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : null;

$other_participant_user_id = null;
$target_doctor_id = null;
$target_patient_id = null;

if ($role === 'patient' && $doctor_id_param) {
    $target_doctor_id = $doctor_id_param;
    $target_patient_id = $logged_in_patient_id;
    $stmt = $conn->prepare("SELECT user_id FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctor_id_param);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $other_participant_user_id = $row['user_id'];
    }
    $stmt->close();
} elseif ($role === 'doctor' && $patient_id_param) {
    $target_patient_id = $patient_id_param;
    $target_doctor_id = $logged_in_doctor_id;
    $stmt = $conn->prepare("SELECT user_id FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patient_id_param);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $other_participant_user_id = $row['user_id'];
    }
    $stmt->close();
}

$other_participant_name = null;
if ($other_participant_user_id) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $other_participant_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $other_participant_name = $row['name'];
    }
    $stmt->close();
}

if ($other_participant_user_id === null && ($doctor_id_param !== null || $patient_id_param !== null)) {
    $_SESSION['error_message'] = "Target user for messaging not found.";
    header("Location: ../index.php");
    exit();
}

$dashboard_link = "../";
if ($role === 'doctor') {
    $dashboard_link = "../doctor/dashboard.php";
} elseif ($role === 'patient') {
    $dashboard_link = "../patient/dashboard.php";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link rel="stylesheet" href="../assets/css/messaging.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="messaging-container">
        <div class="conversations-list" id="conversationsList">
            <div class="conversation-header">
                <a href="<?php echo $dashboard_link; ?>" class="back-link"><i class="fas fa-arrow-left"></i></a>
                <h3>Conversations</h3>
            </div>
            <div class="search-bar">
                <input type="text" placeholder="Search conversations...">
            </div>
            <ul id="conversationItems">
                <!-- Conversations will be loaded here -->
            </ul>
        </div>
        <div class="chat-window" id="chatWindow">
            <div class="chat-header" id="chatHeader">
                <!-- Chat partner's name and profile pic will be here -->
            </div>
            <div class="chat-messages" id="chatMessages">
                <!-- Messages will be loaded here -->
            </div>
            <div class="chat-input">
                <div id="imagePreviewContainer" style="display: none; position: relative; margin-bottom: 10px;">
                    <img id="imagePreview" src="#" alt="Image Preview" style="max-width: 100px; max-height: 100px; border-radius: 5px;">
                    <button id="clearImagePreview" style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.5); color: white; border: none; border-radius: 50%; cursor: pointer;">&times;</button>
                </div>
                <input type="file" id="imageInput" accept="image/*" style="display: none;">
                <button id="uploadImageBtn"><i class="fas fa-image"></i></button>
                <input type="text" id="messageInput" placeholder="Type a message...">
                <button id="sendMessageBtn"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
        const currentUserProfilePic = "<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>";
        window.otherParticipantUserId = <?php echo json_encode($other_participant_user_id); ?>;
        window.otherParticipantName = <?php echo json_encode($other_participant_name); ?>;
        const initialAppointmentId = <?php echo json_encode($appointment_id_param); ?>;
        const targetDoctorId = <?php echo json_encode($target_doctor_id); ?>;
        const targetPatientId = <?php echo json_encode($target_patient_id); ?>;
    </script>
    <script src="../assets/js/messaging.js"></script>
</body>
</html>
