<?php
require_once '../includes/header.php';
require_once '../includes/db.php';
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
    $stmt = $conn->prepare("SELECT a.id, d.name as doctor_name, d.specialization, a.appointment_date, a.reason, a.status FROM appointments a JOIN doctors d ON a.doctor_id = d.id WHERE a.patient_id = ? ORDER BY a.appointment_date DESC");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $medical_history[] = $row;
    }
    $stmt->close();
}
?>

<div class="container">
    <h2>Your Medical History</h2>
    <p>Welcome, <?php echo $_SESSION['username']; ?>! Here is a summary of your medical history:</p>

    <h3>Past Appointments</h3>
    <?php if (empty($medical_history)): ?>
        <p>You have no past appointments recorded.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Appointment ID</th>
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
                        <td><?php echo $record['id']; ?></td>
                        <td><?php echo htmlspecialchars($record['doctor_name']); ?></td>
                        <td><?php echo htmlspecialchars($record['specialization']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($record['appointment_date'])); ?></td>
                        <td><?php echo htmlspecialchars($record['reason']); ?></td>
                        <td><?php echo htmlspecialchars($record['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Add more sections for prescriptions, test results, etc. -->
</div>

<?php
require_once '../includes/footer.php';
$conn->close();
?>