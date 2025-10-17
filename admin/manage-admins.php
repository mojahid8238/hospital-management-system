<?php
$page_title = 'Manage Admins';
include 'base_admin.php';

require_once '../includes/db.php';
require_once '../includes/auth.php';

redirect_if_not_logged_in();
redirect_if_not_admin();

// Handle approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_admin'])) {
    $admin_id_to_approve = $_POST['admin_id'];
    $stmt = $conn->prepare("UPDATE admin SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $admin_id_to_approve);
    $stmt->execute();
    $stmt->close();
}

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_admin'])) {
    $admin_id_to_cancel = $_POST['admin_id'];

    // Get user_id from admin table before deleting
    $stmt_get_user = $conn->prepare("SELECT user_id FROM admin WHERE id = ?");
    $stmt_get_user->bind_param("i", $admin_id_to_cancel);
    $stmt_get_user->execute();
    $stmt_get_user->bind_result($user_id_to_delete);
    $stmt_get_user->fetch();
    $stmt_get_user->close();

    // Delete from admin table
    $stmt_delete_admin = $conn->prepare("DELETE FROM admin WHERE id = ?");
    $stmt_delete_admin->bind_param("i", $admin_id_to_cancel);
    $stmt_delete_admin->execute();
    $stmt_delete_admin->close();

    // Delete from users table
    if ($user_id_to_delete) {
        $stmt_delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt_delete_user->bind_param("i", $user_id_to_delete);
        $stmt_delete_user->execute();
        $stmt_delete_user->close();
    }
}

// Fetch pending admins
$pending_admins = [];
$stmt = $conn->prepare("SELECT id, name, email FROM admin WHERE status = 'pending'");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_admins[] = $row;
}
$stmt->close();
?>

<div class="container">
    <h2>Manage Admins</h2>

    <h3>Pending Admin Requests</h3>
    <?php if (empty($pending_admins)): ?>
        <p>No pending admin requests.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_admins as $admin): ?>
                    <tr>
                        <td><?= htmlspecialchars($admin['name']) ?></td>
                        <td><?= htmlspecialchars($admin['email']) ?></td>
                        <td>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                    <button type="submit" name="approve_admin">Approve</button>
                                </form>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                    <button type="submit" name="cancel_admin" style="background-color: red;" onclick="return confirm('Are you sure you want to cancel this admin request?')">Cancel</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>