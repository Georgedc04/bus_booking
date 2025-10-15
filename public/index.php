<?php
// ======================================================
// index.php - Modern Home Page with Live Routes & Working Booking
// ======================================================
require_once '../config.php';
require_login();

$user_id = $_SESSION['user_id'] ?? 0;

// Fetch user info + counts
$user = ['full_name' => $_SESSION['name'] ?? '', 'email' => ''];
$total_bookings = 0;
$upcoming_bookings = 0;

if ($user_id) {
    $stmt = $mysqli->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total_bookings = $mysqli->query("SELECT COUNT(*) AS c FROM bookings WHERE user_id=$user_id")->fetch_assoc()['c'] ?? 0;
    $upcoming_bookings = $mysqli->query("
        SELECT COUNT(*) AS c
        FROM bookings b
        JOIN schedules s ON b.schedule_id=s.schedule_id
        WHERE b.user_id=$user_id AND b.status='Booked' AND s.departure_time >= NOW()
    ")->fetch_assoc()['c'] ?? 0;
}

// Fetch all routes (for display)
$routes = $mysqli->query("SELECT * FROM routes ORDER BY source ASC")->fetch_all(MYSQLI_ASSOC);

// Fetch today’s available buses
$today = date('Y-m-d');
$available_buses = $mysqli->query("
    SELECT s.schedule_id, b.bus_number, b.bus_type, b.fare,
           r.source, r.destination, s.departure_time, s.arrival_time, s.available_seats
    FROM schedules s
    JOIN buses b ON s.bus_id=b.bus_id
    JOIN routes r ON s.route_id=r.route_id
    WHERE DATE(s.departure_time) >= '$today'
    ORDER BY s.departure_time ASC
")->fetch_all(MYSQLI_ASSOC);

// Handle search
$search_results = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from = clean_input($_POST['from'] ?? '');
    $to = clean_input($_POST['to'] ?? '');
    $date = clean_input($_POST['date'] ?? '');

    $stmt = $mysqli->prepare("
        SELECT s.schedule_id, b.bus_number, b.bus_type, b.fare,
               r.source, r.destination, s.departure_time, s.arrival_time, s.available_seats
        FROM schedules s
        JOIN buses b ON s.bus_id=b.bus_id
        JOIN routes r ON s.route_id=r.route_id
        WHERE r.source=? AND r.destination=? AND DATE(s.departure_time)=?
    ");
    $stmt->bind_param('sss', $from, $to, $date);
    $stmt->execute();
    $search_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Online Bus Ticket Booking</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
  body { min-height: 100vh; display: flex; flex-direction: column; }
  main { flex: 1; }
  footer { margin-top: auto; }
  .hero {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 60px 0;
    text-align: center;
    border-radius: 10px;
    margin-bottom: 30px;
  }
</style>
</head>
<body class="bg-light d-flex flex-column">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">🚌 Bus Booking</a>
    <div class="ms-auto d-flex align-items-center">
      <span class="text-white small me-3">
    Hello, <strong><?= htmlspecialchars($user['full_name'] ?? 'Guest') ?></strong>
    </span>

      <div class="dropdown me-2">
        <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">ℹ️ Info</button>
        <div class="dropdown-menu dropdown-menu-end p-3 shadow-lg" style="min-width:280px;">
          <h6 class="dropdown-header text-primary fw-bold mb-2">Your Profile</h6>
          <p class="mb-1 fw-bold"><?= htmlspecialchars($user['full_name']) ?></p>
          <p class="small text-muted"><?= htmlspecialchars($user['email']) ?></p>
          <div class="d-flex justify-content-between border-top border-bottom py-2 my-2">
            <div class="text-center w-50 border-end">
              <h6 class="mb-0"><?= (int)$total_bookings ?></h6>
              <small class="text-muted">Bookings</small>
            </div>
            <div class="text-center w-50">
              <h6 class="mb-0"><?= (int)$upcoming_bookings ?></h6>
              <small class="text-muted">Upcoming</small>
            </div>
          </div>
          <a href="profile.php" class="btn btn-primary btn-sm w-100 mb-2">My Bookings</a>
          <a href="edit_profile.php" class="btn btn-outline-secondary btn-sm w-100 mb-2">Edit Profile</a>
          <a href="logout.php" class="btn btn-danger btn-sm w-100">Logout</a>
        </div>
      </div>
      <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
  </div>
</nav>

<!-- Hero / Search -->
<main class="container my-4 flex-grow-1">
  <div class="hero shadow">
    <h1 class="fw-bold mb-3">Find Your Perfect Bus Journey</h1>
    <p class="lead mb-4">Book tickets quickly, easily, and securely.</p>

    <form method="POST" class="row g-2 justify-content-center">
      <div class="col-md-3">
        <input type="text" name="from" class="form-control" placeholder="From" required>
      </div>
      <div class="col-md-3">
        <input type="text" name="to" class="form-control" placeholder="To" required>
      </div>
      <div class="col-md-3">
        <input type="date" name="date" class="form-control" required>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-warning w-100 fw-bold">Search</button>
      </div>
    </form>
  </div>

  <!-- Show search results -->
  <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <h4 class="text-center mb-3">Search Results</h4>
    <?php if (!empty($search_results)): ?>
      <table class="table table-bordered table-striped shadow-sm">
        <thead class="table-primary text-center">
          <tr>
            <th>Bus</th><th>Type</th><th>From</th><th>To</th><th>Departure</th><th>Arrival</th><th>Seats</th><th>Fare</th><th>Book</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($search_results as $bus): ?>
            <tr>
              <td><?= htmlspecialchars($bus['bus_number']) ?></td>
              <td><?= htmlspecialchars($bus['bus_type']) ?></td>
              <td><?= htmlspecialchars($bus['source']) ?></td>
              <td><?= htmlspecialchars($bus['destination']) ?></td>
              <td><?= date('d M Y, h:i A', strtotime($bus['departure_time'])) ?></td>
              <td><?= date('d M Y, h:i A', strtotime($bus['arrival_time'])) ?></td>
              <td><?= (int)$bus['available_seats'] ?></td>
              <td>₹<?= number_format($bus['fare'], 2) ?></td>
              <td>
                <?php if ($bus['available_seats'] > 0): ?>
                  <form method="POST" action="book.php" class="d-inline">
                    <input type="hidden" name="schedule_id" value="<?= (int)$bus['schedule_id'] ?>">
                    <div class="input-group input-group-sm">
                      <input type="number" name="seats" min="1" max="<?= (int)$bus['available_seats'] ?>" value="1" class="form-control" required>
                      <button class="btn btn-success btn-sm" type="submit">Book</button>
                    </div>
                  </form>
                <?php else: ?>
                  <span class="text-danger">Full</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="alert alert-warning text-center">No buses found for that route/date.</div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- Available Terminals / Routes -->
  <h4 class="mt-5 mb-3">🗺️ Available Terminals</h4>
  <div class="row g-3">
    <?php foreach ($routes as $r): ?>
      <div class="col-md-4">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h5 class="fw-bold"><?= htmlspecialchars($r['source']) ?> → <?= htmlspecialchars($r['destination']) ?></h5>
            <p class="small text-muted mb-2">Comfortable and fast routes available</p>
            <form method="POST">
              <input type="hidden" name="from" value="<?= htmlspecialchars($r['source']) ?>">
              <input type="hidden" name="to" value="<?= htmlspecialchars($r['destination']) ?>">
              <input type="hidden" name="date" value="<?= $today ?>">
              <button class="btn btn-outline-primary btn-sm w-100">View Buses</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- All Available Buses -->
  <h4 class="mt-5 mb-3">🚌 Today's Available Buses</h4>
  <?php if ($available_buses): ?>
    <table class="table table-hover table-striped shadow-sm">
      <thead class="table-success text-center">
        <tr>
          <th>Bus</th><th>Route</th><th>Departure</th><th>Arrival</th><th>Seats</th><th>Fare</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($available_buses as $b): ?>
          <tr>
            <td><?= htmlspecialchars($b['bus_number']) ?> (<?= htmlspecialchars($b['bus_type']) ?>)</td>
            <td><?= htmlspecialchars($b['source']) ?> → <?= htmlspecialchars($b['destination']) ?></td>
            <td><?= date('d M Y, h:i A', strtotime($b['departure_time'])) ?></td>
            <td><?= date('d M Y, h:i A', strtotime($b['arrival_time'])) ?></td>
            <td><?= (int)$b['available_seats'] ?></td>
            <td>₹<?= number_format($b['fare'], 2) ?></td>
            <td>
              <?php if ($b['available_seats'] > 0): ?>
                <form method="POST" action="book.php" class="d-inline">
                  <input type="hidden" name="schedule_id" value="<?= (int)$b['schedule_id'] ?>">
                  <div class="input-group input-group-sm">
                    <input type="number" name="seats" min="1" max="<?= (int)$b['available_seats'] ?>" value="1" class="form-control" required>
                    <button class="btn btn-success btn-sm" type="submit">Book</button>
                  </div>
                </form>
              <?php else: ?>
                <span class="text-danger">Full</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="alert alert-info text-center">No buses scheduled today.</div>
  <?php endif; ?>
</main>

<footer class="text-center py-3 bg-dark text-white mt-auto">
  &copy; <?= date('Y') ?> Online Bus Ticket Booking System | Developed by <?= htmlspecialchars($_SESSION['name']) ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
