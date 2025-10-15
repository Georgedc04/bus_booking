<?php
// ======================================================
// admin/login.php - Secure Admin Login Page
// ======================================================
require_once '../config.php';

// If already logged in → go to dashboard
if (isset($_SESSION['user_id']) && ($_SESSION['is_admin'] ?? 0) == 1) {
    redirect('dashboard.php');
}

// Handle login
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT user_id, full_name, password, is_admin FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($admin = $result->fetch_assoc()) {
        if ($admin['is_admin'] == 1) {
            // ✅ Use PHP's native password_verify()
            if (password_verify($password, $admin['password'])) {
                $_SESSION['user_id'] = $admin['user_id'];
                $_SESSION['name'] = $admin['full_name'];
                $_SESSION['is_admin'] = 1;

                log_action("Admin logged in", $admin['user_id']);
                redirect('dashboard.php');
            } else {
                $error = "❌ Incorrect password.";
            }
        } else {
            $error = "⚠️ You are not authorized as admin.";
        }
    } else {
        $error = "❌ No admin found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Bus Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5" style="max-width: 500px;">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white text-center">
            <h4>Admin Login Panel</h4>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger text-center"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Admin Email</label>
                    <input type="email" name="email" class="form-control" required placeholder="Enter admin email">
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Enter password">
                </div>

                <button type="submit" class="btn btn-dark w-100">Login</button>
            </form>

            <div class="text-center mt-3">
                <a href="../public/login.php">← Back to User Login</a>
            </div>
        </div>
    </div>
</div>

<footer class="text-center mt-5 py-3 bg-dark text-white">
    &copy; <?= date('Y') ?> Online Bus Ticket Booking System | Admin Portal | Developed by George Kasmiro Quiriko
</footer>

</body>
</html>
