<?php
// ======================================================
// book.php - Secure and clean Bus Ticket Booking handler
// ======================================================
require_once '../config.php';
require_login();

$error = "";
$success = "";

// Run only when POST data exists
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
    $seats = isset($_POST['seats']) ? intval($_POST['seats']) : 0;
    $user_id = $_SESSION['user_id'] ?? 0;

    if ($schedule_id <= 0 || $seats <= 0 || $user_id <= 0) {
        $error = "⚠️ Invalid booking request.";
    } else {
        // Get bus & route details
        $stmt = $mysqli->prepare("
            SELECT s.available_seats, b.fare, b.bus_number, r.source, r.destination
            FROM schedules s
            JOIN buses b ON s.bus_id = b.bus_id
            JOIN routes r ON s.route_id = r.route_id
            WHERE s.schedule_id = ?
        ");
        $stmt->bind_param('i', $schedule_id);
        $stmt->execute();
        $bus = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$bus) {
            $error = "❌ Schedule not found.";
        } elseif ($bus['available_seats'] < $seats) {
            $error = "❌ Only " . $bus['available_seats'] . " seats available.";
        } else {
            // Total fare
            $total = $seats * $bus['fare'];

            // Insert booking
            $insert = $mysqli->prepare("
                INSERT INTO bookings (user_id, schedule_id, seats_booked, total_amount, status, booking_date)
                VALUES (?, ?, ?, ?, 'Booked', NOW())
            ");
            $insert->bind_param('iiid', $user_id, $schedule_id, $seats, $total);

            if ($insert->execute()) {
                // Reduce available seats
                $update = $mysqli->prepare("UPDATE schedules SET available_seats = available_seats - ? WHERE schedule_id = ?");
                $update->bind_param('ii', $seats, $schedule_id);
                $update->execute();

                // Log the action
                log_action("Booked $seats seat(s) from {$bus['source']} to {$bus['destination']} on {$bus['bus_number']}", $user_id);

                $success = "✅ Booking successful! You booked $seats seat(s) from {$bus['source']} → {$bus['destination']}.";
            } else {
                $error = "❌ Booking failed. Please try again.";
            }
        }
    }
} else {
    $error = "⚠️ Invalid access method. Please use the booking form.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Ticket - Bus Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5" style="max-width: 600px;">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white text-center">
            <h4>Bus Ticket Booking</h4>
        </div>
        <div class="card-body">

            <?php if ($error): ?>
                <div class="alert alert-danger text-center"><?= $error ?></div>
                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-secondary">← Back to Home</a>
                </div>
            <?php elseif ($success): ?>
                <div class="alert alert-success text-center"><?= $success ?></div>
                <div class="text-center mt-3">
                    <a href="profile.php" class="btn btn-success">View My Bookings</a>
                    <a href="index.php" class="btn btn-primary">Book Another</a>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">Processing your booking...</div>
            <?php endif; ?>

        </div>
    </div>
</div>

<footer class="text-center mt-5 py-3 bg-dark text-white">
    &copy; <?= date('Y') ?> Online Bus Ticket Booking System | Developed by GK Tech
</footer>

</body>
</html>
