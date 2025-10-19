<?php
session_start();
include_once '../includes/db.php';
include_once '../includes/auth.php';

redirect_if_not_logged_in();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT 
            c.id as conversation_id, 
            c.appointment_id, 
            other_user.id as other_participant_id,
            other_user.name as other_participant_name,
            CASE
                WHEN other_user.role = 'admin' THEN admin.profile_pic
                WHEN other_user.role = 'doctor' THEN doctor.profile_pic
                WHEN other_user.role = 'patient' THEN patient.profile_pic
                ELSE 'assets/images/default-avatar.png'
            END as other_participant_profile_pic,
            m.message_content as last_message, 
            m.timestamp as last_message_timestamp,
            (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND receiver_id = ? AND is_read = 0) as unread_count
        FROM conversations c
        JOIN users u ON (c.participant1_id = u.id OR c.participant2_id = u.id) AND u.id = ?
        JOIN users other_user ON (c.participant1_id = other_user.id OR c.participant2_id = other_user.id) AND other_user.id != ?
        LEFT JOIN admin ON other_user.id = admin.user_id
        LEFT JOIN doctors doctor ON other_user.id = doctor.user_id
        LEFT JOIN patients patient ON other_user.id = patient.user_id
        LEFT JOIN messages m ON c.last_message_id = m.id
        WHERE u.id = ?
        ORDER BY c.updated_at DESC
    ");
    $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        $conversations[] = $row;
    }

    echo json_encode(['success' => true, 'conversations' => $conversations]);

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
