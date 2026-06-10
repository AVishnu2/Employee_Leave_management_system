<?php
/**
 * Employee Dashboard
 * Employee Leave Management System
 */

require_once 'config.php';

// Session validation: Redirect to login if not authenticated or not an employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: index.php");
    exit;
}

// Helper function to calculate duration in days
function getLeaveDurationInDays($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $end->modify('+1 day'); // Make date range inclusive
    $interval = $start->diff($end);
    return $interval->days;
}

$leaves = [];
$total_allowed = $_SESSION['total_leaves']; // Default 20
$approved_days = 0;
$pending_days = 0;

if (!DEMO_MODE && $db) {
    try {
        // Fetch leaves for logged in employee from DB
        $stmt = $db->prepare("SELECT * FROM `leaves` WHERE `user_id` = :user_id ORDER BY `created_at` DESC");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $leaves = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Log or show error
        $error_db = "Unable to fetch leave history from database.";
    }
} else {
    // Demo Mode: Fetch from session
    foreach ($_SESSION['demo_leaves'] as $leave) {
        if ($leave['user_id'] == $_SESSION['user_id']) {
            $leaves[] = $leave;
        }
    }
    // Sort leaves by created_at DESC (simulating DB query order)
    usort($leaves, function($a, $b) {
        return strcmp($b['created_at'], $a['created_at']);
    });
}

// Calculate leaf statistics
foreach ($leaves as $leave) {
    $days = getLeaveDurationInDays($leave['start_date'], $leave['end_date']);
    if ($leave['status'] === 'Approved') {
        $approved_days += $days;
    } elseif ($leave['status'] === 'Pending') {
        $pending_days += $days;
    }
}
$remaining_leaves = max(0, $total_allowed - $approved_days);

