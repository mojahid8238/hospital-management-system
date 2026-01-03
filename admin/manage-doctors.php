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
    $doctor_id = $_POST['doctor_id'] ?? null;

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
        $message = "<div class='alert alert-danger'>All fields are required.</div>";
    } else {
        if ($doctor_id) {
            $stmt = $conn->prepare("UPDATE doctors SET name = ?, specialization_id = ?, degrees = ?, schedule = ?, phone = ?, email = ? WHERE id = ?");
            $stmt->bind_param("sissssi", $name, $specialization_id, $degrees, $schedule, $phone, $email, $doctor_id);
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Doctor updated successfully!</div>";
            } else {
                $message = "<div class='alert alert-danger'>Error updating doctor.</div>";
            }
            $stmt->close();
        } else {
            // New Doctor Creation Logic (Simplified for brevity as current code has it)
            // ... (keeping user and doctor creation logic)
            $username = strtolower(str_replace(' ', '', $name)) . rand(100, 999);
            $password = password_hash("password123", PASSWORD_DEFAULT);
            $role = 'doctor';

            $stmt_user = $conn->prepare("INSERT INTO users (username, password, role, name) VALUES (?, ?, ?, ?)");
            $stmt_user->bind_param("ssss", $username, $password, $role, $name);
            if ($stmt_user->execute()) {
                $new_user_id = $stmt_user->insert_id;
                $stmt_doctor = $conn->prepare("INSERT INTO doctors (user_id, name, specialization_id, degrees, schedule, phone, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt_doctor->bind_param("isissss", $new_user_id, $name, $specialization_id, $degrees, $schedule, $phone, $email);
                if ($stmt_doctor->execute()) {
                    $message = "<div class='alert alert-success'>Doctor added! Username: $username</div>";
                } else {
                    $conn->query("DELETE FROM users WHERE id = $new_user_id");
                    $message = "<div class='alert alert-danger'>Error adding doctor.</div>";
                }
            }
        }
    }
}

// Handle Delete Doctor
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $doctor_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT user_id FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    if ($user_id) {
        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM doctors WHERE id = $doctor_id");
            $conn->query("DELETE FROM users WHERE id = $user_id");
            $conn->commit();
            $message = "<div class='alert alert-success'>Doctor deleted successfully!</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='alert alert-danger'>Error deleting doctor.</div>";
        }
    }
}

// Fetch all doctors
$doctors = [];
$result = $conn->query("SELECT d.*, s.name as spec_name, u.username FROM doctors d JOIN users u ON d.user_id = u.id JOIN specializations s ON d.specialization_id = s.id ORDER BY d.id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}

$rawProfilePic = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';
$profilePic = preg_replace('#^\\.\\./#', '', $rawProfilePic); 
?>
<!DOCTYPE html>
<html lang="en">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/shared-table-design.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">
                <i class="fas fa-bars"></i>
                <span>Menu</span>
            </button>
            <a href="dashboard.php">HealthCare Admin</a>
        </div>
        <div class="nav-right">
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['name'] ?? 'Administrator'); ?></span>
            </div>
            <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="adminSidebar">
            <h3>System Management</h3>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="manage-admins.php" class="sidebar-link" data-target="manage-admins.php"><i class="fas fa-user-shield"></i> Admins</a></li>
                <li><a href="manage-doctors.php" class="active sidebar-link" data-target="manage-doctors.php"><i class="fas fa-user-md"></i> Doctors</a></li>
                <li><a href="manage-patients.php" class="sidebar-link" data-target="manage-patients.php"><i class="fas fa-user-injured"></i> Patients</a></li>
                <li><a href="../messaging/messaging.php"><i class="fas fa-comments"></i> Messages</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container">
                <div class="welcome-section">
                    <h2>Manage Doctors</h2>
                    <p>Register new medical professionals and manage active practitioners.</p>
                </div>

                <?php echo $message; ?>

                <div class="panel-card mt-4">
                    <h3 class="section-title mb-4">Practitioner Directory</h3>
                    
                    <?php if (empty($doctors)): ?>
                        <div class="alert alert-info">No doctors found in the database.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Doctor</th>
                                        <th>Specialization</th>
                                        <th>Schedule</th>
                                        <th>Contact</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($doctors as $doctor): 
                                        $dPic = preg_replace('#^\\.\\./#', '', $doctor['profile_pic'] ?? 'assets/images/default-avatar.png');
                                    ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 12px;">
                                                    <img src="../<?php echo htmlspecialchars($dPic); ?>?t=<?php echo time(); ?>" alt="Doctor" class="user-icon">
                                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                                         <strong style="color: var(--text-main);">Dr. <?php echo htmlspecialchars($doctor['name'] ?? ''); ?></strong>
                                                        <small class="text-muted" style="font-weight: 600;"><?php echo htmlspecialchars($doctor['degrees'] ?? 'N/A'); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                 <span class="badge bg-primary"><?php echo htmlspecialchars($doctor['spec_name'] ?? 'General'); ?></span>
                                            </td>
                                            <td>
                                                 <small><i class="far fa-clock"></i> <?php echo htmlspecialchars($doctor['schedule'] ?? 'Not set'); ?></small>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($doctor['email'] ?? ''); ?></small><br>
                                                <small><?php echo htmlspecialchars($doctor['phone'] ?? ''); ?></small>
                                            </td>
                                            <td>
                                                <div class="button-group">
                                                    <a href="edit-doctor.php?id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                                    <a href="manage-doctors.php?action=delete&id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this doctor?');"><i class="fas fa-trash"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Profile Overlay -->
    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <div class="profile-pic-wrapper" style="position: relative;">
                <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay" class="profile-overlay-pic">
            </div>
            <h3><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></h3>
            <hr>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
            <button class="close-btn" id="closeProfile">Close Panel</button>
        </div>
    </div>

    <script src="../assets/js/profile-overlay.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
    <script src="../assets/js/ui-ux.js"></script>
</body>
</html>