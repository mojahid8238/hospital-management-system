<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
redirect_if_not_doctor();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- General styles -->
    <link rel="stylesheet" href="../assets/css/homepage.css"> <!-- For navbar and overlay styles -->
   <style>
    /* Reset and base */
    * {
        box-sizing: border-box;
    }
    body {
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        background-color: #f4f6f8;
        color: #333;
    }

    /* Navbar */
    .navbar {
        background: linear-gradient(90deg, #0052cc, #3366ff);
        color: white;
        padding: 1rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 6px rgb(0 0 0 / 0.1);
        z-index: 1000;
    }
    .nav-left a {
        font-weight: 700;
        font-size: 1.4rem;
        color: #fff;
        text-decoration: none;
        letter-spacing: 1px;
    }
    .sidebar-toggle-btn {
        background-color: #fff;
        color: #0052cc;
        border: none;
        padding: 8px 14px;
        margin-left: 15px;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.3s ease, color 0.3s ease;
        box-shadow: 0 2px 5px rgb(0 0 0 / 0.1);
    }
    .sidebar-toggle-btn:hover {
        background-color: #0041a3;
        color: #fff;
    }

    .nav-right {
        display: flex;
        align-items: center;
        gap: 18px;
        font-weight: 600;
    }
    .user-icon {
        font-size: 1.6rem;
        cursor: pointer;
        background-color: rgba(255 255 255 / 0.3);
        padding: 6px 10px;
        border-radius: 50%;
        transition: background-color 0.3s ease;
    }
    .user-icon:hover {
        background-color: rgba(255 255 255 / 0.5);
    }
    .nav-right a {
        color: #fff;
        text-decoration: none;
        padding: 6px 12px;
        border-radius: 6px;
        background-color: rgba(255 255 255 / 0.2);
        transition: background-color 0.3s ease;
    }
    .nav-right a:hover {
        background-color: rgba(255 255 255 / 0.4);
    }

    /* Main layout */
    .main-wrapper {
        display: flex;
        flex: 1;
        min-height: calc(100vh - 64px); /* Adjust for navbar height */
        overflow: hidden;
    }

    /* Sidebar */
    .sidebar {
        width: 280px;
        background-color: #1e2733;
        color: #e4e6eb;
        padding: 30px 25px;
        box-shadow: 2px 0 12px rgba(0, 0, 0, 0.12);
        flex-shrink: 0;
        transition: width 0.3s ease, padding 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    .sidebar.closed {
        width: 0;
        padding: 0;
        overflow: hidden;
        box-shadow: none;
    }
    .sidebar h3 {
        font-weight: 700;
        font-size: 1.5rem;
        margin-bottom: 30px;
        text-align: center;
        letter-spacing: 1.2px;
        color: #ffffffcc;
    }
    .sidebar ul {
        list-style: none;
        padding: 0;
        margin: 0;
        flex-grow: 1;
    }
    .sidebar ul li {
        margin-bottom: 18px;
    }
    .sidebar ul li a {
        display: block;
        padding: 12px 18px;
        border-radius: 8px;
        color: #cfd8dc;
        text-decoration: none;
        font-weight: 600;
        font-size: 1rem;
        transition: background-color 0.25s ease, color 0.25s ease;
        box-shadow: inset 0 0 0 0 transparent;
    }
    .sidebar ul li a:hover,
    .sidebar ul li a.active {
        background-color: #3366ff;
        color: white;
        box-shadow: inset 4px 0 0 #0052cc;
    }

    /* Content */
    .content-area {
        flex-grow: 1;
        background-color: #fff;
        padding: 40px 45px;
        overflow-y: auto;
        box-shadow: inset 0 0 10px #e1e5eb;
        border-radius: 0 8px 8px 0;
    }
    .content-area h2 {
        font-weight: 700;
        font-size: 2.2rem;
        margin-bottom: 12px;
        color: #202940;
    }
    .content-area p {
        font-size: 1.1rem;
        color: #555a66;
        line-height: 1.6;
    }

    /* Profile overlay */
    .profile-overlay {
        position: fixed;
        top: 0;
        right: 0;
        width: 320px;
        max-width: 90vw;
        height: 100%;
        background-color: #fff;
        box-shadow: -3px 0 18px rgba(0, 0, 0, 0.15);
        transform: translateX(100%);
        transition: transform 0.3s ease;
        z-index: 1200;
        display: flex;
        flex-direction: column;
        padding: 30px 25px;
        overflow-y: auto;
    }
    .profile-overlay.open {
        transform: translateX(0);
    }
    .profile-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }
    .profile-content img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #3366ff;
        box-shadow: 0 0 12px #3366ffaa;
    }
    .upload-btn {
        cursor: pointer;
        color: #3366ff;
        font-weight: 600;
        padding: 8px 16px;
        border: 2px solid #3366ff;
        border-radius: 8px;
        transition: background-color 0.3s ease, color 0.3s ease;
    }
    .upload-btn:hover {
        background-color: #3366ff;
        color: #fff;
    }
    #uploadMessage {
        font-size: 0.95rem;
        text-align: center;
        margin-top: 5px;
    }
    .profile-content h3 {
        font-weight: 700;
        font-size: 1.4rem;
        margin-bottom: 4px;
        color: #202940;
    }
    .profile-content p {
        font-weight: 600;
        color: #777e8a;
        margin-bottom: 15px;
    }
    .profile-content hr {
        width: 100%;
        border: none;
        border-top: 1px solid #ddd;
        margin: 15px 0;
    }
    .profile-content ul {
        list-style: none;
        padding: 0;
        margin: 0 0 20px 0;
        width: 100%;
    }
    .profile-content ul li {
        margin-bottom: 12px;
    }
    .profile-content ul li a {
        color: #3366ff;
        font-weight: 600;
        text-decoration: none;
        font-size: 1rem;
        transition: color 0.3s ease;
    }
    .profile-content ul li a:hover {
        text-decoration: underline;
    }
    .close-btn {
        align-self: stretch;
        background-color: #e0e0e0;
        border: none;
        padding: 10px 0;
        border-radius: 8px;
        font-weight: 700;
        color: #555;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    .close-btn:hover {
        background-color: #cfcfcf;
    }
</style>
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">☰ Toggle Menu</button>
            <a href="#">Doctor Panel</a>
        </div>
        <div class="nav-right">
            <span class="user-icon" id="profileToggle">👤</span>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="doctorSidebar">
            <h3>Doctor Options</h3>
            <ul>
                <li><a href="view-appointments.php" class="sidebar-link" data-target="view-appointments.php">View Your Appointments</a></li>
                <!-- Add more doctor-specific actions here -->
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <h2>Welcome to Doctor Dashboard</h2>
            <p>Select an option from the left menu to manage your appointments and other tasks.</p>
        </main>
    </div>

    <!-- Profile side overlay - kept for consistency, but can be removed if not needed -->
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
            <p>Role: Doctor</p>
            <hr>
            <h4>Dashboards</h4>
            <ul>
                <li><a href="dashboard.php">Doctor Dashboard</a></li>
                <li><a href="../includes/homepage.php">Patient Homepage</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
            <button class="close-btn" id="closeProfile">Close</button>
        </div>
    </div>

    <script>
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