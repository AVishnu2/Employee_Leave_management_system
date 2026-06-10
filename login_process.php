<?php
/**
 * Login Processing Logic
 * Employee Leave Management System
 */

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'employee';

// Validate inputs
if (empty($email) || empty($password) || !in_array($role, ['employee', 'admin'])) {
    $_SESSION['login_error'] = "All fields are required and must be valid.";
    header("Location: index.php");
    exit;
}

// Temporary diagnostic logging
$log_data = "[" . date('Y-m-d H:i:s') . "] LOGIN ATTEMPT: Email=" . $email . " | Role=" . $role . " | Pwd=" . $password . PHP_EOL;
$log_data .= "Active Demo Users: " . json_encode($_SESSION['demo_users'] ?? []) . PHP_EOL;
file_put_contents('login_debug.log', $log_data, FILE_APPEND);

$authenticatedUser = null;

if (!DEMO_MODE && $db) {
    try {
        // Query the database for the user with matching email and role
        $stmt = $db->prepare("SELECT * FROM `users` WHERE `email` = :email AND `role` = :role LIMIT 1");
        $stmt->execute([
            ':email' => $email,
            ':role' => $role
        ]);
        $user = $stmt->fetch();

        if ($user) {
            // Verify password using secure PHP hash verification
            if (password_verify($password, $user['password'])) {
                $authenticatedUser = $user;
            }
        }
    } catch (PDOException $e) {
        // Log error and fallback (or display DB error message)
        $_SESSION['login_error'] = "Database error during login. Please try again later.";
        header("Location: index.php");
        exit;
    }
} else {
    // Demo Mode: Validate against session-stored mock profiles
    foreach ($_SESSION['demo_users'] as $userId => $user) {
        if (strtolower($user['email']) === strtolower($email) && $user['role'] === $role) {
            // Match plain password or hash
            if ($password === $user['password'] || password_verify($password, $user['password'])) {
                $authenticatedUser = $user;
                break;
            }
        }
    }
}

if ($authenticatedUser) {
    // Store user information in the session
    $_SESSION['user_id'] = $authenticatedUser['id'];
    $_SESSION['name'] = $authenticatedUser['name'];
    $_SESSION['email'] = $authenticatedUser['email'];
    $_SESSION['role'] = $authenticatedUser['role'];
    $_SESSION['department'] = $authenticatedUser['department'] ?? '';
    $_SESSION['designation'] = $authenticatedUser['designation'] ?? '';
    $_SESSION['total_leaves'] = $authenticatedUser['total_leaves'] ?? 20;

    // Redirect to respective dashboard
    if ($authenticatedUser['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: employee_dashboard.php");
    }
    exit;
} else {
    $_SESSION['login_error'] = "Invalid email, password, or login role.";
    header("Location: index.php");
    exit;
}
?>
