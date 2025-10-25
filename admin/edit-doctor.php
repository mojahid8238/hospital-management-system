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
$stmt = $conn->prepare("SELECT d.id, d.name, d.specialization, d.phone, d.email, u.username, u.id as user_id, d.profile_pic FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $doctor = $result->fetch_assoc();
    $_SESSION['profile_pic'] = $doctor['profile_pic'] ?? 'assets/images/default-avatar.png';
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

// -------------------------------------------------------------------------
// FIX 1: Clean the path stored in the database/session by 
//        removing the leading '../' if it exists.
// -------------------------------------------------------------------------
// Use a default path that is relative to the project root (no ../)
$rawProfilePic = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';
// Use ltrim to safely remove the leading "../" if it exists.
$profilePic = preg_replace('#^\\.\\./#', '', $rawProfilePic); 
// Now $profilePic contains: 'assets/images/profile_pics/patient_2.png' or 'assets/images/default-avatar.png'
// -------------------------------------------------------------------------
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor</title>
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
            <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="user-icon user-profile-pic" id="profileToggle">
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
                <h2>Edit Doctor</h2>
                <?php echo $message; ?>

                <?php if ($doctor): ?>
                    <form action="edit-doctor.php?id=<?php echo $doctor['id']; ?>" method="POST">
                        <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($doctor['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="specialization">Specialization:</label>
                            <select id="specialization" name="specialization" required>
                                <?php foreach ($specializations as $spec): ?>
                                    <option value="<?php echo htmlspecialchars($spec['name']); ?>" <?php echo ($doctor['specialization'] == $spec['name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($spec['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone:</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($doctor['phone']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($doctor['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($doctor['username']); ?>" required>
                        </div>
                        <button type="submit">Update Doctor</button>
                        <a href="manage-doctors.php" class="cancel-button">Cancel</a>
                    </form>
                <?php else: ?>
                    <p>Doctor details could not be loaded. <a href="manage-doctors.php">Go back to Manage Doctors</a></p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay" class="user-profile-pic">
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

    <script>
        const BASE_URL = '/';
    </script>
    <script src="../assets/js/profile-overlay.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
</body>
</html>