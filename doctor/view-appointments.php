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

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

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
                                <th scope="col">#ID</th>
                                <th scope="col">Patient</th>
                                <th scope="col">Name</th>
                                <th scope="col">Date & Time</th>
                                <th scope="col">Reason</th>
                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td><?php echo $appointment['id']; ?></td>
                                    <td>
                                        <img src="/hospital-management-system/<?php echo htmlspecialchars($appointment['patient_profile_pic'] ?? 'assets/images/default-avatar.png'); ?>" 
                                            alt="<?php echo htmlspecialchars($appointment['patient_name']); ?>" 
                                            class="rounded-circle" width="50" height="50" style="object-fit: cover;">
                                    </td>
                                    <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($appointment['appointment_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                                    <td>
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
                                    <td>
                                        <a href="#" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="#" class="btn btn-sm btn-outline-success">Complete</a>
                                        <a href="#" class="btn btn-sm btn-outline-danger">Cancel</a>
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

<!-- Bootstrap JS (optional for interactivity) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
