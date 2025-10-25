<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('UTC');
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if (!is_logged_in()) {
    $response['message'] = 'Unauthorized: User not logged in.';
    echo json_encode($response);
    exit();
}

if (!is_patient()) {
    $response['message'] = 'Unauthorized: User is not a patient.';
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

// Verify that the patient owns this appointment and it is cancelled (security check)
$patient_id = null;
$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $patient_id = $row['id'];
}
$stmt->close();

if (!$patient_id) {
    $response['message'] = 'Patient not found or not associated with this user account.';
    echo json_encode($response);
    exit();
}

// Check if the appointment exists, belongs to the patient, and is cancelled
$stmt = $conn->prepare("SELECT status FROM appointments WHERE id = ? AND patient_id = ?");
$stmt->bind_param("ii", $appointment_id, $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if ($row['status'] !== 'Cancelled') {
        $response['message'] = 'Only cancelled appointments can be removed.';
        echo json_encode($response);
        exit();
    }
} else {
    $response['message'] = 'Appointment not found for this patient or does not belong to them.';
    echo json_encode($response);
    exit();
}

// Proceed with deletion
$stmt = $conn->prepare("DELETE FROM appointments WHERE id = ? AND patient_id = ?");
$stmt->bind_param("ii", $appointment_id, $patient_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Cancelled appointment removed successfully.';
    } else {
        $response['message'] = 'Appointment not found or no changes made (0 affected rows).';
    }
} else {
    $response['message'] = 'Database error during deletion: ' . $stmt->error;
}
$stmt->close();
$conn->close();
echo json_encode($response);
?>