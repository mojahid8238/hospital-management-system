<?php
session_start();
include_once '../includes/db.php';
include_once '../includes/auth.php';

redirect_if_not_logged_in();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'] ?? null;
    $message_content = $_POST['message_content'] ?? null;
    $appointment_id = $_POST['appointment_id'] ?? null;

    if (empty($receiver_id) || empty($message_content)) {
        echo json_encode(['success' => false, 'message' => 'Receiver ID and message content are required.']);
        exit();
    }

    // Sanitize input
    $receiver_id = filter_var($receiver_id, FILTER_SANITIZE_NUMBER_INT);
    $message_content = htmlspecialchars($message_content);

    // Insert message into the database
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message_content);

    if ($stmt->execute()) {
        $message_id = $stmt->insert_id;

        // Check if a conversation already exists
        // Consider appointment_id for specific conversations
        $conv_sql = "SELECT id FROM conversations WHERE (
            (participant1_id = ? AND participant2_id = ?)
            OR
            (participant1_id = ? AND participant2_id = ?)
        )";
        $conv_params = [$sender_id, $receiver_id, $receiver_id, $sender_id];
        $conv_types = "iiii";

        if ($appointment_id !== null) {
            $conv_sql .= " AND appointment_id = ?";
            $conv_params[] = $appointment_id;
            $conv_types .= "i";
        } else {
            $conv_sql .= " AND appointment_id IS NULL";
        }

        $conv_stmt = $conn->prepare($conv_sql);
        $conv_stmt->bind_param($conv_types, ...$conv_params);
        $conv_stmt->execute();
        $conv_result = $conv_stmt->get_result();

        if ($conv_result->num_rows > 0) {
            // Update existing conversation
            $conversation = $conv_result->fetch_assoc();
            $conv_id = $conversation['id'];
            $update_stmt = $conn->prepare("UPDATE conversations SET last_message_id = ?, updated_at = NOW() WHERE id = ?");
            $update_stmt->bind_param("ii", $message_id, $conv_id);
            $update_stmt->execute();
        } else {
            // Create new conversation
            $insert_sql = "INSERT INTO conversations (participant1_id, participant2_id, last_message_id";
            $insert_params = [$sender_id, $receiver_id, $message_id];
            $insert_types = "iii";

            if ($appointment_id !== null) {
                $insert_sql .= ", appointment_id";
                $insert_params[] = $appointment_id;
                $insert_types .= "i";
            }
            $insert_sql .= ") VALUES (?, ?, ?";
            if ($appointment_id !== null) {
                $insert_sql .= ", ?";
            }
            $insert_sql .= ")";

            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param($insert_types, ...$insert_params);
            $insert_stmt->execute();
            $conv_id = $insert_stmt->insert_id;
        }

        // Add conversation_id to the message
        $update_msg_stmt = $conn->prepare("UPDATE messages SET conversation_id = ? WHERE id = ?");
        $update_msg_stmt->bind_param("ii", $conv_id, $message_id);
        $update_msg_stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Message sent successfully.', 'message_id' => $message_id, 'conversation_id' => $conv_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message.']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>