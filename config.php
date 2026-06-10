<?php
/**
 * Database Configuration & Session Initialization
 * Employee Leave Management System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'employee_leave_db');

$db = null;
$demo_mode = false;

try {
    // Attempt database connection using PDO
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // If the database connection fails, fall back to Session-based Demo Mode
    $demo_mode = true;
}

define('DEMO_MODE', $demo_mode);

// Reset session if explicitly requested via URL parameter
if (isset($_GET['reset'])) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: index.php");
    exit;
}

// Initialize demo data in $_SESSION if database is unavailable
if (DEMO_MODE) {
    // Self-healing: Populate if not set, not an array, or missing users
    if (!isset($_SESSION['demo_users']) || !is_array($_SESSION['demo_users']) || !isset($_SESSION['demo_users'][1]) || !isset($_SESSION['demo_users'][2])) {
        $_SESSION['demo_users'] = [
            1 => [
                'id' => 1,
                'name' => 'System Administrator',
                'email' => 'admin@leavesys.com',
                'password' => 'admin123', // Plain password for ease in demo mode
                'role' => 'admin',
                'department' => 'IT Administration',
                'designation' => 'System Admin',
                'join_date' => '2025-01-01',
                'total_leaves' => 0
            ],
            2 => [
                'id' => 2,
                'name' => 'John Doe',
                'email' => 'employee@leavesys.com',
                'password' => 'employee123', // Plain password for ease in demo mode
                'role' => 'employee',
                'department' => 'Software Development',
                'designation' => 'Senior Developer',
                'join_date' => '2025-03-15',
                'total_leaves' => 20
            ]
        ];
    }
    
    // Self-healing: Populate leaves if not set, not an array, or missing records
    if (!isset($_SESSION['demo_leaves']) || !is_array($_SESSION['demo_leaves']) || count($_SESSION['demo_leaves']) < 3) {
        $_SESSION['demo_leaves'] = [
            101 => [
                'id' => 101,
                'user_id' => 2,
                'leave_type' => 'Sick Leave',
                'start_date' => '2026-05-10',
                'end_date' => '2026-05-12',
                'reason' => 'Suffering from viral fever. Prescribed 3 days of rest.',
                'status' => 'Approved',
                'admin_remark' => 'Hope you recover quickly!',
                'approved_by' => 1,
                'created_at' => '2026-05-09 10:00:00'
            ],
            102 => [
                'id' => 102,
                'user_id' => 2,
                'leave_type' => 'Casual Leave',
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-02',
                'reason' => 'Personal work at hometown.',
                'status' => 'Rejected',
                'admin_remark' => 'Cannot approve due to critical release milestone.',
                'approved_by' => 1,
                'created_at' => '2026-05-28 14:30:00'
            ],
            103 => [
                'id' => 103,
                'user_id' => 2,
                'leave_type' => 'Annual Leave',
                'start_date' => '2026-07-10',
                'end_date' => '2026-07-15',
                'reason' => 'Family summer vacation travel plans.',
                'status' => 'Pending',
                'admin_remark' => null,
                'approved_by' => null,
                'created_at' => '2026-06-08 09:15:00'
            ]
        ];
    }
}
?>
