<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_patient();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link rel="stylesheet" href="/hospital-management-system/assets/css/home.css"> <!-- For navbar and overlay styles -->
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/medical-history.css"> 
   <link rel="stylesheet" href="../assets/css/doctor-profile.css">


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
                <li><a href="book-appointment.php" class="sidebar-link" data-target="book-appointment.php">Book New Appointment</a></li>
                <li><a href="medical-history.php" class="sidebar-link" data-target="medical-history.php">View Medical History</a></li>
                <!-- Add more patient-specific actions here -->
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <h2>Welcome to Patient Dashboard</h2>
            <p>Select an option from the left menu to manage your appointments and medical records.</p>
        </main>
    </div>

    <!-- Profile side overlay - kept for consistency, but can be removed if not needed -->
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
            const sidebarLinks = document.querySelectorAll('.sidebar-link');

            sidebarToggle.addEventListener('click', () => {
                patientSidebar.classList.toggle('closed');
            });

            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetPage = this.dataset.target;
                    fetch(targetPage)
                        .then(response => response.text())
                        .then(html => {
                            // Extract only the content within the <body> tags of the fetched page
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const bodyContent = doc.querySelector('.container').innerHTML; // Assuming content is in a .container div
                            mainContent.innerHTML = '<div class="container">' + bodyContent + '</div>';
                        })
                        .catch(error => {
                            console.error('Error loading page:', error);
                            mainContent.innerHTML = '<p style="color: red;">Error loading content.</p>';
                        });
                });
            });

            // Profile overlay functionality (copied from homepage.php)
            const profileToggle = document.getElementById('profileToggle');
            const profileOverlay = document.getElementById('profileOverlay');
            const profilePicInput = document.getElementById('profilePicInput');
            const profilePicUploadForm = document.getElementById('profilePicUploadForm');
            const profileImageDisplay = document.getElementById('profileImageDisplay');
            const uploadMessage = document.getElementById('uploadMessage');

            profileToggle.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevent this click from immediately closing the overlay
                profileOverlay.classList.add('open');
            });

            // Close overlay when clicking directly on the overlay background
            profileOverlay.addEventListener('click', function(event) {
                if (event.target === profileOverlay) {
                    profileOverlay.classList.remove('open');
                }
            });

            // Close overlay when clicking on the main content area
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
        });
    </script>
</body>
</html>