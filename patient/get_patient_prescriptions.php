<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 0); 
// ini_set('log_errors', 1);
// ini_set('error_log', '/tmp/php_error.log');

require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    if (!is_logged_in() || !is_patient()) {
        throw new Exception('Unauthorized', 403);
    }

    $patient_id = null;
    $stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $patient_id = $row['id'];
    }
    $stmt->close();

    if (!$patient_id) {
        throw new Exception('Patient not found');
    }

    $query = "
        SELECT 
            pr.id, 
            pr.content, 
            pr.created_at, 
            d.name as doctor_name, 
            s.name as specialization,
            pr.appointment_id
        FROM prescriptions pr
        JOIN doctors d ON pr.doctor_id = d.id
        JOIN specializations s ON d.specialization_id = s.id
        WHERE pr.patient_id = ?
        ORDER BY pr.created_at DESC
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) throw new Exception("Prepare query failed: " . $conn->error);

    $stmt->bind_param("i", $patient_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $prescriptions = [];
        while ($row = $result->fetch_assoc()) {
            $prescription_id = $row['id'];
            $items = [];
            $stmt_items = $conn->prepare("SELECT medicine_name, dosage, frequency, duration FROM prescription_items WHERE prescription_id = ?");
            $stmt_items->bind_param("i", $prescription_id);
            $stmt_items->execute();
            $res_items = $stmt_items->get_result();
            while ($item = $res_items->fetch_assoc()) {
                $items[] = $item;
            }
            $row['items'] = $items;
            $prescriptions[] = $row;
        }
        echo json_encode(['success' => true, 'prescriptions' => $prescriptions]);
    } else {
        throw new Exception($stmt->error);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
