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

$message = '';

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $patient_id) {
    $doctor_id = $_POST['doctor_id'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $reason = $_POST['reason'] ?? '';

    if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time) || empty($reason)) {
        $message = "<p style='color: red;'>All fields are required.</p>";
    } else {
        $full_appointment_datetime = $appointment_date . ' ' . $appointment_time;

        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $patient_id, $doctor_id, $full_appointment_datetime, $reason);

        if ($stmt->execute()) {
            $message = "<p style='color: green;'>Appointment booked successfully!</p>";
        } else {
            $message = "<p style='color: red;'>Error booking appointment: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
}

// Fetch available doctors
$doctors = [];
$result = $conn->query("SELECT id, name, specialization FROM doctors ORDER BY name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}
?>

<div class="container">
    <h2>Book New Appointment</h2>
    <p>Welcome, <?php echo $_SESSION['username']; ?>! Book your appointment here:</p>

    <?php echo $message; ?>

    <?php if (empty($doctors)): ?>
        <p>No doctors available for booking at the moment.</p>
    <?php else: ?>
        <form action="book-appointment.php" method="POST">
            <label for="doctor_id">Select Doctor:</label>
            <select id="doctor_id" name="doctor_id" required>
                <option value="">-- Select a Doctor --</option>
                <?php foreach ($doctors as $doctor): ?>
                    <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['name']) . " (" . htmlspecialchars($doctor['specialization']) . ")"; ?></option>
                <?php endforeach; ?>
            </select>
            <br>
            <label for="appointment_date">Date:</label>
            <input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
            <br>
            <label for="appointment_time">Time:</label>
            <input type="time" id="appointment_time" name="appointment_time" required>
            <br>
            <label for="reason">Reason for Appointment:</label>
            <textarea id="reason" name="reason" rows="4" required></textarea>
            <br>
            <button type="submit">Book Appointment</button>
        </form>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
$conn->close();
?>