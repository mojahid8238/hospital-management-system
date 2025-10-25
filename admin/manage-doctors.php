<?php
$page_title = 'Manage Doctors';
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_admin();

$message = '';

// Fetch all specializations
$specializations = [];
$result_spec = $conn->query("SELECT id, name FROM specializations");
if ($result_spec) {
    while ($row_spec = $result_spec->fetch_assoc()) {
        $specializations[] = $row_spec;
    }
}

// Handle Add/Edit Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $specialization_name = $_POST['specialization'] ?? '';
    $degrees = $_POST['degrees'] ?? '';
    $schedule = $_POST['schedule'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $user_id = $_POST['user_id'] ?? null; // For editing existing doctor linked to a user
    $doctor_id = $_POST['doctor_id'] ?? null; // For editing existing doctor

    // Get specialization_id from name
    $specialization_id = null;
    if (!empty($specialization_name)) {
        $stmt_spec = $conn->prepare("SELECT id FROM specializations WHERE name = ?");
        $stmt_spec->bind_param("s", $specialization_name);
        $stmt_spec->execute();
        $stmt_spec->bind_result($specialization_id);
        $stmt_spec->fetch();
        $stmt_spec->close();
    }

    if (empty($name) || empty($specialization_id) || empty($degrees) || empty($schedule) || empty($phone) || empty($email)) {
        $message = "<p style='color: red;'>All fields are required.</p>";
    } else {
        if ($doctor_id) {
            // Update existing doctor
            $stmt = $conn->prepare("UPDATE doctors SET name = ?, specialization_id = ?, degrees = ?, schedule = ?, phone = ?, email = ? WHERE id = ?");
            $stmt->bind_param("sissssi", $name, $specialization_id, $degrees, $schedule, $phone, $email, $doctor_id);
            if ($stmt->execute()) {
                $message = "<p style='color: green;'>Doctor updated successfully!</p>";
            } else {
                $message = "<p style='color: red;'>Error updating doctor: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            // Add new doctor - first create a user account for the doctor
            $username = strtolower(str_replace(' ', '', $name)) . rand(100, 999); // Generate unique username
            $password = password_hash("password123", PASSWORD_DEFAULT); // Default password
            $role = 'doctor';

            $stmt_user = $conn->prepare("INSERT INTO users (username, password, role, name) VALUES (?, ?, ?, ?)");
            $stmt_user->bind_param("ssss", $username, $password, $role, $name);

            if ($stmt_user->execute()) {
                $new_user_id = $stmt_user->insert_id;
                $stmt_user->close();

                $stmt_doctor = $conn->prepare("INSERT INTO doctors (user_id, name, specialization_id, degrees, schedule, phone, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt_doctor->bind_param("isissss", $new_user_id, $name, $specialization_id, $degrees, $schedule, $phone, $email);

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
$result = $conn->query("SELECT d.id, d.name, s.name as specialization, d.degrees, d.schedule, d.phone, d.email, u.username, d.profile_pic FROM doctors d JOIN users u ON d.user_id = u.id JOIN specializations s ON d.specialization_id = s.id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
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
                            <img src="<?php echo $profile_pic_path; ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="user-icon user-profile-pic" id="profileToggle">        </div>
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
                <h2>Manage Doctors</h2>
                <?php echo $message; ?>

               

                <h3>Existing Doctors</h3>
                <?php if (empty($doctors)): ?>
                    <p>No doctors found.</p>
                <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Picture</th>
                                        <th>Name</th>
                                        <th>Specialization</th>
                                        <th>Degrees</th>
                                        <th>Schedule</th>
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
                                            <td><img src="/<?php echo htmlspecialchars($doctor['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile Pic" class="user-profile-pic" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;"></td>
                                            <td><?php echo $doctor['name']; ?></td>
                                            <td><?php echo $doctor['specialization']; ?></td>
                                            <td><?php echo $doctor['degrees']; ?></td>
                                            <td><?php echo $doctor['schedule']; ?></td>
                                            <td><?php echo $doctor['phone']; ?></td>
                                            <td><?php echo $doctor['email']; ?></td>
                                            <td><?php echo $doctor['username']; ?></td>
                                            <td class="action-links">
                                                <a href="edit-doctor.php?id=<?php echo $doctor['id']; ?>">Edit</a> |
                                                <a href="manage-doctors.php?action=delete&id=<?php echo $doctor['id']; ?>" onclick="return confirm('Are you sure you want to delete this doctor and their associated user account?');">Delete</a>
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
            <img src="<?php echo $profile_pic_path; ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay" class="user-profile-pic">
           
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