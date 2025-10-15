<?php
// ======================================================
// admin/users.php - Manage Users (Admin Only)
// ======================================================
require_once '../config.php';
require_admin();

$error = "";
$success = "";

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $uid = intval($_GET['delete']);

    // Prevent deleting admin
    $check = $mysqli->prepare("SELECT full_name, is_admin FROM users WHERE user_id = ?");
    $check->bind_param('i', $uid);
    $check->execute();
    $user = $check->get_result()->fetch_assoc();

    if ($user) {
        if ($user['is_admin'] == 1) {
            $error = "⚠️ You cannot delete another admin.";
        } else {
            // Delete user
            $stmt = $mysqli->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param('i', $uid);
            if ($stmt->execute()) {
                log_action("Admin deleted user: {$user['full_name']}", $_SESSION['user_id']);
                $success = "✅ User '{$user['full_name']}' deleted successfully.";
            } else {
                $error = "❌ Failed to delete user.";
            }
        }
    } else {
        $error = "⚠️ User not found.";
    }
}

// Fetch all users (non-admins)
$users = $mysqli->query("SELECT user_id, full_name, email, phone, created_at FROM users WHERE is_admin = 0 ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">🚌 Admin Panel</a>
        <div class="d-flex">
            <a href="dashboard.php" class="btn btn-secondary btn-sm me-2">Dashboard</a>
            <a href="../public/logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h3 class="text-center mb-4">Manage Registered Users</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success text-center"><?= $success ?></div>
    <?php endif; ?>

    <?php if (count($users) > 0): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Registered On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['user_id'] ?></td>
                        <td><?= htmlspecialchars($u['full_name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['phone']) ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($u['created_at'])) ?></td>
                        <td>
                            <a href="?delete=<?= $u['user_id'] ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($u['full_name']) ?>?');">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info text-center">No registered users found.</div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<footer class="text-center mt-5 py-3 bg-dark text-white">
    &copy; <?= date('Y') ?> Online Bus Ticket Booking System | Admin Panel | Developed by George Kasmiro Quiriko
</footer>

</body>
</html>
