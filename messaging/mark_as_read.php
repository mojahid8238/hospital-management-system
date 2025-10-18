<?php
error_log("mark_as_read.php: REQUEST_METHOD = " . $_SERVER['REQUEST_METHOD']);
session_start();
include_once '../includes/db.php';
include_once '../includes/auth.php';

redirect_if_not_logged_in();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];

    // Read raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $conversation_id = $data['conversation_id'] ?? null;

    if (empty($conversation_id)) {
        echo json_encode(['success' => false, 'message' => 'Conversation ID is required.']);
        exit();
    }

    $conversation_id = filter_var($conversation_id, FILTER_SANITIZE_NUMBER_INT);

    // Mark messages as read in the conversation for the current user
    $stmt = $conn->prepare("UPDATE messages SET is_read = TRUE WHERE receiver_id = ? AND conversation_id = ? AND is_read = FALSE");
    $stmt->bind_param("ii", $user_id, $conversation_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Messages marked as read.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark messages as read.']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>