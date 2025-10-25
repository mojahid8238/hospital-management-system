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
$stmt = $conn->prepare("SELECT id, user_id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $patient_id = $row['id'];
    $patient_user_id = $row['user_id'];
}
$stmt->close();

$appointments = [];
if ($patient_id) {
    // Filtering and sorting parameters from GET request
    $search_query = $_GET['search'] ?? '';
    $status_filter = $_GET['status'] ?? 'all';
    $sort_by = $_GET['sort'] ?? 'appointment_date_asc';

    $sql = "SELECT a.id, d.id as doctor_id, d.user_id as doctor_user_id, d.name as doctor_name, d.profile_pic, s.name as specialization, a.appointment_date, a.reason, a.status, a.type, c.id as conversation_id FROM appointments a JOIN doctors d ON a.doctor_id = d.id JOIN specializations s ON d.specialization_id = s.id LEFT JOIN conversations c ON (c.participant1_id = ? AND c.participant2_id = d.user_id) OR (c.participant1_id = d.user_id AND c.participant2_id = ?) WHERE a.patient_id = ? AND a.status != 'Cancelled'";

    $params = [$patient_user_id, $patient_user_id, $patient_id];
    $types = "iii";

    // Apply search filter
    if (!empty($search_query)) {
        $sql .= " AND (d.name LIKE ? OR a.reason LIKE ?)";
        $params[] = "%".$search_query."%";
        $params[] = "%".$search_query."%";
        $types .= "ss";
    }

    // Apply status filter
    if ($status_filter !== 'all') {
        // Handle 'online' and 'offline' as types, not statuses
        if ($status_filter === 'online' || $status_filter === 'offline') {
            $sql .= " AND a.type = ?";
            $params[] = ucfirst($status_filter);
            $types .= "s";
        } else {
            $sql .= " AND a.status = ?";
            $params[] = ucfirst($status_filter);
            $types .= "s";
        }
    }

    // Apply sorting
    switch ($sort_by) {
        case 'appointment_date_desc':
            $sql .= " ORDER BY a.appointment_date DESC";
            break;
        case 'doctor_name_asc':
            $sql .= " ORDER BY d.name ASC";
            break;
        case 'doctor_name_desc':
            $sql .= " ORDER BY d.name DESC";
            break;
        case 'appointment_date_asc':
        default:
            $sql .= " ORDER BY a.appointment_date ASC";
            break;
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['patient_user_id'] = $patient_user_id; // Add patient_user_id to each appointment
        $appointments[] = $row;
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($appointments);