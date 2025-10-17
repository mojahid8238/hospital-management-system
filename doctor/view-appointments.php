<?php
date_default_timezone_set('UTC');
// IMPORTANT: You must ensure these files exist and contain the necessary setup
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_doctor(); // Assumes this function checks for session and redirects if not a doctor

$doctor_id = null;
$doctor_name = 'Doctor User'; // Default value
$doctor_profile_pic = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';

// --- START: Added PHP Logic to initialize Doctor's name and profile pic ---
// Get the doctor_id and name associated with the logged-in user
// This fixes the issue where $doctor_name and the header profile picture path were not correctly set.
if (isset($_SESSION['user_id'])) {
    // Assuming $conn is available from db.php
    $stmt = $conn->prepare("SELECT id, name, profile_pic FROM doctors WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $doctor_id = $row['id'];
        $doctor_name = $row['name'];
        // Ensure the session variable for the header is updated with the latest pic path
        $_SESSION['profile_pic'] = $row['profile_pic'] ?? 'assets/images/default-avatar.png';
        $doctor_profile_pic = $_SESSION['profile_pic'];
    }
    $stmt->close();
}
// --- END: Added PHP Logic ---
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
            <img src="/hospital-management-system/<?php echo htmlspecialchars($doctor_profile_pic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="doctorSidebar">
            <h3>Doctor Options</h3>
            <ul>
                <li><a href="view-appointments.php">View Your Appointments</a></li>
                <!-- Re-adding the Cancelled Appointments link as it's typically useful -->
                <li><a href="cancelled-appointments.php">Cancelled Appointments</a></li> 
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container panel-card">
                <h2 class="card-title mb-3">Manage Your Appointments</h2>
                <p class="text-muted">Welcome, <strong>Dr. <?php echo htmlspecialchars($doctor_name); ?></strong>! Here you can manage your scheduled appointments:</p>

                <div class="search-filter-container">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchPatient" placeholder="Search Patient by name...">
                    </div>
                    
                    <div class="filter-bar">
                        <i class="fas fa-filter"></i>
                        <select id="statusFilter" class="form-select w-auto">
                            <option value="all">All</option>
                            <option value="pending">Pending</option>
                            <option value="scheduled">Confirmed</option>
                        </select>
                        <select id="typeFilter" class="form-select w-auto">
                            <option value="all">All Types</option>
                            <option value="Online">Online</option>
                            <option value="Offline">Offline</option>
                        </select>
                    </div>
                </div>

                <!-- Requested Appointments Section (ID added for easy JS targeting) -->
                <!-- The original element with 'mb-5' corresponds to the Pending section -->
                <div class="appointments-section mb-5" id="requestedAppointmentsContainer">
                    <h3 class="mb-3">Requested Appointments</h3>
                    <ul class="doctor-list" id="requestedAppointmentList">
                        <!-- Pending appointments will be loaded here by JavaScript -->
                    </ul>
                </div>

                <!-- Accepted Appointments Section (ID added for easy JS targeting) -->
                <!-- The original element without 'mb-5' corresponds to the Accepted/Confirmed section -->
                <div class="appointments-section" id="acceptedAppointmentsContainer">
                    <h3 class="mb-3">Accepted Appointments</h3>
                    <ul class="doctor-list" id="acceptedAppointmentList">
                        <!-- Accepted appointments will be loaded here by JavaScript -->
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <!-- Profile side overlay -->
    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <img src="/hospital-management-system/<?php echo htmlspecialchars($doctor_profile_pic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay" style="position: relative; z-index: 10; cursor: pointer;">
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
                <button type="submit" style="display: none;">Upload</button>
            </form>
            <div id="uploadMessage" style="margin-top: 10px; color: green;"></div>
            <!-- Display the initialized doctor name here -->
            <h3><?php echo htmlspecialchars($doctor_name); ?></h3>
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
            // --- Sidebar and Profile Overlay Logic ---
            const sidebarToggle = document.getElementById('sidebarToggle');
            const doctorSidebar = document.getElementById('doctorSidebar');
            const profileToggle = document.getElementById('profileToggle');
            const profileOverlay = document.getElementById('profileOverlay');
            const profileImageDisplay = document.getElementById('profileImageDisplay');
            const profilePicInput = document.getElementById('profilePicInput');
            const profilePicUploadForm = document.getElementById('profilePicUploadForm');
            const uploadMessage = document.getElementById('uploadMessage');
            const mainContent = document.getElementById('mainContent');

            sidebarToggle.addEventListener('click', () => doctorSidebar.classList.toggle('closed'));

            profileToggle.addEventListener('click', (event) => {
                event.stopPropagation();
                profileOverlay.classList.add('open');
            });

            // Close overlay when clicking outside
            document.addEventListener('click', (event) => {
                if (!profileToggle.contains(event.target) && !profileOverlay.contains(event.target)) {
                    profileOverlay.classList.remove('open');
                }
            });
            
            // Allow clicking the profile picture to trigger file upload
            profileImageDisplay.addEventListener('click', function() {
                profilePicInput.click();
            });

            // Profile Picture Upload Logic
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
                            // Ensure the image path is correctly prepended with the root
                            const newImagePath = '/hospital-management-system/' + (data.profile_pic_path || 'assets/images/default-avatar.png') + '?t=' + new Date().getTime();
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


            // --- Appointment Management Logic ---
            
            const statusFilter = document.getElementById('statusFilter');
            const typeFilter = document.getElementById('typeFilter');
            const searchPatient = document.getElementById('searchPatient');
            
            // NEW: Get container references
            const requestedAppointmentsContainer = document.getElementById('requestedAppointmentsContainer');
            const acceptedAppointmentsContainer = document.getElementById('acceptedAppointmentsContainer');


            // Event listeners for changes in filtering
            statusFilter.addEventListener('change', fetchAppointments);
            typeFilter.addEventListener('change', fetchAppointments);
            searchPatient.addEventListener('input', applyClientSideFilter);


            let currentAppointments = [];

            // Fetches latest appointments and updates the list if changed
            function fetchAppointments() {
                // NEW: Toggle section visibility based on filter BEFORE fetching
                const selectedStatusFilter = statusFilter.value;
                
                if (selectedStatusFilter === 'pending') {
                    // Show Pending container, hide Accepted container
                    requestedAppointmentsContainer.style.display = 'block';
                    acceptedAppointmentsContainer.style.display = 'none';
                } else if (selectedStatusFilter === 'scheduled') {
                    // Hide Pending container, show Accepted container
                    requestedAppointmentsContainer.style.display = 'none';
                    acceptedAppointmentsContainer.style.display = 'block';
                } else {
                    // 'all' or any other value: Show both
                    requestedAppointmentsContainer.style.display = 'block';
                    acceptedAppointmentsContainer.style.display = 'block';
                }
                
                const selectedSortBy = 'appointment_date'; 
                const selectedOrderBy = 'DESC';

                const selectedTypeFilter = typeFilter.value;

                // IMPORTANT: The PHP script 'get_appointments.php' MUST join tables to fetch 
                // the patient's profile_pic and return it as 'patient_profile_pic' in the JSON response
                const url = `get_appointments.php?sort_by=${selectedSortBy}&order=${selectedOrderBy}&status=${selectedStatusFilter}&type=${selectedTypeFilter}&t=${new Date().getTime()}`;

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
                        currentAppointments = data;
                        renderAppointments(data);
                        // Apply client-side filter after rendering the fetched data
                        applyClientSideFilter();
                    })
                    .catch(error => console.error('Error fetching appointments:', error));
            }
            
            // Renders the list of appointments into two sections
            function renderAppointments(appointments) {
                const requestedAppointmentList = document.getElementById('requestedAppointmentList');
                const acceptedAppointmentList = document.getElementById('acceptedAppointmentList');

                // Clear previous content
                requestedAppointmentList.innerHTML = '';
                acceptedAppointmentList.innerHTML = '';

                let requestedCount = 0;
                let acceptedCount = 0;

                appointments.forEach(appointment => {
                    const patientName = appointment.patient_name.toLowerCase();
                    const status = appointment.status.toLowerCase();
                    const appointmentType = appointment.type.toLowerCase();

                    const listItem = document.createElement('li');
                    listItem.className = 'doctor-item';
                    listItem.id = `appointment-${appointment.id}`;
                    listItem.dataset.name = patientName;
                    listItem.dataset.status = status;
                    listItem.dataset.appointmentTime = appointment.appointment_date;
                    listItem.dataset.appointmentType = appointmentType;

                    let buttonsHtml = '';
                    // The confirmed status is checked as 'Scheduled', aligning with the filter value 'scheduled'
                    if (appointment.status === 'Pending') {
                        buttonsHtml = `
                            <button class="btn btn-sm btn-outline-primary accept-appointment-btn" data-appointment-id="${appointment.id}" data-appointment-type="${appointment.type}">Accept</button>
                            <a href="#" class="btn btn-sm btn-outline-success">Message</a>
                            <button class="btn btn-sm btn-outline-danger cancel-appointment-btn" data-appointment-id="${appointment.id}">Cancel</button>
                        `;
                        requestedCount++;
                    } else if (appointment.status === 'Scheduled' || appointment.status === 'Online' || appointment.status === 'Offline') {
                        // Assuming 'Scheduled' is the status after acceptance, before completion
                        // Or using 'Online'/'Offline' if they are used as confirmed statuses
                         buttonsHtml = `
                            <button class="btn btn-sm btn-outline-info attend-appointment-btn" data-appointment-id="${appointment.id}" data-appointment-type="${appointment.type}">Attend/Complete</button>
                            <a href="#" class="btn btn-sm btn-outline-success">Message</a>
                            <button class="btn btn-sm btn-outline-warning cancel-appointment-btn" data-appointment-id="${appointment.id}">Reschedule/Cancel</button>
                        `;
                        acceptedCount++;
                    }

                    // Determine badge color
                    let badgeClass;
                    if (status === 'pending') {
                        badgeClass = 'warning';
                    } else if (status === 'scheduled' || status === 'online' || status === 'offline') {
                        badgeClass = 'primary';
                    } else if (status === 'completed') {
                        badgeClass = 'success';
                    } else if (status === 'cancelled') {
                        badgeClass = 'danger';
                    } else {
                        badgeClass = 'secondary';
                    }

                    // --- Fix for Profile Picture Loading: Ensure correct path structure ---
                    // Using || for a robust default in case patient_profile_pic is missing or null
                    const profilePicPath = appointment.patient_profile_pic || 'assets/images/default-avatar.png';
                    
                    listItem.innerHTML = `
                        <div class="doctor-avatar">
                            <img src="/hospital-management-system/${profilePicPath}"
                                alt="${appointment.patient_name}" 
                                class="rounded-circle">
                        </div>
                        <div class="doctor-info">
                            <h4>${appointment.patient_name}</h4>
                            <p>Appointment: ${new Date(appointment.appointment_date.replace(' ', 'T') + 'Z').toLocaleString()}</p>
                            <p>Remaining: <span class="remaining-time">Calculating...</span></p>
                        </div>
                        <div class="doctor-info">
                            <p>Reason: ${appointment.reason}</p>
                            <p>Status: <span class="badge bg-${badgeClass}">${appointment.status}</span></p>
                            <p>Type: ${appointment.type}</p>
                        </div>
                        <div class="doctor-info button-group">
                            ${buttonsHtml}
                        </div>
                    `;

                    if (appointment.status === 'Pending') {
                        requestedAppointmentList.appendChild(listItem);
                    } else if (appointment.status === 'Scheduled' || appointment.status === 'Online' || appointment.status === 'Offline') {
                        acceptedAppointmentList.appendChild(listItem);
                    }
                });

                // --- Functionality Fix: Display alerts only if sections are truly empty after filtering/rendering ---
                if (requestedCount === 0) {
                    // Check if the list is empty after the loop
                    const existingAlert = requestedAppointmentList.querySelector('.alert-info');
                    if (!existingAlert) {
                        requestedAppointmentList.innerHTML = '<div class="alert alert-info mt-4">No pending appointments found.</div>';
                    }
                }

                if (acceptedCount === 0) {
                    // Check if the list is empty after the loop
                    const existingAlert = acceptedAppointmentList.querySelector('.alert-info');
                    if (!existingAlert) {
                        acceptedAppointmentList.innerHTML = '<div class="alert alert-info mt-4">No confirmed appointments found.</div>';
                    }
                }
                
                updateAllTimers();
            }

            // Client-side filtering (search bar)
            function applyClientSideFilter() {
                const searchTerm = searchPatient.value.toLowerCase();
                
                // Only filter items that are currently visible based on the filter dropdowns (i.e., items in the lists)
                const allItems = document.querySelectorAll('#requestedAppointmentList .doctor-item, #acceptedAppointmentList .doctor-item');
                
                allItems.forEach(item => {
                    const name = item.dataset.name;
                    // Check if the item should be visible based on the search term
                    if (name.includes(searchTerm)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Re-evaluate empty messages after client-side filter (Optional but robust)
                updateEmptySectionMessages();
            }
            
            function updateEmptySectionMessages() {
                const requestedList = document.getElementById('requestedAppointmentList');
                const acceptedList = document.getElementById('acceptedAppointmentList');
                
                // Only check for visible items if the parent container is also visible
                const requestedContainerVisible = requestedAppointmentsContainer.style.display !== 'none';
                const acceptedContainerVisible = acceptedAppointmentsContainer.style.display !== 'none';
                
                const visibleRequestedItems = requestedList && requestedContainerVisible ? Array.from(requestedList.querySelectorAll('.doctor-item')).filter(item => item.style.display !== 'none').length : 0;
                const visibleAcceptedItems = acceptedList && acceptedContainerVisible ? Array.from(acceptedList.querySelectorAll('.doctor-item')).filter(item => item.style.display !== 'none').length : 0;

                // Remove previous alerts
                requestedList.querySelectorAll('.alert-info').forEach(alert => alert.remove());
                acceptedList.querySelectorAll('.alert-info').forEach(alert => alert.remove());

                // Add alerts if no items are visible AND the container is visible
                if (visibleRequestedItems === 0 && requestedContainerVisible) {
                    requestedList.innerHTML += '<div class="alert alert-info mt-4">No pending appointments match your current search.</div>';
                }
                if (visibleAcceptedItems === 0 && acceptedContainerVisible) {
                    acceptedList.innerHTML += '<div class="alert alert-info mt-4">No confirmed appointments match your current search.</div>';
                }
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

            // Handles the cancel/reschedule button click
            function handleCancelButtonClick(event) {
                const button = event.target.closest('.cancel-appointment-btn');
                if (!button) return;

                const appointmentId = button.dataset.appointmentId;
                // NOTE: Replacing window.confirm with custom modal
                if (window.confirm('Are you sure you want to cancel/reschedule this appointment?')) {
                    button.disabled = true;
                    button.textContent = 'Processing...';

                    fetch('cancel_appointment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ appointment_id: appointmentId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Refresh the appointments list to reflect the status change
                            fetchAppointments(); 
                        } else {
                            console.error('Error:', data.message);
                            button.disabled = false;
                            button.textContent = 'Cancel/Reschedule';
                            window.alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.alert('An error occurred. Please try again.');
                        button.disabled = false;
                        button.textContent = 'Cancel/Reschedule';
                    });
                }
            }

            // Handles the accept button click
            function handleAcceptButtonClick(event) {
                const button = event.target.closest('.accept-appointment-btn');
                if (!button) return;

                const appointmentId = button.dataset.appointmentId;
                const appointmentType = button.dataset.appointmentType;

                if (window.confirm(`Are you sure you want to accept this ${appointmentType} appointment?`)) {
                    button.disabled = true;
                    button.textContent = 'Accepting...';

                    fetch('accept_appointment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ appointment_id: appointmentId, appointment_type: appointmentType })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Refresh the appointments list to reflect the status change
                            fetchAppointments();
                        } else {
                            console.error('Error:', data.message);
                            button.disabled = false;
                            button.textContent = 'Accept';
                            window.alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.alert('An error occurred. Please try again.');
                        button.disabled = false;
                        button.textContent = 'Accept';
                    });
                }
            }

            // Handles the attend/complete button click
            function handleAttendButtonClick(event) {
                const button = event.target.closest('.attend-appointment-btn');
                if (!button) return;

                const appointmentId = button.dataset.appointmentId;
                const appointmentType = button.dataset.appointmentType;

                if (window.confirm(`Are you sure you want to mark this ${appointmentType} appointment as completed?`)) {
                    button.disabled = true;
                    button.textContent = 'Completing...';

                    fetch('complete_appointment.php', { // New PHP script for completing appointments
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ appointment_id: appointmentId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            fetchAppointments(); // Refresh the appointments list
                        } else {
                            console.error('Error:', data.message);
                            button.disabled = false;
                            button.textContent = 'Attend/Complete';
                            window.alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.alert('An error occurred. Please try again.');
                        button.disabled = false;
                        button.textContent = 'Attend/Complete';
                    });
                }
            }

            // Use event delegation for buttons
            mainContent.addEventListener('click', handleCancelButtonClick);
            mainContent.addEventListener('click', handleAcceptButtonClick);
            mainContent.addEventListener('click', handleAttendButtonClick);

            // --- Initializations ---
            // Set up recurring tasks
            setInterval(fetchAppointments, 3000);
            setInterval(updateAllTimers, 1000);
            
            // Initial load
            fetchAppointments();
        });
    </script>
</body>
</html>
