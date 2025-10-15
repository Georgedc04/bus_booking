<?php
// ======================================================
// login.php - User/Admin Login Page (Updated Version)
// ======================================================
require_once '../config.php';

// If already logged in → redirect to home/admin
if (is_logged_in()) {
    if (is_admin()) {
        header("Location: ../admin/dashboard.php");
        exit;
    } else {
        header("Location: index.php");
        exit;
    }
}

$error = "";

// Handle login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT user_id, full_name, password, is_admin FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Check password (supports plain or hashed)
        if (verify_password($password, $user['password']) || $password === $user['password']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['full_name'];
            $_SESSION['is_admin'] = $user['is_admin'];

            log_action("User logged in", $user['user_id']);

            // Use proper POST/Redirect/GET pattern to prevent refresh warnings
            if ($user['is_admin']) {
                header("Location: ../admin/dashboard.php");
                exit;
            } else {
                header("Location: index.php");
                exit;
            }
        } else {
            $error = "❌ Incorrect password.";
        }
    } else {
        $error = "❌ No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Bus Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5" style="max-width: 500px;">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white text-center">
            <h4>Online Bus Ticket Booking</h4>
        </div>
        <div class="card-body">
            <h5 class="text-center mb-3">User / Admin Login</h5>

            <?php if ($error): ?>
                <div class="alert alert-danger text-center"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required placeholder="Enter your email">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Enter password">
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>

            <div class="text-center mt-3">
                <p>Don’t have an account? <a href="register.php">Register here</a></p>
                <p class="mt-2">
                    <a href="../admin/login.php" class="btn btn-outline-dark btn-sm">
                        🔐 Admin Login
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<footer class="text-center mt-5 py-3 bg-dark text-white">
    &copy; <?= date('Y') ?> Online Bus Ticket Booking System | Developed by George Kasmiro Quiriko
</footer>

</body>
</html>
