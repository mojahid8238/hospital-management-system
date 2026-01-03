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
$profilePic = preg_replace('#^\\.\\./#', '', $rawProfilePic); 
// Now $profilePic contains: 'assets/images/profile_pics/patient_2.png' or 'assets/images/default-avatar.png'
// -------------------------------------------------------------------------

$username = $_SESSION['name'] ?? 'User'; // Use name from session
$role = $_SESSION['role'] ?? 'Guest'; // Derive role from session
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Homepage</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../assets/css/home.css" />
  <link rel="stylesheet" href="../assets/css/dashboard.css">

 
</head>

  <header class="navbar">
    <div class="nav-left">
        <a href="homepage.php" style="font-size: 1.6rem; font-weight: 800; background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-heartbeat" style="color: var(--primary-color);"></i>
            HealthCare Portal
        </a>
    </div>
    <div class="nav-right">
      <div class="user-info">
          <span><?php echo htmlspecialchars($username); ?></span>
      </div>
      <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile" class="user-icon" id="profileToggle">
    </div>
  </header>

  <div class="landing-body">
      <main class="content-area-landing" style="padding: 0 10%; max-width: 1600px; margin: 0 auto;">
          <!-- Hero Section -->
          <section class="hero-section">
              <h2 class="hero-title">Your Health, <span style="color: var(--primary-color);">Our Commitment.</span></h2>
              <p class="hero-subtitle">Access professional medical care, secure consultations, and your complete health history in one unified platform.</p>
          </section>

          <?php
            $dashUrl = "../dashboard.php"; 
            if($role === 'admin') $dashUrl = "../admin/dashboard.php";
            elseif($role === 'doctor') $dashUrl = "../doctor/dashboard.php";
            elseif($role === 'patient') $dashUrl = "../patient/dashboard.php";
          ?>

          <!-- Navigation Hub -->
          <div class="hub-grid">
              <a href="../messaging/messaging.php" class="hub-card">
                  <div class="panel-card">
                      <div class="icon-holder bg-primary-light">
                          <i class="fas fa-comment-medical text-primary" style="font-size: 2.25rem;"></i>
                      </div>
                      <h3 style="font-size: 1.5rem; margin-bottom: 12px; font-weight: 700;">Secure Consultations</h3>
                      <p class="text-muted" style="line-height: 1.6; margin-bottom: 24px;">Chat directly with specialists and receive medical guidance from the comfort of your home.</p>
                      <span class="btn btn-primary" style="border-radius: 12px; padding: 12px 30px;">Start Chatting</span>
                  </div>
              </a>

              <a href="<?php echo $dashUrl; ?>" class="hub-card">
                  <div class="panel-card">
                      <div class="icon-holder bg-success-light">
                          <i class="fas fa-th-large text-success" style="font-size: 2.25rem;"></i>
                      </div>
                      <h3 style="font-size: 1.5rem; margin-bottom: 12px; font-weight: 700;">Control Panel</h3>
                      <p class="text-muted" style="line-height: 1.6; margin-bottom: 24px;">Manage appointments, view detailed health metrics, and organize your medical profile.</p>
                      <span class="btn btn-success" style="border-radius: 12px; padding: 12px 30px; background: var(--secondary-color);">Access Dashboard</span>
                  </div>
              </a>
          </div>

          <!-- Feature Slider -->
          <div class="slider-container" id="mainSlider">
              <img src="../assets/images/slider1.png" alt="Health Care" class="active">
              <img src="../assets/images/slider2.png" alt="Modern Facilities">
              <img src="../assets/images/slider3.png" alt="Professional Staff">
              <button class="slider-btn prev" onclick="plusSlides(-1, 'mainSlider')"><i class="fas fa-chevron-left"></i></button>
              <button class="slider-btn next" onclick="plusSlides(1, 'mainSlider')"><i class="fas fa-chevron-right"></i></button>
          </div>

          <div class="grid grid-2" style="gap: 40px; margin-bottom: 80px;">
              <div class="panel-card" style="padding: 40px;">
                  <h3 class="section-title mb-4" style="font-size: 1.5rem;"><i class="fas fa-rocket text-primary"></i> Quick Shortcuts</h3>
                  <div class="quick-actions-grid">
                      <a href="../patient/book-appointment.php" class="action-btn">
                          <div class="icon-circle bg-primary-light" style="width: 50px; height: 50px; margin-bottom: 10px;">
                              <i class="fas fa-calendar-plus text-primary"></i>
                          </div>
                          Book Now
                      </a>
                      <a href="../patient/medical-history.php" class="action-btn">
                         <div class="icon-circle bg-info-light" style="width: 50px; height: 50px; margin-bottom: 10px;">
                              <i class="fas fa-file-medical text-info"></i>
                          </div>
                          Records
                      </a>
                      <a href="../patient/profile.php" class="action-btn">
                          <div class="icon-circle bg-warning-light" style="width: 50px; height: 50px; margin-bottom: 10px;">
                              <i class="fas fa-user-cog text-warning"></i>
                          </div>
                          Settings
                      </a>
                      <a href="../messaging/messaging.php" class="action-btn">
                          <div class="icon-circle bg-error-light" style="width: 50px; height: 50px; margin-bottom: 10px;">
                              <i class="fas fa-hands-helping text-error"></i>
                          </div>
                          Help
                      </a>
                  </div>
              </div>

              <div class="slider-container" id="promoSlider" style="height: 100%;">
                  <img src="../assets/images/promo1.png" alt="Promo" class="active">
                  <img src="../assets/images/promo2.png" alt="Special Offer">
                  <button class="slider-btn prev" onclick="plusSlides(-1, 'promoSlider')"><i class="fas fa-chevron-left"></i></button>
                  <button class="slider-btn next" onclick="plusSlides(1, 'promoSlider')"><i class="fas fa-chevron-right"></i></button>
          <!-- Features Section -->
          <section style="margin-bottom: 80px;">
              <h3 class="section-title mb-5" style="font-size: 2rem; justify-content: center; text-align: center;">Why Choose <span style="color: var(--primary-color); margin-left: 8px;">HealthCare?</span></h3>
              <div class="grid grid-3" style="gap: 32px;">
                  <div class="panel-card" style="text-align: center; padding: 32px;">
                      <div class="icon-circle bg-primary-light" style="width: 70px; height: 70px; margin: 0 auto 20px;">
                          <i class="fas fa-shield-alt text-primary" style="font-size: 1.5rem;"></i>
                      </div>
                      <h4 style="font-weight: 700; margin-bottom: 12px;">Secure Data</h4>
                      <p class="text-muted">Your medical records are encrypted and stored with the highest security standards.</p>
                  </div>
                  <div class="panel-card" style="text-align: center; padding: 32px;">
                      <div class="icon-circle bg-success-light" style="width: 70px; height: 70px; margin: 0 auto 20px;">
                          <i class="fas fa-user-md text-success" style="font-size: 1.5rem;"></i>
                      </div>
                      <h4 style="font-weight: 700; margin-bottom: 12px;">Expert Doctors</h4>
                      <p class="text-muted">Connect with verified specialists across various medical disciplines.</p>
                  </div>
                  <div class="panel-card" style="text-align: center; padding: 32px;">
                      <div class="icon-circle bg-info-light" style="width: 70px; height: 70px; margin: 0 auto 20px;">
                          <i class="fas fa-clock text-info" style="font-size: 1.5rem;"></i>
                      </div>
                      <h4 style="font-weight: 700; margin-bottom: 12px;">24/7 Support</h4>
                      <p class="text-muted">Access our messaging platform and support team anytime, anywhere.</p>
                  </div>
              </div>
          </section>
      </main>
  </div>

  <style>
      .hub-card .panel-card:hover {
          transform: translateY(-10px);
          box-shadow: var(--shadow-xl);
          border-color: var(--primary-color);
      }
      .btn { display: inline-flex; align-items: center; justify-content: center; }
  </style>

  <div class="profile-overlay" id="profileOverlay">
    <div class="profile-content">
      <div class="profile-pic-wrapper" style="position: relative; margin-bottom: 24px;">
          <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay" class="profile-overlay-pic">
          <label for="profilePicInput" style="position: absolute; bottom: 30px; right: 10px; background: var(--primary-color); color: #fff; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid #fff;">
              <i class="fas fa-camera"></i>
          </label>
      </div>
      <h3 style="font-size: 1.5rem; font-weight: 700;"><?php echo htmlspecialchars($username); ?></h3>
      <p class="text-muted" style="text-transform: capitalize;"><?php echo htmlspecialchars($role); ?> Account</p>
      
      <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
        <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
      </form>
      <div id="uploadMessage"></div>

      <hr style="margin: 24px 0;">
      <ul>
        <li><a href="../patient/profile.php" style="padding: 12px; border-radius: var(--radius-md);"><i class="fas fa-user-circle"></i> My Profile</a></li>        
        <li><a href="<?php echo $dashUrl; ?>" style="padding: 12px; border-radius: var(--radius-md);"><i class="fas fa-th-large"></i> Dashboard</a></li>
        <li><a href="../auth/logout.php" class="logout-btn" style="color: var(--error); padding: 12px; border-radius: var(--radius-md);"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
      <button class="close-btn" id="closeProfile">Close Panel</button>
    </div>
  </div>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="../assets/js/ui-ux.js"></script>
  <script src="../assets/js/profile-overlay.js"></script>

  <script>
    const BASE_URL = '/';
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
