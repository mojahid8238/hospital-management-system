<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;

if ($appointment_id === 0) {
    echo "No appointment specified.";
    exit();
}

// Simple redirect to the internal video call page
header("Location: ../video_call.php?appointment_id=" . $appointment_id);
exit();
?>