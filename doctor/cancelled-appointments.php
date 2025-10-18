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
    $stmt = $conn->prepare("SELECT a.id, p.name as patient_name, p.profile_pic as patient_profile_pic, a.appointment_date, a.reason, a.status FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.doctor_id = ? AND a.status = 'Cancelled' ORDER BY a.appointment_date DESC");
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
    <title>Cancelled Appointments</title>
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
                <li><a href="dashboard.php">View Your Appointments</a></li>
                <li><a href="cancelled-appointments.php">Cancelled Appointments</a></li>
                <li><a href="../messaging/messaging.php">Messages</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container panel-card">
                <h2 class="card-title mb-3">Welcome, <strong>Dr. <?php echo htmlspecialchars($doctor_name); ?> </h2>
                <p></strong>Here are your cancelled appointments:</p>

                <div class="search-filter-container">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchPatient" placeholder="Search Patient by name...">
                    </div>
                </div>

                <?php if (empty($appointments)): ?>
                    <div class="alert alert-info mt-4">You have no cancelled appointments.</div>
                <?php else: ?>
                    <ul class="doctor-list" id="appointmentList">
                        <?php 
                        $serial = 1;
                        foreach ($appointments as $appointment): 
                            $appointment_time = new DateTime($appointment['appointment_date']);
                            $now = new DateTime();
                            $interval = $now->diff($appointment_time);
                            $remaining_time = $interval->format('%a Days %h Hours %i Minutes');
                        ?>
                            <li class="doctor-item" 
                                data-name="<?php echo strtolower(htmlspecialchars($appointment['patient_name'])); ?>" 
                                data-status="<?php echo strtolower(htmlspecialchars($appointment['status'])); ?>"
                                id="appointment-<?php echo $appointment['id']; ?>">
                                <div class="doctor-avatar">
                                    <img src="/hospital-management-system/<?php echo htmlspecialchars($appointment['patient_profile_pic'] ?? 'assets/images/default-avatar.png'); ?>" 
                                        alt="<?php echo htmlspecialchars($appointment['patient_name']); ?>" 
                                        class="rounded-circle">
                                </div>
                                <div class="doctor-info">
                                    <h4><?php echo htmlspecialchars($appointment['patient_name']); ?></h4>
                                    <p>Appointment: <?php echo date('Y-m-d H:i', strtotime($appointment['appointment_date'])); ?></p>
                                    <p>Original Remaining: <?php echo $remaining_time; ?></p>
                                </div>
                                <div class="doctor-info">
                                    <p>Reason: <?php echo htmlspecialchars($appointment['reason']); ?></p>
                                    <p>Status: <span class="badge bg-<?php echo strtolower(htmlspecialchars($appointment['status'])); ?>"><?php echo htmlspecialchars(ucfirst($appointment['status'])); ?></span></p>
                                </div>
                                <div class="doctor-info">
                                    <button class="btn btn-sm btn-outline-danger remove-appointment-btn" data-appointment-id="<?php echo $appointment['id']; ?>">Remove</button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Profile side overlay -->
    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <img src="/hospital-management-system/<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay" style="position: relative; z-index: 10; cursor: pointer;">
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
                <button type="submit" style="display: none;">Upload</button>
            </form>
            <div id="uploadMessage" style="margin-top: 10px; color: green;"></div>
            <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
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
            const sidebarToggle = document.getElementById('sidebarToggle');
            const doctorSidebar = document.getElementById('doctorSidebar');
            sidebarToggle.addEventListener('click', () => {
                doctorSidebar.classList.toggle('closed');
            });

            const profileToggle = document.getElementById('profileToggle');
            const profileOverlay = document.getElementById('profileOverlay');
            profileToggle.addEventListener('click', (event) => {
                event.stopPropagation();
                profileOverlay.classList.add('open');
            });

            profileOverlay.addEventListener('click', function(event) {
                if (event.target === profileOverlay) {
                    profileOverlay.classList.remove('open');
                }
            });

            const mainContent = document.getElementById('mainContent');
            mainContent.addEventListener('click', () => {
                profileOverlay.classList.remove('open');
            });

            const profilePicInput = document.getElementById('profilePicInput');
            const profilePicUploadForm = document.getElementById('profilePicUploadForm');
            const profileImageDisplay = document.getElementById('profileImageDisplay');
            const uploadMessage = document.getElementById('uploadMessage');

            profileImageDisplay.addEventListener('click', function() {
                console.log('Profile image clicked, triggering file input.');
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

            const searchPatient = document.getElementById('searchPatient');
            const appointmentList = document.getElementById('appointmentList');
            const appointmentItems = Array.from(appointmentList.getElementsByClassName('doctor-item'));

            function filterAppointments() {
                const searchTerm = searchPatient.value.toLowerCase();

                appointmentItems.forEach(item => {
                    const name = item.dataset.name;

                    const nameMatch = name.includes(searchTerm);

                    if (nameMatch) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }

            searchPatient.addEventListener('input', filterAppointments);

            function handleDeleteButtonClick(event) {
                const button = event.target.closest('.remove-appointment-btn');
                if (!button) return;

                const appointmentId = button.dataset.appointmentId;
                if (confirm('Are you sure you want to permanently remove this cancelled appointment? This action cannot be undone.')) {
                    button.disabled = true;

                    fetch('delete_cancelled_appointment.php', {
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
                                    const appointmentList = document.getElementById('appointmentList');
                                    if (appointmentList && appointmentList.children.length === 0) {
                                        const container = document.querySelector('.container.panel-card');
                                        if (container) {
                                            container.innerHTML += '<div class="alert alert-info mt-4">You have no cancelled appointments.</div>';
                                        }
                                    }
                                }, 500);
                            }
                        } else {
                            alert('Error: ' + data.message);
                            button.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                        button.disabled = false;
                    });
                }
            }

            document.addEventListener('click', handleDeleteButtonClick);
        });
    </script>
</body>
</html>