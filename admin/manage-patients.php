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
$result = $conn->query("SELECT id, name, date_of_birth, gender, address, phone, email, username, profile_pic FROM patients");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
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
                <li><a href="manage-admins.php" class="sidebar-link" data-target="manage-admins.php">Manage Admins</a></li>
                <li><a href="manage-doctors.php" class="sidebar-link" data-target="manage-doctors.php">Manage Doctors</a></li>
                <li><a href="manage-patients.php" class="sidebar-link" data-target="manage-patients.php">Manage Patients</a></li>
                <li><a href="reports.php" class="sidebar-link" data-target="reports.php">View Reports</a></li>
            </ul>
        </aside>

       
        <main class="content-area" id="mainContent">
            <div class="container">
                <h2>Manage Patients</h2>
                <?php echo $message; ?>

                <h3>Existing Patients</h3>
                <?php if (empty($patients)): ?>
                    <p>No patients found.</p>
                <?php else: ?>
                        <div class="table-responsive">
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
                                        <th>Picture</th>
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
                                            <td><img src="/hospital-management-system/<?php echo htmlspecialchars($patient['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>" alt="Profile Pic" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;"></td>
                                            <td class="action-links">
                                                <a href="edit-patient.php?id=<?php echo $patient['id']; ?>">Edit</a> |
                                                <a href="manage-patients.php?action=delete&id=<?php echo $patient['id']; ?>" onclick="return confirm('Are you sure you want to delete this patient and their associated user account?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>                <?php endif; ?>
            </div>
        </main>
    </div>

    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <img src="<?php echo $profile_pic_path; ?>" alt="Profile Picture" id="profileImageDisplay">
           
            <!-- Hidden form and input for file selection -->
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data" style="display: none;">
                <input type="file" name="profile_pic" id="profilePicInput" accept="image/*">
            </form>
            <!-- Upload message container -->
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