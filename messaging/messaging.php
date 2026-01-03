<?php
include_once '../includes/db.php';
include_once '../includes/auth.php';

redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$logged_in_patient_id = null;
$logged_in_doctor_id = null;

if ($role === 'patient') {
    $stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $logged_in_patient_id = $row['id'];
    }
    $stmt->close();
} elseif ($role === 'doctor') {
    $stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $logged_in_doctor_id = $row['id'];
    }
    $stmt->close();
}

$doctor_id_param = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
$patient_id_param = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;
$appointment_id_param = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : null;

$other_participant_user_id = null;
$target_doctor_id = null;
$target_patient_id = null;

if ($role === 'patient' && $doctor_id_param) {
    $target_doctor_id = $doctor_id_param;
    $target_patient_id = $logged_in_patient_id;
    $stmt = $conn->prepare("SELECT user_id FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctor_id_param);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $other_participant_user_id = $row['user_id'];
    }
    $stmt->close();
} elseif ($role === 'doctor' && $patient_id_param) {
    $target_patient_id = $patient_id_param;
    $target_doctor_id = $logged_in_doctor_id;
    $stmt = $conn->prepare("SELECT user_id FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patient_id_param);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $other_participant_user_id = $row['user_id'];
    }
    $stmt->close();
}

$other_participant_name = null;
if ($other_participant_user_id) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $other_participant_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $other_participant_name = $row['name'];
    }
    $stmt->close();
}

if ($other_participant_user_id === null && ($doctor_id_param !== null || $patient_id_param !== null)) {
    $_SESSION['error_message'] = "Target user for messaging not found.";
    header("Location: ../index.php");
    exit();
}

