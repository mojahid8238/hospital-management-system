<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!is_doctor()) {
    $response['message'] = 'Unauthorized access. Doctor role required.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $appointment_id = $input['appointment_id'] ?? null;

    if ($appointment_id) {
        if (!isset($conn)) {
            $response['message'] = 'Database connection not established.';
            echo json_encode($response);
            exit();
        }

        // Update is_call_active to 0 and status to 'Completed'
        $stmt = $conn->prepare("UPDATE appointments SET is_call_active = 0, status = 'Completed' WHERE id = ?");
        if ($stmt === false) {
            $response['message'] = 'SQL prepare failed: ' . $conn->error;
            echo json_encode($response);
            exit();
        }

        $stmt->bind_param("i", $appointment_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Call ended and appointment status updated to Completed.';
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