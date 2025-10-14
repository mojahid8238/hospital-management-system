<?php
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

// Fetch pending admins
$pending_admins = [];
$stmt = $conn->prepare("SELECT id, name, email FROM admin WHERE status = 'pending'");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_admins[] = $row;
}
$stmt->close();

include '../includes/header.php';
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
                            <form method="POST">
                                <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                <button type="submit" name="approve_admin">Approve</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
