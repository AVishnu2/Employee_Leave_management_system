<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: employee_dashboard.php");
    }
    exit;
}

$error_message = '';
if (isset($_SESSION['login_error'])) {
    $error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

$signup_error = '';
if (isset($_SESSION['signup_error'])) {
    $signup_error = $_SESSION['signup_error'];
    unset($_SESSION['signup_error']);
}

$success_message = '';
if (isset($_SESSION['signup_success'])) {
    $success_message = $_SESSION['signup_success'];
    unset($_SESSION['signup_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication - LeaveSpace Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="bg-mesh"></div>

    <?php if (DEMO_MODE): ?>
    <!-- Demo Mode Banner -->
    <div class="demo-banner">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>Running in Demo Mode:</strong> Database is offline. The system is operating on mock session data. Accounts created will reset when the session is closed.
    </div>
    <?php endif; ?>

    <div class="container login-container">
        <div class="row w-100 justify-content-center">
            <div class="col-md-6 col-lg-5 col-xl-4">
                
                <!-- Glassmorphic Authentication Card -->
                <div class="glass-card p-4 p-md-5 fade-in">
                    
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm p-3 mb-3" style="width: 70px; height: 70px;">
                            <i class="bi bi-calendar2-check-fill fs-2" style="color: var(--primary);"></i>
                        </div>
                        <h2 class="brand-title mb-1">LeaveSpace</h2>
                        <p class="text-muted small" id="cardTitle">Sign In</p>
                    </div>

                    <!-- Global Success Alert -->
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 py-2 px-3 mb-4" role="alert" style="font-size: 0.9rem;">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.75rem; padding: 0.8rem 1rem;"></button>
                        </div>
                    <?php endif; ?>

                    <!-- ============================================== -->
                    <!-- LOGIN FORM SECTION -->
                    <!-- ============================================== -->
                    <div id="loginFormSection">
                        <!-- Login Error Alert -->
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 py-2 px-3 mb-4" role="alert" style="font-size: 0.9rem;">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.75rem; padding: 0.8rem 1rem;"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Role Switcher Tabs (Login) -->
                        <div class="d-flex justify-content-center mb-4">
                            <div class="nav-pills-custom d-inline-flex" id="roleTabs">
                                <button type="button" class="nav-link active" id="tab-employee" onclick="setRole('employee')">
                                    <i class="bi bi-person me-2"></i>Employee
                                </button>
                                <button type="button" class="nav-link" id="tab-admin" onclick="setRole('admin')">
                                    <i class="bi bi-shield-lock me-2"></i>Admin Portal
                                </button>
                            </div>
                        </div>

                        <form action="login_process.php" method="POST" id="loginForm" class="needs-validation" novalidate>
                            <!-- Hidden Role Input -->
                            <input type="hidden" name="role" id="login_role" value="employee">

                            <!-- Email Address -->
                            <div class="floating-input-group">
                                <input type="email" class="form-control form-control-custom" name="email" id="email" placeholder="Email Address" required>
                                <i class="bi bi-envelope"></i>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>

                            <!-- Password -->
                            <div class="floating-input-group">
                                <input type="password" class="form-control form-control-custom" name="password" id="password" placeholder="Password" required>
                                <i class="bi bi-lock"></i>
                                <div class="invalid-feedback">Please enter your password.</div>
                            </div>

                            <!-- Login Button -->
                            <button type="submit" class="btn btn-custom w-100 py-2.5 mt-2">
                                <span>Sign In</span>
                                <i class="bi bi-arrow-right-short ms-1 fs-5 align-middle"></i>
                            </button>
                        </form>

                        <div class="mt-4 pt-3 border-top">
                            <p class="mb-0 text-center small text-muted">
                                Don't have an account? 
                                <a href="#" id="showSignup" class="text-primary fw-semibold text-decoration-none ms-1">Sign Up here</a>
                            </p>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- SIGNUP FORM SECTION -->
                    <!-- ============================================== -->
                    <div id="signupFormSection" class="d-none">
                        <!-- Signup Error Alert -->
                        <?php if (!empty($signup_error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 py-2 px-3 mb-4" role="alert" style="font-size: 0.9rem;">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo htmlspecialchars($signup_error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.75rem; padding: 0.8rem 1rem;"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Role Switcher Tabs (Signup) -->
                        <div class="d-flex justify-content-center mb-4">
                            <div class="nav-pills-custom d-inline-flex" id="signupRoleTabs">
                                <button type="button" class="nav-link active" id="signup-tab-employee" onclick="setSignupRole('employee')">
                                    <i class="bi bi-person me-2"></i>Employee
                                </button>
                                <button type="button" class="nav-link" id="signup-tab-admin" onclick="setSignupRole('admin')">
                                    <i class="bi bi-shield-lock me-2"></i>Admin
                                </button>
                            </div>
                        </div>

                        <form action="signup_process.php" method="POST" id="signupForm" class="needs-validation" novalidate>
                            <!-- Hidden Role Input -->
                            <input type="hidden" name="role" id="signup_role" value="employee">

                            <!-- Full Name -->
                            <div class="floating-input-group">
                                <input type="text" class="form-control form-control-custom" name="name" id="signup_name" placeholder="Full Name" required>
                                <i class="bi bi-person-badge"></i>
                                <div class="invalid-feedback">Please enter your name.</div>
                            </div>

                            <!-- Email Address -->
                            <div class="floating-input-group">
                                <input type="email" class="form-control form-control-custom" name="email" id="signup_email" placeholder="Email Address" required>
                                <i class="bi bi-envelope"></i>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>

                            <!-- Password -->
                            <div class="floating-input-group">
                                <input type="password" class="form-control form-control-custom" name="password" id="signup_password" placeholder="Password (min 6 characters)" required minlength="6">
                                <i class="bi bi-lock"></i>
                                <div class="invalid-feedback">Password must be at least 6 characters.</div>
                            </div>

                            <!-- Employee Specific Fields Container -->
                            <div id="employee-fields-signup">
                                <div class="row g-2">
                                    <!-- Department -->
                                    <div class="col-6">
                                        <div class="floating-input-group">
                                            <input type="text" class="form-control form-control-custom" name="department" id="signup_dept" placeholder="Department" required>
                                            <i class="bi bi-building"></i>
                                            <div class="invalid-feedback">Required.</div>
                                        </div>
                                    </div>
                                    <!-- Designation -->
                                    <div class="col-6">
                                        <div class="floating-input-group">
                                            <input type="text" class="form-control form-control-custom" name="designation" id="signup_desig" placeholder="Designation" required>
                                            <i class="bi bi-person-workspace"></i>
                                            <div class="invalid-feedback">Required.</div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Join Date -->
                                <div class="floating-input-group">
                                    <input type="date" class="form-control form-control-custom" name="join_date" id="signup_join_date" placeholder="Join Date" required>
                                    <i class="bi bi-calendar-event"></i>
                                    <div class="invalid-feedback">Please select join date.</div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-custom w-100 py-2.5 mt-2">
                                <span>Create Account</span>
                                <i class="bi bi-person-plus ms-1 fs-5 align-middle"></i>
                            </button>
                        </form>

                        <div class="mt-4 pt-3 border-top">
                            <p class="mb-0 text-center small text-muted">
                                Already have an account? 
                                <a href="#" id="showLogin" class="text-primary fw-semibold text-decoration-none ms-1">Sign In here</a>
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- UI Toggle and Role Switcher Logic -->
    <script>
        // Set Login Role
        function setRole(role) {
            document.getElementById('login_role').value = role;
            const empTab = document.getElementById('tab-employee');
            const adminTab = document.getElementById('tab-admin');

            if (role === 'employee') {
                empTab.classList.add('active');
                adminTab.classList.remove('active');
            } else {
                adminTab.classList.add('active');
                empTab.classList.remove('active');
            }
        }

        // Set Signup Role
        function setSignupRole(role) {
            document.getElementById('signup_role').value = role;
            const empTab = document.getElementById('signup-tab-employee');
            const adminTab = document.getElementById('signup-tab-admin');
            const empFields = document.getElementById('employee-fields-signup');

            if (role === 'employee') {
                empTab.classList.add('active');
                adminTab.classList.remove('active');
                empFields.classList.remove('d-none');
                
                // Set inputs as required
                document.getElementById('signup_dept').required = true;
                document.getElementById('signup_desig').required = true;
                document.getElementById('signup_join_date').required = true;
            } else {
                adminTab.classList.add('active');
                empTab.classList.remove('active');
                empFields.classList.add('d-none');
                
                // Clear and disable requirements
                document.getElementById('signup_dept').required = false;
                document.getElementById('signup_desig').required = false;
                document.getElementById('signup_join_date').required = false;
            }
        }

        // Form View Toggle Events
        document.getElementById('showSignup').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('loginFormSection').classList.add('d-none');
            document.getElementById('signupFormSection').classList.remove('d-none');
            document.getElementById('cardTitle').innerText = 'Create Account';
        });

        document.getElementById('showLogin').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('signupFormSection').classList.add('d-none');
            document.getElementById('loginFormSection').classList.remove('d-none');
            document.getElementById('cardTitle').innerText = 'Sign In';
        });

        // Form Validation Bootstrap Code
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

    <!-- Handle PHP Signup Redirect State -->
    <?php if (!empty($signup_error)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('loginFormSection').classList.add('d-none');
                document.getElementById('signupFormSection').classList.remove('d-none');
                document.getElementById('cardTitle').innerText = 'Create Account';
            });
        </script>
    <?php endif; ?>
</body>
</html>
