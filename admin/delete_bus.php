<?php
// ======================================================
// admin/delete_bus.php - Delete Bus (Admin Only)
// ======================================================
require_once '../config.php';
require_admin();

// Check if bus ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('dashboard.php');
}

$bus_id = intval($_GET['id']);

// Fetch bus details before deleting (for logs)
$stmt = $mysqli->prepare("SELECT bus_number FROM buses WHERE bus_id = ?");
$stmt->bind_param('i', $bus_id);
$stmt->execute();
$result = $stmt->get_result();
$bus = $result->fetch_assoc();

if (!$bus) {
    redirect('dashboard.php');
}

// Begin deletion
$mysqli->begin_transaction();

try {
    // Delete any schedules linked to this bus
    $del_schedules = $mysqli->prepare("DELETE FROM schedules WHERE bus_id = ?");
    $del_schedules->bind_param('i', $bus_id);
    $del_schedules->execute();

    // Delete bus itself
    $del_bus = $mysqli->prepare("DELETE FROM buses WHERE bus_id = ?");
    $del_bus->bind_param('i', $bus_id);
    $del_bus->execute();

    // Commit changes
    $mysqli->commit();

    // Log deletion
    log_action("Admin deleted bus: {$bus['bus_number']}", $_SESSION['user_id']);

    // Redirect back to dashboard with message
    header("Location: dashboard.php?deleted=1");
    exit;

} catch (Exception $e) {
    // Rollback if something failed
    $mysqli->rollback();
    echo "<script>alert('❌ Failed to delete bus: {$e->getMessage()}'); window.location.href='dashboard.php';</script>";
}
?>