// Get success/error messages from redirect
$success_message = $_SESSION['apply_success'] ?? $_SESSION['profile_success'] ?? '';
$error_message = $_SESSION['apply_error'] ?? $_SESSION['profile_error'] ?? '';
unset($_SESSION['apply_success'], $_SESSION['apply_error'], $_SESSION['profile_success'], $_SESSION['profile_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - LeaveSpace</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="bg-mesh"></div>

    <?php if (DEMO_MODE): ?>
    <!-- Demo Mode Notification -->
    <div class="demo-banner">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>Demo Mode Active:</strong> Any new leave applications will be stored in your browser session and will reset once you close the session.
    </div>
    <?php endif; ?>

    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="d-flex align-items-center">
            <i class="bi bi-calendar2-check-fill fs-4 text-primary me-2"></i>
            <span class="fw-bold brand-title fs-5">LeaveSpace</span>
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
                    <div class="avatar me-3">
                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-semibold text-truncate" style="max-width: 160px;"><?php echo htmlspecialchars($_SESSION['name']); ?></h6>
                        <span class="text-muted small d-block text-truncate" style="max-width: 160px;"><?php echo htmlspecialchars($_SESSION['designation']); ?></span>
                    </div>
                </div>
                <div class="mt-2 pt-2 border-top border-light d-flex justify-content-between align-items-center">
                    <span class="badge bg-indigo-subtle text-indigo border border-indigo-subtle rounded-pill" style="font-size: 0.75rem; background-color: rgba(99, 102, 241, 0.1); color: var(--primary);">
                        <?php echo htmlspecialchars($_SESSION['department']); ?>
                    </span>
                </div>
            </div>

            <!-- Sidebar Navigation Links -->
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="employee_dashboard.php" class="sidebar-link active">
                        <i class="bi bi-grid-1x2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
                        <i class="bi bi-plus-circle"></i>
                        <span>Apply for Leave</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link" data-bs-toggle="modal" data-bs-target="#profileModal">
                        <i class="bi bi-person-gear"></i>
                        <span>Profile Settings</span>
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
                <span class="text-muted small">&copy; 2026 LeaveSpace v1.0</span>
            </div>
        </nav>

        <!-- Main Content Panel -->
        <main class="main-content">
            
            <!-- Welcome Header -->
            <div class="d-md-flex align-items-center justify-content-between mb-4 fade-in">
                <div>
                    <h3 class="fw-bold mb-1">Hello, <?php echo htmlspecialchars(explode(' ', $_SESSION['name'])[0]); ?>!</h3>
                    <p class="text-muted mb-0">Track and manage your leave applications here.</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
                        <i class="bi bi-plus-lg me-2"></i>Apply for Leave
                    </button>
                </div>
            </div>

            <!-- Session Alerts -->
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

            <!-- Leave Stats Grid -->
            <div class="row g-4 mb-4 fade-in">
                <!-- Allowed Leaves -->
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="text-muted fw-medium">Annual Quota</span>
                            <div class="icon-wrapper" style="background-color: var(--info-bg); color: var(--info);">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                        </div>
                        <h2 class="fw-bold mb-1"><?php echo $total_allowed; ?></h2>
                        <span class="text-muted small">Total allowed days</span>
                    </div>
                </div>

                <!-- Approved Leaves -->
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="text-muted fw-medium">Approved Leaves</span>
                            <div class="icon-wrapper" style="background-color: var(--success-bg); color: var(--success);">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                        </div>
                        <h2 class="fw-bold mb-1"><?php echo $approved_days; ?></h2>
                        <span class="text-muted small">Days consumed</span>
                    </div>
                </div>

                <!-- Pending Leaves -->
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="text-muted fw-medium">Pending Approvals</span>
                            <div class="icon-wrapper" style="background-color: var(--warning-bg); color: var(--warning);">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                        </div>
                        <h2 class="fw-bold mb-1"><?php echo $pending_days; ?></h2>
                        <span class="text-muted small">Days awaiting action</span>
                    </div>
                </div>

                <!-- Remaining Leaves -->
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="text-muted fw-medium">Remaining Balance</span>
                            <div class="icon-wrapper" style="background-color: rgba(168, 85, 247, 0.1); color: var(--secondary);">
                                <i class="bi bi-calendar-plus"></i>
                            </div>
                        </div>
                        <h2 class="fw-bold mb-1"><?php echo $remaining_leaves; ?></h2>
                        <span class="text-muted small">Days available</span>
                    </div>
                </div>
            </div>

            <!-- Leave History Table Card -->
            <div class="card-premium fade-in">
                <div class="card-premium-header">
                    <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>My Leave History</h5>
                    <span class="text-muted small"><?php echo count($leaves); ?> Request(s)</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-premium">
                        <thead>
                            <tr>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>Reason</th>
                                <th>Applied On</th>
                                <th class="text-center">Status</th>
                                <th>Admin Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($leaves)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-calendar-x fs-1 mb-2 d-block opacity-50"></i>
                                        You haven't applied for any leaves yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($leaves as $leave): 
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
                                        <td class="fw-semibold text-indigo-900"><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                                        <td>
                                            <div class="fw-medium">
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
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Apply Leave Modal Dialog -->
    <div class="modal fade" id="applyLeaveModal" tabindex="-1" aria-labelledby="applyLeaveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header border-bottom-0 pt-4 px-4 pb-0">
                    <h5 class="modal-title fw-bold" id="applyLeaveModalLabel">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Apply for Leave
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <form action="apply_leave.php" method="POST" id="leaveApplicationForm" class="needs-validation" novalidate>
                        
                        <!-- Leave Type -->
                        <div class="mb-3">
                            <label for="leave_type" class="form-label fw-medium text-secondary">Leave Type</label>
                            <select class="form-select form-control-custom" id="leave_type" name="leave_type" required>
                                <option value="" disabled selected>Select Leave Type...</option>
                                <option value="Sick Leave">Sick Leave</option>
                                <option value="Casual Leave">Casual Leave</option>
                                <option value="Annual Leave">Annual Leave</option>
                                <option value="Maternity/Paternity Leave">Maternity/Paternity Leave</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="invalid-feedback">Please select a leave type.</div>
                        </div>

                        <!-- Date Grid -->
                        <div class="row g-3 mb-3">
                            <!-- Start Date -->
                            <div class="col-6">
                                <label for="start_date" class="form-label fw-medium text-secondary">Start Date</label>
                                <input type="date" class="form-control form-control-custom" id="start_date" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
                                <div class="invalid-feedback">Invalid start date.</div>
                            </div>
                            
                            <!-- End Date -->
                            <div class="col-6">
                                <label for="end_date" class="form-label fw-medium text-secondary">End Date</label>
                                <input type="date" class="form-control form-control-custom" id="end_date" name="end_date" required min="<?php echo date('Y-m-d'); ?>">
                                <div class="invalid-feedback">Invalid end date.</div>
                            </div>
                        </div>

                        <!-- Dynamically Calculated Days Alert -->
                        <div class="alert alert-info py-2 px-3 mb-3 rounded-3" id="calculated_days_container" style="display: none; font-size: 0.9rem;">
                            <i class="bi bi-calculator me-2"></i>
                            <strong>Total Duration:</strong> <span id="calculated_days" class="fw-bold">0 Days</span>
                        </div>

                        <!-- Reason -->
                        <div class="mb-4">
                            <label for="reason" class="form-label fw-medium text-secondary">Reason for Leave</label>
                            <textarea class="form-control form-control-custom" id="reason" name="reason" rows="4" placeholder="Brief explanation of your leave request..." required minlength="10"></textarea>
                            <div class="invalid-feedback">Please provide a reason (at least 10 characters).</div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex gap-2 justify-content-end border-top pt-3">
                            <button type="button" class="btn btn-outline-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-custom px-4 rounded-3">Submit Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Settings Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header border-bottom-0 pt-4 px-4 pb-0">
                    <h5 class="modal-title fw-bold" id="profileModalLabel">
                        <i class="bi bi-person-gear text-primary me-2"></i>Profile Settings
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <form action="update_profile.php" method="POST" class="needs-validation" novalidate>
                        
                        <!-- Full Name -->
                        <div class="mb-3">
                            <label for="profile_name" class="form-label fw-medium text-secondary">Full Name</label>
                            <input type="text" class="form-control form-control-custom" id="profile_name" name="name" value="<?php echo htmlspecialchars($_SESSION['name']); ?>" required>
                            <div class="invalid-feedback">Please enter your name.</div>
                        </div>

                        <!-- Email (Read-only) -->
                        <div class="mb-3">
                            <label for="profile_email" class="form-label fw-medium text-secondary">Email Address (Read-only)</label>
                            <input type="email" class="form-control form-control-custom bg-light text-muted" id="profile_email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly disabled>
                        </div>

                        <div class="row g-3 mb-4">
                            <!-- Department -->
                            <div class="col-6">
                                <label for="profile_dept" class="form-label fw-medium text-secondary">Department</label>
                                <input type="text" class="form-control form-control-custom" id="profile_dept" name="department" value="<?php echo htmlspecialchars($_SESSION['department']); ?>" required>
                                <div class="invalid-feedback">Required.</div>
                            </div>
                            
                            <!-- Designation -->
                            <div class="col-6">
                                <label for="profile_desig" class="form-label fw-medium text-secondary">Designation</label>
                                <input type="text" class="form-control form-control-custom" id="profile_desig" name="designation" value="<?php echo htmlspecialchars($_SESSION['designation']); ?>" required>
                                <div class="invalid-feedback">Required.</div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex gap-2 justify-content-end border-top pt-3">
                            <button type="button" class="btn btn-outline-secondary px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-custom px-4 rounded-3">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS Asset -->
    <script src="main.js"></script>
    
    <script>
        // Form validations
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        const startDate = new Date(document.getElementById('start_date').value);
                        const endDate = new Date(document.getElementById('end_date').value);
                        
                        if (endDate < startDate) {
                            document.getElementById('end_date').classList.add('is-invalid');
                            event.preventDefault();
                            event.stopPropagation();
                            return;
                        }

                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false)
                })
        })()
    </script>
</body>
</html>
