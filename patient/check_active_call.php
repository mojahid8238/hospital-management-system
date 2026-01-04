<?php
date_default_timezone_set('UTC');
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
$response = ['active' => false];

if (!is_logged_in() || !is_patient()) {
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check for any in_progress video call where the current patient is the receiver
$stmt = $conn->prepare("SELECT v.id, v.meeting_link, v.room_name, v.appointment_id, d.name as doctor_name 
                        FROM video_calls v 
                        JOIN appointments a ON v.appointment_id = a.id 
                        JOIN doctors d ON a.doctor_id = d.id
                        WHERE v.receiver_id = ? AND v.status = 'in_progress'
                        ORDER BY v.created_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $response = [
            'active' => true,
            'meeting_link' => $row['meeting_link'],
            'room_name' => $row['room_name'],
            'appointment_id' => $row['appointment_id'],
            'doctor_name' => $row['doctor_name']
        ];
    }
}
$stmt->close();
echo json_encode($response);
