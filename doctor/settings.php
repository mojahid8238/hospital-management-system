<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_doctor();

$user_id = $_SESSION['user_id'];
$message = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $username = $_POST['username'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $degrees = $_POST['degrees'] ?? '';

    // Process Schedule
    $schedule = '';
    if (isset($_POST['days']) && is_array($_POST['days']) && !empty($_POST['start_time']) && !empty($_POST['end_time'])) {
        $days_str = implode(', ', $_POST['days']);
        $start_fmt = date('h:i A', strtotime($_POST['start_time']));
        $end_fmt = date('h:i A', strtotime($_POST['end_time']));
        $schedule = "$days_str: $start_fmt - $end_fmt";
    } else {
         $schedule = $_POST['schedule_text'] ?? ''; // Fallback
    }

    // Update Doctors Table
    $stmt = $conn->prepare("UPDATE doctors SET name = ?, phone = ?, email = ?, degrees = ?, schedule = ? WHERE user_id = ?");
    $stmt->bind_param("sssssi", $name, $phone, $email, $degrees, $schedule, $user_id);
    
    if ($stmt->execute()) {
            // Also update Users table for username/name changes
            $stmt_update_user = $conn->prepare("UPDATE users SET username = ?, name = ? WHERE id = ?");
            $stmt_update_user->bind_param("ssi", $username, $name, $user_id);
            if($stmt_update_user->execute()) {
                 $_SESSION['name'] = $name; // Update session name
            }
            $stmt_update_user->close();

            $message = "<div class='alert alert-success'>Profile updated successfully!</div>";
    } else {
            $message = "<div class='alert alert-danger'>Error updating profile: " . $conn->error . "</div>";
    }
    $stmt->close();
}

// Fetch doctor details
$stmt = $conn->prepare("SELECT d.id, d.name, s.name as specialization, d.phone, d.email, u.username, d.profile_pic, d.degrees, d.schedule FROM doctors d JOIN users u ON d.user_id = u.id LEFT JOIN specializations s ON d.specialization_id = s.id WHERE d.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $doctor = $result->fetch_assoc();
    $_SESSION['profile_pic'] = $doctor['profile_pic'] ?? 'assets/images/default-avatar.png';
} else {
    // Should not happen for a logged in doctor
    die("Doctor profile not found.");
}
$stmt->close();


