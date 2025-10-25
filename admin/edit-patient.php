<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/auth.php';
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
            $stmt_patient = $conn->prepare("UPDATE patients SET name = ?, date_of_birth = ?, gender = ?, address = ?, phone = ?, email = ?, username = ? WHERE id = ?");
            $stmt_patient->bind_param("sssssssi", $name, $date_of_birth, $gender, $address, $phone, $email, $username, $patient_id);
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">☰ Toggle Menu</button>
            <a href="#">Admin Panel</a>
        </div>
        <div class="nav-right">
            <img src="<?php echo $profile_pic_path; ?>" alt="Profile Picture" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="adminSidebar">
            <h3>Admin Options</h3>
            <ul>
                <li><a href="manage-admins.php" class="sidebar-link">Manage Admins</a></li>
                <li><a href="manage-doctors.php" class="sidebar-link">Manage Doctors</a></li>
                <li><a href="manage-patients.php" class="sidebar-link">Manage Patients</a></li>
                <li><a href="reports.php" class="sidebar-link">View Reports</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container">
                <h2>Edit Patient</h2>
                <?php echo $message; ?>

                <?php if ($patient): ?>
                    <form action="edit-patient.php?id=<?php echo $patient['id']; ?>" method="POST">
                        <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($patient['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth:</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($patient['date_of_birth']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender:</label>
                            <select id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($patient['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($patient['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($patient['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="address">Address:</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($patient['address']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone:</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($patient['phone']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($patient['username']); ?>" required>
                        </div>
                        <button type="submit">Update Patient</button>
                        <a href="manage-patients.php" class="cancel-button">Cancel</a>
                    </form>
                <?php else: ?>
                    <p>Patient details could not be loaded. <a href="manage-patients.php">Go back to Manage Patients</a></p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <img src="<?php echo $profile_pic_path; ?>" alt="Profile Picture" id="profileImageDisplay">
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data" style="display: none;">
                <input type="file" name="profile_pic" id="profilePicInput" accept="image/*">
            </form>
            <div id="uploadMessage" style="font-size: 0.95rem; text-align: center; margin-top: 5px;"></div>
            <h3><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></h3>
            <hr>
            <ul>
                <li><a href="dashboard.php">Admin Dashboard</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>

    <script src="../assets/js/profile-overlay.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
</body>
</html>