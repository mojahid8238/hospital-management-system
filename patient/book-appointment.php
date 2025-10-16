<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_patient();

$patient_id = null;

// Get patient_id for the logged-in user
$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($patient_id);
$stmt->fetch();
$stmt->close();

$message = '';

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $patient_id) {
    $doctor_id = $_POST['doctor_id'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $reason = $_POST['reason'] ?? '';

    if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time) || empty($reason)) {
        $message = "<p style='color: red;'>All fields are required.</p>";
    } else {
        $full_appointment_datetime = $appointment_date . ' ' . $appointment_time;
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $patient_id, $doctor_id, $full_appointment_datetime, $reason);

        if ($stmt->execute()) {
            $message = "<p style='color: green;'>Appointment booked successfully!</p>";
        } else {
            $message = "<p style='color: red;'>Error booking appointment: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
}

// Fetch doctors
$doctors = [];
$result = $conn->query("SELECT id, name, specialization FROM doctors ORDER BY name ASC");
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
    <title>Book Appointment</title>
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
        .container form input[type="time"],
        .container form select,
        .container form textarea {
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
        .container form button[type="button"] {
            background-color: #dc3545;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            margin-left: 10px;
        }
        .container form button[type="button"]:hover {
            background-color: #c82333;
        }
        .search-wrapper {
            position: relative;
            margin-bottom: 15px;
        }
        #doctorSuggestions {
            list-style: none;
            padding: 0;
            margin: 0;
            border: 1px solid #ddd;
            border-top: none;
            position: absolute;
            width: 100%;
            background-color: #fff;
            z-index: 100;
            max-height: 150px;
            overflow-y: auto;
            display: none; /* Hidden by default */
        }
        #doctorSuggestions li {
            padding: 10px;
            cursor: pointer;
        }
        #doctorSuggestions li:hover {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <a href="#">Patient Panel</a>
        </div>
        <div class="nav-right">
            <span class="user-icon" id="profileToggle">👤</span>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <h2>Book New Appointment</h2>
        <p>Welcome, <?php echo $_SESSION['username']; ?>! Book your appointment here:</p>

        <?php echo $message; ?>

        <?php if (empty($doctors)): ?>
            <p>No doctors available for booking at the moment.</p>
        <?php else: ?>
            <form action="book-appointment.php" method="POST" onsubmit="return validateDoctorSelection()">
                <label for="specializationFilter">Filter by Specialization:</label>
                <select id="specializationFilter">
                    <option value="">All Specializations</option>
                    <?php
                    $specializations = array_unique(array_column($doctors, 'specialization'));
                    sort($specializations);
                    foreach ($specializations as $spec) {
                        echo "<option value=\"" . htmlspecialchars($spec) . "\">" . htmlspecialchars($spec) . "</option>";
                    }
                    ?>
                </select>

                <label for="searchDoctor">Search Doctor:</label>
                <div class="search-wrapper">
                    <input type="text" id="searchDoctor" placeholder="Type doctor name..." autocomplete="off">
                    <ul id="doctorSuggestions"></ul>
                </div>

                <input type="hidden" name="doctor_id" id="selectedDoctorId" required>

                <label for="appointment_date">Date:</label>
                <input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">

                <label for="appointment_time">Time:</label>
                <input type="time" id="appointment_time" name="appointment_time" required>

                <label for="reason">Reason for Appointment:</label>
                <textarea id="reason" name="reason" rows="4" required></textarea>

                <button type="submit">Book Appointment</button>
                <button type="button" onclick="window.location.href='../includes/homepage.php'">Cancel</button>
            </form>
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
            <p>Role: Patient</p>
            <hr>
            <h4>Dashboards</h4>
            <ul>
                <li><a href="dashboard.php">Patient Dashboard</a></li>
                <li><a href="../includes/homepage.php">Patient Homepage</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
            <button class="close-btn" id="closeProfile">Close</button>
        </div>
    </div>

    <script>
        const doctors = <?php echo json_encode($doctors); ?>;
        const input = document.getElementById('searchDoctor');
        const suggestions = document.getElementById('doctorSuggestions');
        const specializationFilter = document.getElementById('specializationFilter');
        const hiddenDoctorId = document.getElementById('selectedDoctorId');

        input.addEventListener('input', showSuggestions);
        specializationFilter.addEventListener('change', showSuggestions);

        function showSuggestions() {
            const query = input.value.toLowerCase();
            const specialization = specializationFilter.value.toLowerCase();
            suggestions.innerHTML = '';

            if (query.length < 1) {
                suggestions.style.display = 'none';
                return;
            }

            const filtered = doctors.filter(doc => {
                const matchesName = doc.name.toLowerCase().includes(query);
                const matchesSpec = !specialization || doc.specialization.toLowerCase() === specialization;
                return matchesName && matchesSpec;
            });

            if (filtered.length === 0) {
                suggestions.style.display = 'none';
                return;
            }

            filtered.forEach(doc => {
                const li = document.createElement('li');
                li.textContent = `${doc.name} (${doc.specialization})`;
                li.dataset.id = doc.id;
                li.addEventListener('click', () => {
                    input.value = `${doc.name} (${doc.specialization})`;
                    hiddenDoctorId.value = doc.id;
                    suggestions.innerHTML = '';
                    suggestions.style.display = 'none';
                });
                suggestions.appendChild(li);
            });

            suggestions.style.display = 'block';
        }

        function validateDoctorSelection() {
            if (!hiddenDoctorId.value) {
                alert("Please select a valid doctor from the suggestions.");
                return false;
            }
            return true;
        }

        // Profile overlay functionality (copied from homepage.php)
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