$rawProfilePic = $_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png';
$profilePic = preg_replace('#^\\.\\./#', '', $rawProfilePic); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | Health Care</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo.ico">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .edit-form-card {
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            padding: 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            max-width: 800px;
            margin: 0 auto;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.9rem;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            background: var(--bg-hover);
            transition: var(--transition-base);
            font-family: inherit;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .form-actions {
            margin-top: 32px;
            display: flex;
            gap: 16px;
            border-top: 1px solid var(--border-color);
            padding-top: 24px;
        }
        .btn-update {
            background: var(--gradient-primary);
            color: #fff;
            padding: 12px 32px;
            border-radius: var(--radius-md);
            border: none;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition-base);
        }
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .btn-cancel {
            background: var(--bg-color);
            color: var(--text-main);
            padding: 12px 32px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 700;
            transition: var(--transition-base);
            display: inline-block;
        }
        .btn-cancel:hover {
            background: var(--border-color);
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">
                <i class="fas fa-bars"></i>
                <span>Menu</span>
            </button>
            <a href="dashboard.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; font-weight: 800; font-size: 1.25rem;">
                <img src="../assets/images/logo.png" alt="Logo" style="height: 35px; border-radius: 5px;">
                Health Care
            </a>
        </div>
        <div class="nav-right">
            <div class="user-info">
                <span>Dr. <?php echo htmlspecialchars($_SESSION['name'] ?? 'Doctor'); ?></span>
            </div>
            <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="doctorSidebar">
            <h3>Medical Menu</h3>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="cancelled-appointments.php"><i class="fas fa-calendar-times"></i> Cancelled</a></li>
                <li><a href="../messaging/messaging.php"><i class="fas fa-comments"></i> Messages</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container">
                <div class="welcome-section">
                    <h2>Account Settings</h2>
                    <p>Manage your professional profile and account details.</p>
                </div>

                <?php echo $message; ?>

                <div class="edit-form-card">
                    <form action="settings.php" method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name"><i class="fas fa-user-md"></i> Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($doctor['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="username"><i class="fas fa-id-badge"></i> Username</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($doctor['username']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="specialization"><i class="fas fa-stethoscope"></i> Specialization</label>
                                <input type="text" value="<?php echo htmlspecialchars($doctor['specialization'] ?? 'N/A'); ?>" readonly style="cursor: not-allowed; opacity: 0.7;">
                                <small class="text-muted">Contact Admin to change specialization.</small>
                            </div>
                            <div class="form-group">
                                <label for="degrees"><i class="fas fa-graduation-cap"></i> Degrees</label>
                                <input type="text" id="degrees" name="degrees" value="<?php echo htmlspecialchars($doctor['degrees'] ?? ''); ?>" placeholder="e.g. MBBS, MD" required>
                            </div>
                            
                            <?php
                                // Parse Schedule
                                // Format: "Mon, Wed, Fri: 09:00 AM - 05:00 PM"
                                $sch_days = [];
                                $sch_start = '';
                                $sch_end = '';
                                
                                $raw_schedule = $doctor['schedule'] ?? '';
                                if(strpos($raw_schedule, ':') !== false) {
                                    $parts = explode(':', $raw_schedule);
                                    $day_part = trim($parts[0]); // "Mon, Wed, Fri"
                                    $time_part = trim($parts[1]); // "09:00 AM - 05:00 PM"
                                    
                                    $sch_days = array_map('trim', explode(',', $day_part));
                                    
                                    if(strpos($time_part, '-') !== false) {
                                        $times = explode('-', $time_part);
                                        $sch_start = date('H:i', strtotime(trim($times[0])));
                                        $sch_end = date('H:i', strtotime(trim($times[1])));
                                    }
                                }
                            ?>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600;"><i class="fas fa-clock"></i> Consultation Schedule</label>
                                <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 12px;">
                                    <?php 
                                    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                    foreach($days as $day): 
                                        $checked = in_array($day, $sch_days) ? 'checked' : '';
                                    ?>
                                        <label style="display: flex; align-items: center; gap: 6px; font-weight: 500; cursor: pointer;">
                                            <input type="checkbox" name="days[]" value="<?php echo $day; ?>" <?php echo $checked; ?>> <?php echo $day; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <div style="display: flex; gap: 16px;">
                                    <div style="flex: 1;">
                                        <small style="display: block; margin-bottom: 4px; color: var(--text-muted);">Start Time</small>
                                        <input type="time" name="start_time" value="<?php echo $sch_start; ?>" required style="padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border-color); width: 100%;">
                                    </div>
                                    <div style="flex: 1;">
                                        <small style="display: block; margin-bottom: 4px; color: var(--text-muted);">End Time</small>
                                        <input type="time" name="end_time" value="<?php echo $sch_end; ?>" required style="padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border-color); width: 100%;">
                                    </div>
                                </div>
                                <!-- Hidden fallback to preserve old data if structure changes -->
                                <input type="hidden" name="schedule_text" value="<?php echo htmlspecialchars($raw_schedule); ?>">
                            </div>

                            <div class="form-group">
                                <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($doctor['phone']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($doctor['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-update"><i class="fas fa-save"></i> Save Changes</button>
                            <a href="dashboard.php" class="btn-cancel">Back to Dashboard</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Profile Overlay -->
    <div class="profile-overlay" id="profileOverlay">
        <div class="profile-content">
            <div class="profile-pic-wrapper" style="position: relative;">
                <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profileImageDisplay" class="profile-overlay-pic">
                 <label for="profilePicInput" style="position: absolute; bottom: 30px; right: 10px; background: var(--primary-color); color: #fff; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid #fff;">
                    <i class="fas fa-camera"></i>
                </label>
            </div>
            <h3>Dr. <?php echo htmlspecialchars($_SESSION['name'] ?? 'Doctor'); ?></h3>
            <p>Medical Practitioner</p>
            
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display: none;">
            </form>
            <div id="uploadMessage"></div>
            
            <hr>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-chart-line" style="margin-right: 12px;"></i> Dashboard</a></li>
                <li><a href="settings.php"><i class="fas fa-user-cog" style="margin-right: 12px;"></i> Account Settings</a></li>
                <li><a href="../auth/logout.php" style="color: var(--error);"><i class="fas fa-sign-out-alt" style="margin-right: 12px;"></i> Logout</a></li>
            </ul>
            <button class="close-btn" id="closeProfile">Close Panel</button>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
        const BASE_URL = '/';
    </script>
    <script src="../assets/js/ui-ux.js"></script>
    <script src="../assets/js/profile-overlay.js"></script>
</body>
</html>
