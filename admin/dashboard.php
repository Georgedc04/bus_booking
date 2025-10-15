<?php
// ======================================================
// admin/dashboard.php - Admin Control Panel
// ======================================================
require_once '../config.php';
require_admin(); // only admins can access this page

// ----- Fetch summary counts -----
$total_users = $mysqli->query("SELECT COUNT(*) AS c FROM users WHERE is_admin=0")->fetch_assoc()['c'];
$total_buses = $mysqli->query("SELECT COUNT(*) AS c FROM buses")->fetch_assoc()['c'];
$total_routes = $mysqli->query("SELECT COUNT(*) AS c FROM routes")->fetch_assoc()['c'];
$total_bookings = $mysqli->query("SELECT COUNT(*) AS c FROM bookings")->fetch_assoc()['c'];
$total_revenue = $mysqli->query("SELECT IFNULL(SUM(total_amount),0) AS s FROM bookings WHERE status='Booked'")->fetch_assoc()['s'];

// ----- Latest 5 bookings -----
$recent_bookings = $mysqli->query("
    SELECT b.booking_id, u.full_name, r.source, r.destination, s.departure_time, b.total_amount, b.status
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN schedules s ON b.schedule_id = s.schedule_id
    JOIN routes r ON s.route_id = r.route_id
    ORDER BY b.booking_date DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// ----- Recent logs -----
$audit_logs = $mysqli->query("
    SELECT l.action, u.full_name, l.log_time
    FROM audit_logs l
    LEFT JOIN users u ON l.user_id = u.user_id
    ORDER BY l.log_time DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Bus Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">🚌 Admin Dashboard</a>
        <div class="d-flex align-items-center">
            <span class="navbar-text text-white me-3">
                Welcome, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>
            </span>
            <a href="../public/logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container my-5">
    <h3 class="mb-4 text-center fw-bold">Admin Control Panel</h3>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4 text-center">
        <div class="col-md-3">
            <div class="card shadow-sm border-primary">
                <div class="card-body">
                    <i class="bi bi-people text-primary fs-2"></i>
                    <h5 class="text-primary mt-2">Total Users</h5>
                    <h2><?= $total_users ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-success">
                <div class="card-body">
                    <i class="bi bi-bus-front text-success fs-2"></i>
                    <h5 class="text-success mt-2">Total Buses</h5>
                    <h2><?= $total_buses ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-warning">
                <div class="card-body">
                    <i class="bi bi-geo-alt text-warning fs-2"></i>
                    <h5 class="text-warning mt-2">Total Routes</h5>
                    <h2><?= $total_routes ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-danger">
                <div class="card-body">
                    <i class="bi bi-ticket-detailed text-danger fs-2"></i>
                    <h5 class="text-danger mt-2">Bookings</h5>
                    <h2><?= $total_bookings ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue -->
    <div class="card shadow-sm mb-5 text-center">
        <div class="card-body bg-light">
            <i class="bi bi-cash-coin text-success fs-3"></i>
            <h5>Total Revenue</h5>
            <h3 class="text-success">₹<?= number_format($total_revenue, 2) ?></h3>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-clock-history"></i> Recent Bookings
        </div>
        <div class="card-body">
            <?php if (count($recent_bookings) > 0): ?>
                <table class="table table-bordered table-striped text-center align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Route</th>
                            <th>Departure</th>
                            <th>Amount (₹)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $b): ?>
                            <tr>
                                <td><?= $b['booking_id'] ?></td>
                                <td><?= htmlspecialchars($b['full_name']) ?></td>
                                <td><?= htmlspecialchars($b['source']) ?> → <?= htmlspecialchars($b['destination']) ?></td>
                                <td><?= date('d M, h:i A', strtotime($b['departure_time'])) ?></td>
                                <td><?= number_format($b['total_amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= $b['status'] == 'Booked' ? 'success' : ($b['status'] == 'Cancelled' ? 'danger' : 'secondary') ?>">
                                        <?= htmlspecialchars($b['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center text-muted">No recent bookings found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Audit Logs -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <i class="bi bi-list-check"></i> Recent Admin/User Activities
        </div>
        <div class="card-body">
            <?php if (count($audit_logs) > 0): ?>
                <ul class="list-group">
                    <?php foreach ($audit_logs as $log): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <strong><?= htmlspecialchars($log['full_name'] ?? 'System') ?></strong> - <?= htmlspecialchars($log['action']) ?>
                            </span>
                            <span class="text-muted small"><?= date('d M Y, h:i A', strtotime($log['log_time'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-center text-muted">No recent activity logs.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Admin Action Buttons -->
    <div class="text-center mt-5">
        <a href="add_bus.php" class="btn btn-success me-2"><i class="bi bi-plus-circle"></i> Add New Bus</a>
        <a href="routes.php" class="btn btn-primary me-2"><i class="bi bi-geo"></i> Manage Routes</a>
        <a href="schedules.php" class="btn btn-info me-2"><i class="bi bi-calendar-week"></i> Manage Schedules</a>
        <a href="users.php" class="btn btn-warning me-2"><i class="bi bi-person-lines-fill"></i> Manage Users</a>
        <a href="bookings.php" class="btn btn-secondary"><i class="bi bi-list-ul"></i> View All Bookings</a>
    </div>
</div>

<footer class="text-center mt-5 py-3 bg-dark text-white">
    &copy; <?= date('Y') ?> Online Bus Ticket Booking System | Admin Panel | Developed by GK Tech
</footer>

</body>
</html>
