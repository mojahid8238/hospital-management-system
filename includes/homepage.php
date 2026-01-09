<?php
require_once '../includes/auth.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit();
}

$rawProfilePic = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';
$profilePic = preg_replace('#^\\.\\./#', '', $rawProfilePic); 

$username = $_SESSION['name'] ?? 'User';
$role = $_SESSION['role'] ?? 'Guest';

$dashUrl = "../patient/dashboard.php";
if ($role === 'admin') {
    $dashUrl = "../admin/dashboard.php";
} elseif ($role === 'doctor') {
    $dashUrl = "../doctor/dashboard.php";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>HealthCare Portal - Home</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/home.css" />
</head>

<body class="landing-body">
  <header class="navbar">
    <div class="nav-left">
        <a href="homepage.php" style="font-size: 1.6rem; font-weight: 800; background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-heartbeat" style="color: var(--primary-color);"></i>
            HealthCare
        </a>
    </div>
    <div class="nav-right">
      <div class="user-info">
          <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
      </div>
      <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile" class="user-icon" id="profileToggle">
    </div>
  </header>

  <main class="content-area-landing" id="mainContent" style="padding: 0 8%; max-width: var(--container-max-width); margin: 0 auto;">
      <!-- Hero Section -->
      <section class="hero-section">
          <h1 class="hero-title">Your Health, <span style="color: var(--primary-color);">Our Commitment.</span></h1>
          <p class="hero-subtitle">A seamless, unified platform for professional medical care, secure consultations, and your complete health history.</p>
      </section>

      <!-- Navigation Hub -->
      <div class="hub-grid">
          <a href="../messaging/messaging.php" class="hub-card">
              <div class="panel-card">
                  <div class="icon-holder bg-primary-light">
                      <i class="fas fa-comment-medical text-primary" style="font-size: 2rem;"></i>
                  </div>
                  <div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 12px; font-weight: 700;">Secure Consultations</h3>
                    <p class="text-muted" style="line-height: 1.6;">Chat directly with specialists and receive medical guidance from home.</p>
                  </div>
              </div>
          </a>

          <a href="../patient/book-appointment.php" class="hub-card">
              <div class="panel-card">
                  <div class="icon-holder bg-success-light">
                      <i class="fas fa-calendar-check text-success" style="font-size: 2rem;"></i>
                  </div>
                  <div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 12px; font-weight: 700;">Book Appointments</h3>
                    <p class="text-muted" style="line-height: 1.6;">Easily schedule appointments with your preferred doctors.</p>
                  </div>
              </div>
          </a>
      </div>

        <!-- Services Section -->
        <section class="services-section">
            <h2 class="section-title" style="text-align: center; font-size: 2.5rem; font-weight: 800; margin-bottom: 64px;">Comprehensive Healthcare Services</h2>
            <div class="grid grid-3" style="gap: 32px;">
                <div class="service-card">
                    <div class="icon-circle bg-primary-light">
                        <i class="fas fa-file-medical text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h4>Digital Health Records</h4>
                    <p class="text-muted">Access and manage your complete medical history, prescriptions, and lab results in one secure place.</p>
                </div>
                <div class="service-card">
                    <div class="icon-circle bg-success-light">
                        <i class="fas fa-video text-success" style="font-size: 2rem;"></i>
                    </div>
                    <h4>Video Consultations</h4>
                    <p class="text-muted">Connect with doctors face-to-face through high-quality, secure video calls for a personal touch.</p>
                </div>
                <div class="service-card">
                    <div class="icon-circle bg-info-light">
                        <i class="fas fa-pills text-info" style="font-size: 2rem;"></i>
                    </div>
                    <h4>E-Prescriptions</h4>
                    <p class="text-muted">Receive digital prescriptions from your doctor, which can be easily accessed and managed online.</p>
                </div>
            </div>
        </section>

      <!-- Quick Actions Panel -->
        <section style="margin-bottom: 96px;">
            <div class="panel-card" style="padding: 48px;">
                <h3 class="section-title mb-4" style="font-size: 2rem; text-align: center; font-weight: 800;"><i class="fas fa-rocket text-primary"></i> Quick Actions</h3>
                <div class="quick-actions-grid" style="margin-top: 40px;">
                    <a href="../patient/book-appointment.php" class="action-btn">
                        <i class="fas fa-calendar-plus text-primary"></i>
                        <span>Book Now</span>
                    </a>
                    <a href="../patient/dashboard.php" class="action-btn">
                        <i class="fas fa-file-invoice text-info"></i>
                        <span>My Records</span>
                    </a>
                    <a href="../patient/settings.php" class="action-btn">
                        <i class="fas fa-user-cog text-warning"></i>
                        <span>Profile</span>
                    </a>
                    <a href="../messaging/messaging.php" class="action-btn">
                        <i class="fas fa-hands-helping text-error"></i>
                        <span>Get Help</span>
                    </a>
                </div>
            </div>
        </section>
  </main>

  <div class="profile-overlay" id="profileOverlay">
    <div class="profile-content">
      <div class="profile-pic-wrapper" style="position: relative; margin-bottom: 24px;">
          <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay" class="profile-overlay-pic">
          <label for="profilePicInput" style="position: absolute; bottom: 30px; right: 10px; background: var(--primary-color); color: #fff; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid #fff;">
              <i class="fas fa-camera"></i>
          </label>
      </div>
      <h3 style="font-size: 1.5rem; font-weight: 700;"><?php echo htmlspecialchars($username); ?></h3>

      
      <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
        <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
      </form>
      <div id="uploadMessage"></div>

      <hr style="margin: 24px 0;">
      <ul>
        <li><a href="<?php echo $dashUrl; ?>"><i class="fas fa-user-circle" style="margin-right: 12px;"></i> My Profile</a></li>        
        <li><a href="<?php echo $dashUrl; ?>"><i class="fas fa-chart-line" style="margin-right: 12px;"></i> Dashboard</a></li>
        <li><a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt" style="margin-right: 12px;"></i> Logout</a></li>
      </ul>
      <button class="close-btn" id="closeProfile">Close Panel</button>
    </div>
  </div>

  <script src="../assets/js/profile-overlay.js"></script>

</body>
</html>
