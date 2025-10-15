<?php
// ======================================================
// admin/schedules.php - Manage Bus Schedules (Admin Only)
// ======================================================
require_once '../config.php';
require_admin();

$error = "";
$success = "";

// Fetch all buses and routes for dropdowns
$buses = $mysqli->query("SELECT bus_id, bus_number FROM buses ORDER BY bus_number ASC")->fetch_all(MYSQLI_ASSOC);
$routes = $mysqli->query("SELECT route_id, source, destination FROM routes ORDER BY source ASC")->fetch_all(MYSQLI_ASSOC);

// Handle Add Schedule
if (isset($_POST['add_schedule'])) {
    $bus_id = intval($_POST['bus_id']);
    $route_id = intval($_POST['route_id']);
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $available_seats = intval($_POST['available_seats']);

    if ($bus_id <= 0 || $route_id <= 0 || empty($departure_time) || empty($arrival_time)) {
        $error = "⚠️ All fields are required.";
    } else {
        $stmt = $mysqli->prepare("INSERT INTO schedules (bus_id, route_id, departure_time, arrival_time, available_seats) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('i ss si', $bus_id, $route_id, $departure_time, $arrival_time, $available_seats);

        if ($stmt->execute()) {
            log_action("Admin added new schedule (Bus ID: $bus_id, Route ID: $route_id)", $_SESSION['user_id']);
            $success = "✅ Schedule added successfully!";
        } else {
            $error = "❌ Failed to add schedule: " . $mysqli->error;
        }
    }
}

// Handle Delete Schedule
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $schedule_id = intval($_GET['delete']);
    $stmt = $mysqli->prepare("DELETE FROM schedules WHERE schedule_id = ?");
    $stmt->bind_param('i', $schedule_id);

    if ($stmt->execute()) {
        log_action("Admin deleted schedule ID $schedule_id", $_SESSION['user_id']);
        $success = "✅ Schedule deleted successfully.";
    } else {
        $error = "❌ Failed to delete schedule.";
    }
}

// Fetch all schedules
$schedules = $mysqli->query("
    SELECT s.schedule_id, b.bus_number, r.source, r.destination, s.departure_time, s.arrival_time, s.available_seats
    FROM schedules s
    JOIN buses b ON s.bus_id = b.bus_id
    JOIN routes r ON s.route_id = r.route_id
    ORDER BY s.departure_time DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Schedules - Admin Panel</title>
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
    <h3 class="text-center mb-4">Manage Bus Schedules</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success text-center"><?= $success ?></div>
    <?php endif; ?>

    <!-- Add Schedule Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Add New Schedule</h5>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Select Bus</label>
                    <select name="bus_id" class="form-select" required>
                        <option value="">-- Select Bus --</option>
                        <?php foreach ($buses as $b): ?>
                            <option value="<?= $b['bus_id'] ?>"><?= htmlspecialchars($b['bus_number']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Select Route</label>
                    <select name="route_id" class="form-select" required>
                        <option value="">-- Select Route --</option>
                        <?php foreach ($routes as $r): ?>
                            <option value="<?= $r['route_id'] ?>"><?= htmlspecialchars($r['source']) ?> → <?= htmlspecialchars($r['destination']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Departure Time</label>
                    <input type="datetime-local" name="departure_time" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Arrival Time</label>
                    <input type="datetime-local" name="arrival_time" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Available Seats</label>
                    <input type="number" name="available_seats" min="1" class="form-control" required>
                </div>
                <div class="col-md-12 text-center">
                    <button type="submit" name="add_schedule" class="btn btn-primary mt-2 w-50">Add Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Schedule List -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">All Bus Schedules</h5>
        </div>
        <div class="card-body">
            <?php if (count($schedules) > 0): ?>
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Bus</th>
                            <th>Route</th>
                            <th>Departure</th>
                            <th>Arrival</th>
                            <th>Seats</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $s): ?>
                            <tr>
                                <td><?= $s['schedule_id'] ?></td>
                                <td><?= htmlspecialchars($s['bus_number']) ?></td>
                                <td><?= htmlspecialchars($s['source']) ?> → <?= htmlspecialchars($s['destination']) ?></td>
                                <td><?= date('d M Y, h:i A', strtotime($s['departure_time'])) ?></td>
                                <td><?= date('d M Y, h:i A', strtotime($s['arrival_time'])) ?></td>
                                <td><?= $s['available_seats'] ?></td>
                                <td>
                                    <a href="?delete=<?= $s['schedule_id'] ?>" class="btn btn-danger btn-sm"
                                       onclick="return confirm('Delete this schedule?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info text-center">No schedules found.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<footer class="text-center mt-5 py-3 bg-dark text-white">
    &copy; <?= date('Y') ?> Online Bus Ticket Booking System | Admin Panel | Developed by GK Tech
</footer>

</body>
</html>
