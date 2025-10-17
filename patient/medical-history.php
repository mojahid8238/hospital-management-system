<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_patient();

$patient_id = null;

// Get the patient_id associated with the logged-in user
$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($patient_id);
$stmt->fetch();
$stmt->close();

$medical_history = [];
if ($patient_id) {
    // Fetch past appointments for the patient
    // FIXED: Join with specializations table (s) to get the specialization name
    $stmt = $conn->prepare("SELECT a.id, d.name as doctor_name, d.profile_pic, s.name as specialization, a.appointment_date, a.reason, a.status FROM appointments a JOIN doctors d ON a.doctor_id = d.id JOIN specializations s ON d.specialization_id = s.id WHERE a.patient_id = ? AND a.status != 'Cancelled' ORDER BY a.appointment_date DESC");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $medical_history[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/shared-table.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">☰ Toggle Menu</button>
            <a href="#">Patient Panel</a>
        </div>
        <div class="nav-right">
            <img src="/hospital-management-system/<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="patientSidebar">
            <h3>Patient Options</h3>
            <ul>
                <li><a href="book-appointment.php">Book New Appointment</a></li>
                <li><a href="medical-history.php">View Medical History</a></li>
                <li><a href="cancelled-appointments.php">Cancelled Appointments</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container panel-card">
                <h2 class="card-title mb-3">Your Medical History</h2>
                <p class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>! Here is a summary of your medical history:</p>

                <div class="d-flex justify-content-end mb-3">
                    <select id="statusFilter" class="form-select w-auto">
                        <option value="all">All</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <!-- Removed cancelled option as they are already filtered out by default -->
                    </select>
                </div>

                <?php if (empty($medical_history)): ?>
                    <p>You have no past appointments recorded.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Appointment ID</th>
                                    <th>Doctor Image</th>
                                    <th>Doctor Name</th>
                                    <th>Specialization</th>
                                    <th>Date & Time</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($medical_history as $record): ?>
                                    <tr>
                                        <td data-label="Appointment ID"><?php echo $record['id']; ?></td>
                                        <td data-label="Doctor Image"><img src="../<?php echo htmlspecialchars($record['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>" alt="Dr. <?php echo htmlspecialchars($record['doctor_name']); ?>" class="rounded-circle"></td>
                                        <td data-label="Doctor Name"><?php echo htmlspecialchars($record['doctor_name']); ?></td>
                                        <td data-label="Specialization"><?php echo htmlspecialchars($record['specialization']); ?></td>
                                        <td data-label="Date & Time"><?php echo date('Y-m-d H:i', strtotime($record['appointment_date'])); ?></td>
                                        <td data-label="Reason"><?php echo htmlspecialchars($record['reason']); ?></td>
                                        <td data-label="Status"><span class="badge bg-<?php echo strtolower(htmlspecialchars($record['status'])); ?>"><?php echo htmlspecialchars($record['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Profile side overlay -->
    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <img src="/hospital-management-system/<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay">
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
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
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const patientSidebar = document.getElementById('patientSidebar');
            const mainContent = document.getElementById('mainContent');

            sidebarToggle.addEventListener('click', () => {
                patientSidebar.classList.toggle('closed');
            });

            // Profile overlay functionality
            const profileToggle = document.getElementById('profileToggle');
            const profileOverlay = document.getElementById('profileOverlay');
            const profilePicInput = document.getElementById('profilePicInput');
            const profilePicUploadForm = document.getElementById('profilePicUploadForm');
            const profileImageDisplay = document.getElementById('profileImageDisplay');
            const uploadMessage = document.getElementById('uploadMessage');

            profileToggle.addEventListener('click', (event) => {
                event.stopPropagation();
                profileOverlay.classList.add('open');
            });

            profileOverlay.addEventListener('click', function(event) {
                if (event.target === profileOverlay) {
                    profileOverlay.classList.remove('open');
                }
            });

            mainContent.addEventListener('click', () => {
                profileOverlay.classList.remove('open');
            });

            profileImageDisplay.addEventListener('click', function() {
                profilePicInput.click();
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
                            const newImagePath = '/hospital-management-system/' + data.profile_pic_path + '?t=' + new Date().getTime();
                            profileImageDisplay.src = newImagePath;
                            document.getElementById('profileToggle').src = newImagePath;
                            uploadMessage.textContent = 'Profile picture updated successfully!';
                            uploadMessage.style.color = 'green';
                            setTimeout(() => {
                                uploadMessage.textContent = '';
                            }, 1000);
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

            // Medical history specific JavaScript (filter and fetch)
            let currentMedicalHistory = [];

            function fetchMedicalHistory() {
                fetch('get_patient_appointments.php')
                    .then(response => response.json())
                    .then(data => {
                        if (JSON.stringify(data) !== JSON.stringify(currentMedicalHistory)) {
                            currentMedicalHistory = data;
                            renderMedicalHistory(data);
                        }
                    })
                    .catch(error => console.error('Error fetching medical history:', error));
            }

            function renderMedicalHistory(medicalHistory) {
                const tableBody = document.querySelector('#mainContent tbody');
                if (!tableBody) return; 
                tableBody.innerHTML = ''; 

                if (medicalHistory.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="7">You have no past appointments recorded.</td></tr>';
                    return;
                }

                medicalHistory.forEach(record => {
                    const row = document.createElement('tr');
                    row.dataset.status = record.status.toLowerCase(); 
                    row.innerHTML = `
                        <td data-label="Appointment ID">${record.id}</td>
                        <td data-label="Doctor Image"><img src="../${record.profile_pic ?? 'assets/images/default-avatar.png'}" alt="Dr. ${record.doctor_name}" class="rounded-circle"></td>
                        <td data-label="Doctor Name">${record.doctor_name}</td>
                        <td data-label="Specialization">${record.specialization}</td>
                        <td data-label="Date & Time">${record.appointment_date.replace('T', ' ')}</td>
                        <td data-label="Reason">${record.reason}</td>
                        <td data-label="Status"><span class="badge bg-${record.status.toLowerCase()}">${record.status}</span></td>
                    `;
                    tableBody.appendChild(row);
                });

                filterMedicalHistory();
            }

            function filterMedicalHistory() {
                const selectedStatus = statusFilter.value;
                const rows = document.querySelectorAll('#mainContent tbody tr');

                rows.forEach(row => {
                    const rowStatus = row.dataset.status;
                    if (selectedStatus === 'all' || rowStatus === selectedStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            const statusFilter = document.getElementById('statusFilter');
            statusFilter.addEventListener('change', filterMedicalHistory);

            fetchMedicalHistory();
            setInterval(fetchMedicalHistory, 5000);
        });
    </script>
</body>
</html>
