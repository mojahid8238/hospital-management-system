<?php
include_once '../includes/db.php';
include_once '../includes/auth.php';

redirect_if_not_logged_in();

header('Content-Type: application/json');

if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $current_user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT 
            u.id as user_id, 
            u.name, 
            u.role,
            CASE
                WHEN u.role = 'admin' THEN a.profile_pic
                WHEN u.role = 'doctor' THEN d.profile_pic
                WHEN u.role = 'patient' THEN p.profile_pic
                ELSE 'assets/images/default-avatar.png'
            END as profile_pic
        FROM users u
        LEFT JOIN admin a ON u.id = a.user_id
        LEFT JOIN doctors d ON u.id = d.user_id
        LEFT JOIN patients p ON u.id = p.user_id
        WHERE u.id != ?
        GROUP BY u.id
        ORDER BY u.name ASC
    ");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode(['success' => true, 'users' => $users]);

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