$dashboard_link = "../includes/homepage.php"; 
if ($role === 'doctor') {
    $dashboard_link = "../doctor/dashboard.php";
} elseif ($role === 'patient') {
    $dashboard_link = "../patient/dashboard.php";
} elseif ($role === 'admin') {
    $dashboard_link = "../admin/dashboard.php";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | HealthCare</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/messaging.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="messaging-page">
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">
                <i class="fas fa-bars"></i>
                <span>Menu</span>
            </button>
            <a href="<?php echo $dashboard_link; ?>" style="font-size: 1.4rem; font-weight: 800; background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-heartbeat" style="color: var(--primary-color);"></i>
                <?php echo $role === 'admin' ? 'HealthCare Admin' : 'HealthCare Chat'; ?>
            </a>
        </div>
        <div class="nav-right">
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            </div>
            <img src="../<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="<?php echo $role; ?>Sidebar">
            <?php if ($role === 'admin'): ?>
                <h3>System Management</h3>
                <ul>
                    <li><a href="../admin/dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="../admin/manage-admins.php"><i class="fas fa-user-shield"></i> Admins</a></li>
                    <li><a href="../admin/manage-doctors.php"><i class="fas fa-user-md"></i> Doctors</a></li>
                    <li><a href="../admin/manage-patients.php"><i class="fas fa-user-injured"></i> Patients</a></li>
                    <li><a href="messaging.php" class="active"><i class="fas fa-comment-medical"></i> Consultations</a></li>
                </ul>
            <?php elseif ($role === 'doctor'): ?>
                <h3>Medical Menu</h3>
                <ul>
                    <li><a href="../doctor/dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="../doctor/cancelled-appointments.php"><i class="fas fa-calendar-times"></i> Cancelled</a></li>
                    <li><a href="messaging.php" class="active"><i class="fas fa-comment-medical"></i> Consultations</a></li>
                </ul>
            <?php elseif ($role === 'patient'): ?>
                <h3>Patient Menu</h3>
                <ul>
                    <li><a href="../patient/dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="../patient/book-appointment.php"><i class="fas fa-calendar-plus"></i> Book Appointment</a></li>
                    <li><a href="../patient/cancelled-appointments.php"><i class="fas fa-calendar-times"></i> Cancelled</a></li>
                    <li><a href="messaging.php" class="active"><i class="fas fa-comments"></i> Messages</a></li>
                </ul>
            <?php endif; ?>
            
        </aside>

        <main class="content-area" style="padding: 0; flex: 1; height: calc(100vh - var(--header-height)); overflow: hidden; display: flex; flex-direction: column;">
            <div class="messaging-wrapper" style="padding: 24px; height: 100%; display: flex; flex-direction: column;">
                <div class="messaging-container" style="flex: 1; min-height: 0;">
                    <aside class="conversations-sidebar">
                        <div class="sidebar-header">
                            <h3><?php echo $role === 'admin' ? 'All Users' : 'Recent Chats'; ?></h3>
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="convSearch" placeholder="<?php echo $role === 'admin' ? 'Search users...' : 'Search conversations...'; ?>">
                            </div>
                        </div>
                        <div class="conversations-list" id="conversationItems">
                            <!-- Conversations will be loaded here via JS -->
                        </div>
                    </aside>

                    <main class="chat-area">
                        <div class="chat-header" id="chatHeader">
                            <div class="placeholder-header">
                                <i class="fas fa-comments"></i>
                                <span>Select a conversation to start chatting</span>
                            </div>
                        </div>

                        <div class="chat-messages" id="chatMessages">
                            <div class="welcome-chat">
                                <div class="icon-circle">
                                    <i class="fas fa-comment-medical"></i>
                                </div>
                                <h2>Your Secure Consultations</h2>
                                <p>End-to-end encrypted messaging with your medical specialists.</p>
                            </div>
                        </div>

                        <div class="chat-input-area" id="chatInputArea" style="display: none;">
                            <div id="imagePreviewContainer" style="display: none;">
                                <img id="imagePreview" src="#" alt="Preview">
                                <button id="clearImagePreview"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="input-controls">
                                <input type="file" id="imageInput" accept="image/*" style="display: none;">
                                <button class="icon-btn" id="uploadImageBtn" title="Upload Image"><i class="fas fa-image"></i></button>
                                <div class="input-wrapper">
                                    <input type="text" id="messageInput" placeholder="Type your message here...">
                                </div>
                                <button class="send-btn" id="sendMessageBtn"><i class="fas fa-paper-plane"></i></button>
                            </div>
                        </div>
                    </main>
                </div>
            </div>

            <script>
                // Local variables for messaging
                window.currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
                window.currentUserProfilePic = "../<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>";
                window.otherParticipantUserId = <?php echo json_encode($other_participant_user_id); ?>;
                window.otherParticipantName = <?php echo json_encode($other_participant_name); ?>;
                window.initialAppointmentId = <?php echo json_encode($appointment_id_param); ?>;
                window.targetDoctorId = <?php echo json_encode($target_doctor_id); ?>;
                window.targetPatientId = <?php echo json_encode($target_patient_id); ?>;
                window.userRole = <?php echo json_encode($role); ?>;
                window.BASE_URL = '/';
            </script>
            <script src="../assets/js/messaging.js"></script>
            <script>
                if(window.otherParticipantUserId) {
                    const inputArea = document.getElementById('chatInputArea');
                    if(inputArea) inputArea.style.display = 'block';
                }
            </script>
        </main>
    </div>

    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <div class="profile-pic-wrapper" style="position: relative; margin-bottom: 24px;">
                <img src="../<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay" class="profile-overlay-pic" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-light);">
            </div>
            <h3 style="font-size: 1.5rem; font-weight: 700;"><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
            <p class="text-muted" style="text-transform: capitalize;"><?php echo htmlspecialchars($role); ?> Account</p>
            
            <hr style="margin: 24px 0;">
            <ul>
                <li><a href="../patient/profile.php" style="padding: 12px; border-radius: var(--radius-md);"><i class="fas fa-user-circle"></i> My Profile</a></li>        
                <li><a href="<?php echo $dashboard_link; ?>" style="padding: 12px; border-radius: var(--radius-md);"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="../auth/logout.php" class="logout-btn" style="color: var(--error); padding: 12px; border-radius: var(--radius-md);"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
            <button class="close-btn" id="closeProfile">Close Panel</button>
        </div>
    </div>

    <!-- These scripts only run on full page load -->
    <script src="../assets/js/ui-ux.js"></script>
    <script src="../assets/js/profile-overlay.js"></script>
</body>
</html>
