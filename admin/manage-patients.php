<?php
require_once '../includes/header.php';
require_once '../includes/db.php';
redirect_if_not_admin();

$message = '';

// Handle Add/Edit Patient
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $patient_id = $_POST['patient_id'] ?? null; // For editing existing patient

    if (empty($name) || empty($date_of_birth) || empty($gender) || empty($address) || empty($phone) || empty($email)) {
        $message = "<p style='color: red;'>All fields are required.</p>";
    } else {
        if ($patient_id) {
            // Update existing patient
            $stmt = $conn->prepare("UPDATE patients SET name = ?, date_of_birth = ?, gender = ?, address = ?, phone = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssssssi", $name, $date_of_birth, $gender, $address, $phone, $email, $patient_id);
            if ($stmt->execute()) {
                $message = "<p style='color: green;'>Patient updated successfully!</p>";
            } else {
                $message = "<p style='color: red;'>Error updating patient: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            // Add new patient - first create a user account for the patient
            $username = strtolower(str_replace(' ', '', $name)) . rand(100, 999); // Generate unique username
            $password = password_hash("password123", PASSWORD_DEFAULT); // Default password
            $role = 'patient';

            $stmt_user = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt_user->bind_param("sss", $username, $password, $role);

            if ($stmt_user->execute()) {
                $new_user_id = $stmt_user->insert_id;
                $stmt_user->close();

                $stmt_patient = $conn->prepare("INSERT INTO patients (user_id, name, date_of_birth, gender, address, phone, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt_patient->bind_param("issssss", $new_user_id, $name, $date_of_birth, $gender, $address, $phone, $email);

                if ($stmt_patient->execute()) {
                    $message = "<p style='color: green;'>Patient added successfully with username: <strong>{$username}</strong> and default password: <strong>password123</strong></p>";
                } else {
                    $message = "<p style='color: red;'>Error adding patient: " . $stmt_patient->error . "</p>";
                    // Rollback user creation if patient creation fails
                    $conn->query("DELETE FROM users WHERE id = {$new_user_id}");
                }
                $stmt_patient->close();
            } else {
                $message = "<p style='color: red;'>Error creating user for patient: " . $stmt_user->error . "</p>";
            }
        }
    }
}

// Handle Delete Patient
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $patient_id_to_delete = $_GET['id'];

    // Get user_id associated with the patient to delete from users table as well
    $stmt_get_user_id = $conn->prepare("SELECT user_id FROM patients WHERE id = ?");
    $stmt_get_user_id->bind_param("i", $patient_id_to_delete);
    $stmt_get_user_id->execute();
    $stmt_get_user_id->bind_result($user_id_to_delete);
    $stmt_get_user_id->fetch();
    $stmt_get_user_id->close();

    if ($user_id_to_delete) {
        $conn->begin_transaction();
        try {
            // Delete patient
            $stmt_patient = $conn->prepare("DELETE FROM patients WHERE id = ?");
            $stmt_patient->bind_param("i", $patient_id_to_delete);
            $stmt_patient->execute();
            $stmt_patient->close();

            // Delete associated user
            $stmt_user = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt_user->bind_param("i", $user_id_to_delete);
            $stmt_user->execute();
            $stmt_user->close();

            $conn->commit();
            $message = "<p style='color: green;'>Patient and associated user deleted successfully!</p>";
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $message = "<p style='color: red;'>Error deleting patient: " . $exception->getMessage() . "</p>";
        }
    }
}

// Fetch all patients
$patients = [];
$result = $conn->query("SELECT p.id, p.name, p.date_of_birth, p.gender, p.address, p.phone, p.email, u.username FROM patients p JOIN users u ON p.user_id = u.id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}
?>

<div class="container">
    <h2>Manage Patients</h2>
    <?php echo $message; ?>

   

    <?php if (empty($patients)): ?>
        <p>No patients found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Date of Birth</th>
                    <th>Gender</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patients as $patient): ?>
                    <tr>
                        <td><?php echo $patient['id']; ?></td>
                        <td><?php echo $patient['name']; ?></td>
                        <td><?php echo $patient['date_of_birth']; ?></td>
                        <td><?php echo $patient['gender']; ?></td>
                        <td><?php echo $patient['address']; ?></td>
                        <td><?php echo $patient['phone']; ?></td>
                        <td><?php echo $patient['email']; ?></td>
                        <td><?php echo $patient['username']; ?></td>
                        <td><a 
    href="edit-patient.php?id=<?php echo $patient['id']; ?>" 
    style="
        background-color: #27ae60;
        color: white;
        padding: 8px 16px;
        text-decoration: none;
        border-radius: 4px;
        font-size: 14px;
        margin-right: 8px;
        display: inline-block;
        transition: background-color 0.3s ease;
    "
    onmouseover="this.style.backgroundColor='#1e8449';"
    onmouseout="this.style.backgroundColor='#27ae60';"
>
    Edit
</a>

<a 
    href="manage-patients.php?action=delete&id=<?php echo $patient['id']; ?>" 
    onclick="return confirm('Are you sure you want to delete this patient and their associated user account?');"
    style="
        background-color: #e74c3c;
        color: white;
        padding: 8px 16px;
        text-decoration: none;
        border-radius: 4px;
        font-size: 14px;
        display: inline-block;
        transition: background-color 0.3s ease;
    "
    onmouseover="this.style.backgroundColor='#c0392b';"
    onmouseout="this.style.backgroundColor='#e74c3c';"
>
    Delete
</a>

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