<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_doctor();

$doctor_id = null;
// Get the doctor_id associated with the logged-in user
$stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($doctor_id);
$stmt->fetch();
$stmt->close();

$appointments = [];
if ($doctor_id) {
    $stmt = $conn->prepare("SELECT a.id, p.name as patient_name, a.appointment_date, a.reason, a.status FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.doctor_id = ? ORDER BY a.appointment_date DESC");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt->close();
}
?>

<div class="container">
    <h2>Your Appointments</h2>
    <p>Welcome, Dr. <?php echo $_SESSION['username']; ?>! Here are your scheduled appointments:</p>

    <?php if (empty($appointments)): ?>
        <p>You have no appointments scheduled.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Appointment ID</th>
                    <th>Patient Name</th>
                    <th>Date & Time</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?php echo $appointment['id']; ?></td>
                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($appointment['appointment_date'])); ?></td>
                        <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['status']); ?></td>
                            <td>
                                <!-- Add actions like 'Complete', 'Cancel', 'Reschedule' -->
                                <a href="#">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>