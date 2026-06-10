<?php
/**
 * Sign Up Processing Logic
 * Employee Leave Management System
 */

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Sanitize and validate inputs
$role = $_POST['role'] ?? 'employee';
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!in_array($role, ['employee', 'admin'])) {
    $_SESSION['signup_error'] = "Invalid account role selected.";
    header("Location: index.php");
    exit;
}

if (empty($name) || !$email || empty($password)) {
    $_SESSION['signup_error'] = "All standard registration fields are required.";
    header("Location: index.php");
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['signup_error'] = "Password must be at least 6 characters long.";
    header("Location: index.php");
    exit;
}

// Extraneous fields based on role
$department = null;
$designation = null;
$join_date = null;
$total_leaves = 0;

if ($role === 'employee') {
    $department = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_SPECIAL_CHARS);
    $designation = filter_input(INPUT_POST, 'designation', FILTER_SANITIZE_SPECIAL_CHARS);
    $join_date = $_POST['join_date'] ?? null;
    $total_leaves = 20; // Default employee leaf quota

    if (empty($department) || empty($designation) || empty($join_date)) {
        $_SESSION['signup_error'] = "Please provide your Department, Designation, and Join Date.";
        header("Location: index.php");
        exit;
    }
} else {
    // Admins don't need department/designation, populate defaults
    $department = 'IT Administration';
    $designation = 'Administrator';
    $join_date = date('Y-m-d');
    $total_leaves = 0;
}

// Success flag
$success = false;

if (!DEMO_MODE && $db) {
    try {
        // 1. Check if email already exists
        $stmt_check = $db->prepare("SELECT COUNT(*) as count FROM `users` WHERE `email` = :email");
        $stmt_check->execute([':email' => $email]);
        $row = $stmt_check->fetch();

        if ($row['count'] > 0) {
            $_SESSION['signup_error'] = "The email '{$email}' is already registered.";
            header("Location: index.php");
            exit;
        }

        // 2. Hash password and insert
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt_insert = $db->prepare("INSERT INTO `users` (`name`, `email`, `password`, `role`, `department`, `designation`, `join_date`, `total_leaves`) 
                                     VALUES (:name, :email, :password, :role, :department, :designation, :join_date, :total_leaves)");
        $success = $stmt_insert->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $hashed_password,
            ':role' => $role,
            ':department' => $department,
            ':designation' => $designation,
            ':join_date' => $join_date,
            ':total_leaves' => $total_leaves
        ]);

    } catch (PDOException $e) {
        $_SESSION['signup_error'] = "Database error: Could not complete registration. Please try again.";
        header("Location: index.php");
        exit;
    }
} else {
    // Demo Mode: Add user to session array
    // Check if email already exists in session
    foreach ($_SESSION['demo_users'] as $user) {
        if (strtolower($user['email']) === strtolower($email)) {
            $_SESSION['signup_error'] = "The email '{$email}' is already registered in demo session.";
            header("Location: index.php");
            exit;
        }
    }

    $new_id = empty($_SESSION['demo_users']) ? 1 : (max(array_keys($_SESSION['demo_users'])) + 1);
    
    $_SESSION['demo_users'][$new_id] = [
        'id' => $new_id,
        'name' => $name,
        'email' => $email,
        'password' => $password, // Store plain password so it works instantly with plain matching
        'role' => $role,
        'department' => $department,
        'designation' => $designation,
        'join_date' => $join_date,
        'total_leaves' => $total_leaves
    ];
    $success = true;
}

if ($success) {
    $_SESSION['signup_success'] = "Account created successfully for " . htmlspecialchars($name) . "! Please log in below.";
}

header("Location: index.php");
exit;
?>
