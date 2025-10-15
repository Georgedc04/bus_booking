<?php
// ======================================================
// admin/edit_bus.php - Edit Existing Bus (Admin Only)
// ======================================================
require_once '../config.php';
require_admin();

$error = "";
$success = "";

// Get bus ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('dashboard.php');
}
$bus_id = intval($_GET['id']);

// Fetch all operators for dropdown
$operators = $mysqli->query("SELECT operator_id, operator_name FROM operators ORDER BY operator_name ASC")->fetch_all(MYSQLI_ASSOC);

// Fetch bus details
$stmt = $mysqli->prepare("SELECT * FROM buses WHERE bus_id=?");
$stmt->bind_param('i', $bus_id);
$stmt->execute();
$bus = $stmt->get_result()->fetch_assoc();

if (!$bus) {
    redirect('dashboard.php');
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operator_id = intval($_POST['operator_id']);
    $bus_number = clean_input($_POST['bus_number']);
    $bus_type = clean_input($_POST['bus_type']);
    $total_seats = intval($_POST['total_seats']);
    $fare = floatval($_POST['fare']);

    if (empty($bus_number) || $total_seats <= 0 || $fare <= 0) {
        $error = "⚠️ Please fill all fields correctly.";
    } else {
        // Check if bus number belongs to another bus
        $check = $mysqli->prepare("SELECT bus_id FROM buses WHERE bus_number = ? AND bus_id != ?");
        $check->bind_param('si', $bus_number, $bus_id);
        $check->execute();
        $exists = $check->get_result();

        if ($exists->num_rows > 0) {
            $error = "❌ Another bus with this number already exists.";
        } else {
            $update = $mysqli->prepare("UPDATE buses SET operator_id=?, bus_number=?, bus_type=?, total_seats=?, fare=? WHERE bus_id=?");
            $update->bind_param('issidi', $operator_id, $bus_number, $bus_type, $total_seats, $fare, $bus_id);

            if ($update->execute()) {
                log_action("Admin edited bus: $bus_number", $_SESSION['user_id']);
                $success = "✅ Bus details updated successfully!";
                // Refresh bus data
                $stmt->execute();
                $bus = $stmt->get_result()->fetch_assoc();
            } else {
                $error = "❌ Update failed. " . $mysqli->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Bus - Admin Panel</title>
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
        <div class="card-header bg-warning text-dark text-center">
            <h4>Edit Bus Details</h4>
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
                        <?php foreach ($operators as $op): ?>
                            <option value="<?= $op['operator_id'] ?>" <?= $bus['operator_id'] == $op['operator_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($op['operator_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Bus Number</label>
                    <input type="text" name="bus_number" class="form-control" value="<?= htmlspecialchars($bus['bus_number']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Bus Type</label>
                    <select name="bus_type" class="form-select" required>
                        <option value="AC" <?= $bus['bus_type'] == 'AC' ? 'selected' : '' ?>>AC</option>
                        <option value="Non-AC" <?= $bus['bus_type'] == 'Non-AC' ? 'selected' : '' ?>>Non-AC</option>
                        <option value="Sleeper" <?= $bus['bus_type'] == 'Sleeper' ? 'selected' : '' ?>>Sleeper</option>
                        <option value="Semi-Sleeper" <?= $bus['bus_type'] == 'Semi-Sleeper' ? 'selected' : '' ?>>Semi-Sleeper</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Total Seats</label>
                    <input type="number" name="total_seats" class="form-control" min="1" value="<?= htmlspecialchars($bus['total_seats']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Fare (₹)</label>
                    <input type="number" name="fare" class="form-control" min="1" step="0.01" value="<?= htmlspecialchars($bus['fare']) ?>" required>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-warning w-100">Update Bus</button>
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
