<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_admin();

$message = '';

// Handle Add/Edit Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $specialization = $_POST['specialization'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $user_id = $_POST['user_id'] ?? null; // For editing existing doctor linked to a user
    $doctor_id = $_POST['doctor_id'] ?? null; // For editing existing doctor

    if (empty($name) || empty($specialization) || empty($phone) || empty($email)) {
        $message = "<p style='color: red;'>All fields are required.</p>";
    } else {
        if ($doctor_id) {
            // Update existing doctor
            $stmt = $conn->prepare("UPDATE doctors SET name = ?, specialization = ?, phone = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $specialization, $phone, $email, $doctor_id);
            if ($stmt->execute()) {
                $message = "<p style='color: green;'>Doctor updated successfully!</p>";
            } else {
                $message = "<p style='color: red;'>Error updating doctor: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            // Add new doctor - first create a user account for the doctor
            // For simplicity, let's assume a default password and role 'doctor' for new doctors
            $username = strtolower(str_replace(' ', '', $name)) . rand(100, 999); // Generate unique username
            $password = password_hash("password123", PASSWORD_DEFAULT); // Default password
            $role = 'doctor';

            $stmt_user = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt_user->bind_param("sss", $username, $password, $role);

            if ($stmt_user->execute()) {
                $new_user_id = $stmt_user->insert_id;
                $stmt_user->close();

                $stmt_doctor = $conn->prepare("INSERT INTO doctors (user_id, name, specialization, phone, email) VALUES (?, ?, ?, ?, ?)");
                $stmt_doctor->bind_param("issss", $new_user_id, $name, $specialization, $phone, $email);

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
$result = $conn->query("SELECT d.id, d.name, d.specialization, d.phone, d.email, u.username FROM doctors d JOIN users u ON d.user_id = u.id");
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
    <title>Manage Doctors</title>
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
        .action-links a {
            margin-right: 10px;
            color: #007bff;
            text-decoration: none;
        }
        .action-links a:hover {
            text-decoration: underline;
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
        <h2>Manage Doctors</h2>
        <?php echo $message; ?>

        <h3>Add New Doctor</h3>
        <form action="manage-doctors.php" method="POST">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
            <br>
            <label for="specialization">Specialization:</label>
            <input type="text" id="specialization" name="specialization" required>
            <br>
            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" required>
            <br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            <br>
            <button type="submit">Add Doctor</button>
        </form>

        <h3>Existing Doctors</h3>
        <?php if (empty($doctors)): ?>
            <p>No doctors found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Specialization</th>
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
                            <td><?php echo $doctor['name']; ?></td>
                            <td><?php echo $doctor['specialization']; ?></td>
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