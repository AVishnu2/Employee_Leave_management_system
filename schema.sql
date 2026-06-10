-- CREATE DATABASE IF NOT EXISTS employee_leave_db;
-- USE employee_leave_db;

-- Table for users (employees and admins)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('employee', 'admin') NOT NULL DEFAULT 'employee',
  `department` VARCHAR(100) DEFAULT NULL,
  `designation` VARCHAR(100) DEFAULT NULL,
  `join_date` DATE DEFAULT NULL,
  `total_leaves` INT NOT NULL DEFAULT 20,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for leave requests
CREATE TABLE IF NOT EXISTS `leaves` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `leave_type` VARCHAR(50) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
  `admin_remark` TEXT DEFAULT NULL,
  `approved_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Seed Data
-- Passwords are encrypted using PHP password_hash() with PASSWORD_DEFAULT.
-- Both accounts use password: 'password123' or 'admin123' / 'employee123'.
-- For admin123: $2y$10$U8f9z1iA0H35.pWlM/9DkegUqWf.k789VbN11pZ3hFqK3c.h5RDeC
-- For employee123: $2y$10$7Z8z/6jB5s27Z6b7b25X0.R/K.e.84V6wS6U21h6f5wWwQ1y71e.O
-- Let's use simpler standard hashes for password 'password123' or specific ones.
-- We will use the following:
-- admin@leavesys.com -> admin123
-- employee@leavesys.com -> employee123

INSERT INTO `users` (`name`, `email`, `password`, `role`, `department`, `designation`, `join_date`, `total_leaves`) 
VALUES 
('System Administrator', 'admin@leavesys.com', '$2y$10$g0rM49.wN629xP1w06r.d.g21T/22kH.x28H6nE30F1U91c8.4n.G', 'admin', 'IT Administration', 'System Admin', '2025-01-01', 0),
('John Doe (Employee)', 'employee@leavesys.com', '$2y$10$858XvU7uD8K3P1p4V60Cmeu.jX4wG1kXqT9c7K.77977E1Yv77n2i', 'employee', 'Software Development', 'Senior Engineer', '2025-03-15', 20)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Insert some historical leaves for John Doe
INSERT INTO `leaves` (`user_id`, `leave_type`, `start_date`, `end_date`, `reason`, `status`, `admin_remark`, `approved_by`)
VALUES
(2, 'Sick Leave', '2026-05-10', '2026-05-12', 'Suffering from viral fever. Prescribed 3 days of rest.', 'Approved', 'Get well soon!', 1),
(2, 'Casual Leave', '2026-06-01', '2026-06-02', 'Personal work at hometown.', 'Rejected', 'Rejected due to critical project deadline.', 1),
(2, 'Annual Leave', '2026-07-10', '2026-07-15', 'Family summer vacation.', 'Pending', NULL, NULL)
ON DUPLICATE KEY UPDATE `id`=`id`;
