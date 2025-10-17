<?php
require_once '../includes/auth.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit();
}

// -------------------------------------------------------------------------
// FIX 1: Clean the path stored in the database/session by 
//        removing the leading '../' if it exists.
// -------------------------------------------------------------------------
// Use a default path that is relative to the project root (no ../)
$rawProfilePic = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';
// Use ltrim to safely remove the leading "../" if it exists.
$profilePic = ltrim($rawProfilePic, '../'); 
// Now $profilePic contains: 'assets/images/profile_pics/patient_2.png' or 'assets/images/default-avatar.png'
// -------------------------------------------------------------------------

$username = $_SESSION['username'] ?? 'User'; // Use username from session
$role = $_SESSION['role'] ?? 'Guest'; // Derive role from session
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Homepage</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../assets/css/home.css" />
  <link rel="stylesheet" href="../assets/css/medical-history.css" />

 
</head>

  <header class="navbar">
    <div class="nav-left">
        <a href="">Homepage</a>
    </div>
    <div class="nav-right">
      <!-- FIX 2: Prepend the ONLY necessary '../' to the CLEAN path ($profilePic) -->
      <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="user-icon" id="profileToggle">
    </div>
  </header>

    <div class="main-wrapper">
        <main class="content-area" id="mainContent">
            <!-- Asset paths are already correctly using '../' from /includes/ -->
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

           
        </main>
    </div>

  <!-- Profile side overlay - kept for consistency, but can be removed if not needed -->
  <div class="profile-overlay" id="profileOverlay">
    <div class="profile-content">
      <!-- FIX 3: Prepend the ONLY necessary '../' to the CLEAN path ($profilePic) -->
      <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay">
      <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
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
    </div>
  </div>

  <script>
    // Global slider functions
    let slideIndex = { 'mainSlider': 0, 'promoSlider': 0 };

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

    document.addEventListener('DOMContentLoaded', function() {
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

        // Close overlay when clicking outside profile-content or profileToggle
        document.addEventListener('click', function(event) {
            if (profileOverlay.classList.contains('open') &&
                !event.target.closest('.profile-content') &&
                !event.target.closest('#profileToggle')) {
                profileOverlay.classList.remove('open');
            }
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
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            console.error("Server returned non-OK status. Raw response:", text);
                            throw new Error(`Server returned status ${response.status}: See console for raw response.`);
                        });
                    }
                    return response.json().catch(e => {
                        console.error("Failed to parse JSON response. Server output may contain errors/warnings:", e);
                        throw new Error("Server output non-JSON (likely PHP error/warning). Check console for details.");
                    });
                })
                .then(data => {
                    if (data.success) {
                        let cleanPath = data.profile_pic_path;
                                         
                        // --- FIX ---
                        // The path from the server is clean (e.g., 'assets/images/pic.png').
                        // Since this page is one directory deep, we MUST prepend '../' to go up
                        // one level before finding the assets folder.
                        const newImagePath = '../' + cleanPath + '?t=' + new Date().getTime();
                        
                        // This part is already correct and will now work as expected
                        profileImageDisplay.src = newImagePath; // Update overlay image
                        document.getElementById('profileToggle').src = newImagePath; // Update header icon
                        
                        uploadMessage.textContent = 'Profile picture updated successfully!';
                        uploadMessage.style.color = 'green';
                        setTimeout(() => {
                            uploadMessage.textContent = '';
                        }, 1000);
                    } else {
                        uploadMessage.textContent = data.message || 'Upload failed.';
                        uploadMessage.style.color = 'red';
                        console.error("Server-reported upload failure:", data.message);
                    }
                })
                .catch(error => {
                    uploadMessage.textContent = 'Upload failed: ' + error.message;
                    uploadMessage.style.color = 'red';
                    console.error("AJAX Upload Error (Client-side Catch):", error);
                });
            }
        });

        // Slider functionality initialization
        showSlides(0, 'mainSlider');
        showSlides(0, 'promoSlider');

        // Automatic slideshow
        setInterval(() => {
            plusSlides(1, 'mainSlider');
        }, 10000);

        setInterval(() => {
            plusSlides(1, 'promoSlider');
        }, 10000);
    });
  </script>

</body>
</html>
