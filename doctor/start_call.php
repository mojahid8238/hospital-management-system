<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

date_default_timezone_set('UTC');
require_once '../includes/db.php';
require_once '../includes/auth.php';
// require_once '../includes/jitsi_helper.php';

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

try {
    if (!is_logged_in() || !is_doctor()) {
        throw new Exception('Unauthorized');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $appointment_id = $input['appointment_id'] ?? null;

    if (!$appointment_id) {
        throw new Exception('Appointment ID is required.');
    }

    // Get doctor_id
    $stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
    if (!$stmt) throw new Exception('Doctor lookup prepare failed: ' . $conn->error);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();
    $doctor_id = $doctor['id'] ?? null;
    $stmt->close();

    if (!$doctor_id) {
        throw new Exception('Doctor profile not found.');
    }

    // Verify appointment and get patient info
    $stmt = $conn->prepare("SELECT a.patient_id, p.user_id as patient_user_id, a.type FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.id = ? AND a.doctor_id = ?");
    if (!$stmt) throw new Exception('Appointment lookup prepare failed: ' . $conn->error);
    $stmt->bind_param("ii", $appointment_id, $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
    $stmt->close();

    if (!$appointment) {
        throw new Exception('Appointment not found or not assigned to you.');
    }

    // Generate room name and link
    $room_name = 'appointment-' . $appointment_id;
    // Point to our internal video_call.php
    $meeting_link = 'video_call.php?appointment_id=' . $appointment_id;

    // Check if call already exists
    $stmt = $conn->prepare("SELECT id FROM video_calls WHERE appointment_id = ? AND status IN ('scheduled', 'in_progress')");
    if (!$stmt) throw new Exception('Call lookup prepare failed: ' . $conn->error);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $existing_result = $stmt->get_result();
    $existing_call = $existing_result->fetch_assoc();
    $stmt->close();

    if ($existing_call) {
        // Update existing call if it exists
        $stmt = $conn->prepare("UPDATE video_calls SET start_time = NOW(), status = 'in_progress', meeting_link = ?, room_name = ? WHERE id = ?");
        if (!$stmt) throw new Exception('Call update prepare failed: ' . $conn->error);
        $stmt->bind_param("ssi", $meeting_link, $room_name, $existing_call['id']);
    } else {
        // Insert new call record
        $stmt = $conn->prepare("INSERT INTO video_calls (caller_id, receiver_id, appointment_id, start_time, status, meeting_link, room_name) VALUES (?, ?, ?, NOW(), 'in_progress', ?, ?)");
        if (!$stmt) throw new Exception('Call insert prepare failed: ' . $conn->error);
        $caller_id = $_SESSION['user_id'];
        $receiver_id = $appointment['patient_user_id'];
        $stmt->bind_param("iiiss", $caller_id, $receiver_id, $appointment_id, $meeting_link, $room_name);
    }

    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'meeting_url' => $meeting_link,
            'room_name' => $room_name
        ];
    } else {
        throw new Exception('DB execution failed: ' . $stmt->error);
    }
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

$stray_output = ob_get_clean();
if (!empty($stray_output)) {
    $response['debug_output'] = $stray_output;
}

header('Content-Type: application/json');
echo json_encode($response);
exit();
