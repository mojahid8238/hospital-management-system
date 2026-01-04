<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

// Debug Logging
// file_put_contents('end_call_log.txt', date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);

if (!is_logged_in()) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$input = json_decode(file_get_contents('php://input'), true);
$appointment_id = $input['appointment_id'] ?? null;

if (!$appointment_id) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Missing appointment ID']));
}

// Update video_calls status to completed
$stmt = $conn->prepare("UPDATE video_calls SET status = 'completed', end_time = NOW() WHERE appointment_id = ? AND status = 'in_progress'");
$stmt->bind_param("i", $appointment_id);
$success = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $success]);
?>
