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

if (!is_logged_in()) {
    $response['message'] = 'Unauthorized: User not logged in.';
    echo json_encode($response);
    exit();
}

if (!is_doctor()) {
    $response['message'] = 'Unauthorized: User is not a doctor.';
    echo json_encode($response);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$appointment_id = $input['appointment_id'] ?? null;

if (!$appointment_id) {
    $response['message'] = 'Appointment ID is required.';
    echo json_encode($response);
    exit();
}

// Verify that the doctor owns this appointment (security check)
$doctor_id = null;
$stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $doctor_id = $row['id'];
}
$stmt->close();

if (!$doctor_id) {
    $response['message'] = 'Doctor not found or not associated with this user account.';
    echo json_encode($response);
    exit();
}

// Check if the appointment exists, belongs to the doctor, and is in 'Online' or 'Offline' status
$stmt = $conn->prepare("SELECT status FROM appointments WHERE id = ? AND doctor_id = ?");
$stmt->bind_param("ii", $appointment_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if ($row['status'] !== 'Online' && $row['status'] !== 'Offline') {
        $response['message'] = 'Only accepted appointments (Online/Offline) can be marked as completed.';
        echo json_encode($response);
        exit();
    }
} else {
    $response['message'] = 'Appointment not found for this doctor or does not belong to them.';
    echo json_encode($response);
    exit();
}

// Proceed with updating status to 'Completed'
$stmt = $conn->prepare("UPDATE appointments SET status = 'Completed' WHERE id = ? AND doctor_id = ?");
$stmt->bind_param("ii", $appointment_id, $doctor_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Appointment marked as completed successfully.';
    } else {
        $response['message'] = 'Appointment not found or no changes made (possibly already completed).';
    }
} else {
    $response['message'] = 'Database error during update: ' . $stmt->error;
}
$stmt->close();
$conn->close();
echo json_encode($response);
?>