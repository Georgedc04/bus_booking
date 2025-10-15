<?php
// ======================================================
// admin/add_bus.php - Add New Bus (Admin Only)
// ======================================================
require_once '../config.php';
require_admin();

$error = "";
$success = "";

// Fetch all operators for dropdown
$operators = $mysqli->query("SELECT operator_id, operator_name FROM operators ORDER BY operator_name ASC")->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operator_id = intval($_POST['operator_id']);
    $bus_number = clean_input($_POST['bus_number']);
    $bus_type = clean_input($_POST['bus_type']);
    $total_seats = intval($_POST['total_seats']);
    $fare = floatval($_POST['fare']);

    // Validation
    if (empty($bus_number) || $total_seats <= 0 || $fare <= 0) {
        $error = "⚠️ Please fill all fields correctly.";
    } else {
        // Check if bus number exists
        $check = $mysqli->prepare("SELECT bus_id FROM buses WHERE bus_number = ?");
        $check->bind_param('s', $bus_number);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $error = "❌ Bus number already exists.";
        } else {
            // Insert new bus
            $stmt = $mysqli->prepare("INSERT INTO buses (operator_id, bus_number, bus_type, total_seats, fare) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('issid', $operator_id, $bus_number, $bus_type, $total_seats, $fare);

            if ($stmt->execute()) {
                log_action("Admin added new bus: $bus_number", $_SESSION['user_id']);
                $success = "✅ Bus added successfully!";
            } else {
                $error = "❌ Failed to add bus. " . $mysqli->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Bus - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">🚌 Admin Panel</a>
        <div class="d-flex">
            <a href="dashboard.php" class="btn btn-secondary btn-sm me-2">Dashboard</a>
            <a href="../public/logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5" style="max-width: 700px;">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white text-center">
            <h4>Add New Bus</h4>
        </div>
        <div class="card-body">

            <?php if ($error): ?>
                <div class="alert alert-danger text-center"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success text-center"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Bus Operator</label>
                    <select name="operator_id" class="form-select" required>
                        <option value="">-- Select Operator --</option>
                        <?php foreach ($operators as $op): ?>
                            <option value="<?= $op['operator_id'] ?>"><?= htmlspecialchars($op['operator_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Bus Number</label>
                    <input type="text" name="bus_number" class="form-control" placeholder="e.g. UK07BUS123" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Bus Type</label>
                    <select name="bus_type" class="form-select" required>
                        <option value="AC">AC</option>
                        <option value="Non-AC">Non-AC</option>
                        <option value="Sleeper">Sleeper</option>
                        <option value="Semi-Sleeper">Semi-Sleeper</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Total Seats</label>
                    <input type="number" name="total_seats" min="1" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Fare (₹)</label>
                    <input type="number" name="fare" min="1" step="0.01" class="form-control" required>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-success w-100">Add Bus</button>
                </div>
            </form>

            <div class="text-center mt-3">
                <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<footer class="text-center mt-5 py-3 bg-dark text-white">
    &copy; <?= date('Y') ?> Online Bus Ticket Booking System | Admin Panel | Developed by George Kasmiro Quiriko
</footer>

</body>
</html>
