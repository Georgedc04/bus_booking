<?php
// ======================================================
// admin/reports.php - Admin Reports & Analytics
// ======================================================
require_once '../config.php';
require_admin();

// ---------------------- Fetch Report Data ----------------------

// Total revenue
$total_revenue = $mysqli->query("SELECT IFNULL(SUM(total_amount),0) AS total FROM bookings WHERE status='Booked'")->fetch_assoc()['total'];

// Total bookings
$total_bookings = $mysqli->query("SELECT COUNT(*) AS count FROM bookings")->fetch_assoc()['count'];

// Total users
$total_users = $mysqli->query("SELECT COUNT(*) AS count FROM users WHERE is_admin=0")->fetch_assoc()['count'];

// Most booked routes
$popular_routes = $mysqli->query("
    SELECT r.source, r.destination, COUNT(b.booking_id) AS total_bookings
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.schedule_id
    JOIN routes r ON s.route_id = r.route_id
    WHERE b.status='Booked'
    GROUP BY r.source, r.destination
    ORDER BY total_bookings DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Top 5 customers
$top_users = $mysqli->query("
    SELECT u.full_name, u.email, COUNT(b.booking_id) AS total_trips, SUM(b.total_amount) AS total_spent
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    WHERE b.status='Booked'
    GROUP BY u.user_id
    ORDER BY total_spent DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Bookings per day (last 7 days)
$daily_bookings = $mysqli->query("
    SELECT DATE(booking_date) AS date, COUNT(*) AS count
    FROM bookings
    WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(booking_date)
    ORDER BY date ASC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">🚌 Admin Reports</a>
        <div class="d-flex">
            <a href="dashboard.php" class="btn btn-secondary btn-sm me-2">Dashboard</a>
            <a href="../public/logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container my-5">
    <h3 class="text-center mb-4">System Analytics & Reports</h3>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4 text-center">
        <div class="col-md-4">
            <div class="card shadow-sm border-success">
                <div class="card-body">
                    <h5 class="text-success">Total Revenue</h5>
                    <h3>₹<?= number_format($total_revenue, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-primary">
                <div class="card-body">
                    <h5 class="text-primary">Total Bookings</h5>
                    <h3><?= $total_bookings ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-warning">
                <div class="card-body">
                    <h5 class="text-warning">Total Users</h5>
                    <h3><?= $total_users ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Bookings Chart -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Bookings in Last 7 Days</h5>
        </div>
        <div class="card-body">
            <canvas id="bookingsChart"></canvas>
        </div>
    </div>

    <!-- Popular Routes -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Top 5 Popular Routes</h5>
                </div>
                <div class="card-body">
                    <?php if (count($popular_routes) > 0): ?>
                        <table class="table table-bordered table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th>Route</th>
                                    <th>Bookings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_routes as $r): ?>
                                    <tr>
                                        <td><?= $r['source'] ?> → <?= $r['destination'] ?></td>
                                        <td><?= $r['total_bookings'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-center text-muted">No route data available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Top 5 Customers</h5>
                </div>
                <div class="card-body">
                    <?php if (count($top_users) > 0): ?>
                        <table class="table table-bordered table-striped">
                            <thead class="table-success">
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Trips</th>
                                    <th>Spent (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_users as $u): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($u['full_name']) ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td><?= $u['total_trips'] ?></td>
                                        <td><?= number_format($u['total_spent'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-center text-muted">No user booking data available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<footer class="text-center mt-5 py-3 bg-dark text-white">
    &copy; <?= date('Y') ?> Online Bus Ticket Booking System | Admin Panel | Developed by George Kasmiro Quiriko
</footer>

<!-- Chart.js Data -->
<script>
const ctx = document.getElementById('bookingsChart').getContext('2d');
const bookingsChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($daily_bookings, 'date')) ?>,
        datasets: [{
            label: 'Bookings per Day',
            data: <?= json_encode(array_column($daily_bookings, 'count')) ?>,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0,123,255,0.2)',
            tension: 0.3,
            fill: true,
            borderWidth: 2
        }]
    },
    options: {
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

</body>
</html>
