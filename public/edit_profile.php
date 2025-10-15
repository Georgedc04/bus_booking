<?php
require_once '../config.php';
require_login();

$user_id = $_SESSION['user_id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];

    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE users SET full_name=?, email=?, password=? WHERE user_id=?");
        $stmt->bind_param('sssi', $name, $email, $hashed, $user_id);
    } else {
        $stmt = $mysqli->prepare("UPDATE users SET full_name=?, email=? WHERE user_id=?");
        $stmt->bind_param('ssi', $name, $email, $user_id);
    }

    if ($stmt->execute()) {
        $_SESSION['name'] = $name;
        $message = "<div class='alert alert-success'>✅ Profile updated successfully.</div>";
    } else {
        $message = "<div class='alert alert-danger'>❌ Error updating profile.</div>";
    }
}

$user = $mysqli->query("SELECT full_name, email FROM users WHERE user_id=$user_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - Bus Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🚌 Bus Booking</a>
        <div class="ms-auto">
            <a href="profile.php" class="btn btn-outline-light btn-sm me-2">Back to Profile</a>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5" style="max-width:600px;">
    <h3 class="text-center mb-4">Edit Profile</h3>
    <?= $message ?>

    <form method="POST" class="card p-4 shadow-sm">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($user['full_name']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($user['email']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">New Password (optional)</label>
            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
        </div>
        <button type="submit" class="btn btn-primary w-100">Update Profile</button>
    </form>
</div>

<footer class="text-center py-3 bg-dark text-white mt-5">
    &copy; <?= date('Y') ?> Online Bus Ticket Booking System | Developed by <?= htmlspecialchars($_SESSION['name']) ?>
</footer>
</body>
</html>
