<?php
// ======================================================
// register.php - User Registration Page
// ======================================================
require_once '../config.php';

// If already logged in → redirect to homepage
if (is_logged_in()) {
    redirect('index.php');
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = "⚠️ All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "⚠️ Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "⚠️ Email already registered.";
        } else {
            // Insert new user
            $hashed = hash_password($password);
            $stmt = $mysqli->prepare("INSERT INTO users (full_name, email, phone, password, is_admin) VALUES (?, ?, ?, ?, 0)");
            $stmt->bind_param('ssss', $name, $email, $phone, $hashed);

            if ($stmt->execute()) {
                log_action("New user registered: $name");
                $success = "✅ Registration successful! You can now login.";
            } else {
                $error = "❌ Registration failed: " . $mysqli->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Bus Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5" style="max-width: 600px;">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white text-center">
            <h4>Create a New Account</h4>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger text-center"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success text-center"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="Enter phone number" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm" class="form-control" placeholder="Confirm password" required>
                </div>

                <button type="submit" class="btn btn-success w-100">Register</button>
            </form>

            <div class="text-center mt-3">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<footer class="text-center mt-5 py-3 bg-dark text-white">
    &copy; <?= date('Y') ?> Online Bus Ticket Booking System | Developed by George Kasmiro Quiriko
</footer>

</body>
</html>
