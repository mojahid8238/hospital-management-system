<?php
date_default_timezone_set('UTC');
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_patient();

$patient_id = null;
$patient_name = '';

// Get the patient_id and name associated with the logged-in user
$stmt = $conn->prepare("SELECT id, name FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $patient_id = $row['id'];
    $patient_name = $row['name'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
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
                <li><a href="dashboard.php">Your Appointments & History</a></li>
                <li><a href="cancelled-appointments.php">Cancelled Appointments</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container panel-card">
                <h2 class="card-title mb-3">Welcome, <?php echo htmlspecialchars($patient_name); ?>!</h2>

                <!-- Upcoming Appointments Section -->
                <div class="appointments-section mb-5">
                    <h3 class="mb-3">Your Upcoming Appointments</h3>
                    <div class="search-filter-container">
                        <div class="search-bar">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchUpcoming" placeholder="Search Doctor by name...">
                        </div>
                        <div class="filter-bar">
                            <i class="fas fa-filter"></i>
                            <select id="statusFilterUpcoming" class="form-select w-auto">
                                <option value="all">All</option>
                                <option value="pending">Pending</option>
                                <option value="scheduled">Confirmed</option>
                            </select>
                        </div>
                    </div>
                    <ul class="doctor-list" id="upcomingAppointmentList">
                        <!-- Appointments will be loaded here by JavaScript -->
                    </ul>
                </div>

                <!-- Medical History Section -->
                <div class="medical-history-section">
                    <h3 class="mb-3">Your Medical History</h3>
                    <div class="search-filter-container">
                        <div class="search-bar">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchHistory" placeholder="Search Doctor by name...">
                        </div>
                        <div class="filter-bar">
                            <i class="fas fa-filter"></i>
                            <select id="statusFilterHistory" class="form-select w-auto">
                                <option value="all">All</option>
                                <option value="completed">Completed</option>
                                <option value="online">Online</option>
                                <option value="offline">Offline</option>
                            </select>
                        </div>
                    </div>
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
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody id="medicalHistoryTableBody">
                                <!-- Medical history will be loaded here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
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
            <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
            <hr>
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
            // --- Sidebar and Profile Overlay Logic (from dashboard.php) ---
            const sidebarToggle = document.getElementById('sidebarToggle');
            const patientSidebar = document.getElementById('patientSidebar');
            const mainContent = document.getElementById('mainContent');

            sidebarToggle.addEventListener('click', () => {
                patientSidebar.classList.toggle('closed');
            });

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

            // --- Combined Appointments and Medical History Logic ---
            let allAppointments = []; // Store all fetched appointments

            const searchUpcoming = document.getElementById('searchUpcoming');
            const statusFilterUpcoming = document.getElementById('statusFilterUpcoming');
            const searchHistory = document.getElementById('searchHistory');
            const statusFilterHistory = document.getElementById('statusFilterHistory');

            searchUpcoming.addEventListener('input', filterAndRenderAll);
            statusFilterUpcoming.addEventListener('change', filterAndRenderAll);
            searchHistory.addEventListener('input', filterAndRenderAll);
            statusFilterHistory.addEventListener('change', filterAndRenderAll);

            function fetchAllAppointments() {
                const url = `get_patient_appointments.php?t=${new Date().getTime()}`;
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache',
                        'Expires': '0'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    const newAppointmentsJson = JSON.stringify(data);
                    const currentAppointmentsJson = JSON.stringify(allAppointments);

                    if (newAppointmentsJson !== currentAppointmentsJson) {
                        allAppointments = data;
                        filterAndRenderAll(); // Re-render both sections if data changes
                    }
                })
                .catch(error => console.error('Error fetching all appointments:', error));
            }

            function filterAndRenderAll() {
                const now = new Date();
                const upcomingAppointments = [];
                const medicalHistory = [];

                allAppointments.forEach(appointment => {
                    const appointmentDate = new Date(appointment.appointment_date.replace(' ', 'T') + 'Z');
                    if (appointmentDate > now && (appointment.status === 'Pending' || appointment.status === 'Scheduled')) {
                        upcomingAppointments.push(appointment);
                    } else {
                        medicalHistory.push(appointment);
                    }
                });

                renderUpcomingAppointments(upcomingAppointments);
                renderMedicalHistory(medicalHistory);
            }

            function renderUpcomingAppointments(appointments) {
                const appointmentList = document.getElementById('upcomingAppointmentList');
                appointmentList.innerHTML = '';

                const searchTerm = searchUpcoming.value.toLowerCase();
                const selectedStatus = statusFilterUpcoming.value;

                const filteredAppointments = appointments.filter(appointment => {
                    const doctorName = appointment.doctor_name.toLowerCase();
                    const status = appointment.status.toLowerCase();
                    const nameMatch = doctorName.includes(searchTerm);
                    const statusMatch = selectedStatus === 'all' || status === selectedStatus;
                    return nameMatch && statusMatch;
                });

                if (filteredAppointments.length === 0) {
                    appointmentList.innerHTML = '<div class="alert alert-info mt-4">You have no upcoming appointments scheduled.</div>';
                    return;
                }

                filteredAppointments.forEach(appointment => {
                    const listItem = document.createElement('li');
                    listItem.className = 'doctor-item';
                    listItem.id = `appointment-${appointment.id}`;
                    listItem.dataset.doctorName = appointment.doctor_name.toLowerCase();
                    listItem.dataset.status = appointment.status.toLowerCase();
                    listItem.dataset.appointmentTime = appointment.appointment_date;

                    listItem.innerHTML = `
                        <div class="doctor-avatar">
                            <img src="/hospital-management-system/${appointment.profile_pic || 'assets/images/default-avatar.png'}"
                                alt="Dr. ${appointment.doctor_name}" class="rounded-circle">
                        </div>
                        <div class="doctor-info">
                            <h4>Dr. ${appointment.doctor_name}</h4>
                            <p>Appointment: ${new Date(appointment.appointment_date.replace(' ', 'T') + 'Z').toLocaleString()}</p>
                            <p>Remaining: <span class="remaining-time">Calculating...</span></p>
                        </div>
                        <div class="doctor-info">
                            <p>Reason: ${appointment.reason}</p>
                            <p>Status: <span class="badge bg-${appointment.status.toLowerCase()}">${appointment.status}</span></p>
                            <p>Type: ${appointment.type}</p>
                        </div>
                        <div class="doctor-info">
                            ${(appointment.status === 'Pending' || appointment.status === 'Confirmed') ?
                                `<button class="btn btn-sm btn-outline-danger cancel-appointment-btn" data-appointment-id="${appointment.id}">Cancel</button>` : ''}
                        </div>
                    `;
                    appointmentList.appendChild(listItem);
                });
                updateAllTimers();
            }

            function renderMedicalHistory(history) {
                const tableBody = document.getElementById('medicalHistoryTableBody');
                tableBody.innerHTML = '';

                const searchTerm = searchHistory.value.toLowerCase();
                const selectedStatus = statusFilterHistory.value;

                const filteredHistory = history.filter(record => {
                    const doctorName = record.doctor_name.toLowerCase();
                    const status = record.status.toLowerCase();
                    const type = record.type.toLowerCase();
                    const nameMatch = doctorName.includes(searchTerm);
                    const statusMatch = selectedStatus === 'all' || status === selectedStatus;
                    const typeMatch = selectedStatus === 'online' && type === 'online' || selectedStatus === 'offline' && type === 'offline';
                    
                    if (selectedStatus === 'all' || selectedStatus === 'completed') {
                        return nameMatch && statusMatch;
                    } else { // For 'online' or 'offline' filters
                        return nameMatch && typeMatch;
                    }
                });

                if (filteredHistory.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="8">You have no medical history records.</td></tr>';
                    return;
                }

                filteredHistory.forEach(record => {
                    const row = document.createElement('tr');
                    row.dataset.status = record.status.toLowerCase();
                    row.dataset.type = record.type.toLowerCase();
                    row.innerHTML = `
                        <td data-label="Appointment ID">${record.id}</td>
                        <td data-label="Doctor Image"><img src="/hospital-management-system/${record.profile_pic || 'assets/images/default-avatar.png'}" alt="Dr. ${record.doctor_name}" class="rounded-circle"></td>
                        <td data-label="Doctor Name">${record.doctor_name}</td>
                        <td data-label="Specialization">${record.specialization}</td>
                        <td data-label="Date & Time">${new Date(record.appointment_date.replace(' ', 'T') + 'Z').toLocaleString()}</td>
                        <td data-label="Reason">${record.reason}</td>
                        <td data-label="Status"><span class="badge bg-${record.status.toLowerCase()}">${record.status}</span></td>
                        <td data-label="Type">${record.type}</td>
                    `;
                    tableBody.appendChild(row);
                });
            }

            function updateAllTimers() {
                document.querySelectorAll('.remaining-time').forEach(timerEl => {
                    const listItem = timerEl.closest('.doctor-item');
                    if (!listItem) return;

                    const appointmentTime = new Date(listItem.dataset.appointmentTime.replace(' ', 'T') + 'Z');
                    const interval = appointmentTime.getTime() - Date.now();

                    if (interval < 0) {
                        timerEl.textContent = 'Appointment has passed.';
                        timerEl.style.color = '#dc3545';
                        return;
                    }

                    const days = Math.floor(interval / 86400000);
                    const hours = Math.floor((interval % 86400000) / 3600000);
                    const minutes = Math.floor((interval % 3600000) / 60000);
                    const seconds = Math.floor((interval % 60000) / 1000);

                    timerEl.textContent = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                });
            }

            function handleCancelButtonClick(event) {
                const button = event.target.closest('.cancel-appointment-btn');
                if (!button) return;

                const appointmentId = button.dataset.appointmentId;
                if (confirm('Are you sure you want to cancel this appointment?')) {
                    button.disabled = true;
                    button.textContent = 'Cancelling...';

                    fetch('cancel_appointment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ appointment_id: appointmentId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Refresh all appointments to reflect the status change
                            fetchAllAppointments();
                        } else {
                            alert('Error: ' + data.message);
                            button.disabled = false;
                            button.textContent = 'Cancel';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                        button.disabled = false;
                        button.textContent = 'Cancel';
                    });
                }
            }

            document.getElementById('mainContent').addEventListener('click', handleCancelButtonClick);

            // Initial load and periodic updates
            fetchAllAppointments();
            setInterval(fetchAllAppointments, 5000);
            setInterval(updateAllTimers, 1000);
        });
    </script>
</body>
</html>