<?php
// ======================================================
// CONFIG.PHP
// Online Bus Ticket Booking System
// Author: George Kasmiro Quiriko
// ======================================================

session_start();

// ---------- DATABASE CONNECTION ----------
$DB_HOST = 'localhost';
$DB_USER = 'root';          // Default for XAMPP
$DB_PASS = '';              // Leave empty for XAMPP
$DB_NAME = 'bus_booking';   // Database name

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Check connection
if ($mysqli->connect_errno) {
    die("❌ Database connection failed: " . $mysqli->connect_error);
}

// ---------- HELPER FUNCTIONS ----------

// Redirect helper (works safely in all folders)
function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit;
    } else {
        echo "<script>window.location.href='$url';</script>";
        exit;
    }
}

// Clean and sanitize input
function clean_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// ---------- LOGIN HELPERS ----------

// Check if a user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if logged-in user is admin
function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Require user to be logged in
function require_login() {
    if (!is_logged_in()) {
        redirect('../public/login.php');
    }
}

// Require admin privileges
function require_admin() {
    if (!is_logged_in() || !is_admin()) {
        redirect('../admin/login.php');
    }
}

// ---------- LOGGING ----------
// Record important actions to audit_logs
function log_action($action, $user_id = null) {
    global $mysqli;
    if (!isset($mysqli)) return;

    $stmt = $mysqli->prepare("INSERT INTO audit_logs (action, user_id) VALUES (?, ?)");
    $stmt->bind_param('si', $action, $user_id);
    $stmt->execute();
    $stmt->close();
}

// ---------- PASSWORD SECURITY ----------

// Hash a password using bcrypt
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Verify hashed password
function verify_password($password, $hash) {
    if (empty($hash)) return false;
    return password_verify($password, $hash);
}

// ---------- DEBUGGING (optional, remove in production) ----------
function dd($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    exit;
}
?>
