<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

redirect_if_not_logged_in();
redirect_if_not_admin();

// Handle approval
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
    <title>Manage Admins</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- General styles -->
    <link rel="stylesheet" href="../assets/css/homepage.css"> <!-- For navbar and overlay styles -->
    <style>
        /* Specific styles for this page */
        .container {
            padding: 20px;
            max-width: 900px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .container h2 {
            color: #007bff;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        form button {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        form button[name="approve_admin"] {
            background-color: #28a745;
            color: white;
        }
        form button[name="approve_admin"]:hover {
            background-color: #218838;
        }
        form button[name="cancel_admin"] {
            background-color: #dc3545;
            color: white;
        }
        form button[name="cancel_admin"]:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <a href="#">Admin Panel</a>
        </div>
        <div class="nav-right">
            <span class="user-icon" id="profileToggle">👤</span>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <h2>Manage Admins</h2>

        <h3>Pending Admin Requests</h3>
        <?php if (empty($pending_admins)): ?>
            <p>No pending admin requests.</p>
        <?php else: ?>
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
        <?php endif; ?>
    </div>

    <!-- Profile side overlay - copied from homepage.php, adjust as needed -->
    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <img src="<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'default-avatar.png'); ?>" alt="Profile Picture" id="profileImageDisplay">
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                <label for="profilePicInput" class="upload-btn">Change Picture</label>
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
                <button type="submit" style="display: none;">Upload</button>
            </form>
            <div id="uploadMessage" style="margin-top: 10px; color: green;"></div>
            <h3><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
            <p>Role: Admin</p>
            <hr>
            <h4>Dashboards</h4>
            <ul>
                <li><a href="dashboard.php">Admin Dashboard</a></li>
                <li><a href="../includes/homepage.php">Patient Homepage</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
            <button class="close-btn" id="closeProfile">Close</button>
        </div>
    </div>

    <script>
        const profileToggle = document.getElementById('profileToggle');
        const profileOverlay = document.getElementById('profileOverlay');
        const closeProfile = document.getElementById('closeProfile');
        const profilePicInput = document.getElementById('profilePicInput');
        const profilePicUploadForm = document.getElementById('profilePicUploadForm');
        const profileImageDisplay = document.getElementById('profileImageDisplay');
        const uploadMessage = document.getElementById('uploadMessage');

        profileToggle.addEventListener('click', () => {
            profileOverlay.classList.add('open');
        });
        closeProfile.addEventListener('click', () => {
            profileOverlay.classList.remove('open');
        });

        profilePicInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const formData = new FormData(profilePicUploadForm);
                fetch(profilePicUploadForm.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        profileImageDisplay.src = data.profile_pic_path + '?t=' + new Date().getTime(); // Add timestamp to bust cache
                        uploadMessage.textContent = 'Profile picture updated successfully!';
                        uploadMessage.style.color = 'green';
                    } else {
                        uploadMessage.textContent = data.message || 'Error uploading profile picture.';
                        uploadMessage.style.color = 'red';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    uploadMessage.textContent = 'An error occurred during upload.';
                    uploadMessage.style.color = 'red';
                });
            }
        });
    </script>
</body>
</html>