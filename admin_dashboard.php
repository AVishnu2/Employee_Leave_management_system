<?php
/**
 * Admin Dashboard
 * Employee Leave Management System
 */

require_once 'config.php';

// Session Validation: Ensure admin is authenticated
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Helper to calculate leave duration in days
function getLeaveDurationInDays($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $end->modify('+1 day'); // inclusive
    $interval = $start->diff($end);
    return $interval->days;
}

$all_leaves = [];
$total_employees = 0;
$pending_count = 0;
$approved_count = 0;
$active_today_count = 0;

$today_date = date('Y-m-d');

if (!DEMO_MODE && $db) {
    try {
        // Fetch all leaves joined with user data
        $stmt = $db->query("SELECT l.*, u.name as employee_name, u.email as employee_email, u.department as employee_dept, u.designation as employee_desig 
                            FROM `leaves` l 
                            JOIN `users` u ON l.user_id = u.id 
                            ORDER BY l.created_at DESC");
        $all_leaves = $stmt->fetchAll();

        // Fetch total employees count
        $stmt_emp = $db->query("SELECT COUNT(*) as count FROM `users` WHERE `role` = 'employee'");
        $row_emp = $stmt_emp->fetch();
        $total_employees = $row_emp['count'] ?? 0;

    } catch (PDOException $e) {
        $error_db = "Unable to fetch data from database.";
    }
} else {
    // Demo Mode: Fetch from sessions
    $total_employees = 0;
    foreach ($_SESSION['demo_users'] as $user) {
        if ($user['role'] === 'employee') {
            $total_employees++;
        }
    }

    foreach ($_SESSION['demo_leaves'] as $leave) {
        $emp = $_SESSION['demo_users'][$leave['user_id']] ?? null;
        $all_leaves[] = array_merge($leave, [
            'employee_name' => $emp ? $emp['name'] : 'Unknown Employee',
            'employee_email' => $emp ? $emp['email'] : '',
            'employee_dept' => $emp ? $emp['department'] : '',
            'employee_desig' => $emp ? $emp['designation'] : ''
        ]);
    }
    
    // Sort leaves by created_at DESC
    usort($all_leaves, function($a, $b) {
        return strcmp($b['created_at'], $a['created_at']);
    });
}

// Calculate Stats
foreach ($all_leaves as $leave) {
    if ($leave['status'] === 'Pending') {
        $pending_count++;
    } elseif ($leave['status'] === 'Approved') {
        $approved_count++;
        
        // Active leaves today check
        if ($today_date >= $leave['start_date'] && $today_date <= $leave['end_date']) {
            $active_today_count++;
        }
    }
}

// Get action feedback messages
$success_message = $_SESSION['action_success'] ?? '';
$error_message = $_SESSION['action_error'] ?? '';
unset($_SESSION['action_success'], $_SESSION['action_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LeaveSpace</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="bg-mesh"></div>

    <?php if (DEMO_MODE): ?>
    <!-- Demo Banner -->
    <div class="demo-banner">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>Demo Mode Active:</strong> You can approve or reject leaves here. Operations will affect mock users and reset on session end.
    </div>
    <?php endif; ?>

    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="d-flex align-items-center">
            <i class="bi bi-calendar2-check-fill fs-4 text-primary me-2"></i>
            <span class="fw-bold brand-title fs-5">LeaveSpace Admin</span>
        </div>
        <button class="btn btn-outline-secondary btn-sm" id="sidebarToggle">
            <i class="bi bi-list fs-4"></i>
        </button>
    </div>

    <div class="dashboard-wrapper">
        
        <!-- Sidebar Navigation -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="bi bi-calendar2-check-fill fs-3 text-primary me-2"></i>
                    <span class="fw-bold brand-title fs-4">LeaveSpace</span>
                </div>
                <button class="btn-close d-lg-none" id="sidebarClose" aria-label="Close"></button>
            </div>

            <!-- Profile Info Widget -->
            <div class="px-4 py-3 border-bottom bg-light bg-opacity-50">
                <div class="d-flex align-items-center">
                    <div class="avatar me-3" style="background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);">
                        AD
                    </div>
                    <div>
                        <h6 class="mb-0 fw-semibold text-truncate" style="max-width: 160px;"><?php echo htmlspecialchars($_SESSION['name']); ?></h6>
                        <span class="text-muted small d-block">System Administrator</span>
                    </div>
                </div>
            </div>

            <!-- Sidebar Navigation Links -->
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="admin_dashboard.php" class="sidebar-link active">
                        <i class="bi bi-shield-shaded"></i>
                        <span>Admin Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#pending-requests-section" class="sidebar-link">
                        <i class="bi bi-file-earmark-diff"></i>
                        <span>Pending Requests</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#all-history-section" class="sidebar-link">
                        <i class="bi bi-clock-history"></i>
                        <span>All Leave History</span>
                    </a>
                </li>
                <li class="sidebar-item mt-auto">
                    <a href="logout.php" class="sidebar-link text-danger">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer text-center">
                <span class="text-muted small">&copy; 2026 LeaveSpace Admin</span>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            
            <!-- Dashboard Title Banner -->
            <div class="d-md-flex align-items-center justify-content-between mb-4 fade-in">
                <div>
                    <h3 class="fw-bold mb-1">Leave Management Control Panel</h3>
                    <p class="text-muted mb-0">Review pending requests, check current statuses, and manage staff leave quotas.</p>
                </div>
            </div>

            <!-- Toast / Success Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 py-3 px-4 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 py-3 px-4 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Admin Stats Grid -->
            <div class="row g-4 mb-5 fade-in">
                <!-- Pending Leaves count -->
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="text-muted fw-medium">Pending Requests</span>
                            <div class="icon-wrapper" style="background-color: var(--warning-bg); color: var(--warning);">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                        </div>
                        <h2 class="fw-bold mb-1"><?php echo $pending_count; ?></h2>
                        <span class="text-muted small">Requires your action</span>
                    </div>
                </div>

                <!-- Active Leaves Today -->
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="text-muted fw-medium">Active Leaves Today</span>
                            <div class="icon-wrapper" style="background-color: rgba(168, 85, 247, 0.1); color: var(--secondary);">
                                <i class="bi bi-person-workspace"></i>
                            </div>
                        </div>
                        <h2 class="fw-bold mb-1"><?php echo $active_today_count; ?></h2>
                        <span class="text-muted small">Employees out today</span>
                    </div>
                </div>

                <!-- Total Approved Leaves -->
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="text-muted fw-medium">Total Approved</span>
                            <div class="icon-wrapper" style="background-color: var(--success-bg); color: var(--success);">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                        </div>
                        <h2 class="fw-bold mb-1"><?php echo $approved_count; ?></h2>
                        <span class="text-muted small">Historic approved leaves</span>
                    </div>
                </div>

                <!-- Total Staff -->
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="text-muted fw-medium">Total Employees</span>
                            <div class="icon-wrapper" style="background-color: var(--info-bg); color: var(--info);">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                        <h2 class="fw-bold mb-1"><?php echo $total_employees; ?></h2>
                        <span class="text-muted small">Registered staff members</span>
                    </div>
                </div>
            </div>

            <!-- SECTION: PENDING LEAVE REQUESTS -->
            <div id="pending-requests-section" class="card-premium mb-5 fade-in">
                <div class="card-premium-header">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-file-earmark-diff text-warning me-2"></i>Pending Leave Requests
                    </h5>
                    <span class="badge bg-warning text-dark rounded-pill fw-semibold"><?php echo $pending_count; ?> Pending</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-premium">
                        <thead>
                            <tr>
                                <th>Employee Details</th>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>Reason</th>
                                <th>Applied Date</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $has_pending = false;
                            foreach ($all_leaves as $leave): 
                                if ($leave['status'] !== 'Pending') continue;
                                $has_pending = true;
                                $duration = getLeaveDurationInDays($leave['start_date'], $leave['end_date']);
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($leave['employee_name']); ?></div>
                                        <div class="text-muted small">
                                            <?php echo htmlspecialchars($leave['employee_desig']); ?> &bull; 
                                            <span class="text-primary"><?php echo htmlspecialchars($leave['employee_dept']); ?></span>
                                        </div>
                                    </td>
                                    <td class="fw-medium text-dark"><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                                    <td>
                                        <div class="fw-semibold">
                                            <?php echo date('M d, Y', strtotime($leave['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($leave['end_date'])); ?>
                                        </div>
                                        <small class="text-muted"><?php echo $duration; ?> <?php echo ($duration == 1) ? 'day' : 'days'; ?></small>
                                    </td>
                                    <td>
                                        <span class="d-inline-block text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($leave['reason']); ?>">
                                            <?php echo htmlspecialchars($leave['reason']); ?>
                                        </span>
                                    </td>
                                    <td class="text-muted">
                                        <?php echo date('M d, Y H:i', strtotime($leave['created_at'])); ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <!-- Approve Trigger -->
                                            <button type="button" class="btn btn-sm btn-success rounded-3 px-3 modal-action-trigger" 
                                                    data-bs-toggle="modal" data-bs-target="#adminActionModal"
                                                    data-leave-id="<?php echo $leave['id']; ?>"
                                                    data-action="Approve"
                                                    data-employee-name="<?php echo htmlspecialchars($leave['employee_name']); ?>"
                                                    data-leave-type="<?php echo htmlspecialchars($leave['leave_type']); ?>">
                                                <i class="bi bi-check-lg me-1"></i>Approve
                                            </button>
                                            
                                            <!-- Reject Trigger -->
                                            <button type="button" class="btn btn-sm btn-danger rounded-3 px-3 modal-action-trigger" 
                                                    data-bs-toggle="modal" data-bs-target="#adminActionModal"
                                                    data-leave-id="<?php echo $leave['id']; ?>"
                                                    data-action="Reject"
                                                    data-employee-name="<?php echo htmlspecialchars($leave['employee_name']); ?>"
                                                    data-leave-type="<?php echo htmlspecialchars($leave['leave_type']); ?>">
                                                <i class="bi bi-x-lg me-1"></i>Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (!$has_pending): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-clipboard-check fs-1 mb-2 d-block opacity-50 text-success"></i>
                                        Hurrah! No pending leave requests to action.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECTION: ALL LEAVE HISTORY -->
            <div id="all-history-section" class="card-premium fade-in">
                <div class="card-premium-header">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-clock-history text-primary me-2"></i>Global Leave Audit Log
                    </h5>
                    <span class="text-muted small"><?php echo count($all_leaves); ?> Request(s) Total</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-premium">
                        <thead>
                            <tr>
                                <th>Employee Details</th>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Admin Remark</th>
                                <th>Created On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_leaves)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-calendar-x fs-1 mb-2 d-block opacity-50"></i>
                                        No leaves recorded in the system.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_leaves as $leave): 
                                    $duration = getLeaveDurationInDays($leave['start_date'], $leave['end_date']);
                                    $status_class = 'badge-pending';
                                    $status_icon = 'bi-hourglass-split';
                                    if ($leave['status'] === 'Approved') {
                                        $status_class = 'badge-approved';
                                        $status_icon = 'bi-check-circle-fill';
                                    } elseif ($leave['status'] === 'Rejected') {
                                        $status_class = 'badge-rejected';
                                        $status_icon = 'bi-x-circle-fill';
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($leave['employee_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($leave['employee_dept']); ?></small>
                                        </td>
                                        <td class="fw-medium text-dark"><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                                        <td>
                                            <div class="fw-semibold">
                                                <?php echo date('M d, Y', strtotime($leave['start_date'])); ?> - 
                                                <?php echo date('M d, Y', strtotime($leave['end_date'])); ?>
                                            </div>
                                            <small class="text-muted"><?php echo $duration; ?> <?php echo ($duration == 1) ? 'day' : 'days'; ?></small>
                                        </td>
                                        <td>
                                            <span class="badge-custom <?php echo $status_class; ?>">
                                                <i class="bi <?php echo $status_icon; ?>"></i>
                                                <?php echo $leave['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($leave['admin_remark'])): ?>
                                                <span class="text-secondary small">
                                                    <i class="bi bi-chat-left-text me-1 text-muted"></i>
                                                    <?php echo htmlspecialchars($leave['admin_remark']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted">
                                            <?php echo date('M d, Y H:i', strtotime($leave['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Unified Action Modal (Approve / Reject) -->
    <div class="modal fade" id="adminActionModal" tabindex="-1" aria-labelledby="adminActionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header border-bottom-0 pt-4 px-4 pb-0">
                    <h5 class="modal-title fw-bold" id="adminActionModalLabel">
                        <i class="bi bi-clipboard2-check text-primary me-2"></i>Review Leave Request
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="action_leave.php" method="POST" class="needs-validation" novalidate>
                    <div class="modal-body px-4 py-3">
                        
                        <!-- Hidden inputs -->
                        <input type="hidden" name="leave_id" id="action_leave_id" value="">
                        <input type="hidden" name="action" id="action_type" value="">

                        <!-- Summary block inside modal -->
                        <div class="p-3 bg-light rounded-3 mb-3 border border-light">
                            <div class="row g-2">
                                <div class="col-4 text-muted small">Employee:</div>
                                <div class="col-8 fw-semibold" id="modalEmployeeName">John Doe</div>
                                <div class="col-4 text-muted small">Leave Type:</div>
                                <div class="col-8 fw-semibold" id="modalLeaveType">Sick Leave</div>
                                <div class="col-4 text-muted small">Operation:</div>
                                <div class="col-8" id="modalOperation"><span id="modalActionTitle">Action</span></div>
                            </div>
                        </div>

                        <!-- Admin Remark Text Area -->
                        <div class="mb-3">
                            <label for="admin_remark" class="form-label fw-medium text-secondary">Admin Remarks / Notes</label>
                            <textarea class="form-control form-control-custom" id="admin_remark" name="admin_remark" rows="4" placeholder="Add optional remarks or notes regarding this decision..."></textarea>
                            <div class="invalid-feedback">Please provide remarks if rejecting.</div>
                        </div>

                    </div>
                    <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn btn-outline-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-custom px-4 rounded-3" id="confirmActionButton">Confirm Decision</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS Asset -->
    <script src="main.js"></script>
    
    <script>
        // Set dynamic submit button labels and classes in modal based on action type
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('adminActionModal');
            modal.addEventListener('show.bs.modal', function () {
                const action = document.getElementById('action_type').value;
                const submitBtn = document.getElementById('confirmActionButton');
                const textarea = document.getElementById('admin_remark');

                if (action === 'Approve') {
                    submitBtn.innerText = 'Approve Request';
                    submitBtn.className = 'btn btn-success px-4 rounded-3';
                    textarea.placeholder = 'Add optional remarks for the employee (e.g., "Approved, cover plan in place")...';
                    textarea.required = false;
                } else {
                    submitBtn.innerText = 'Reject Request';
                    submitBtn.className = 'btn btn-danger px-4 rounded-3';
                    textarea.placeholder = 'Provide reasons for rejection (Required)...';
                    textarea.required = true;
                }
            });
        });

        // Validation Bootstrap
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>
