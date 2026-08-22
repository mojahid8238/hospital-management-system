<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/change_password.php';
redirect_if_not_admin();

$rawProfilePic = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';
$profilePic = preg_replace('#^\\.\\./#', '', $rawProfilePic);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | Health Care</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo.ico">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .edit-form-card {
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            padding: 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            max-width: 800px;
            margin: 0 auto;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.9rem;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            background: var(--bg-hover);
            transition: var(--transition-base);
            font-family: inherit;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .form-actions {
            margin-top: 32px;
            display: flex;
            gap: 16px;
            border-top: 1px solid var(--border-color);
            padding-top: 24px;
        }
        .btn-update {
            background: var(--gradient-primary);
            color: #fff;
            padding: 12px 32px;
            border-radius: var(--radius-md);
            border: none;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition-base);
        }
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .btn-cancel {
            background: var(--bg-color);
            color: var(--text-main);
            padding: 12px 32px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 700;
            transition: var(--transition-base);
            display: inline-block;
        }
        .btn-cancel:hover {
            background: var(--border-color);
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">
                <i class="fas fa-bars"></i>
                <span>Menu</span>
            </button>
            <a href="dashboard.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; font-weight: 800; font-size: 1.25rem;">
                <img src="../assets/images/logo.png" alt="Logo" style="height: 35px; border-radius: 5px;">
                Health Care Admin
            </a>
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
                <li><a href="manage-admins.php"><i class="fas fa-user-shield"></i> Admins</a></li>
                <li><a href="manage-doctors.php"><i class="fas fa-user-md"></i> Doctors</a></li>
                <li><a href="manage-patients.php"><i class="fas fa-user-injured"></i> Patients</a></li>
                <li><a href="../messaging/messaging.php"><i class="fas fa-comments"></i> Messages</a></li>
                <li><a href="settings.php"><i class="fas fa-user-cog"></i> Account Settings</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container">
                <div class="welcome-section">
                    <h2>Account Settings</h2>
                    <p>Manage your account security.</p>
                </div>

                <?php render_change_password_form($change_password_message); ?>
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
                <li><a href="settings.php"><i class="fas fa-user-cog" style="margin-right: 12px;"></i> Account Settings</a></li>
                <li><a href="../auth/logout.php" style="color: var(--error);"><i class="fas fa-sign-out-alt" style="margin-right: 12px;"></i> Logout</a></li>
            </ul>
            <button class="close-btn" id="closeProfile">Close Panel</button>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
        const BASE_URL = '/';
    </script>
    <script src="../assets/js/ui-ux.js"></script>
    <script src="../assets/js/profile-overlay.js"></script>
</body>
</html>
