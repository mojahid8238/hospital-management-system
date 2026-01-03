<?php
include_once '../includes/db.php';
include_once '../includes/auth.php';

redirect_if_not_logged_in();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = $_SESSION['user_id'];
    $conversation_id = $_GET['conversation_id'] ?? null;
    $receiver_id = $_GET['receiver_id'] ?? null;

    $current_conv_id = null;

    if (!empty($conversation_id)) {
        $current_conv_id = filter_var($conversation_id, FILTER_SANITIZE_NUMBER_INT);
    } elseif (!empty($receiver_id)) {
        $receiver_id = filter_var($receiver_id, FILTER_SANITIZE_NUMBER_INT);

        // Try to find existing conversation
        $find_conv_stmt = $conn->prepare("SELECT id FROM conversations WHERE (participant1_id = ? AND participant2_id = ?) OR (participant1_id = ? AND participant2_id = ?)");
        $find_conv_stmt->bind_param("iiii", $user_id, $receiver_id, $receiver_id, $user_id);
        $find_conv_stmt->execute();
        $find_conv_result = $find_conv_stmt->get_result();

        if ($find_conv_result->num_rows > 0) {
            $conversation = $find_conv_result->fetch_assoc();
            $current_conv_id = $conversation['id'];
        } else {
            // DO NOT create a conversation here. Just return success with empty messages.
            // This satisfies the requirement that admins don't appear until they send something.
            echo json_encode(['success' => true, 'messages' => [], 'conversation_id' => null]);
            exit();
        }
        $find_conv_stmt->close();
    }

    if ($current_conv_id === null) {
        echo json_encode(['success' => false, 'message' => 'Conversation not found.']);
        exit();
    }

    // Now use $current_conv_id to fetch messages
    $stmt = $conn->prepare("SELECT m.id, m.sender_id, m.message_content, m.message_type, m.timestamp, u.name as sender_name, u.role as sender_role FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.conversation_id = ? ORDER BY m.timestamp ASC");
    $stmt->bind_param("i", $current_conv_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    echo json_encode(['success' => true, 'messages' => $messages, 'conversation_id' => $current_conv_id]);

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
