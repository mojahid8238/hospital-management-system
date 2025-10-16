<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (is_logged_in()) {
    if (is_admin()) {
        header("Location: ../admin/dashboard.php");
    } elseif (is_doctor()) {
        header("Location: ../doctor/dashboard.php");
    } else {
        header("Location: ../includes/homepage.php");
    }
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $hashed_password, $role);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                if ($role === 'admin') {
                    $stmt_admin = $conn->prepare("SELECT status FROM admin WHERE user_id = ?");
                    $stmt_admin->bind_param("i", $user_id);
                    $stmt_admin->execute();
                    $stmt_admin->bind_result($status);
                    $stmt_admin->fetch();
                    $stmt_admin->close();

                    if ($status === 'approved') {
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['role'] = $role;
                        header("Location: ../admin/dashboard.php");
                        exit();
                    } else {
                        $error_message = "Your admin account is pending approval.";
                    }
                } else {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $role;

                    if ($role === 'doctor') {
                        header("Location: ../doctor/dashboard.php");
                    } elseif ($role === 'patient') {
                        header("Location: ../includes/homepage.php");
                    } else {
                        header("Location: ../includes/homepage.php");
                    }
                    exit();
                }
            } else {
                $error_message = "Invalid username or password.";
            }
        } else {
            $error_message = "Invalid username or password.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login Page</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="../assets/css/login-signup.css">

</head>
<body>
  <div class="floating-bg">
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="geometric-shape"></div>
    <div class="geometric-shape"></div>
    <div class="geometric-shape"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
  </div>

  <div class="form-container">
    <div class="form-box">
      <h2>Login</h2>
      <?php if (!empty($error_message)): ?>
        <div class="error-message">
          <?php echo $error_message; ?>
        </div>
      <?php endif; ?>
      <form method="POST">
        <div class="input-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" placeholder="Enter your username" required>
        </div>
        <div class="input-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="form-btn">Log In</button>
        <p>Don't have an account? <a href="
        register.php">Sign up</a></p>
      </form>
    </div>
  </div>
</body>
</html>
<?php $conn->close(); ?>