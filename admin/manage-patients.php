<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_admin();

$message = '';

// Handle Delete Patient
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $patient_id = $_GET['id'];
    
    // Get user_id first
    $stmt = $conn->prepare("SELECT user_id FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    if ($user_id) {
        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM patients WHERE id = $patient_id");
            $conn->query("DELETE FROM users WHERE id = $user_id");
            $conn->commit();
            $message = "<div class='alert alert-success'>Patient and associated account deleted.</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='alert alert-danger'>Error deleting patient.</div>";
        }
    }
}

// Fetch all patients
$patients = [];
$result = $conn->query("SELECT p.*, u.username FROM patients p JOIN users u ON p.user_id = u.id ORDER BY p.id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}

$rawProfilePic = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';
$profilePic = preg_replace('#^\\.\\./#', '', $rawProfilePic); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patients | Admin</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/shared-table-design.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle"><i class="fas fa-bars"></i> <span>Menu</span></button>
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
                <li><a href="manage-doctors.php" class="sidebar-link" data-target="manage-doctors.php"><i class="fas fa-user-md"></i> Doctors</a></li>
                <li><a href="manage-patients.php" class="active sidebar-link" data-target="manage-patients.php"><i class="fas fa-user-injured"></i> Patients</a></li>
                <li><a href="../messaging/messaging.php"><i class="fas fa-comments"></i> Messages</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container">
                <div class="welcome-section">
                    <h2>Manage Patients</h2>
                    <p>View and manage all registered patients in the system.</p>
                </div>
                
                <?php echo $message; ?>

                <div class="panel-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="section-title mb-0"><i class="fas fa-users text-primary"></i> Patient Directory</h3>
                        <span class="badge bg-primary-light text-primary"><?php echo count($patients); ?> Total Patients</span>
                    </div>
                    
                    <?php if (empty($patients)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-injured fa-3x"></i>
                            <p>No patients found in the system.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table-shared">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Contact Info</th>
                                        <th>Demographics</th>
                                        <th>Account</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($patients as $patient): 
                                        $pPic = preg_replace('#^\\.\\./#', '', $patient['profile_pic'] ?? 'assets/images/default-avatar.png');
                                    ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 12px;">
                                                    <img src="../<?php echo htmlspecialchars($pPic); ?>?t=<?php echo time(); ?>" alt="Patient" class="user-icon">
                                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                                        <div style="font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($patient['name']); ?></div>
                                                        <small class="text-muted" style="font-weight: 600;">#P-<?php echo $patient['id']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="contact-info">
                                                    <small><i class="fas fa-phone-alt fa-xs text-primary"></i> <?php echo htmlspecialchars($patient['phone']); ?></small>
                                                    <small><i class="fas fa-envelope fa-xs text-primary"></i> <?php echo htmlspecialchars($patient['email']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                                    <div>
                                                        <span class="badge <?php echo $patient['gender'] == 'Male' ? 'bg-info-light text-info' : ($patient['gender'] == 'Female' ? 'bg-success-light text-success' : 'bg-warning-light text-warning'); ?>">
                                                            <?php echo htmlspecialchars($patient['gender']); ?>
                                                        </span>
                                                    </div>
                                                    <small class="text-muted"><i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($patient['date_of_birth'])); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                                    <code><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($patient['username']); ?></code>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="button-group">
                                                    <a href="edit-patient.php?id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit Profile"><i class="fas fa-user-edit"></i></a>
                                                    <a href="manage-patients.php?action=delete&id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Note: This will permanently delete the patient and their user account. Proceed?');" title="Delete Account"><i class="fas fa-trash-alt"></i></a>
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
                <label for="profilePicInput" style="position: absolute; bottom: 30px; right: 10px; background: var(--primary-color); color: #fff; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid #fff;">
                    <i class="fas fa-camera"></i>
                </label>
            </div>
            <h3><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></h3>
            <p>System Administrator</p>
            
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
            </form>
            <div id="uploadMessage"></div>
            
            <hr>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-chart-line" style="margin-right: 12px;"></i> Dashboard</a></li>
                <li><a href="../auth/logout.php" style="color: var(--error);"><i class="fas fa-sign-out-alt" style="margin-right: 12px;"></i> Logout</a></li>
            </ul>
            <button class="close-btn" id="closeProfile">Close Panel</button>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
        const BASE_URL = '/';
    </script>
    <script src="../assets/js/profile-overlay.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
    <script src="../assets/js/ui-ux.js"></script>
</body>
</html>