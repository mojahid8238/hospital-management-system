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
  <link rel="stylesheet" href="../assets/css/homepage.css" />
  <style>
        /* Specific styles for this page */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }
        .main-wrapper {
            display: flex;
            flex: 1;
        }
        .content-area {
            flex-grow: 1;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #1976d2;
            color: #fff;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar .nav-left a {
            color: #fff;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .navbar .nav-right {
            display: flex;
            align-items: center;
        }
        .navbar .nav-right .user-icon {
            font-size: 1.5rem;
            cursor: pointer;
            margin-right: 15px;
        }
        .navbar .nav-right a {
            color: #fff;
            text-decoration: none;
            margin-left: 10px;
        }

        /* Slider styles */
        .slider-container {
            position: relative;
            width: 100%;
            max-width: 800px; /* Adjust as needed */
            margin: 20px auto;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .slider-container img {
            width: 100%;
            display: none;
        }
        .slider-container img.active {
            display: block;
        }
        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(0,0,0,0.5);
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 1.5rem;
            z-index: 10;
        }
        .slider-btn.prev {
            left: 0;
            border-radius: 0 5px 5px 0;
        }
        .slider-btn.next {
            right: 0;
            border-radius: 5px 0 0 5px;
        }
    </style>
</head>
<body>

  <header class="navbar">
    <div class="nav-left">
        <a href="#">Patient Homepage</a>
    </div>
    <div class="nav-right">
      <span class="user-icon" id="profileToggle">👤</span>
      <a href="../auth/logout.php">Logout</a>
    </div>
  </header>

    <div class="main-wrapper">
        <main class="content-area" id="mainContent">
            <div class="slider-container" id="mainSlider">
                <img src="../assets/images/slider1.png" alt="Slider Image 1" class="active">
                <img src="../assets/images/slider2.png" alt="Slider Image 2">
                <img src="../assets/images/slider3.png" alt="Slider Image 3">
                <button class="slider-btn prev" onclick="plusSlides(-1, 'mainSlider')">&#10094;</button>
                <button class="slider-btn next" onclick="plusSlides(1, 'mainSlider')">&#10095;</button>
            </div>

            <div class="slider-container" id="promoSlider">
                <img src="../assets/images/promo1.png" alt="Promo Image 1" class="active">
                <img src="../assets/images/promo2.png" alt="Promo Image 2">
                <button class="slider-btn prev" onclick="plusSlides(-1, 'promoSlider')">&#10094;</button>
                <button class="slider-btn next" onclick="plusSlides(1, 'promoSlider')">&#10095;</button>
            </div>

            <section class="doctor-slider" id="doctors">
                <h3>Doctor Slider</h3>
                <p>Showcase featured doctors or specialties here.</p>
            </section>

            <section class="about" id="services">
                <h3>About Our Services</h3>
                <p>Information about the platform, services provided, or health guidance.</p>
            </section>
        </main>
    </div>

  <!-- Profile side overlay - kept for consistency, but can be removed if not needed -->
  <div class="profile-overlay" id="profileOverlay">
    <div class="profile-content">
      <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" id="profileImageDisplay">
      <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
        <label for="profilePicInput" class="upload-btn">Change Picture</label>
        <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
        <button type="submit" style="display: none;">Upload</button>
      </form>
      <div id="uploadMessage" style="margin-top: 10px; color: green;"></div>

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

    // Slider functionality
    let slideIndex = { 'mainSlider': 0, 'promoSlider': 0 };
    showSlides(0, 'mainSlider');
    showSlides(0, 'promoSlider');

    // Automatic slideshow
    setInterval(() => {
        plusSlides(1, 'mainSlider');
    }, 3000);

    setInterval(() => {
        plusSlides(1, 'promoSlider');
    }, 3000);

    function plusSlides(n, sliderId) {
        showSlides(slideIndex[sliderId] += n, sliderId);
    }

    function showSlides(n, sliderId) {
        let i;
        let slider = document.getElementById(sliderId);
        let slides = slider.getElementsByTagName('img');
        if (n >= slides.length) { slideIndex[sliderId] = 0 }
        if (n < 0) { slideIndex[sliderId] = slides.length - 1 }
        for (i = 0; i < slides.length; i++) {
            slides[i].classList.remove('active');
        }
        slides[slideIndex[sliderId]].classList.add('active');
    }
  </script>

</body>
</html>