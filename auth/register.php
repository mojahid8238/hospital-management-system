<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/auth.php';

if (is_logged_in()) {
    header("Location: ../index.php");
    exit();
}

$error_message = '';
$success_message = '';

// Fetch all specializations
$specializations = [];
$result_spec = $conn->query("SELECT id, name FROM specializations");
if ($result_spec) {
    while ($row_spec = $result_spec->fetch_assoc()) {
        $specializations[] = $row_spec;
    }
}

if (empty($specializations)) {
    $error_message = "Could not fetch specializations. Please run the latest sql/hospital_db.sql file.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = htmlspecialchars(trim($_POST['role'] ?? ''));
    $fullname = htmlspecialchars(trim($_POST['fullname'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

    // Role-specific fields
    $specialization = htmlspecialchars(trim($_POST['specialization'] ?? ''));
    $phone = '';
    if ($role === 'doctor') {
        $phone = htmlspecialchars(trim($_POST['phone_doc'] ?? ''));
    } elseif ($role === 'patient') {
        $phone = htmlspecialchars(trim($_POST['phone_pat'] ?? ''));
    }
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
            if ($role === 'admin') {
                // Check for existing admin email
                $stmt_check_admin_email = $conn->prepare("SELECT id FROM admin WHERE email = ?");
                $stmt_check_admin_email->bind_param("s", $email);
                $stmt_check_admin_email->execute();
                $stmt_check_admin_email->store_result();

                if ($stmt_check_admin_email->num_rows > 0) {
                    $error_message = "An admin with this email already exists.";
                } else {
                    // Check if there are any approved admins
                    $stmt_check_admins = $conn->prepare("SELECT id FROM admin WHERE status = 'approved'");
                    $stmt_check_admins->execute();
                    $stmt_check_admins->store_result();
                    $admin_count = $stmt_check_admins->num_rows;
                    $stmt_check_admins->close();

                    $status = ($admin_count === 0) ? 'approved' : 'pending';

                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_user = $conn->prepare("INSERT INTO users (username, password, role, name) VALUES (?, ?, ?, ?)");
                    $stmt_user->bind_param("ssss", $username, $hashed_password, $role, $fullname);

                    if ($stmt_user->execute()) {
                        $user_id = $conn->insert_id;
                        $stmt_admin = $conn->prepare("INSERT INTO admin (user_id, name, email, status) VALUES (?, ?, ?, ?)");
                        $stmt_admin->bind_param("isss", $user_id, $fullname, $email, $status);

                        if (!$stmt_admin->execute()) {
                            $error_message = "Error inserting admin details: " . $stmt_admin->error;
                            $conn->query("DELETE FROM users WHERE id = $user_id");
                        } else {
                            if ($status === 'approved') {
                                $_SESSION['user_id'] = $user_id;
                                $_SESSION['username'] = $username;
                                $_SESSION['role'] = $role;
                                $_SESSION['name'] = $fullname; // Set the name for the session
                                $_SESSION['profile_pic'] = 'assets/images/default-avatar.png'; // Set a default profile pic
                                header("Location: ../admin/dashboard.php");
                                exit();
                            } else {
                                $success_message = "Admin registration successful. Your account is pending approval from an existing admin.";
                            }
                        }
                        $stmt_admin->close();
                    } else {
                        $error_message = "Error creating user: " . $stmt_user->error;
                    }
                    $stmt_user->close();
                }
                $stmt_check_admin_email->close();            } elseif ($role === 'doctor') {
                // Get specialization_id from name
                $specialization_id = null;
                if (!empty($specialization)) {
                    $stmt_spec = $conn->prepare("SELECT id FROM specializations WHERE name = ?");
                    $stmt_spec->bind_param("s", $specialization);
                    $stmt_spec->execute();
                    $stmt_spec->bind_result($specialization_id);
                    $stmt_spec->fetch();
                    $stmt_spec->close();
                }

                // Check for existing doctor's email and phone
                $stmt_check_doctor = $conn->prepare("SELECT id FROM doctors WHERE email = ? OR phone = ?");
                $stmt_check_doctor->bind_param("ss", $email, $phone);
                $stmt_check_doctor->execute();
                $stmt_check_doctor->store_result();

                if ($stmt_check_doctor->num_rows > 0) {
                    $error_message = "A doctor with this email or phone number already exists.";
                } else {
                    // All checks passed, now insert the user and then the doctor details
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_user = $conn->prepare("INSERT INTO users (username, password, role, name) VALUES (?, ?, ?, ?)");
                    $stmt_user->bind_param("ssss", $username, $hashed_password, $role, $fullname);

                    if ($stmt_user->execute()) {
                        $user_id = $conn->insert_id;
                        $stmt_doc = $conn->prepare("INSERT INTO doctors (user_id, name, specialization_id, phone, email) VALUES (?, ?, ?, ?, ?)");
                        $stmt_doc->bind_param("isiss", $user_id, $fullname, $specialization_id, $phone, $email);

                        if (!$stmt_doc->execute()) {
                            $error_message = "Error inserting doctor details: " . $stmt_doc->error;
                            // Rollback user creation
                            $conn->query("DELETE FROM users WHERE id = $user_id");
                        } else {
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['username'] = $username;
                            $_SESSION['role'] = $role;
                            $_SESSION['name'] = $fullname; // Set the name for the session
                            $_SESSION['profile_pic'] = 'assets/images/default-avatar.png'; // Set a default profile pic
                            header("Location: ../doctor/dashboard.php");
                            exit();
                        }
                        $stmt_doc->close();
                    } else {
                        $error_message = "Error creating user: " . $stmt_user->error;
                    }
                    $stmt_user->close();
                }
                $stmt_check_doctor->close();
            } elseif ($role === 'patient') {
                // Check for existing patient's email and phone
                $stmt_check_patient = $conn->prepare("SELECT id FROM patients WHERE email = ? OR phone = ?");
                $stmt_check_patient->bind_param("ss", $email, $phone);
                $stmt_check_patient->execute();
                $stmt_check_patient->store_result();

                if ($stmt_check_patient->num_rows > 0) {
                    $error_message = "A patient with this email or phone number already exists.";
                } else {
                    // All checks passed, now insert the user and then the patient details
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_user = $conn->prepare("INSERT INTO users (username, password, role, name) VALUES (?, ?, ?, ?)");
                    $stmt_user->bind_param("ssss", $username, $hashed_password, $role, $fullname);

                    if ($stmt_user->execute()) {
                        $user_id = $conn->insert_id;
                        $stmt_pat = $conn->prepare("INSERT INTO patients (user_id, name, date_of_birth, gender, address, phone, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt_pat->bind_param("issssss", $user_id, $fullname, $date_of_birth, $gender, $address, $phone, $email);

                        if (!$stmt_pat->execute()) {
                            $error_message = "Error inserting patient details: " . $stmt_pat->error;
                            // Rollback user creation
                            $conn->query("DELETE FROM users WHERE id = $user_id");
                        } else {
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['username'] = $username;
                            $_SESSION['role'] = $role;
                            $_SESSION['name'] = $fullname; // Set the name for the session
                            $_SESSION['profile_pic'] = 'assets/images/default-avatar.png'; // Set a default profile pic
                            header("Location: ../includes/homepage.php");
                            exit();
                        }
                        $stmt_pat->close();
                    } else {
                        $error_message = "Error creating user: " . $stmt_user->error;
                    }
                    $stmt_user->close();
                }
                $stmt_check_patient->close();
            }

        }
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
      } else if (role === 'admin') {
        document.getElementById('admin-fields').style.display = 'block';
      }
    }
    window.onload = () => {
      toggleRoleFields();
      document.getElementById('role').addEventListener('change', toggleRoleFields);
    }
  </script>
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
    <div class="form-box signup-box">
      <h2>Sign Up</h2>

      

      <?php if (!empty($error_message)): ?>
        <div class="error-message" style="color: red;"><?= $error_message ?></div>
      <?php endif; ?>

      <?php if (!empty($success_message)): ?>
        <div class="success-message" style="color: green;"><?= $success_message ?></div>
      <?php endif; ?>

      

      <form method="POST" novalidate>
        <div class="input-group">

      <div class="input-group">
          <label for="role">Role</label>
          <select id="role" name="role" required>
            <option value="">Select Role</option>
            <option value="patient" <?= (($_POST['role'] ?? '') === 'patient') ? 'selected' : '' ?>>Patient</option>
            <option value="doctor" <?= (($_POST['role'] ?? '') === 'doctor') ? 'selected' : '' ?>>Doctor</option>
            <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
          </select>
        </div>

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

        <!-- Doctor-specific -->
        <div id="doctor-fields" class="role-fields">
          <div class="input-group">
            <label for="specialization">Specialization</label>
            <input list="specialization-list" id="specialization" name="specialization" value="<?= htmlspecialchars($_POST['specialization'] ?? '') ?>" />
            <datalist id="specialization-list">
                <?php foreach ($specializations as $spec): ?>
                    <option value="<?php echo $spec['name']; ?>">
                <?php endforeach; ?>
            </datalist>
          </div>
          <div class="input-group">
            <label for="phone_doc">Phone</label>
            <input type="text" id="phone_doc" name="phone_doc" value="<?= htmlspecialchars($_POST['phone_doc'] ?? '') ?>" />
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
            <input type="text" id="phone_pat" name="phone_pat" value="<?= htmlspecialchars($_POST['phone_pat'] ?? '') ?>" />
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