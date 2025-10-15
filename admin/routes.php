<?php
// ======================================================
// admin/routes.php - Manage Bus Routes (Admin Only)
// ======================================================
require_once '../config.php';
require_admin();

$error = "";
$success = "";

// Handle Add Route
if (isset($_POST['add_route'])) {
    $source = clean_input($_POST['source']);
    $destination = clean_input($_POST['destination']);
    $distance = floatval($_POST['distance_km']);
    $time = clean_input($_POST['estimated_time']);

    if (empty($source) || empty($destination) || $source === $destination) {
        $error = "⚠️ Invalid route: source and destination must be different.";
    } else {
        // Check duplicate
        $check = $mysqli->prepare("SELECT route_id FROM routes WHERE source=? AND destination=?");
        $check->bind_param('ss', $source, $destination);
        $check->execute();
        $exists = $check->get_result();

        if ($exists->num_rows > 0) {
            $error = "❌ Route already exists.";
        } else {
            $stmt = $mysqli->prepare("INSERT INTO routes (source, destination, distance_km, estimated_time) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssds', $source, $destination, $distance, $time);
            if ($stmt->execute()) {
                log_action("Admin added new route: $source → $destination", $_SESSION['user_id']);
                $success = "✅ Route added successfully!";
            } else {
                $error = "❌ Failed to add route. " . $mysqli->error;
            }
        }
    }
}

// Handle Delete Route
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $stmt = $mysqli->prepare("SELECT source, destination FROM routes WHERE route_id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $route = $stmt->get_result()->fetch_assoc();

    if ($route) {
        $del = $mysqli->prepare("DELETE FROM routes WHERE route_id=?");
        $del->bind_param('i', $id);
        if ($del->execute()) {
            log_action("Admin deleted route: {$route['source']} → {$route['destination']}", $_SESSION['user_id']);
            $success = "✅ Route deleted successfully.";
        } else {
            $error = "❌ Failed to delete route.";
        }
    } else {
        $error = "⚠️ Route not found.";
    }
}

// Fetch all routes
$routes = $mysqli->query("SELECT * FROM routes ORDER BY source, destination")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Routes - Admin Panel</title>
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
    <h3 class="text-center mb-4">Manage Bus Routes</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success text-center"><?= $success ?></div>
    <?php endif; ?>

    <!-- Add New Route Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Add New Route</h5>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Source</label>
                    <input type="text" name="source" class="form-control" placeholder="e.g. Dehradun" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Destination</label>
                    <input type="text" name="destination" class="form-control" placeholder="e.g. Delhi" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Distance (km)</label>
                    <input type="number" step="0.1" name="distance_km" class="form-control" placeholder="250" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Est. Time</label>
                    <input type="text" name="estimated_time" class="form-control" placeholder="6 hrs" required>
                </div>
                <div class="col-md-12 text-center">
                    <button type="submit" name="add_route" class="btn btn-primary">Add Route</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Routes Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">All Routes</h5>
        </div>
        <div class="card-body">
            <?php if (count($routes) > 0): ?>
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Source</th>
                            <th>Destination</th>
                            <th>Distance (km)</th>
                            <th>Est. Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($routes as $r): ?>
                            <tr>
                                <td><?= $r['route_id'] ?></td>
                                <td><?= htmlspecialchars($r['source']) ?></td>
                                <td><?= htmlspecialchars($r['destination']) ?></td>
                                <td><?= $r['distance_km'] ?></td>
                                <td><?= htmlspecialchars($r['estimated_time']) ?></td>
                                <td>
                                    <a href="?delete=<?= $r['route_id'] ?>" class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this route?');">
                                       Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info text-center">No routes found.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<footer class="text-center mt-5 py-3 bg-dark text-white">
    &copy; <?= date('Y') ?> Online Bus Ticket Booking System | Admin Panel | Developed by George Kasmiro Quiriko
</footer>

</body>
</html>
