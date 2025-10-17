<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
redirect_if_not_doctor();

// --- FIX 1: Robustly determine the absolute profile picture path ---
// This handles both custom and default pictures reliably.
$base_path = "/hospital-management-system/";
$relative_pic_path = !empty($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'assets/images/default-avatar.png';
$profilePicPath = $base_path . $relative_pic_path;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/shared-table.css">

<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">☰ Toggle Menu</button>
            <a href="#">Doctor Panel</a>
        </div>
        <div class="nav-right">
            <img src="<?php echo htmlspecialchars($profilePicPath); ?>" alt="Profile Picture" class="user-icon" id="profileToggle" style="object-fit: cover;">
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
            <h2>Welcome to Doctor Dashboard</h2>
            <p>Select an option from the left menu to manage your appointments and other tasks.</p>
        </main>
    </div>

    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <img src="<?php echo htmlspecialchars($profilePicPath); ?>" alt="Profile Picture" id="profileImageDisplay">
            
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data" style="display: contents;">
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
            // Pass the base URL from PHP to JavaScript
            const BASE_URL = '<?php echo $base_path; ?>'; 

            const sidebarToggle = document.getElementById('sidebarToggle');
            const doctorSidebar = document.getElementById('doctorSidebar');
            const mainContent = document.getElementById('mainContent');
            const sidebarLinks = document.querySelectorAll('.sidebar-link');

            sidebarToggle.addEventListener('click', () => {
                doctorSidebar.classList.toggle('closed');
            });

            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetPage = this.dataset.target;
                    fetch(targetPage)
                        .then(response => response.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const container = doc.querySelector('.container');
                            const bodyContent = container ? container.innerHTML : html; 
                            mainContent.innerHTML = '<div class="container">' + bodyContent + '</div>';
                        })
                        .catch(error => {
                            console.error('Error loading page:', error);
                            mainContent.innerHTML = '<p style="color: red;">Error loading content.</p>';
                        });
                });
            });

            // Profile overlay functionality
            const profileToggle = document.getElementById('profileToggle');
            const profileOverlay = document.getElementById('profileOverlay');
            const profilePicInput = document.getElementById('profilePicInput');
            // --- FIX 2: This now correctly finds a <form> element ---
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
                    // This will no longer throw a TypeError
                    const formData = new FormData(profilePicUploadForm);
                    fetch(profilePicUploadForm.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // The path logic here is already correct for instant updates
                            const newImagePath = '/hospital-management-system/' + data.profile_pic_path + '?t=' + new Date().getTime();
                            
                            // Update both images instantly
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