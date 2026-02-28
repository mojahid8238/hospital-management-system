<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_admin();

$doctor_id = $_GET['id'] ?? null;
$doctor = null;
$message = '';

if (!$doctor_id) {
    header("Location: manage-doctors.php");
    exit();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_GET['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $username = $_POST['username'] ?? '';
    // $specialization_name = $_POST['specialization'] ?? ''; // We use ID or look it up
    $specialization_name = $_POST['specialization'] ?? '';
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

    // Get specialization_id
    $specialization_id = null;
    $stmt_spec = $conn->prepare("SELECT id FROM specializations WHERE name = ?");
    $stmt_spec->bind_param("s", $specialization_name);
    $stmt_spec->execute();
    $stmt_spec->bind_result($specialization_id);
    $stmt_spec->fetch();
    $stmt_spec->close();

    if ($doctor_id && $specialization_id) {
        // Update Doctors Table
        $stmt = $conn->prepare("UPDATE doctors SET name = ?, specialization_id = ?, phone = ?, email = ?, degrees = ?, schedule = ? WHERE id = ?");
        $stmt->bind_param("sissssi", $name, $specialization_id, $phone, $email, $degrees, $schedule, $doctor_id);
        
        if ($stmt->execute()) {
             // Also update Users table for username/name changes
             // First fetch user_id
             $stmt_u = $conn->prepare("SELECT user_id FROM doctors WHERE id = ?");
             $stmt_u->bind_param("i", $doctor_id);
             $stmt_u->execute();
             $res_u = $stmt_u->get_result();
             if($row_u = $res_u->fetch_assoc()){
                 $u_id = $row_u['user_id'];
                 $stmt_update_user = $conn->prepare("UPDATE users SET username = ?, name = ? WHERE id = ?");
                 $stmt_update_user->bind_param("ssi", $username, $name, $u_id);
                 $stmt_update_user->execute();
                 $stmt_update_user->close();
             }
             $stmt_u->close();

             $message = "<div class='alert alert-success'>Doctor updated successfully!</div>";
        } else {
             $message = "<div class='alert alert-danger'>Error updating doctor: " . $conn->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-danger'>Invalid data provided. Specialization not found or ID missing.</div>";
    }
}

// Fetch doctor details
$stmt = $conn->prepare("SELECT d.id, d.name, s.name as specialization, d.phone, d.email, u.username, u.id as user_id, d.profile_pic, d.degrees, d.schedule FROM doctors d JOIN users u ON d.user_id = u.id LEFT JOIN specializations s ON d.specialization_id = s.id WHERE d.id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $doctor = $result->fetch_assoc();
    $_SESSION['profile_pic'] = $doctor['profile_pic'] ?? 'assets/images/default-avatar.png';
} else {
    $message = "<p style='color: red;'>Doctor not found.</p>";
    $doctor_id = null;
}
    $stmt->close();

    // Fetch all specializations for the dropdown
    $specializations_result = $conn->query("SELECT id, name FROM specializations ORDER BY name ASC");
    $specializations = [];
    while ($row = $specializations_result->fetch_assoc()) {
        $specializations[] = $row;
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor | Health Care Admin</title>
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
                Health Care Admin
            </a>
        </div>
        <div class="nav-right">
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['name'] ?? 'Administrator'); ?></span>
            </div>
            <img src="../<?php echo htmlspecialchars($profilePic); ?>?t=<?php echo time(); ?>" alt="Profile" class="user-icon" id="profileToggle">
        </div>
    </header>

    <div class="main-wrapper">
        <aside class="sidebar" id="adminSidebar">
            <h3>System Management</h3>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="manage-admins.php" class="sidebar-link" data-target="manage-admins.php"><i class="fas fa-user-shield"></i> Admins</a></li>
                <li><a href="manage-doctors.php" class="active sidebar-link" data-target="manage-doctors.php"><i class="fas fa-user-md"></i> Doctors</a></li>
                <li><a href="manage-patients.php" class="sidebar-link" data-target="manage-patients.php"><i class="fas fa-user-injured"></i> Patients</a></li>
                <li><a href="reports.php" class="sidebar-link" data-target="reports.php"><i class="fas fa-file-invoice-dollar"></i> Reports</a></li>
            </ul>
        </aside>

        <main class="content-area" id="mainContent">
            <div class="container">
                <div class="welcome-section">
                    <h2>Edit Medical Practitioner</h2>
                    <p>Update doctor credentials and professional availability.</p>
                </div>

                <?php echo $message; ?>

                <?php if ($doctor): ?>
                    <div class="edit-form-card">
                        <form action="edit-doctor.php?id=<?php echo $doctor['id']; ?>" method="POST">
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
                                    <select id="specialization" name="specialization" required>
                                        <?php foreach ($specializations as $spec): ?>
                                            <option value="<?php echo htmlspecialchars($spec['name']); ?>" <?php echo ($doctor['specialization'] == $spec['name']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($spec['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
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
                                <button type="submit" class="btn-update"><i class="fas fa-save"></i> Update Doctor</button>
                                <a href="manage-doctors.php" class="btn-cancel">Discard Changes</a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="panel-card text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <p>Doctor details could not be loaded. <a href="manage-doctors.php">Go back to Manage Doctors</a></p>
                    </div>
                <?php endif; ?>
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
            <h3><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></h3>
            <p>System Administrator</p>
            
            <form id="profilePicUploadForm" action="../auth/upload_profile_pic.php" method="POST" enctype="multipart/form-data" style="display: none;">
                <input type="file" name="profile_pic" id="profilePicInput" accept="image/*">
            </form>
            <div id="uploadMessage"></div>
            
            <hr>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-chart-line" style="margin-right: 12px;"></i> Dashboard</a></li>
                <li><a href="../auth/logout.php" style="color: var(--error);"><i class="fas fa-sign-out-alt" style="margin-right: 12px;"></i> Logout</a></li>
            </ul>
            <button class="close-btn" id="closeProfile">Close Panel</button>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
        const BASE_URL = '/';
    </script>
    <script src="../assets/js/profile-overlay.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
    <script src="../assets/js/ui-ux.js"></script>
</body>
</html>