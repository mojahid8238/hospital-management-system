<?php
require_once '../includes/header.php';
require_once '../includes/db.php';
redirect_if_not_admin();

$message = '';

// Handle Add/Edit Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $specialization = $_POST['specialization'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $user_id = $_POST['user_id'] ?? null; // For editing existing doctor linked to a user
    $doctor_id = $_POST['doctor_id'] ?? null; // For editing existing doctor

    if (empty($name) || empty($specialization) || empty($phone) || empty($email)) {
        $message = "<p style='color: red;'>All fields are required.</p>";
    } else {
        if ($doctor_id) {
            // Update existing doctor
            $stmt = $conn->prepare("UPDATE doctors SET name = ?, specialization = ?, phone = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $specialization, $phone, $email, $doctor_id);
            if ($stmt->execute()) {
                $message = "<p style='color: green;'>Doctor updated successfully!</p>";
            } else {
                $message = "<p style='color: red;'>Error updating doctor: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            // Add new doctor - first create a user account for the doctor
            // For simplicity, let's assume a default password and role 'doctor' for new doctors
            $username = strtolower(str_replace(' ', '', $name)) . rand(100, 999); // Generate unique username
            $password = password_hash("password123", PASSWORD_DEFAULT); // Default password
            $role = 'doctor';

            $stmt_user = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt_user->bind_param("sss", $username, $password, $role);

            if ($stmt_user->execute()) {
                $new_user_id = $stmt_user->insert_id;
                $stmt_user->close();

                $stmt_doctor = $conn->prepare("INSERT INTO doctors (user_id, name, specialization, phone, email) VALUES (?, ?, ?, ?, ?)");
                $stmt_doctor->bind_param("issss", $new_user_id, $name, $specialization, $phone, $email);

                if ($stmt_doctor->execute()) {
                    $message = "<p style='color: green;'>Doctor added successfully with username: <strong>{$username}</strong> and default password: <strong>password123</strong></p>";
                } else {
                    $message = "<p style='color: red;'>Error adding doctor: " . $stmt_doctor->error . "</p>";
                    // Rollback user creation if doctor creation fails
                    $conn->query("DELETE FROM users WHERE id = {$new_user_id}");
                }
                $stmt_doctor->close();
            } else {
                $message = "<p style='color: red;'>Error creating user for doctor: " . $stmt_user->error . "</p>";
            }
        }
    }
}

// Handle Delete Doctor
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $doctor_id_to_delete = $_GET['id'];

    // Get user_id associated with the doctor to delete from users table as well
    $stmt_get_user_id = $conn->prepare("SELECT user_id FROM doctors WHERE id = ?");
    $stmt_get_user_id->bind_param("i", $doctor_id_to_delete);
    $stmt_get_user_id->execute();
    $stmt_get_user_id->bind_result($user_id_to_delete);
    $stmt_get_user_id->fetch();
    $stmt_get_user_id->close();

    if ($user_id_to_delete) {
        $conn->begin_transaction();
        try {
            // Delete doctor
            $stmt_doctor = $conn->prepare("DELETE FROM doctors WHERE id = ?");
            $stmt_doctor->bind_param("i", $doctor_id_to_delete);
            $stmt_doctor->execute();
            $stmt_doctor->close();

            // Delete associated user
            $stmt_user = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt_user->bind_param("i", $user_id_to_delete);
            $stmt_user->execute();
            $stmt_user->close();

            $conn->commit();
            $message = "<p style='color: green;'>Doctor and associated user deleted successfully!</p>";
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $message = "<p style='color: red;'>Error deleting doctor: " . $exception->getMessage() . "</p>";
        }
    } else {
        $message = "<p style='color: red;'>Doctor not found.</p>";
    }
}

// Fetch all doctors
$doctors = [];
$result = $conn->query("SELECT d.id, d.name, d.specialization, d.phone, d.email, u.username FROM doctors d JOIN users u ON d.user_id = u.id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}
?>

<div class="container">
    <h2>Manage Doctors</h2>
    <?php echo $message; ?>

    <h3>Add New Doctor</h3>
    <form action="manage-doctors.php" method="POST">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
        <br>
        <label for="specialization">Specialization:</label>
        <input type="text" id="specialization" name="specialization" required>
        <br>
        <label for="phone">Phone:</label>
        <input type="text" id="phone" name="phone" required>
        <br>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <br>
        <button type="submit">Add Doctor</button>
    </form>

    <h3>Existing Doctors</h3>
    <?php if (empty($doctors)): ?>
        <p>No doctors found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Specialization</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doctors as $doctor): ?>
                    <tr>
                        <td><?php echo $doctor['id']; ?></td>
                        <td><?php echo $doctor['name']; ?></td>
                        <td><?php echo $doctor['specialization']; ?></td>
                        <td><?php echo $doctor['phone']; ?></td>
                        <td><?php echo $doctor['email']; ?></td>
                        <td><?php echo $doctor['username']; ?></td>
                        <td>
                            <a href="edit-doctor.php?id=<?php echo $doctor['id']; ?>">Edit</a> |
                            <a href="manage-doctors.php?action=delete&id=<?php echo $doctor['id']; ?>" onclick="return confirm('Are you sure you want to delete this doctor and their associated user account?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
$conn->close();
?>