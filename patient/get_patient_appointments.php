<?php
date_default_timezone_set('UTC');
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_patient()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$patient_id = null;
$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $patient_id = $row['id'];
}
$stmt->close();

    $appointments = [];
    if ($patient_id) {
        $stmt = $conn->prepare("SELECT a.id, d.name as doctor_name, d.profile_pic, s.name as specialization, a.appointment_date, a.reason, a.status, a.type FROM appointments a JOIN doctors d ON a.doctor_id = d.id JOIN specializations s ON d.specialization_id = s.id WHERE a.patient_id = ? AND a.status != 'Cancelled'");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
        $stmt->close();
    }

    header('Content-Type: application/json');
    echo json_encode($appointments);?>