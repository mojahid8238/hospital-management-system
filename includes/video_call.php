<?php
session_start();
include_once 'db.php';
include_once 'auth.php';

if (!is_logged_in()) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;

if ($appointment_id === 0) {
    echo "No appointment specified.";
    exit();
}

// Fetch appointment details and validate user access
$stmt = $pdo->prepare("
    SELECT a.*, 
           d.name as doctor_name, d.username as doctor_username,
           p.name as patient_name, p.username as patient_username
    FROM appointments a
    JOIN users d ON a.doctor_id = d.id
    JOIN users p ON a.patient_id = p.id
    WHERE a.id = ?
");
$stmt->execute([$appointment_id]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    echo "Appointment not found.";
    exit();
}

// Verify user is part of this appointment
if ($role === 'doctor' && $appointment['doctor_id'] != $user_id) {
    echo "Access denied. You are not the doctor for this appointment.";
    exit();
}

if ($role === 'patient' && $appointment['patient_id'] != $user_id) {
    echo "Access denied. You are not the patient for this appointment.";
    exit();
}

// Set user information based on role
$jitsi_public_url = getenv('PUBLIC_URL') ? getenv('PUBLIC_URL') : 'http://localhost:8000'; // Fallback
$jitsi_base_url = rtrim($jitsi_public_url, '/');

$jitsi_host_and_port = parse_url($jitsi_public_url, PHP_URL_HOST);
if (parse_url($jitsi_public_url, PHP_URL_PORT)) {
    $jitsi_host_and_port .= ':' . parse_url($jitsi_public_url, PHP_URL_PORT);
}

$roomName = 'appointment-' . $appointment_id; // Unique room per appointment
$userEmail = ($role === 'doctor') ? $appointment['doctor_username'] . '@hospital.local' : $appointment['patient_username'] . '@hospital.local';
$userName = ($role === 'doctor') ? $appointment['doctor_name'] : $appointment['patient_name'];

// Construct the direct Jitsi meeting URL
// We append query parameters for user info and display name as Jitsi natively supports them
$jitsi_meeting_url = "{$jitsi_base_url}/{$roomName}#userInfo.displayName=" . urlencode($userName) . "&userInfo.email=" . urlencode($userEmail);

// Redirect to the Jitsi meeting URL
header("Location: {$jitsi_meeting_url}");
exit();

?>