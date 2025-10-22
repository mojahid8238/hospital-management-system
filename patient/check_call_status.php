<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
// Assuming patient_id is stored in session after login
// If not, you might need to fetch it from the database using $_SESSION['user_id']

header('Content-Type: text/plain'); // Return plain text for simplicity

// Ensure only logged-in patients can access this
if (!is_patient()) {
    echo 'inactive'; // Or an error message, but 'inactive' is safer for polling
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? null;

    if ($appointment_id) {
        // Ensure $conn is available from db.php
        if (!isset($conn)) {
            error_log("Database connection not established in check_call_status.php");
            echo 'inactive';
            exit();
        }

        // Assuming $_SESSION['patient_id'] is set from patient/dashboard.php
        $patient_id_from_session = $_SESSION['patient_id'] ?? null;

        if ($patient_id_from_session === null) {
            error_log("patient_id not found in session in check_call_status.php");
            echo 'inactive';
            exit();
        }

        $stmt = $conn->prepare("SELECT is_call_active FROM appointments WHERE id = ? AND patient_id = ?");
        if ($stmt === false) {
            error_log("SQL prepare failed in check_call_status.php: " . $conn->error);
            echo 'inactive';
            exit();
        }

        $stmt->bind_param("ii", $appointment_id, $patient_id_from_session);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if ($row['is_call_active'] == 1) {
                echo 'active';
            } else {
                echo 'inactive';
            }
        } else {
            echo 'inactive'; // Appointment not found or not for this patient
        }
        $stmt->close();
    } else {
        echo 'inactive'; // Appointment ID not provided
    }
} else {
    echo 'inactive'; // Invalid request method
}
?>