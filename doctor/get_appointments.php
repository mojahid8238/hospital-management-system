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
    // Sorting parameters
    $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'appointment_date';
    $order = isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC']) ? strtoupper($_GET['order']) : 'ASC';

    // Filtering parameters
    $filter_status = isset($_GET['status']) ? $_GET['status'] : 'upcoming'; // 'upcoming' is a custom status for default view
    $filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
    $search_term = isset($_GET['search']) ? $_GET['search'] : '';

    $sql = "SELECT a.id, p.id as patient_id, p.user_id as patient_user_id, p.name as patient_name, p.profile_pic as patient_profile_pic, a.appointment_date, a.reason, a.status, a.type FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.doctor_id = ?";

    $params = [$doctor_id];
    $types = "i";

    if ($filter_status === 'upcoming') {
        $sql .= " AND a.appointment_date > NOW() AND a.status != 'Cancelled'";
    } elseif ($filter_status !== 'all') {
        $sql .= " AND a.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }

    if ($filter_type !== 'all') {
        $sql .= " AND a.type = ?";
        $params[] = $filter_type;
        $types .= "s";
    }

    if (!empty($search_term)) {
        $sql .= " AND p.name LIKE ?";
        $params[] = "%".$search_term."%";
        $types .= "s";
    }

    // Validate sort_by column to prevent SQL injection
    $allowed_sort_columns = ['patient_name', 'appointment_date', 'status'];
    if (!in_array($sort_by, $allowed_sort_columns)) {
        $sort_by = 'appointment_date'; // Default to a safe column
    }

    $sql .= " ORDER BY " . $sort_by . " " . $order;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($appointments);