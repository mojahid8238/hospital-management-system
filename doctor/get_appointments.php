<?php
date_default_timezone_set('UTC');
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_doctor()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$doctor_id = null;
$stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $doctor_id = $row['id'];
}
$stmt->close();

$appointments = [];
if ($doctor_id) {
    $stmt = $conn->prepare("SELECT a.id, p.name as patient_name, p.profile_pic as patient_profile_pic, a.appointment_date, a.reason, a.status FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.doctor_id = ? AND a.appointment_date > NOW() ORDER BY a.appointment_date ASC");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($appointments);
?>