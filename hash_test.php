<?php
// hash_test.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_BCRYPT);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bcrypt Hash Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5" style="max-width: 500px;">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="text-center">Bcrypt Password Hash Generator</h5>
        </div>
        <div class="card-body">

            <form method="POST">
                <label class="form-label">Enter Password</label>
                <input type="text" name="password" class="form-control" required>
                <button class="btn btn-primary w-100 mt-3">Generate Hash</button>
            </form>

            <?php if (!empty($hash)) : ?>
                <div class="alert alert-success mt-3">
                    <strong>Bcrypt Hash:</strong><br>
                    <code><?php echo $hash; ?></code>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>
