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

$success = false;
$prescription_id = null;

if ($res->num_rows > 0) {
    // Update
    $prescription = $res->fetch_assoc();
    $prescription_id = $prescription['id'];
    $stmt = $conn->prepare("UPDATE prescriptions SET content = ?, created_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $content, $prescription_id);
    $success = $stmt->execute();
} else {
    // Insert
    $stmt = $conn->prepare("INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, content) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $appointment_id, $doctor_id, $patient_id, $content);
    $success = $stmt->execute();
    $prescription_id = $conn->insert_id;
}

if ($success && $prescription_id) {
    // Clear old items
    $conn->query("DELETE FROM prescription_items WHERE prescription_id = $prescription_id");

    // Parse content for structured items
    $lines = explode("\n", $content);
    $stmt_item = $conn->prepare("INSERT INTO prescription_items (prescription_id, medicine_name, dosage, frequency, duration) VALUES (?, ?, ?, ?, ?)");
    
    $first_med = null;
    foreach ($lines as $line) {
        if (strpos($line, '|') !== false) {
            $parts = explode('|', $line);
            $name = trim($parts[0] ?? '');
            $dosage = trim($parts[1] ?? '');
            $freq = trim($parts[2] ?? '');
            $duration = trim($parts[3] ?? '');

            if (!empty($name)) {
                if (!$first_med) {
                    $first_med = ['name' => $name, 'dosage' => $dosage, 'freq' => $freq, 'duration' => $duration];
                }
                $stmt_item->bind_param("issss", $prescription_id, $name, $dosage, $freq, $duration);
                $stmt_item->execute();
            }
        }
    }

    // Update main prescription with first medicine for simple device access
    if ($first_med) {
        $stmt_main = $conn->prepare("UPDATE prescriptions SET med_name = ?, med_dosage = ?, med_freq = ?, med_duration = ? WHERE id = ?");
        $stmt_main->bind_param("ssssi", $first_med['name'], $first_med['dosage'], $first_med['freq'], $first_med['duration'], $prescription_id);
        $stmt_main->execute();
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
?>
