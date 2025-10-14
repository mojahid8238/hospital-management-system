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

    // Role-specific fields
    $specialization = htmlspecialchars(trim($_POST['specialization'] ?? ''));
    $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $date_of_birth = $_POST['date_of_birth'] ?? null;  // YYYY-MM-DD or null
    $gender = $_POST['gender'] ?? null;
    $address = htmlspecialchars(trim($_POST['address'] ?? ''));

    // Basic validation
    if (empty($username) || empty($password) || empty($confirm_password) || empty($role) || empty($fullname) || empty($email)) {
        $error_message = "All required fields are mandatory.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        // Check username uniqueness in users table
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error_message = "Username already exists. Please choose a different one.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user first
            $stmt_user = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt_user->bind_param("sss", $username, $hashed_password, $role);
            if ($stmt_user->execute()) {
                $user_id = $conn->insert_id;

                if ($role === 'admin') {
                    // Insert into admin table
                    $stmt_admin = $conn->prepare("INSERT INTO admin (user_id, name, email) VALUES (?, ?, ?)");
                    $stmt_admin->bind_param("iss", $user_id, $fullname, $email);
                    if (!$stmt_admin->execute()) {
                        $error_message = "Error inserting admin details: " . $stmt_admin->error;
                    }
                    $stmt_admin->close();

                } elseif ($role === 'doctor') {
                    $stmt_doc = $conn->prepare("INSERT INTO doctors (user_id, name, specialization, phone, email) VALUES (?, ?, ?, ?, ?)");
                    $stmt_doc->bind_param("issss", $user_id, $fullname, $specialization, $phone, $email);
                    if (!$stmt_doc->execute()) {
                        $error_message = "Error inserting doctor details: " . $stmt_doc->error;
                    }
                    $stmt_doc->close();

                } elseif ($role === 'patient') {
                    $stmt_pat = $conn->prepare("INSERT INTO patients (user_id, name, date_of_birth, gender, address, phone, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt_pat->bind_param("issssss", $user_id, $fullname, $date_of_birth, $gender, $address, $phone, $email);
                    if (!$stmt_pat->execute()) {
                        $error_message = "Error inserting patient details: " . $stmt_pat->error;
                    }
                    $stmt_pat->close();
                }

                if (empty($error_message)) {
                    $success_message = ucfirst($role) . " registration successful. <a href=\"login.php\">Login here</a>.";
                }

            } else {
                $error_message = "Error inserting user: " . $stmt_user->error;
            }
            $stmt_user->close();
        }

        $stmt_check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sign Up</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/css/login-signup.css" />
  <style>
    .role-fields { display: none; }
  </style>
  <script>
    function toggleRoleFields() {
      const role = document.getElementById('role').value;
      document.querySelectorAll('.role-fields').forEach(el => el.style.display = 'none');
      if (role === 'doctor') {
        document.getElementById('doctor-fields').style.display = 'block';
      } else if (role === 'patient') {
        document.getElementById('patient-fields').style.display = 'block';
      }
    }
    window.onload = () => {
      toggleRoleFields();
      document.getElementById('role').addEventListener('change', toggleRoleFields);
    }
  </script>
</head>
<body>
  <div class="form-container">
    <div class="form-box">
      <h2>Sign Up</h2>

      <?php if (!empty($error_message)): ?>
        <div class="error-message" style="color: red;"><?= $error_message ?></div>
      <?php endif; ?>

      <?php if (!empty($success_message)): ?>
        <div class="success-message" style="color: green;"><?= $success_message ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="input-group">
          <label for="fullname">Full Name</label>
          <input type="text" id="fullname" name="fullname" required value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" />
        </div>

        <div class="input-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
        </div>

        <div class="input-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" />
        </div>

        <div class="input-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required />
        </div>

        <div class="input-group">
          <label for="confirm-password">Confirm Password</label>
          <input type="password" id="confirm-password" name="confirm_password" required />
        </div>

        <div class="input-group">
          <label for="role">Role</label>
          <select id="role" name="role" required>
            <option value="">Select Role</option>
            <option value="patient" <?= (($_POST['role'] ?? '') === 'patient') ? 'selected' : '' ?>>Patient</option>
            <option value="doctor" <?= (($_POST['role'] ?? '') === 'doctor') ? 'selected' : '' ?>>Doctor</option>
            <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
          </select>
        </div>

        <!-- Doctor-specific -->
        <div id="doctor-fields" class="role-fields">
          <div class="input-group">
            <label for="specialization">Specialization</label>
            <input type="text" id="specialization" name="specialization" value="<?= htmlspecialchars($_POST['specialization'] ?? '') ?>" />
          </div>
          <div class="input-group">
            <label for="phone_doc">Phone</label>
            <input type="text" id="phone_doc" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" />
          </div>
        </div>

        <!-- Patient-specific -->
        <div id="patient-fields" class="role-fields">
          <div class="input-group">
            <label for="date_of_birth">Date of Birth</label>
            <input type="date" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>" />
          </div>
          <div class="input-group">
            <label for="gender">Gender</label>
            <select id="gender" name="gender">
              <option value="">Select Gender</option>
              <option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
              <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
              <option value="Other" <?= (($_POST['gender'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
            </select>
          </div>
          <div class="input-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" />
          </div>
          <div class="input-group">
            <label for="phone_pat">Phone</label>
            <input type="text" id="phone_pat" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" />
          </div>
        </div>

        <button type="submit" class="form-btn">Sign Up</button>
        <p>Already have an account? <a href="login.php">Log in</a></p>
      </form>
    </div>
  </div>
</body>
</html>

<?php $conn->close(); ?>
