<?php
// Shared change-password handler + form. Include on settings pages.
// Handles POST only when action === 'change_password', so it won't
// clash with a page's own profile-update form.
if (!is_logged_in()) {
    return;
}

$change_password_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $current = $_POST['password_current'] ?? '';
    $new = $_POST['password_new'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($stored_hash);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($current, $stored_hash)) {
        $change_password_message = "Current password is incorrect.";
    } elseif (strlen($new) < 6) {
        $change_password_message = "New password must be at least 6 characters.";
    } elseif ($new !== $confirm) {
        $change_password_message = "New passwords do not match.";
    } else {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_hash, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $change_password_message = "Password changed successfully.";
        } else {
            $change_password_message = "Error updating password: " . $conn->error;
        }
        $stmt->close();
    }
}

function render_change_password_form($message = '') {
    if (!empty($message)) {
        echo "<div class='alert " . (strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-danger') . "'>$message</div>";
    }
    ?>
    <div class="edit-form-card" style="margin-top: 32px;">
        <h3 style="margin-bottom: 8px;"><i class="fas fa-lock"></i> Change Password</h3>
        <p class="text-muted" style="margin-bottom: 20px;">Update your login password.</p>
        <form action="" method="POST">
            <input type="hidden" name="action" value="change_password">
            <div class="form-grid">
                <div class="form-group">
                    <label for="password_current"><i class="fas fa-key"></i> Current Password</label>
                    <div class="password-wrapper" style="position: relative;">
                        <input type="password" id="password_current" name="password_current" required style="box-sizing: border-box; padding-right: 64px;">
                        <button type="button" class="password-toggle" aria-label="Show password" style="position: absolute; top: 50%; right: 12px; transform: translateY(-50%); border: none; background: none; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: var(--primary-color);">Show</button>
                    </div>
                </div>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="password_new"><i class="fas fa-lock"></i> New Password</label>
                    <div class="password-wrapper" style="position: relative;">
                        <input type="password" id="password_new" name="password_new" required style="box-sizing: border-box; padding-right: 64px;">
                        <button type="button" class="password-toggle" aria-label="Show password" style="position: absolute; top: 50%; right: 12px; transform: translateY(-50%); border: none; background: none; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: var(--primary-color);">Show</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password_confirm"><i class="fas fa-lock"></i> Confirm New Password</label>
                    <div class="password-wrapper" style="position: relative;">
                        <input type="password" id="password_confirm" name="password_confirm" required style="box-sizing: border-box; padding-right: 64px;">
                        <button type="button" class="password-toggle" aria-label="Show password" style="position: absolute; top: 50%; right: 12px; transform: translateY(-50%); border: none; background: none; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: var(--primary-color);">Show</button>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-update"><i class="fas fa-save"></i> Update Password</button>
            </div>
        </form>
    </div>
    <script src="../assets/js/password-toggle.js"></script>
    <?php
}
