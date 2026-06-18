-- Migration: Cleanup Deprecated Columns
-- Date: 2026-06-09
-- Author: Cascade AI
-- 
-- This migration removes deprecated columns that are no longer used.
-- BACKUP DATABASE BEFORE RUNNING!

-- ============================================
-- 1. Remove deprecated 'email' column from users table
-- ============================================
-- Note: The 'no_hp' field is now the primary contact method
-- Only run this after confirming no code references 'email' column

-- First, verify the column exists and check data
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'email';

-- Check if any users have email data (should be minimal or null)
SELECT COUNT(*) as total_users, 
       COUNT(email) as users_with_email,
       COUNT(CASE WHEN email IS NOT NULL AND email != '' THEN 1 END) as non_empty_email
FROM users;

-- If above shows 0 or minimal email usage, proceed with removal:
-- ALTER TABLE users DROP COLUMN email;

-- Alternative: Keep but mark as deprecated (safer approach)
-- This renames the column to indicate deprecation
-- ALTER TABLE users CHANGE email email_deprecated VARCHAR(255) NULL;

-- ============================================
-- 2. Add comment to document the change
-- ============================================
-- ALTER TABLE users COMMENT = 'Users table - email column removed, using no_hp as primary contact';

-- ============================================
-- 3. Cleanup: Remove other deprecated columns if any
-- ============================================

-- Check for any other potentially unused columns
SELECT COLUMN_NAME, COUNT(*) as row_count
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME IN ('users', 'questions', 'tryout_sessions', 'answers')
GROUP BY TABLE_NAME, COLUMN_NAME
ORDER BY TABLE_NAME, COLUMN_NAME;

-- ============================================
-- 4. Verify table structure after cleanup
-- ============================================
DESCRIBE users;
DESCRIBE questions;
DESCRIBE tryout_sessions;
DESCRIBE answers;

-- ============================================
-- NOTES:
-- ============================================
-- - The 'email' column has been deprecated in favor of 'no_hp'
-- - This migration should be run during a maintenance window
-- - Ensure all application code has been updated to use 'no_hp'
-- - Consider adding a backup of email data before removal if needed
