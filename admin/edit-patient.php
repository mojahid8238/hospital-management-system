<?php
require_once '../includes/header.php';
require_once '../includes/db.php';
redirect_if_not_admin();

$patient_id = $_GET['id'] ?? null;
$patient = null;
$message = '';

if (!$patient_id) {
    header("Location: manage-patients.php");
    exit();
}

// Fetch patient details
$stmt = $conn->prepare("SELECT p.id, p.name, p.date_of_birth, p.gender, p.address, p.phone, p.email, u.username, u.id as user_id FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $patient = $result->fetch_assoc();
} else {
    $message = "<p style='color: red;'>Patient not found.</p>";
    $patient_id = null; // Invalidate patient_id if not found
}
$stmt->close();

// Handle Update Patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $patient_id) {
    $name = $_POST['name'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? ''; // Allow updating username

    if (empty($name) || empty($date_of_birth) || empty($gender) || empty($address) || empty($phone) || empty($email) || empty($username)) {
        $message = "<p style='color: red;'>All fields are required.</p>";
    } else {
        $conn->begin_transaction();
        try {
            // Update patient details
            $stmt_patient = $conn->prepare("UPDATE patients SET name = ?, date_of_birth = ?, gender = ?, address = ?, phone = ?, email = ? WHERE id = ?");
            $stmt_patient->bind_param("ssssssi", $name, $date_of_birth, $gender, $address, $phone, $email, $patient_id);
            $stmt_patient->execute();
            $stmt_patient->close();

            // Update associated user's username
            $stmt_user = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt_user->bind_param("si", $username, $patient['user_id']);
            $stmt_user->execute();
            $stmt_user->close();

            $conn->commit();
            $message = "<p style='color: green;'>Patient updated successfully!</p>";
            // Refresh patient data after update
            $stmt = $conn->prepare("SELECT p.id, p.name, p.date_of_birth, p.gender, p.address, p.phone, p.email, u.username, u.id as user_id FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $patient = $result->fetch_assoc();
            $stmt->close();

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $message = "<p style='color: red;'>Error updating patient: " . $exception->getMessage() . "</p>";
        }
    }
}
?>

<div class="container">
    <h2>Edit Patient</h2>
    <?php echo $message; ?>

    <?php if ($patient): ?>
        <form action="edit-patient.php?id=<?php echo $patient['id']; ?>" method="POST">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($patient['name']); ?>" required>
            <br>
            <label for="date_of_birth">Date of Birth:</label>
            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($patient['date_of_birth']); ?>" required>
            <br>
            <label for="gender">Gender:</label>
            <select id="gender" name="gender" required>
                <option value="">Select Gender</option>
                <option value="Male" <?php echo ($patient['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo ($patient['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                <option value="Other" <?php echo ($patient['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>
            <br>
            <label for="address">Address:</label>
            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($patient['address']); ?>" required>
            <br>
            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($patient['phone']); ?>" required>
            <br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" required>
            <br>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($patient['username']); ?>" required>
            <br>
            <button type="submit">Update Patient</button>
<a 
    href="manage-patients.php" 
    style="
        background-color: #ce4e1bff;
        color: white;
        padding: 10px 16px;
        text-decoration: none;
        border-radius: 4px;
        margin-left: 5px;
        font-size: 16px;
        display: inline-block;
        transition: background-color 0.3s ease;
    "
    onmouseover="this.style.backgroundColor='#e65f11ff';"
    onmouseout="this.style.backgroundColor='#df3c13ff';"
>
    Cancel
</a>
        </form>
    <?php else: ?>
        <p>Patient details could not be loaded. <a href="manage-patients.php">Go back to Manage Patients</a></p>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
$conn->close();
?>