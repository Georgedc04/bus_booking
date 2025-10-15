<?php
require_once '../config.php';
require_login();

$user_id = $_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch bookings
$bookings = $mysqli->query("
    SELECT b.booking_id, r.source, r.destination, s.departure_time, s.arrival_time, b.seats_booked, b.total_amount, b.status
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.schedule_id
    JOIN routes r ON s.route_id = r.route_id
    WHERE b.user_id = $user_id
    ORDER BY b.booking_date DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - Bus Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🚌 Bus Booking</a>
        <div class="ms-auto">
            <a href="edit_profile.php" class="btn btn-outline-light btn-sm me-2">Edit Profile</a>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h3 class="text-center mb-4">My Profile</h3>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5><?= htmlspecialchars($user['full_name']) ?></h5>
            <p class="text-muted mb-1"><?= htmlspecialchars($user['email']) ?></p>
        </div>
    </div>

    <h4 class="mb-3">My Bookings</h4>
    <?php if (count($bookings) > 0): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>Route</th>
                    <th>Departure</th>
                    <th>Seats</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><?= $b['booking_id'] ?></td>
                        <td><?= $b['source'] ?> → <?= $b['destination'] ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($b['departure_time'])) ?></td>
                        <td><?= $b['seats_booked'] ?></td>
                        <td>₹<?= number_format($b['total_amount'], 2) ?></td>
                        <td>
                            <?php if ($b['status'] === 'Booked'): ?>
                                <span class="badge bg-success">Booked</span>
                            <?php elseif ($b['status'] === 'Cancelled'): ?>
                                <span class="badge bg-danger">Cancelled</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($b['status']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info text-center">No bookings yet.</div>
    <?php endif; ?>
</div>

<footer class="text-center py-3 bg-dark text-white mt-5">
    &copy; <?= date('Y') ?> Online Bus Ticket Booking System | Developed by <?= htmlspecialchars($_SESSION['name']) ?>
</footer>
</body>
</html>
