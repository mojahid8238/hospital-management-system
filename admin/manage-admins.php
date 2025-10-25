<?php
$page_title = 'Manage Admins';
require_once '../includes/db.php';
require_once '../includes/auth.php';

redirect_if_not_logged_in();
redirect_if_not_admin();

// Ensure profile_pic session is initialized for display
if (!isset($_SESSION['profile_pic'])) {
    $_SESSION['profile_pic'] = 'assets/images/default-avatar.png'; 
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_admin'])) {
    $admin_id_to_approve = $_POST['admin_id'];
    $stmt = $conn->prepare("UPDATE admin SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $admin_id_to_approve);
    $stmt->execute();
    $stmt->close();
}

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_admin'])) {
    $admin_id_to_cancel = $_POST['admin_id'];

    // Get user_id from admin table before deleting
    $stmt_get_user = $conn->prepare("SELECT user_id FROM admin WHERE id = ?");
    $stmt_get_user->bind_param("i", $admin_id_to_cancel);
    $stmt_get_user->execute();
    $stmt_get_user->bind_result($user_id_to_delete);
    $stmt_get_user->fetch();
    $stmt_get_user->close();

    // Delete from admin table
    $stmt_delete_admin = $conn->prepare("DELETE FROM admin WHERE id = ?");
    $stmt_delete_admin->bind_param("i", $admin_id_to_cancel);
    $stmt_delete_admin->execute();
    $stmt_delete_admin->close();

    // Delete from users table
    if ($user_id_to_delete) {
        $stmt_delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt_delete_user->bind_param("i", $user_id_to_delete);
        $stmt_delete_user->execute();
        $stmt_delete_user->close();
    }
}

// Fetch pending admins
$pending_admins = [];
$stmt = $conn->prepare("SELECT id, name, email FROM admin WHERE status = 'pending'");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_admins[] = $row;
}
$stmt->close();
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
            <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="user-icon user-profile-pic" id="profileToggle">
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
                <h2>Manage Admins</h2>

                <h3>Pending Admin Requests</h3>
                <?php if (empty($pending_admins)): ?>
                    <p>No pending admin requests.</p>
                <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_admins as $admin): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($admin['name']) ?></td>
                                            <td><?= htmlspecialchars($admin['email']) ?></td>
                                            <td>
                                                <form method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                                    <button type="submit" name="approve_admin">Approve</button>
                                                </form>
                                                <form method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                                    <button type="submit" name="cancel_admin" style="background-color: red;" onclick="return confirm('Are you sure you want to cancel this admin request?')">Cancel</button>
                                                </form>
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
            <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay" class="user-profile-pic">
           
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

    <script>
        const BASE_URL = '/';
    </script>
    <script src="../assets/js/profile-overlay.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
</body>
</html>