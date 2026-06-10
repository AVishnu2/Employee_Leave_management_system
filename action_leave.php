<?php
/**
 * Action Leave Application (Approve/Reject)
 * Employee Leave Management System
 */

require_once 'config.php';

// Session Validation: Ensure admin is authenticated
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php");
    exit;
}

// Gather and sanitize inputs
$leave_id = filter_input(INPUT_POST, 'leave_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
$admin_remark = filter_input(INPUT_POST, 'admin_remark', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$leave_id || !in_array($action, ['Approve', 'Reject'])) {
    $_SESSION['action_error'] = "Invalid request arguments.";
    header("Location: admin_dashboard.php");
    exit;
}

// Map Action to database ENUM states
$status_db = ($action === 'Approve') ? 'Approved' : 'Rejected';

if ($action === 'Reject' && empty(trim($admin_remark))) {
    $_SESSION['action_error'] = "Remarks are mandatory when rejecting a leave request.";
    header("Location: admin_dashboard.php");
    exit;
}

$success = false;

if (!DEMO_MODE && $db) {
    try {
        // Update database record
        $stmt = $db->prepare("UPDATE `leaves` 
                              SET `status` = :status, `admin_remark` = :admin_remark, `approved_by` = :admin_id 
                              WHERE `id` = :leave_id");
        $success = $stmt->execute([
            ':status' => $status_db,
            ':admin_remark' => $admin_remark ?: null,
            ':admin_id' => $_SESSION['user_id'],
            ':leave_id' => $leave_id
        ]);
    } catch (PDOException $e) {
        $_SESSION['action_error'] = "Database error: Could not process request. Please try again.";
        header("Location: admin_dashboard.php");
        exit;
    }
} else {
    // Demo Mode: Update $_SESSION['demo_leaves']
    if (isset($_SESSION['demo_leaves'][$leave_id])) {
        $_SESSION['demo_leaves'][$leave_id]['status'] = $status_db;
        $_SESSION['demo_leaves'][$leave_id]['admin_remark'] = $admin_remark ?: null;
        $_SESSION['demo_leaves'][$leave_id]['approved_by'] = $_SESSION['user_id'];
        $success = true;
    } else {
        $_SESSION['action_error'] = "Error: Request ID " . $leave_id . " not found in demo session.";
        header("Location: admin_dashboard.php");
        exit;
    }
}

if ($success) {
    $_SESSION['action_success'] = "Leave request was successfully " . strtolower($status_db) . ".";
}

header("Location: admin_dashboard.php");
exit;
?>
