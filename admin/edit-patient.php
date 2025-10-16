<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_admin();

$patient_id = $_GET['id'] ?? null;
$patient = null;
$message = '';

if (!$patient_id) {
    header("Location: manage-patients.php");
    exit();
}

// Fetch patient details
$stmt = $conn->prepare("SELECT p.id, p.name, p.date_of_birth, p.gender, p.address, p.phone, p.email, u.username, u.id as user_id FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $patient = $result->fetch_assoc();
} else {
    $message = "<p style='color: red;'>Patient not found.</p>";
    $patient_id = null; // Invalidate patient_id if not found
}
$stmt->close();

// Handle Update Patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $patient_id) {
    $name = $_POST['name'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? ''; // Allow updating username

    if (empty($name) || empty($date_of_birth) || empty($gender) || empty($address) || empty($phone) || empty($email) || empty($username)) {
        $message = "<p style='color: red;'>All fields are required.</p>";
    } else {
        $conn->begin_transaction();
        try {
            // Update patient details
            $stmt_patient = $conn->prepare("UPDATE patients SET name = ?, date_of_birth = ?, gender = ?, address = ?, phone = ?, email = ? WHERE id = ?");
            $stmt_patient->bind_param("ssssssi", $name, $date_of_birth, $gender, $address, $phone, $email, $patient_id);
            $stmt_patient->execute();
            $stmt_patient->close();

            // Update associated user's username
            $stmt_user = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt_user->bind_param("si", $username, $patient['user_id']);
            $stmt_user->execute();
            $stmt_user->close();

            $conn->commit();
            $message = "<p style='color: green;'>Patient updated successfully!</p>";
            // Refresh patient data after update
            $stmt = $conn->prepare("SELECT p.id, p.name, p.date_of_birth, p.gender, p.address, p.phone, p.email, u.username, u.id as user_id FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $patient = $result->fetch_assoc();
            $stmt->close();

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $message = "<p style='color: red;'>Error updating patient: " . $exception->getMessage() . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient</title>
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
        .container form input[type="date"],
        .container form input[type="email"],
        .container form select {
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
        <h2>Edit Patient</h2>
        <?php echo $message; ?>

        <?php if ($patient): ?>
            <form action="edit-patient.php?id=<?php echo $patient['id']; ?>" method="POST">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($patient['name']); ?>" required>
                <br>
                <label for="date_of_birth">Date of Birth:</label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($patient['date_of_birth']); ?>" required>
                <br>
                <label for="gender">Gender:</label>
                <select id="gender" name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male" <?php echo ($patient['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($patient['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo ($patient['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
                <br>
                <label for="address">Address:</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($patient['address']); ?>" required>
                <br>
                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($patient['phone']); ?>" required>
                <br>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" required>
                <br>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($patient['username']); ?>" required>
                <br>
                <button type="submit">Update Patient</button>
                <a href="manage-patients.php" class="cancel-button">Cancel</a>
            </form>
        <?php else: ?>
            <p>Patient details could not be loaded. <a href="manage-patients.php">Go back to Manage Patients</a></p>
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