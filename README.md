# LeaveSpace 📅
### A Premium Employee Leave Management System

LeaveSpace is a modern, responsive, and glassmorphism-inspired Employee Leave Management System built using **PHP (PDO)**, **MySQL**, **JavaScript**, and **Bootstrap 5**. It features unified dashboards for both employees and administrators, real-time date logic, profile updates, and a zero-configuration "Demo Mode" fallback.

---

## ✨ Features

### 👤 Employee Dashboard
*   **Unified Auth Portal**: Easily switch between **Sign In** and **Sign Up** panels.
*   **Dynamic Sign Up Fields**: Signing up as an Employee collects additional details (Department, Designation, Join Date) while signing up as an Admin hides them.
*   **Real-time Duration Calculator**: Choose start/end dates and see the number of leave days calculate dynamically. Includes safety locks to prevent invalid dates.
*   **Profile Customization**: Update Name, Designation, and Department directly from the sidebar. Profile settings sync instantly.
*   **Leave History Tracker**: Check the status of your applications (`Pending` ⏳, `Approved` ✅, `Rejected` ❌) and view admin remarks.

### 🔑 Admin Workspace
*   **Actionable Triage Grid**: Review pending applications and Approve/Reject them instantly from a popup modal.
*   **Mandatory Rejection Remarks**: Admins must write comments/reasons when rejecting an application, keeping communication transparent.
*   **Analytics Panel**: Track key organization metrics:
    *   Total Staff Registered
    *   Pending Approvals
    *   Active Leaves Today (auto-calculated)
    *   Total Historic Approved Leaves
*   **Global Leave Audit Log**: A read-only historical list of all leave applications across the company.

---

## 🛠️ Technology Stack
*   **Backend**: PHP (using PDO for secure, prepared SQL interactions)
*   **Frontend**: HTML5, Vanilla JavaScript, CSS3
*   **Design Framework**: Bootstrap 5 + Custom Glassmorphic Overlays
*   **Database**: MySQL

---

## 📂 Project Directory Structure
```text
├── action_leave.php        # Processes admin approval/rejection operations
├── admin_dashboard.php     # Admin control panel and metrics dashboard
├── apply_leave.php         # Handles leave request submissions from employees
├── config.php              # Connects to PDO & manages the self-healing Demo Mode cache
├── employee_dashboard.php  # Employee leave application & balance dashboard
├── .gitignore              # Hides environment files, config overrides, and logs
├── index.php               # Unified login and registration portal
├── login_process.php       # Processes authentication requests
├── logout.php              # Cleans active login sessions (preserves demo data)
├── main.js                 # Frontend scripts (sidebar toggles, day counts, modals)
├── README.md               # Project documentation
├── schema.sql              # Database creation script and seed data
├── signup_process.php      # Processes registration submissions
├── style.css               # Premium HSL variables, glassmorphic filters, and layouts
└── update_profile.php      # Processes employee profile settings updates
```

---

## 🚀 Getting Started (Local Setup)

### 1. Requirements
*   A local server stack containing PHP 7.4+ and MySQL (e.g. **XAMPP**, **WAMP**, or **Laragon**).

### 2. File Location
Clone this repository or copy its contents into your local web server root (typically `C:\xampp\htdocs\leave-system`).

### 3. Database Setup (MySQL)
1. Open your database administration GUI (like **phpMyAdmin** at `http://localhost/phpmyadmin/`).
2. Create a new database named: **`employee_leave_db`**.
3. Go to the **Import** tab, choose the **`schema.sql`** file from the root folder, and click **Import** (or **Go**).
4. *(Optional)* If your local database uses a custom root password, edit **`config.php`** to reflect your credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', 'YOUR_DB_PASSWORD');
   define('DB_NAME', 'employee_leave_db');
   ```

---

## 💡 Fallback Demo Mode (Zero Setup)
If the application is launched and cannot establish a connection to a local MySQL server, it will **automatically fall back to Demo Mode** (a banner will appear at the top). 

All account registrations, profile updates, leave applications, and admin decisions will be temporarily stored in the browser's session memory, allowing you to test the entire application instantly with zero configuration!

### Default Seed Accounts
You can sign up for new accounts or log in using the pre-seeded credentials:

| Role | Email | Password |
|---|---|---|
| **Employee** | `employee@leavesys.com` | `employee123` |
| **Admin** | `admin@leavesys.com` | `admin123` |

*Note: If you want to wipe all session data and start from scratch in Demo Mode, visit: `http://localhost/leave-system/index.php?reset=1`*

---

## ☁️ Cloud Deployment

The database connection is configured to check for system environment variables. This makes it compatible out-of-the-box with cloud platforms like **Railway.app** or **Render**:
1. Connect your GitHub repository to **Railway**.
2. Provision a **MySQL Database** inside the same Railway project.
3. Railway will automatically inject the connection variables (`MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`), and the app will connect automatically!
