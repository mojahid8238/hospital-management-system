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
    $stmt = $conn->prepare("SELECT a.id, p.name as patient_name, p.profile_pic as patient_profile_pic, a.appointment_date, a.reason, a.status FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.doctor_id = ? ORDER BY a.appointment_date DESC");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointments</title>
    <link rel="stylesheet" href="../assets/css/shared-table.css" />
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title mb-3">Your Appointments</h2>
                <p class="text-muted">Welcome, <strong>Dr. <?php echo htmlspecialchars($_SESSION['username']); ?></strong>! Here are your scheduled appointments:</p>

                <?php if (empty($appointments)): ?>
                    <div class="alert alert-info mt-4">You have no appointments scheduled.</div>
                <?php else: ?>
                    <div class="table-responsive mt-4">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col">Serial Number</th>
                                    <th scope="col">Patient</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Date & Time</th>
                                    <th scope="col">Reason</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Actions</th>
                                    <th scope="col">Remaining</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td data-label="Serial Number"><?php echo $appointment['id']; ?></td>
                                        <td data-label="Patient">
                                            <img src="/hospital-management-system/<?php echo htmlspecialchars($appointment['patient_profile_pic'] ?? 'assets/images/default-avatar.png'); ?>" 
                                                alt="<?php echo htmlspecialchars($appointment['patient_name']); ?>" 
                                                class="rounded-circle" width="50" height="50" style="object-fit: cover;">
                                        </td>
                                        <td data-label="Name"><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                        <td data-label="Date & Time"><?php echo date('Y-m-d H:i', strtotime($appointment['appointment_date'])); ?></td>
                                        <td data-label="Reason"><?php echo htmlspecialchars($appointment['reason']); ?></td>
                                        <td data-label="Status">
                                            <span class="badge bg-<?php
                                                switch (strtolower($appointment['status'])) {
                                                    case 'completed': echo 'success'; break;
                                                    case 'cancelled': echo 'danger'; break;
                                                    case 'pending': echo 'warning'; break;
                                                    default: echo 'secondary'; break;
                                                }
                                            ?>">
                                                <?php echo htmlspecialchars(ucfirst($appointment['status'])); ?>
                                            </span>
                                        </td>
                                        <td data-label="Actions">
                                            <a href="#" class="btn btn-sm btn-outline-primary">Attend</a>
                                            <a href="#" class="btn btn-sm btn-outline-success">Message</a>
                                            <a href="#" class="btn btn-sm btn-outline-danger">Cancel</a>
                                        </td>
                                        <td data-label="Remaining">
                                            <p>2Days 3Hour 38Minutes</p>
                                        </td>
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
