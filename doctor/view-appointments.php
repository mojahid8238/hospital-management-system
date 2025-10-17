<?php
date_default_timezone_set('UTC');
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_doctor();

$doctor_id = null;
$doctor_name = '';

// Get the doctor_id and name associated with the logged-in user
$stmt = $conn->prepare("SELECT id, name FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $doctor_id = $row['id'];
    $doctor_name = $row['name'];
}
$stmt->close();

$appointments = [];
if ($doctor_id) {
    $stmt = $conn->prepare("SELECT a.id, p.name as patient_name, p.profile_pic as patient_profile_pic, a.appointment_date, a.reason, a.status FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.doctor_id = ? AND a.status != 'Cancelled' ORDER BY a.appointment_date ASC");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointments</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/shared-table.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">☰ Toggle Menu</button>
            <a href="#">Doctor Panel</a>
        </div>
        <div class="nav-right">
            <img src="/hospital-management-system/<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="doctorSidebar">
            <h3>Doctor Options</h3>
            <ul>
                <li><a href="view-appointments.php">View Your Appointments</a></li>
                <li><a href="cancelled-appointments.php">Cancelled Appointments</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container panel-card">
                <h2 class="card-title mb-3">Your Appointments</h2>
                <p class="text-muted">Welcome, <strong>Dr. <?php echo htmlspecialchars($doctor_name); ?></strong>! Here are your scheduled appointments:</p>

                <div class="search-filter-container">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchPatient" placeholder="Search Patient by name...">
                    </div>
                    <div class="filter-bar">
                        <i class="fas fa-filter"></i>
                        <select id="statusFilter" class="form-select w-auto">
                            <option value="all">All</option>
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                        </select>
                    </div>
                </div>

                <ul class="doctor-list" id="appointmentList">
                    <?php if (empty($appointments)): ?>
                        <div class="alert alert-info mt-4">You have no upcoming appointments scheduled.</div>
                    <?php else: ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <li class="doctor-item" 
                                data-name="<?php echo strtolower(htmlspecialchars($appointment['patient_name'])); ?>" 
                                data-status="<?php echo strtolower(htmlspecialchars($appointment['status'])); ?>"
                                data-appointment-time="<?php echo htmlspecialchars($appointment['appointment_date']); ?>"
                                id="appointment-<?php echo $appointment['id']; ?>">
                                <div class="doctor-avatar">
                                    <img src="/hospital-management-system/<?php echo htmlspecialchars($appointment['patient_profile_pic'] ?? 'assets/images/default-avatar.png'); ?>" 
                                        alt="<?php echo htmlspecialchars($appointment['patient_name']); ?>" 
                                        class="rounded-circle">
                                </div>
                                <div class="doctor-info">
                                    <h4><?php echo htmlspecialchars($appointment['patient_name']); ?></h4>
                                    <p>Appointment: <?php echo date('Y-m-d H:i', strtotime($appointment['appointment_date'])); ?></p>
                                    <p>Remaining: <span class="remaining-time">Calculating...</span></p>
                                </div>
                                <div class="doctor-info">
                                    <p>Reason: <?php echo htmlspecialchars($appointment['reason']); ?></p>
                                    <p>Status: <span class="badge bg-<?php echo strtolower(htmlspecialchars($appointment['status'])); ?>"><?php echo htmlspecialchars(ucfirst($appointment['status'])); ?></span></p>
                                </div>
                                <div class="doctor-info">
                                    <a href="#" class="btn btn-sm btn-outline-primary">Attend</a>
                                    <a href="#" class="btn btn-sm btn-outline-success">Message</a>
                                    <button class="btn btn-sm btn-outline-danger cancel-appointment-btn" data-appointment-id="<?php echo $appointment['id']; ?>">Cancel</button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
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
            <p>Role: Doctor</p>
            <hr>
            <h4>Dashboards</h4>
            <ul>
                <li><a href="dashboard.php">Doctor Dashboard</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Sidebar and Profile Overlay Logic (No Changes) ---
            const sidebarToggle = document.getElementById('sidebarToggle');
            const doctorSidebar = document.getElementById('doctorSidebar');
            sidebarToggle.addEventListener('click', () => doctorSidebar.classList.toggle('closed'));

            const profileToggle = document.getElementById('profileToggle');
            const profileOverlay = document.getElementById('profileOverlay');
            profileToggle.addEventListener('click', (event) => {
                event.stopPropagation();
                profileOverlay.classList.add('open');
            });
            document.addEventListener('click', (event) => {
                if (event.target === profileOverlay || event.target.closest('.content-area')) {
                    profileOverlay.classList.remove('open');
                }
            });

            // --- Search and Filter Logic (No Changes) ---
            const searchPatient = document.getElementById('searchPatient');
            const statusFilter = document.getElementById('statusFilter');
            searchPatient.addEventListener('input', filterAppointments);
            statusFilter.addEventListener('change', filterAppointments);

            function filterAppointments() {
                const searchTerm = searchPatient.value.toLowerCase();
                const selectedStatus = statusFilter.value;
                const appointmentItems = document.querySelectorAll('#appointmentList .doctor-item');

                appointmentItems.forEach(item => {
                    const name = item.dataset.name || '';
                    const status = item.dataset.status || '';
                    const nameMatch = name.includes(searchTerm);
                    const statusMatch = selectedStatus === 'all' || status === selectedStatus;
                    item.style.display = (nameMatch && statusMatch) ? 'flex' : 'none';
                });
            }
            
            let currentAppointments = [];

            // Fetches latest appointments and updates the list if changed
            function fetchAppointments() {
                const url = `get_appointments.php?t=${new Date().getTime()}`;

                // --- THIS IS THE KEY CHANGE ---
                // Add headers to prevent caching on both browser and server
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
                        const newIds = data.map(a => a.id).sort().join(',');
                        const currentIds = currentAppointments.map(a => a.id).sort().join(',');

                        if (newIds !== currentIds) {
                            currentAppointments = data;
                            renderAppointments(data);
                        }
                    })
                    .catch(error => console.error('Error fetching appointments:', error));
            }
            
            // Renders the list of appointments
            function renderAppointments(appointments) {
                const appointmentList = document.getElementById('appointmentList');
                appointmentList.innerHTML = ''; 

                if (!appointments || appointments.length === 0) {
                    appointmentList.innerHTML = '<div class="alert alert-info mt-4">You have no upcoming appointments scheduled.</div>';
                    return;
                }

                appointments.forEach(appointment => {
                    const listItem = document.createElement('li');
                    listItem.className = 'doctor-item';
                    listItem.id = `appointment-${appointment.id}`;
                    listItem.dataset.name = appointment.patient_name.toLowerCase();
                    listItem.dataset.status = appointment.status.toLowerCase();
                    listItem.dataset.appointmentTime = appointment.appointment_date;

                    listItem.innerHTML = `
                        <div class="doctor-avatar">
                            <img src="/hospital-management-system/${appointment.patient_profile_pic || 'assets/images/default-avatar.png'}" 
                                alt="${appointment.patient_name}" class="rounded-circle">
                        </div>
                        <div class="doctor-info">
                            <h4>${appointment.patient_name}</h4>
                            <p>Appointment: ${new Date(appointment.appointment_date.replace(' ', 'T') + 'Z').toLocaleString()}</p>
                            <p>Remaining: <span class="remaining-time">Calculating...</span></p>
                        </div>
                        <div class="doctor-info">
                            <p>Reason: ${appointment.reason}</p>
                            <p>Status: <span class="badge bg-${appointment.status.toLowerCase()}">${appointment.status}</span></p>
                        </div>
                        <div class="doctor-info">
                            <a href="#" class="btn btn-sm btn-outline-primary">Attend</a>
                            <a href="#" class="btn btn-sm btn-outline-success">Message</a>
                            <button class="btn btn-sm btn-outline-danger cancel-appointment-btn" data-appointment-id="${appointment.id}">Cancel</button>
                        </div>
                    `;
                    appointmentList.appendChild(listItem);
                });
                filterAppointments();
            }
            
            // Updates all countdown timers every second
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

            // Handles the cancel button click
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
                            const listItem = document.getElementById(`appointment-${appointmentId}`);
                            if (listItem) {
                                listItem.style.transition = 'opacity 0.5s ease';
                                listItem.style.opacity = '0';
                                setTimeout(() => {
                                    listItem.remove();
                                    currentAppointments = currentAppointments.filter(app => app.id != appointmentId);
                                    if (currentAppointments.length === 0) {
                                        renderAppointments([]);
                                    }
                                }, 500);
                            }
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

            // Use event delegation for cancel buttons
            document.getElementById('mainContent').addEventListener('click', handleCancelButtonClick);

            // --- Initializations ---
            setInterval(fetchAppointments, 3000);
            setInterval(updateAllTimers, 1000);
            fetchAppointments();
        });
    </script>
</body>
</html>

