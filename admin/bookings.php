<?php
// ======================================================
// admin/bookings.php - Manage Bookings (Admin Only)
// ======================================================
require_once '../config.php';
require_admin();

$error = "";
$success = "";

// Handle booking cancellation (admin control)
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $booking_id = intval($_GET['cancel']);

    // Fetch booking details
    $stmt = $mysqli->prepare("
        SELECT b.*, s.schedule_id, s.available_seats 
        FROM bookings b 
        JOIN schedules s ON b.schedule_id = s.schedule_id
        WHERE b.booking_id = ?
    ");
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if ($booking && $booking['status'] == 'Booked') {
        // Cancel booking
        $cancel = $mysqli->prepare("UPDATE bookings SET status='Cancelled' WHERE booking_id=?");
        $cancel->bind_param('i', $booking_id);
        $cancel->execute();

        // Restore seats
        $restore = $mysqli->prepare("UPDATE schedules SET available_seats = available_seats + ? WHERE schedule_id=?");
        $restore->bind_param('ii', $booking['seats_booked'], $booking['schedule_id']);
        $restore->execute();

        log_action("Admin cancelled booking ID $booking_id", $_SESSION['user_id']);
        $success = "✅ Booking #$booking_id cancelled successfully.";
    } else {
        $error = "⚠️ Invalid or already cancelled booking.";
    }
}

// Filter bookings
$filter = isset($_GET['status']) ? clean_input($_GET['status']) : 'All';

$query = "
    SELECT b.booking_id, u.full_name AS user_name, r.source, r.destination, s.departure_time, 
           b.seats_booked, b.total_amount, b.status, b.booking_date
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN schedules s ON b.schedule_id = s.schedule_id
    JOIN routes r ON s.route_id = r.route_id
";

if ($filter !== 'All') {
    $query .= " WHERE b.status = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $filter);
} else {
    $stmt = $mysqli->prepare($query);
}

$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Bookings - Admin Panel</title>
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
    <h3 class="text-center mb-4">Manage All Bookings</h3>

    <!-- Filters -->
    <form method="GET" class="mb-4 text-center">
        <label class="me-2 fw-bold">Filter by Status:</label>
        <select name="status" class="form-select d-inline-block w-auto me-2">
            <option <?= $filter === 'All' ? 'selected' : '' ?>>All</option>
            <option <?= $filter === 'Booked' ? 'selected' : '' ?>>Booked</option>
            <option <?= $filter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            <option <?= $filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
    </form>

    <?php if ($error): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success text-center"><?= $success ?></div>
    <?php endif; ?>

    <?php if (count($bookings) > 0): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Route</th>
                    <th>Departure</th>
                    <th>Seats</th>
                    <th>Total (₹)</th>
                    <th>Status</th>
                    <th>Booked On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><?= $b['booking_id'] ?></td>
                        <td><?= htmlspecialchars($b['user_name']) ?></td>
                        <td><?= $b['source'] ?> → <?= $b['destination'] ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($b['departure_time'])) ?></td>
                        <td><?= $b['seats_booked'] ?></td>
                        <td><?= number_format($b['total_amount'], 2) ?></td>
                        <td>
                            <?php if ($b['status'] === 'Booked'): ?>
                                <span class="badge bg-success"><?= $b['status'] ?></span>
                            <?php elseif ($b['status'] === 'Cancelled'): ?>
                                <span class="badge bg-danger"><?= $b['status'] ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= $b['status'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d M Y, h:i A', strtotime($b['booking_date'])) ?></td>
                        <td>
                            <?php if ($b['status'] === 'Booked'): ?>
                                <a href="?cancel=<?= $b['booking_id'] ?>" class="btn btn-warning btn-sm"
                                   onclick="return confirm('Cancel booking #<?= $b['booking_id'] ?>?');">
                                   Cancel
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info text-center">No bookings found for the selected filter.</div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<footer class="text-center mt-5 py-3 bg-dark text-white">
    &copy; <?= date('Y') ?> Online Bus Ticket Booking System | Admin Panel | Developed by GK Tech
</footer>

</body>
</html>
