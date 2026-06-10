<?php
/**
 * Submit Leave Application
 * Employee Leave Management System
 */

require_once 'config.php';

// Session Validation: Ensure employee is authenticated
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: employee_dashboard.php");
    exit;
}

// Sanitize inputs
$leave_type = filter_input(INPUT_POST, 'leave_type', FILTER_SANITIZE_SPECIAL_CHARS);
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_SPECIAL_CHARS);

// Server-side validation
if (empty($leave_type) || empty($start_date) || empty($end_date) || empty($reason)) {
    $_SESSION['apply_error'] = "All fields are required. Please fill in the entire form.";
    header("Location: employee_dashboard.php");
    exit;
}

$start_time = strtotime($start_date);
$end_time = strtotime($end_date);

if ($end_time < $start_time) {
    $_SESSION['apply_error'] = "Invalid Dates: End Date cannot be earlier than Start Date.";
    header("Location: employee_dashboard.php");
    exit;
}

// Success flag
$success = false;

if (!DEMO_MODE && $db) {
    try {
        // Insert into database
        $stmt = $db->prepare("INSERT INTO `leaves` (`user_id`, `leave_type`, `start_date`, `end_date`, `reason`, `status`) 
                              VALUES (:user_id, :leave_type, :start_date, :end_date, :reason, 'Pending')");
        $success = $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':leave_type' => $leave_type,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':reason' => $reason
        ]);
    } catch (PDOException $e) {
        $_SESSION['apply_error'] = "Database error: Could not submit your leave request. Please try again.";
        header("Location: employee_dashboard.php");
        exit;
    }
} else {
    // Demo Mode: Add to session
    $new_id = empty($_SESSION['demo_leaves']) ? 101 : (max(array_keys($_SESSION['demo_leaves'])) + 1);
    
    $_SESSION['demo_leaves'][$new_id] = [
        'id' => $new_id,
        'user_id' => $_SESSION['user_id'],
        'leave_type' => $leave_type,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'reason' => $reason,
        'status' => 'Pending',
        'admin_remark' => null,
        'approved_by' => null,
        'created_at' => date('Y-m-d H:i:s')
    ];
    $success = true;
}

if ($success) {
    $_SESSION['apply_success'] = "Your leave request for " . htmlspecialchars($leave_type) . " has been submitted successfully!";
}

header("Location: employee_dashboard.php");
exit;
?>
