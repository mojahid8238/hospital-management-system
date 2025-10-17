<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_patient();

$patient_id = null;

// Get the patient_id associated with the logged-in user
$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($patient_id);
$stmt->fetch();
$stmt->close();

$medical_history = [];
if ($patient_id) {
    // Fetch past appointments for the patient
    // FIXED: Join with specializations table (s) to get the specialization name
    $stmt = $conn->prepare("SELECT a.id, d.name as doctor_name, d.profile_pic, s.name as specialization, a.appointment_date, a.reason, a.status FROM appointments a JOIN doctors d ON a.doctor_id = d.id JOIN specializations s ON d.specialization_id = s.id WHERE a.patient_id = ? ORDER BY a.appointment_date DESC");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $medical_history[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History</title>
   <link rel="stylesheet" href="../assets/css/shared-table.css" />
   
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title">Your Medical History</h2>
                <p class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>! Here is a summary of your medical history:</p>

                <?php if (empty($medical_history)): ?>
                    <p>You have no past appointments recorded.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Appointment ID</th>
                                    <th>Doctor Image</th>
                                    <th>Doctor Name</th>
                                    <th>Specialization</th>
                                    <th>Date & Time</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($medical_history as $record): ?>
                                    <tr>
                                        <td data-label="Appointment ID"><?php echo $record['id']; ?></td>
                                        <td data-label="Doctor Image"><img src="../<?php echo htmlspecialchars($record['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>" alt="Dr. <?php echo htmlspecialchars($record['doctor_name']); ?>" class="rounded-circle"></td>
                                        <td data-label="Doctor Name"><?php echo htmlspecialchars($record['doctor_name']); ?></td>
                                        <td data-label="Specialization"><?php echo htmlspecialchars($record['specialization']); ?></td>
                                        <td data-label="Date & Time"><?php echo date('Y-m-d H:i', strtotime($record['appointment_date'])); ?></td>
                                        <td data-label="Reason"><?php echo htmlspecialchars($record['reason']); ?></td>
                                        <td data-label="Status"><span class="badge bg-<?php echo strtolower(htmlspecialchars($record['status'])); ?>"><?php echo htmlspecialchars($record['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

