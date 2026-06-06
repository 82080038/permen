-- Migration: Add user management features
-- Date: 2026-06-07
-- Description: Add user status and activity log for admin management

-- Add status column to users table
ALTER TABLE users 
ADD COLUMN status ENUM('active', 'suspended', 'banned') DEFAULT 'active' COMMENT 'User account status' AFTER role,
ADD COLUMN suspended_at TIMESTAMP NULL COMMENT 'Timestamp when user was suspended' AFTER status,
ADD COLUMN suspended_reason TEXT NULL COMMENT 'Reason for suspension' AFTER suspended_at,
ADD INDEX idx_status (status);

-- Create user activity log table
CREATE TABLE IF NOT EXISTS user_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL COMMENT 'Action performed (login, tryout_start, tryout_finish, etc.)',
    details TEXT NULL COMMENT 'Additional details about the action',
    ip_address VARCHAR(45) NULL COMMENT 'IP address of the user',
    user_agent VARCHAR(255) NULL COMMENT 'Browser user agent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User activity log for admin monitoring';
