<?php
/**
 * Update User Profile
 * Employee Leave Management System
 */

require_once 'config.php';

// Session Validation: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: employee_dashboard.php");
    exit;
}

// Sanitize inputs
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
$department = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_SPECIAL_CHARS);
$designation = filter_input(INPUT_POST, 'designation', FILTER_SANITIZE_SPECIAL_CHARS);

if (empty($name) || empty($department) || empty($designation)) {
    $_SESSION['profile_error'] = "All profile fields are required.";
    header("Location: employee_dashboard.php");
    exit;
}

$success = false;
$user_id = $_SESSION['user_id'];

if (!DEMO_MODE && $db) {
    try {
        // Update DB record
        $stmt = $db->prepare("UPDATE `users` 
                              SET `name` = :name, `department` = :department, `designation` = :designation 
                              WHERE `id` = :id");
        $success = $stmt->execute([
            ':name' => $name,
            ':department' => $department,
            ':designation' => $designation,
            ':id' => $user_id
        ]);
    } catch (PDOException $e) {
        $_SESSION['profile_error'] = "Database error: Could not update profile details.";
        header("Location: employee_dashboard.php");
        exit;
    }
} else {
    // Demo Mode: Update session array
    if (isset($_SESSION['demo_users'][$user_id])) {
        $_SESSION['demo_users'][$user_id]['name'] = $name;
        $_SESSION['demo_users'][$user_id]['department'] = $department;
        $_SESSION['demo_users'][$user_id]['designation'] = $designation;
        $success = true;
    } else {
        $_SESSION['profile_error'] = "Error: User ID " . $user_id . " not found in demo session.";
        header("Location: employee_dashboard.php");
        exit;
    }
}

if ($success) {
    // Instantly update current session variables to refresh sidebar/greeting
    $_SESSION['name'] = $name;
    $_SESSION['department'] = $department;
    $_SESSION['designation'] = $designation;
    
    $_SESSION['profile_success'] = "Your profile settings have been updated successfully!";
}

header("Location: employee_dashboard.php");
exit;
?>
