<?php
session_start();
include_once '../includes/db.php';
include_once '../includes/auth.php';

redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role']; // Assuming role is stored in session

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
    // If logged in as patient, the other participant is the doctor
    $target_doctor_id = $doctor_id_param;
    $target_patient_id = $logged_in_patient_id; // This should be the patient's own ID

    // Get the user_id of the doctor
    $stmt = $conn->prepare("SELECT user_id FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctor_id_param);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $other_participant_user_id = $row['user_id'];
    }
    $stmt->close();
} elseif ($role === 'doctor' && $patient_id_param) {
    // If logged in as doctor, the other participant is the patient
    $target_patient_id = $patient_id_param;
    $target_doctor_id = $logged_in_doctor_id; // This should be the doctor's own ID

    // Get the user_id of the patient
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

error_log("doctor_id_param: " . var_export($doctor_id_param, true));
error_log("patient_id_param: " . var_export($patient_id_param, true));
error_log("other_participant_user_id before json_encode: " . var_export($other_participant_user_id, true));

// If other_participant_user_id is still null, it means the target doctor/patient was not found.
// Redirect to dashboard or show an error.
if ($other_participant_user_id === null && ($doctor_id_param !== null || $patient_id_param !== null)) {
    $_SESSION['error_message'] = "Target user for messaging not found.";
    header("Location: ../index.php"); // Redirect to a suitable page
    exit();
}

// Determine the correct dashboard link based on role
$dashboard_link = "../"; // Default link
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
    <header class="navbar">
        <div class="nav-left">
            <a href="<?php echo $dashboard_link; ?>">Back to Dashboard</a>
        </div>
        <div class="nav-right">
            <img src="/hospital-management-system/<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="user-icon">
        </div>
    </header>

    <div class="messaging-container">
        <div class="conversations-list" id="conversationsList">
            <div class="conversation-header">
                <h3>Conversations</h3>
            </div>
            <ul id="conversationItems">
                <!-- Conversations will be loaded here -->
            </ul>
        </div>
        <div class="chat-window" id="chatWindow">
            <div class="chat-header" id="chatHeader">
                <!-- Chat partner's name will be here -->
            </div>
            <div class="chat-messages" id="chatMessages">
                <!-- Messages will be loaded here -->
            </div>
            <div class="chat-input">
                <input type="text" id="messageInput" placeholder="Type a message...">
                <button id="sendMessageBtn"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
        window.otherParticipantUserId = <?php echo json_encode($other_participant_user_id); ?>;
        window.otherParticipantName = <?php echo json_encode($other_participant_name); ?>;
        const initialAppointmentId = <?php echo json_encode($appointment_id_param); ?>;
        const targetDoctorId = <?php echo json_encode($target_doctor_id); ?>;
        const targetPatientId = <?php echo json_encode($target_patient_id); ?>;

        console.log("PHP Debug - other_participant_user_id:", <?php echo json_encode($other_participant_user_id); ?>);
        console.log("PHP Debug - doctor_id_param:", <?php echo json_encode($doctor_id_param); ?>);
        console.log("PHP Debug - patient_id_param:", <?php echo json_encode($patient_id_param); ?>);
    </script>
    <script src="../assets/js/messaging.js"></script>
</body>
</html>