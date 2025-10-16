<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_admin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        .container h3 {
            margin-top: 25px;
            margin-bottom: 15px;
            color: #333;
        }
        .container ul {
            list-style: none;
            padding: 0;
        }
        .container ul li {
            margin-bottom: 10px;
        }
        .container ul li a {
            display: inline-block;
            padding: 10px 15px;
            background-color: #e3f2fD;
            color: #1976d2;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .container ul li a:hover {
            background-color: #bbdefb;
            color: #1565c0;
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
        <h2>Admin Dashboard</h2>
        <p>Welcome, Admin <?php echo $_SESSION['username']; ?>!</p>

        <h3>Quick Actions</h3>
        <ul>
            <li><a href="manage-admins.php">Manage Admins</a></li>
            <li><a href="manage-doctors.php">Manage Doctors</a></li>
            <li><a href="manage-patients.php">Manage Patients</a></li>
            <li><a href="reports.php">View Reports</a></li>
        </ul>

        <!-- Add more admin-specific content here -->
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