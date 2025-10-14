<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (is_logged_in()) {
    header("Location: ../index.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = htmlspecialchars(trim($_POST['role'] ?? ''));
    $fullname = htmlspecialchars(trim($_POST['fullname'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

    // Validation
    if (empty($username) || empty($password) || empty($confirm_password) || empty($role) || empty($fullname) || empty($email)) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        // Check if username exists in BOTH tables
        $stmt_users = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_users->bind_param("s", $username);
        $stmt_users->execute();
        $stmt_users->store_result();
        
        $stmt_admin = $conn->prepare("SELECT id FROM admin WHERE username = ?");
        $stmt_admin->bind_param("s", $username);
        $stmt_admin->execute();
        $stmt_admin->store_result();

        if ($stmt_users->num_rows > 0 || $stmt_admin->num_rows > 0) {
            $error_message = "Username already exists. Please choose a different one.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            if ($role === 'admin') {
                // Insert only into admin
                $stmt = $conn->prepare("INSERT INTO admin (name, email, username, password) VALUES (?, ?, ?, ?)");
                if (!$stmt) {
                    $error_message = "Error preparing admin insert: " . $conn->error;
                } else {
                    $stmt->bind_param("ssss", $fullname, $email, $username, $hashed_password);
                    if ($stmt->execute()) {
                        $success_message = "Admin registration successful. <a href=\"login.php\">Login here</a>.";
                    } else {
                        $error_message = "Error inserting admin: " . $stmt->error;
                    }
                    $stmt->close();
                }
            } else {
                // Insert into users for non-admin roles
                $stmt = $conn->prepare("INSERT INTO users (username, password, role, name, email) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt) {
                    $error_message = "Error preparing user insert: " . $conn->error;
                } else {
                    $stmt->bind_param("sssss", $username, $hashed_password, $role, $fullname, $email);
                    if ($stmt->execute()) {
                        $success_message = ucfirst($role) . " registration successful. <a href=\"login.php\">Login here</a>.";
                    } else {
                        $error_message = "Error inserting user: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }

        // Close both SELECT statements
        $stmt_users->close();
        $stmt_admin->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign Up Page</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/login-signup.css">
</head>
<body>
  <div class="floating-bg">
    <!-- Background visuals (optional) -->
  </div>

  <div class="form-container">
    <div class="form-box">
      <h2>Sign Up</h2>

      <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
      <?php endif; ?>

      <?php if (!empty($success_message)): ?>
        <div class="success-message"><?php echo $success_message; ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="input-group">
          <label for="fullname">Full Name</label>
          <input type="text" id="fullname" name="fullname" placeholder="Enter your name" required
                 value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>">
        </div>
        <div class="input-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="Enter your email" required
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="input-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" placeholder="Enter your username" required
                 value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>
        <div class="input-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Create a password" required>
        </div>
        <div class="input-group">
          <label for="confirm-password">Confirm Password</label>
          <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm your password" required>
        </div>
        <div class="input-group">
          <label for="role">Role</label>
          <select id="role" name="role" required>
            <option value="">Select Role</option>
            <option value="patient" <?php if (($_POST['role'] ?? '') == 'patient') echo 'selected'; ?>>Patient</option>
            <option value="doctor" <?php if (($_POST['role'] ?? '') == 'doctor') echo 'selected'; ?>>Doctor</option>
            <option value="admin" <?php if (($_POST['role'] ?? '') == 'admin') echo 'selected'; ?>>Admin</option>
          </select>
        </div>
        <button type="submit" class="form-btn">Sign Up</button>
        <p>Already have an account? <a href="login.php">Log in</a></p>
      </form>
    </div>
  </div>
</body>
</html>

<?php $conn->close(); ?>
