<?php
session_start();
include_once '../includes/db.php';
include_once '../includes/auth.php';

redirect_if_not_logged_in();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = $_SESSION['user_id'];

    // Fetch conversations for the current user
    $stmt = $conn->prepare("
        SELECT 
            c.id as conversation_id, 
            c.appointment_id, 
            IF(c.participant1_id = ?, p2.id, p1.id) as other_participant_id,
            IF(c.participant1_id = ?, p2.name, p1.name) as other_participant_name,
            CASE
                WHEN IF(c.participant1_id = ?, p2.role, p1.role) = 'admin' THEN IF(c.participant1_id = ?, admin2.profile_pic, admin1.profile_pic)
                WHEN IF(c.participant1_id = ?, p2.role, p1.role) = 'doctor' THEN IF(c.participant1_id = ?, doctor2.profile_pic, doctor1.profile_pic)
                WHEN IF(c.participant1_id = ?, p2.role, p1.role) = 'patient' THEN IF(c.participant1_id = ?, patient2.profile_pic, patient1.profile_pic)
                ELSE 'assets/images/default-avatar.png'
            END as other_participant_profile_pic,
            m.message_content as last_message, 
            m.timestamp as last_message_timestamp,
            (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND receiver_id = ? AND is_read = 0) as unread_count
        FROM conversations c
        JOIN users p1 ON c.participant1_id = p1.id
        JOIN users p2 ON c.participant2_id = p2.id
        LEFT JOIN admin admin1 ON p1.id = admin1.user_id
        LEFT JOIN admin admin2 ON p2.id = admin2.user_id
        LEFT JOIN doctors doctor1 ON p1.id = doctor1.user_id
        LEFT JOIN doctors doctor2 ON p2.id = doctor2.user_id
        LEFT JOIN patients patient1 ON p1.id = patient1.user_id
        LEFT JOIN patients patient2 ON p2.id = patient2.user_id
        LEFT JOIN messages m ON c.last_message_id = m.id
        WHERE c.participant1_id = ? OR c.participant2_id = ?
        ORDER BY c.updated_at DESC
    ");
    $stmt->bind_param("iiiiiiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
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