<?php
session_start();
include_once '../includes/db.php';
include_once '../includes/auth.php';

redirect_if_not_logged_in();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = $_SESSION['user_id'];
    $conversation_id = $_GET['conversation_id'] ?? null;

    if (empty($conversation_id)) {
        echo json_encode(['success' => false, 'message' => 'Conversation ID is required.']);
        exit();
    }

    $conversation_id = filter_var($conversation_id, FILTER_SANITIZE_NUMBER_INT);

    $stmt = $conn->prepare("SELECT m.id, m.sender_id, m.message_content, m.message_type, m.timestamp, u.name as sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.conversation_id = ? ORDER BY m.timestamp ASC");
    $stmt->bind_param("i", $conversation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    echo json_encode(['success' => true, 'messages' => $messages]);

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
