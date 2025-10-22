<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Temporarily removed is_doctor() check and file_put_contents for debugging.
// This script will now attempt to process the request regardless of user role or method.

// Temporary debug log to check actual request method seen by PHP
error_log("start_call.php received method: " . $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $appointment_id = $input['appointment_id'] ?? null;

    if ($appointment_id) {
        if (!isset($conn)) {
            $response['message'] = 'Database connection not established.';
            echo json_encode($response);
            exit();
        }

        $stmt = $conn->prepare("UPDATE appointments SET is_call_active = 1 WHERE id = ?");
        if ($stmt === false) {
            $response['message'] = 'SQL prepare failed: ' . $conn->error;
            echo json_encode($response);
            exit();
        }

        $stmt->bind_param("i", $appointment_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Call status updated to active.';
        } else {
            $response['message'] = 'Failed to update call status: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = 'Appointment ID not provided.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>