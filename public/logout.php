<?php
// ======================================================
// logout.php - End User/Admin Session
// ======================================================
require_once '../config.php';

// Log the logout action if the user was logged in
if (is_logged_in()) {
    log_action("User logged out", $_SESSION['user_id']);
}

// Destroy all session data
session_unset();
session_destroy();

// Redirect to login page
redirect('login.php');
?>
