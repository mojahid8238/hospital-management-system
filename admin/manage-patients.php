<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_admin();

$message = '';

// Handle Delete Patient
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $patient_id_to_delete = $_GET['id'];

    // Get user_id associated with the patient to delete from users table as well
    $stmt_get_user = $conn->prepare("SELECT user_id FROM patients WHERE id = ?");
    $stmt_get_user->bind_param("i", $patient_id_to_delete);
    $stmt_get_user->execute();
    $stmt_get_user->bind_result($user_id_to_delete);
    $stmt_get_user->fetch();
    $stmt_get_user->close();

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
$result = $conn->query("SELECT id, name, date_of_birth, gender, address, phone, email, username, image FROM patients");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}
?>

<div class="container">
    <h2>Manage Patients</h2>
    <?php echo $message; ?>

    <h3>Existing Patients</h3>
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
                            <th>Image</th>
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
                                <td><img src="../assets/images/<?php echo $patient['image']; ?>" alt="<?php echo $patient['name']; ?>" width="50"></td>
                                <td class="action-links">
                                    <a href="edit-patient.php?id=<?php echo $patient['id']; ?>">Edit</a> |
                                    <a href="manage-patients.php?action=delete&id=<?php echo $patient['id']; ?>" onclick="return confirm('Are you sure you want to delete this patient and their associated user account?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>        <?php endif; ?>
    </div>