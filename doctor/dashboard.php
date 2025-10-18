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
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/shared-table.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/view-appointments.css">
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
                <li><a href="dashboard.php">View Your Appointments</a></li>
                <!-- Re-adding the Cancelled Appointments link as it's typically useful -->
                <li><a href="cancelled-appointments.php">Cancelled Appointments</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container panel-card">
                <h2 class="card-title mb-3">Welcome, <strong>Dr. <?php echo htmlspecialchars($doctor_name); ?></strong>!</h2>
                <p class="text-muted">Here you can manage your scheduled appointments:</p>

                <!-- The Status Filter Section has been removed as appointments are now separated by status below -->

                <div class="search-filter-container mb-4">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchPatient" placeholder="Search Patient by name...">
                    </div>

                    <div class="filter-bar">
                        <i class="fas fa-filter"></i>
                        <!-- Only typeFilter remains here -->
                        <select id="typeFilter" class="form-select w-auto">
                            <option value="all">All Types</option>
                            <option value="Online">Online</option>
                            <option value="Offline">Offline</option>
                        </select>
                    </div>
                </div>

                <!-- Appointments Section -->
                <div class="appointments-section mb-5" id="appointmentsContainer">
                    <h3 class="mb-4">Appointment Overview</h3>

                    <!-- PENDING APPOINTMENTS SECTION -->
                    <div class="card mb-5 p-4 border-l-8 border-yellow-500 shadow-xl" id="pendingAppointmentsCard">
                        <h4 class="card-title text-yellow-600 mb-4"><i class="fas fa-clock mr-2"></i> **Pending Appointments** (Requires Action)</h4>
                        <ul class="doctor-list" id="pendingAppointmentList">
                            <!-- Pending appointments will be loaded here by JavaScript -->
                        </ul>
                    </div>

                    <!-- CONFIRMED/SCHEDULED APPOINTMENTS SECTION -->
                    <div class="card p-4 border-l-8 border-blue-500 shadow-xl" id="confirmedAppointmentsCard">
                        <h4 class="card-title text-blue-600 mb-4"><i class="fas fa-calendar-check mr-2"></i> **Confirmed/Scheduled Appointments**</h4>
                        <ul class="doctor-list" id="confirmedAppointmentList">
                            <!-- Confirmed/Scheduled appointments will be loaded here by JavaScript -->
                        </ul>
                    </div>
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
            <hr>
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

            // Status Filter is now handled by the UI sections and is removed from the controls.
            // const statusFilter = document.getElementById('statusFilter'); // REMOVED
            const typeFilter = document.getElementById('typeFilter');
            const searchPatient = document.getElementById('searchPatient');

            // New references for the two separate appointment lists
            const pendingAppointmentList = document.getElementById('pendingAppointmentList');
            const confirmedAppointmentList = document.getElementById('confirmedAppointmentList');
            const pendingAppointmentsCard = document.getElementById('pendingAppointmentsCard'); // New
            const confirmedAppointmentsCard = document.getElementById('confirmedAppointmentsCard'); // New


            // Event listeners for changes in filtering
            // statusFilter.addEventListener('change', fetchAppointments); // REMOVED
            typeFilter.addEventListener('change', fetchAppointments);
            searchPatient.addEventListener('input', applyClientSideFilter);


            let currentAppointments = [];

            // Fetches latest appointments. Note: The status filter is removed from the request
            // to fetch all relevant appointments for client-side separation.
            function fetchAppointments() {
                // Fetch all appointments (status parameter is omitted)
                const selectedSortBy = 'appointment_date';
                const selectedOrderBy = 'ASC'; // Sort ascending to show nearest appointments first
                const selectedTypeFilter = typeFilter.value;

                // IMPORTANT: The URL no longer includes the 'status' parameter to get all relevant appointments (pending, scheduled, etc.)
                const url = `get_appointments.php?sort_by=${selectedSortBy}&order=${selectedOrderBy}&type=${selectedTypeFilter}&t=${new Date().getTime()}`;

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
                        // Apply search filter only after rendering the new lists
                        applyClientSideFilter();
                    })
                    .catch(error => console.error('Error fetching appointments:', error));
            }

            function renderAppointments(appointments) {
                // Clear both lists first
                pendingAppointmentList.innerHTML = '';
                confirmedAppointmentList.innerHTML = '';

                // Group the appointments by their status (Pending vs. Scheduled/Confirmed)
                const pending = appointments.filter(a => a.status === 'Pending');
                const confirmed = appointments.filter(a => a.status === 'Scheduled' || a.status === 'Online' || a.status === 'Offline');

                // Helper function to create the list item
                const createListItem = (appointment) => {
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
                    if (appointment.status === 'Pending') {
                        buttonsHtml = `
                            <button class="btn btn-sm btn-outline-primary accept-appointment-btn" data-appointment-id="${appointment.id}" data-appointment-type="${appointment.type}">Accept</button>
                            <a href="#" class="btn btn-sm btn-outline-success">Message</a>
                            <button class="btn btn-sm btn-outline-danger cancel-appointment-btn" data-appointment-id="${appointment.id}">Cancel</button>
                        `;
                    } else if (appointment.status === 'Scheduled' || appointment.status === 'Online' || appointment.status === 'Offline') {
                        buttonsHtml = `
                            <button class="btn btn-sm btn-outline-primary accept-appointment-btn" data-appointment-id="${appointment.id}" data-appointment-type="${appointment.type}">Accept</button>
                            <a href="#" class="btn btn-sm btn-outline-success">Message</a>
                            <button class="btn btn-sm btn-outline-danger cancel-appointment-btn" data-appointment-id="${appointment.id}">Cancel</button>
                        `;
                    }

                    let badgeClass;
                    if (status === 'pending') {
                        badgeClass = 'warning';
                    } else if (status === 'scheduled' || status === 'online' || status === 'offline') {
                        badgeClass = 'success';
                    } else if (status === 'completed') {
                        badgeClass = 'primary';
                    } else if (status === 'cancelled') {
                        badgeClass = 'danger';
                    } else {
                        badgeClass = 'secondary';
                    }

                    const profilePicPath = appointment.patient_profile_pic || 'assets/images/default-avatar.png';

                    listItem.innerHTML = `
                        <div class="doctor-info">
                            <div class="doctor-avatar">
                                <img src="/hospital-management-system/${profilePicPath}"
                                    alt="${appointment.patient_name}"
                                    class="rounded-circle">
                            </div>
                            <div>
                                <h4>${appointment.patient_name}</h4>
                                <p>Appointment: ${new Date(appointment.appointment_date.replace(' ', 'T') + 'Z').toLocaleString()}</p>
                                <p>Remaining: <span class="remaining-time">Calculating...</span></p>
                            </div>
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
                    return listItem;
                };

                // Render into pending list
                pending.forEach(appointment => {
                    pendingAppointmentList.appendChild(createListItem(appointment));
                });

                // Render into confirmed list
                confirmed.forEach(appointment => {
                    confirmedAppointmentList.appendChild(createListItem(appointment));
                });

                // Hide/show pending appointments card
                if (pending.length === 0) {
                    pendingAppointmentsCard.style.display = 'none';
                } else {
                    pendingAppointmentsCard.style.display = 'block';
                }

                // Hide/show confirmed appointments card
                if (confirmed.length === 0) {
                    confirmedAppointmentsCard.style.display = 'none';
                } else {
                    confirmedAppointmentsCard.style.display = 'block';
                }

                updateAllTimers();
            }

            function applyClientSideFilter() {
                const searchTerm = searchPatient.value.toLowerCase();

                // Check items in both lists
                const pendingItems = document.querySelectorAll('#pendingAppointmentList .doctor-item');
                const confirmedItems = document.querySelectorAll('#confirmedAppointmentList .doctor-item');

                let visiblePending = 0;
                pendingItems.forEach(item => {
                    const name = item.dataset.name;
                    const isVisible = name.includes(searchTerm);
                    item.style.display = isVisible ? 'flex' : 'none';
                    if (isVisible) visiblePending++;
                });

                let visibleConfirmed = 0;
                confirmedItems.forEach(item => {
                    const name = item.dataset.name;
                    const isVisible = name.includes(searchTerm);
                    item.style.display = isVisible ? 'flex' : 'none';
                    if (isVisible) visibleConfirmed++;
                });

                // Update visibility of the cards based on filtered results
                if (visiblePending === 0) {
                    pendingAppointmentsCard.style.display = 'none';
                } else {
                    pendingAppointmentsCard.style.display = 'block';
                }

                if (visibleConfirmed === 0) {
                    confirmedAppointmentsCard.style.display = 'none';
                } else {
                    confirmedAppointmentsCard.style.display = 'block';
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
                // IMPORTANT: Since window.confirm() is forbidden in this environment, replace it with a console error or a non-blocking UI message.
                // Assuming `window.alert` is a temporary stand-in for a custom modal or message box.
                if (!button) return;

                const appointmentId = button.dataset.appointmentId;
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

                    fetch('complete_appointment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ appointment_id: appointmentId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            fetchAppointments();
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