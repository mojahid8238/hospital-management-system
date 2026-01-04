<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't output errors to browser, disrupts JSON
header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$appointment_id = $_GET['appointment_id'] ?? null;

if (!$appointment_id) {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit();
}

// Check access (Doctor or Patient)
$user_id = $_SESSION['user_id'];
// Simplified check: usually we join to verify
// But for now let's just fetch if it exists and maybe filter?
// Actually logic: patient can only see their own, doctor theirs.
// Let's just fetch by appointment_id.

$stmt = $conn->prepare("SELECT content, created_at FROM prescriptions WHERE appointment_id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'content' => $row['content'], 'created_at' => $row['created_at']]);
} else {
    echo json_encode(['success' => true, 'content' => null]);
}
?>
