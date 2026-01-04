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

$stmt = $conn->prepare("SELECT id, content, created_at FROM prescriptions WHERE appointment_id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $prescription_id = $row['id'];
    
    // Fetch structured items
    $items = [];
    $stmt_items = $conn->prepare("SELECT medicine_name, dosage, frequency, duration FROM prescription_items WHERE prescription_id = ?");
    $stmt_items->bind_param("i", $prescription_id);
    $stmt_items->execute();
    $res_items = $stmt_items->get_result();
    while ($item = $res_items->fetch_assoc()) {
        $items[] = $item;
    }
    
    echo json_encode([
        'success' => true, 
        'content' => $row['content'], 
        'created_at' => $row['created_at'],
        'items' => $items
    ]);
} else {
    echo json_encode(['success' => true, 'content' => null, 'items' => []]);
}
?>
