<?php
require_once '../includes/auth.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit();
}

$profilePic = $_SESSION['profile_pic'] ?? 'default-avatar.png';
$username = $_SESSION['username'] ?? 'User'; // Use username from session
$role = $_SESSION['role'] ?? 'Guest'; // Derive role from session
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Patient Homepage</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
 <link rel="stylesheet" href="../assets/css/homepage.css">
</head>
<body>

  <header class="navbar">
    <div class="nav-left">
      <a href="homepage.php">HMS</a>
    </div>
    <div class="nav-right">
      <span class="user-icon" id="profileToggle">👤</span>
    </div>
  </header>

  <section class="slider">
    <h3>Main Slider</h3>
    <p>This area can display promotional banners, health tips, or announcements.</p>
  </section>

  <section class="doctor-slider" id="doctors">
    <h3>Doctor Slider</h3>
    <p>Showcase featured doctors or specialties here.</p>
  </section>

  <section class="about" id="services">
    <h3>About Our Services</h3>
    <p>Information about the platform, services provided, or health guidance.</p>
  </section>

  <!-- Profile side overlay -->
  <div class="profile-overlay" id="profileOverlay">
    <div class="profile-content">
      <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" id="profileImageDisplay">
      <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
        <label for="profilePicInput" class="upload-btn">Change Picture</label>
        <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
        <button type="submit" style="display: none;">Upload</button>
      </form>
      <div id="uploadMessage" style="margin-top: 10px;"></div>

      <h3><?php echo htmlspecialchars($username); ?></h3>
      <p>Role: <?php echo htmlspecialchars($role); ?></p>
      <hr>
      <ul>
        <li><a href="#">Settings</a></li>
        <li><a href="../patient/book-appointment.php">Book New Appointment</a></li>
        <li><a href="../patient/medical-history.php">View History</a></li>
        <li><a href="../patient/dashboard.php">Dashboard</a></li>
        <li><a href="../auth/logout.php" class="logout-btn">Logout</a></li>
      </ul>
      <button class="close-btn" id="closeProfile">Close</button>
    </div>
  </div>

  <script>
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
            profileImageDisplay.src = data.profile_pic_path + '?t=' + new Date().getTime();
            uploadMessage.textContent = 'Profile picture updated!';
            uploadMessage.style.color = 'green';
          } else {
            uploadMessage.textContent = data.message || 'Upload failed.';
            uploadMessage.style.color = 'red';
          }
        })
        .catch(() => {
          uploadMessage.textContent = 'Error during upload.';
          uploadMessage.style.color = 'red';
        });
      }
    });
  </script>

</body>
</html>