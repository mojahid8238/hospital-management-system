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
$stmt = $conn->prepare("SELECT d.id, d.name, d.specialization, d.phone, d.email, u.username, u.id as user_id FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $doctor = $result->fetch_assoc();
} else {
    $message = "<p style='color: red;'>Doctor not found.</p>";
    $doctor_id = null;
}
$stmt->close();

// Handle Update Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $doctor_id) {
    $name = $_POST['name'] ?? '';
    $specialization = $_POST['specialization'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';

    if (empty($name) || empty($specialization) || empty($phone) || empty($email) || empty($username)) {
        $message = "<p style='color: red;'>All fields are required.</p>";
    } else {
        $conn->begin_transaction();
        try {
            // Update doctor details
            $stmt_doctor = $conn->prepare("UPDATE doctors SET name = ?, specialization = ?, phone = ?, email = ? WHERE id = ?");
            $stmt_doctor->bind_param("ssssi", $name, $specialization, $phone, $email, $doctor_id);
            $stmt_doctor->execute();
            $stmt_doctor->close();

            // Update username
            $stmt_user = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt_user->bind_param("si", $username, $doctor['user_id']);
            $stmt_user->execute();
            $stmt_user->close();

            $conn->commit();
            $message = "<p style='color: green;'>Doctor updated successfully!</p>";

            // Refresh doctor data
            $stmt = $conn->prepare("SELECT d.id, d.name, d.specialization, d.phone, d.email, u.username, u.id as user_id FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ?");
            $stmt->bind_param("i", $doctor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $doctor = $result->fetch_assoc();
            $stmt->close();

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $message = "<p style='color: red;'>Error updating doctor: " . $exception->getMessage() . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- General styles -->
    <link rel="stylesheet" href="../assets/css/homepage.css"> <!-- For navbar and overlay styles -->
    <style>
        /* Specific styles for this page */
        .container {
            padding: 20px;
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .container h2 {
            color: #007bff;
            margin-bottom: 15px;
        }
        .container form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .container form input[type="text"],
        .container form input[type="email"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .container form button[type="submit"] {
            background-color: #28a745;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .container form button[type="submit"]:hover {
            background-color: #218838;
        }
        .container .cancel-button {
            background-color: #dc3545;
            color: white;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 4px;
            margin-left: 5px;
            font-size: 16px;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        .container .cancel-button:hover {
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
        <h2>Edit Doctor</h2>
        <?php echo $message; ?>

        <?php if ($doctor): ?>
            <form action="edit-doctor.php?id=<?php echo $doctor['id']; ?>" method="POST">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($doctor['name']); ?>" required>
                <br>
                <label for="specialization">Specialization:</label>
                <input type="text" id="specialization" name="specialization" value="<?php echo htmlspecialchars($doctor['specialization']); ?>" required>
                <br>
                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($doctor['phone']); ?>" required>
                <br>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($doctor['email']); ?>" required>
                <br>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($doctor['username']); ?>" required>
                <br>
                <button type="submit">Update Doctor</button>
                <a href="manage-doctors.php" class="cancel-button">Cancel</a>
            </form>
        <?php else: ?>
            <p>Doctor details could not be loaded. <a href="manage-doctors.php">Go back to Manage Doctors</a></p>
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