<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('UTC');

// Function to handle fatal errors and output JSON
function fatal_error_handler($errno, $errstr, $errfile, $errline) {
    if ($errno === E_ERROR || $errno === E_PARSE || $errno === E_CORE_ERROR || $errno === E_COMPILE_ERROR) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => "Fatal Error: $errstr in $errfile on line $errline"]);
        exit();
    }
    return false;
}
set_error_handler("fatal_error_handler");

require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// Log session data for debugging
error_log("Session data: " . print_r($_SESSION, true));

// Check if user is logged in
$isLoggedIn = is_logged_in();
error_log("is_logged_in() result: " . ($isLoggedIn ? 'true' : 'false'));
if (!$isLoggedIn) {
    $response['message'] = 'Unauthorized: User not logged in.';
    echo json_encode($response);
    exit();
}

// Check if user is a doctor
$isDoctor = is_doctor();
error_log("is_doctor() result: " . ($isDoctor ? 'true' : 'false'));
if (!$isDoctor) {
    $response['message'] = 'Unauthorized: User is not a doctor.';
    echo json_encode($response);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$appointment_id = $input['appointment_id'] ?? null;
$appointment_type = $input['type'] ?? null;

// Log received data
error_log("Received appointment_id for acceptance: " . ($appointment_id ?? 'null'));
error_log("Received appointment_type for acceptance: " . ($appointment_type ?? 'null'));

if (!$appointment_id || !$appointment_type) {
    $response['message'] = 'Appointment ID and Type are required.';
    echo json_encode($response);
    exit();
}

// Validate appointment_type
if (!in_array($appointment_type, ['Online', 'Offline'])) {
    $response['message'] = "Invalid appointment type received: " . htmlspecialchars($appointment_type) . ". Expected 'Online' or 'Offline'.";
    echo json_encode($response);
    exit();
}

// Verify that the doctor owns this appointment (security check)
$doctor_id = null;
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'null'));
$stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $doctor_id = $row['id'];
}
$stmt->close();

// Log retrieved doctor_id
error_log("Retrieved doctor_id for acceptance: " . ($doctor_id ?? 'null'));

if (!$doctor_id) {
    $response['message'] = 'Doctor not found or not associated with this user account.';
    echo json_encode($response);
    exit();
}

// Check if the appointment exists, belongs to the doctor, and is in 'Pending' status
$stmt = $conn->prepare("SELECT status FROM appointments WHERE id = ? AND doctor_id = ?");
$stmt->bind_param("ii", $appointment_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    error_log("Appointment status from DB: " . $row['status']);
    if ($row['status'] !== 'Pending') {
        $response['message'] = 'Only pending appointments can be accepted.';
        echo json_encode($response);
        exit();
    }
} else {
    $response['message'] = 'Appointment not found for this doctor or does not belong to them.';
    echo json_encode($response);
    exit();
}

    // Proceed with updating status to 'Scheduled'
$stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?");
$status_scheduled = 'Scheduled';
$stmt->bind_param("sii", $status_scheduled, $appointment_id, $doctor_id);
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Appointment accepted successfully. Status set to ' . $appointment_type . '.';
    } else {
        $response['message'] = 'Appointment not found or no changes made (possibly already accepted).';
    }
} else {
    $response['message'] = 'Database error during update: ' . $stmt->error;
    error_log("Database UPDATE error: " . $stmt->error);
}
$stmt->close();
$conn->close();
echo json_encode($response);
?>