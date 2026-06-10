<?php
/**
 * Logout Page
 * Employee Leave Management System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only unset the authentication keys to preserve Demo Mode session database state
$auth_keys = ['user_id', 'name', 'email', 'role', 'department', 'designation', 'total_leaves', 'login_error', 'signup_error', 'signup_success', 'apply_success', 'apply_error', 'profile_success', 'profile_error', 'action_success', 'action_error'];
foreach ($auth_keys as $key) {
    if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}

// Redirect to login page
header("Location: index.php");
exit;
?>
