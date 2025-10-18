<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_admin();

$doctor_id = $_GET['id'] ?? null;
$doctor = null;
$message = '';

if (!$doctor_id) {
    header("Location: manage-doctors.php");
    exit();
}

// Fetch doctor details
$stmt = $conn->prepare("SELECT d.id, d.name, d.specialization, d.phone, d.email, u.username, u.id as user_id FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $doctor = $result->fetch_assoc();
} else {
    $message = "<p style='color: red;'>Doctor not found.</p>";
    $doctor_id = null;
}
    $stmt->close();

    // Fetch all specializations for the dropdown
    $specializations_result = $conn->query("SELECT id, name FROM specializations ORDER BY name ASC");
    $specializations = [];
    while ($row = $specializations_result->fetch_assoc()) {
        $specializations[] = $row;
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $doctor_id) {
    $name = $_POST['name'] ?? '';
    $specialization_name = $_POST['specialization'] ?? ''; // Get specialization name from form
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';

    if (empty($name) || empty($specialization_name) || empty($phone) || empty($email) || empty($username)) {
        $message = "<p style='color: red;'>All fields are required.</p>";
    } else {
        // Get specialization_id from specializations table
        $stmt_spec_id = $conn->prepare("SELECT id FROM specializations WHERE name = ?");
        $stmt_spec_id->bind_param("s", $specialization_name);
        $stmt_spec_id->execute();
        $result_spec_id = $stmt_spec_id->get_result();
        $spec_row = $result_spec_id->fetch_assoc();
        $specialization_id = $spec_row['id'] ?? null;
        $stmt_spec_id->close();

        if ($specialization_id === null) {
            $message = "<p style='color: red;'>Error: Invalid specialization selected.</p>";
        } else {
            $conn->begin_transaction();
            try {
                // Update doctor details
                $stmt_doctor = $conn->prepare("UPDATE doctors SET name = ?, specialization_id = ?, phone = ?, email = ? WHERE id = ?");
                $stmt_doctor->bind_param("sisssi", $name, $specialization_id, $phone, $email, $doctor_id);
                $stmt_doctor->execute();
                $stmt_doctor->close();

                // Update username
                $stmt_user = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt_user->bind_param("si", $username, $doctor['user_id']);
                $stmt_user->execute();
                $stmt_user->close();

                $conn->commit();
                $message = "<p style='color: green;'>Doctor updated successfully!</p>";

                // Refresh doctor data
                $stmt = $conn->prepare("SELECT d.id, d.name, s.name as specialization, d.phone, d.email, u.username, u.id as user_id FROM doctors d JOIN users u ON d.user_id = u.id JOIN specializations s ON d.specialization_id = s.id WHERE d.id = ?");
                $stmt->bind_param("i", $doctor_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $doctor = $result->fetch_assoc();
                $stmt->close();

            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                $message = "<p style='color: red;'>Error updating doctor: " . $exception->getMessage() . "</p>";
            }
        }
    }
}
?>

<div class="container">
    <h2>Edit Doctor</h2>
    <?php echo $message; ?>

    <?php if ($doctor): ?>
        <form action="edit-doctor.php?id=<?php echo $doctor['id']; ?>" method="POST">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($doctor['name']); ?>" required>
            <br>
            <label for="specialization">Specialization:</label>
            <select id="specialization" name="specialization" required>
                <?php foreach ($specializations as $spec): ?>
                    <option value="<?php echo htmlspecialchars($spec['name']); ?>" <?php echo ($doctor['specialization'] == $spec['name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($spec['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <br>
            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($doctor['phone']); ?>" required>
            <br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($doctor['email']); ?>" required>
            <br>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($doctor['username']); ?>" required>
            <br>
            <button type="submit">Update Doctor</button>
            <a href="manage-doctors.php" class="cancel-button">Cancel</a>
        </form>
    <?php else: ?>
        <p>Doctor details could not be loaded. <a href="manage-doctors.php">Go back to Manage Doctors</a></p>
    <?php endif; ?>
</div>