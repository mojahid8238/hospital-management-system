<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!is_logged_in() || !is_doctor()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$appointment_id = $input['appointment_id'] ?? null;
$content = $input['content'] ?? '';

if (!$appointment_id || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

// Verify appointment belongs to doctor
// $doctor_id = get_doctor_id(); // Removed to rely on fallback below
$doctor_id = null;
if (!$doctor_id) {
    // Fallback if helper lacks
    // Get doctor_id
    $stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $doctor = $res->fetch_assoc();
    $doctor_id = $doctor['id'] ?? null;
}

if (!$doctor_id) {
     echo json_encode(['success' => false, 'message' => 'Doctor profile not found']);
     exit();
}

// Get patient_id from appointment
$stmt = $conn->prepare("SELECT patient_id FROM appointments WHERE id = ? AND doctor_id = ?");
$stmt->bind_param("ii", $appointment_id, $doctor_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    exit();
}
$row = $res->fetch_assoc();
$patient_id = $row['patient_id'];

// Check if prescription already exists
$stmt = $conn->prepare("SELECT id FROM prescriptions WHERE appointment_id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    // Update
    $stmt = $conn->prepare("UPDATE prescriptions SET content = ?, created_at = NOW() WHERE appointment_id = ?");
    $stmt->bind_param("si", $content, $appointment_id);
} else {
    // Insert
    $stmt = $conn->prepare("INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, content) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $appointment_id, $doctor_id, $patient_id, $content);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
?>
